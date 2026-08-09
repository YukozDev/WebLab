<?php

namespace App\Services;

use App\Models\PasswordHistory;
use App\Models\SecuritySetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cycle de vie du mot de passe d'un utilisateur.
 *
 * Centralise la seule ecriture autorisee des colonnes password_hash, salt et
 * hash_iterations. Aucun controleur ne les modifie directement : toute
 * definition de mot de passe passe par definir(), ce qui garantit qu'un
 * changement archive systematiquement l'ancienne empreinte dans l'historique
 * et remet a zero les compteurs de securite.
 */
class PasswordManager
{
    /**
     * @param  \App\Services\PasswordHasher  $hacheur  Service de hachage PBKDF2.
     */
    public function __construct(private readonly PasswordHasher $hacheur)
    {
    }

    /**
     * Definit le mot de passe d'un utilisateur.
     *
     * L'ancienne empreinte est archivee dans password_histories avant d'etre
     * remplacee, puis l'historique est elague pour ne conserver que le nombre
     * d'entrees exige par la politique. Un nouveau sel est tire a chaque
     * changement : reutiliser l'ancien permettrait de comparer les empreintes
     * successives et de savoir si l'utilisateur a reellement change de mot de
     * passe.
     *
     * L'ensemble est execute dans une transaction : en cas d'erreur, on ne veut
     * pas d'un compte dont l'empreinte a change mais dont l'historique est
     * incoherent.
     *
     * @param  \App\Models\User  $utilisateur  L'utilisateur concerne.
     * @param  string  $nouveauMotDePasse  Le nouveau mot de passe en clair.
     * @param  bool  $forcerChangement  Vrai pour exiger un changement des la prochaine connexion.
     * @return void
     */
    public function definir(User $utilisateur, string $nouveauMotDePasse, bool $forcerChangement = false): void
    {
        $parametres = SecuritySetting::courants();

        DB::transaction(function () use ($utilisateur, $nouveauMotDePasse, $forcerChangement, $parametres) {
            // Archiver l'empreinte actuelle, s'il y en a une (pas le cas a la creation).
            if (! empty($utilisateur->password_hash)) {
                PasswordHistory::create([
                    'user_id' => $utilisateur->id,
                    'password_hash' => $utilisateur->password_hash,
                    'salt' => $utilisateur->salt,
                    'hash_iterations' => $utilisateur->hash_iterations,
                ]);
            }

            $sel = $this->hacheur->genererSel();
            $iterations = $parametres->hash_iterations;

            $utilisateur->password_hash = $this->hacheur->hacher($nouveauMotDePasse, $sel, $iterations);
            $utilisateur->salt = $sel;
            $utilisateur->hash_iterations = $iterations;
            $utilisateur->password_changed_at = now();
            $utilisateur->must_change_password = $forcerChangement;
            // Un changement de mot de passe reussi solde les echecs precedents.
            $utilisateur->failed_attempts = 0;
            $utilisateur->last_failed_at = null;
            $utilisateur->save();

            $this->elaguerHistorique($utilisateur, $parametres->password_history_count);
        });
    }

    /**
     * Verifie si un mot de passe candidat fait partie des X derniers utilises.
     *
     * La comparaison porte sur le mot de passe actuel et sur les entrees
     * d'historique, chacune rehachee avec le sel et le nombre d'iterations qui
     * lui sont propres : deux empreintes du meme mot de passe sont differentes
     * si les sels different, on ne peut donc pas se contenter de comparer les
     * empreintes entre elles.
     *
     * @param  \App\Models\User  $utilisateur  L'utilisateur concerne.
     * @param  string  $motDePasseCandidat  Le mot de passe propose en clair.
     * @return bool Vrai si ce mot de passe a deja ete utilise recemment.
     */
    public function estDejaUtilise(User $utilisateur, string $motDePasseCandidat): bool
    {
        $nombreInterdit = SecuritySetting::courants()->password_history_count;

        if ($nombreInterdit <= 0) {
            return false;
        }

        // Le mot de passe courant compte comme le plus recent de l'historique.
        if (! empty($utilisateur->password_hash) && $this->hacheur->verifier(
            $motDePasseCandidat,
            $utilisateur->password_hash,
            $utilisateur->salt,
            $utilisateur->hash_iterations
        )) {
            return true;
        }

        // Les X-1 precedents, du plus recent au plus ancien.
        $anciens = $utilisateur->passwordHistories()
            ->limit(max(0, $nombreInterdit - 1))
            ->get();

        foreach ($anciens as $ancien) {
            if ($this->hacheur->verifier(
                $motDePasseCandidat,
                $ancien->password_hash,
                $ancien->salt,
                $ancien->hash_iterations
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indique si le mot de passe de l'utilisateur a depasse sa duree de validite.
     *
     * @param  \App\Models\User  $utilisateur  L'utilisateur concerne.
     * @return bool Vrai si le mot de passe est expire et doit etre change.
     */
    public function estExpire(User $utilisateur): bool
    {
        $joursValidite = SecuritySetting::courants()->password_expiry_days;

        if ($joursValidite <= 0 || $utilisateur->password_changed_at === null) {
            return false;
        }

        return $utilisateur->password_changed_at->addDays($joursValidite)->isPast();
    }

    /**
     * Supprime les entrees d'historique devenues inutiles.
     *
     * On conserve $nombreConserve - 1 entrees : la derniere position du quota
     * est occupee par le mot de passe courant, qui vit dans la table users.
     * Purger evite que la table ne croisse indefiniment et limite la quantite
     * d'empreintes exposees en cas de fuite de la base.
     *
     * @param  \App\Models\User  $utilisateur  L'utilisateur concerne.
     * @param  int  $nombreConserve  Taille de l'historique exigee par la politique.
     * @return void
     */
    private function elaguerHistorique(User $utilisateur, int $nombreConserve): void
    {
        $aConserver = max(0, $nombreConserve - 1);

        $idsAConserver = $utilisateur->passwordHistories()
            ->limit($aConserver)
            ->pluck('id');

        $utilisateur->passwordHistories()
            ->whereNotIn('id', $idsAConserver)
            ->delete();
    }
}
