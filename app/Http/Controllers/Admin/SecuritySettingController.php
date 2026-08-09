<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\SecuritySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Configuration des parametres de securite, reservee au role Administrateur.
 *
 * Cette page regroupe tous les parametres marques « Oui » dans la colonne
 * Parametres du tableau de l'enonce : limite de tentatives, delai entre deux
 * essais, norme de complexite des mots de passe, taille de l'historique,
 * duree de validite, cout du hachage et delai d'inactivite des sessions.
 */
class SecuritySettingController extends Controller
{
    /**
     * Affiche le formulaire de configuration.
     *
     * @return \Illuminate\View\View La vue du formulaire, pre-remplie avec les valeurs courantes.
     */
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'parametres' => SecuritySetting::courants(),
        ]);
    }

    /**
     * Enregistre les nouveaux parametres de securite.
     *
     * Chaque champ est borne par une validation : la page d'administration ne
     * doit pas pouvoir servir a affaiblir arbitrairement le systeme. Par
     * exemple, la longueur minimale d'un mot de passe ne peut descendre sous 8
     * caracteres, et le nombre d'iterations de hachage sous 10 000. Un compte
     * administrateur compromis ne peut donc pas ramener la politique a un
     * niveau trivial avant d'attaquer les autres comptes.
     *
     * @param  \Illuminate\Http\Request  $requete  La requete contenant les parametres.
     * @return \Illuminate\Http\RedirectResponse Redirection vers le formulaire avec un message de confirmation.
     *
     * @throws \Illuminate\Validation\ValidationException Si un parametre est hors bornes.
     */
    public function update(Request $requete): RedirectResponse
    {
        $donnees = $requete->validate([
            'max_login_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'failed_attempt_delay_seconds' => ['required', 'integer', 'min:0', 'max:600'],
            'password_min_length' => ['required', 'integer', 'min:8', 'max:64'],
            'require_uppercase' => ['nullable', 'boolean'],
            'require_lowercase' => ['nullable', 'boolean'],
            'require_digit' => ['nullable', 'boolean'],
            'require_special' => ['nullable', 'boolean'],
            'password_history_count' => ['required', 'integer', 'min:0', 'max:24'],
            'password_expiry_days' => ['required', 'integer', 'min:0', 'max:730'],
            'session_idle_timeout_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'hash_iterations' => ['required', 'integer', 'min:10000', 'max:1000000'],
        ]);

        // Une case a cocher non cochee n'est pas transmise par le navigateur :
        // sans ce traitement, decocher une exigence resterait sans effet.
        foreach (['require_uppercase', 'require_lowercase', 'require_digit', 'require_special'] as $caseACocher) {
            $donnees[$caseACocher] = $requete->boolean($caseACocher);
        }

        $parametres = SecuritySetting::courants();
        $ancien = $parametres->getOriginal();
        $parametres->update($donnees);

        // Les parametres memoises doivent etre relus pour que la suite de la
        // requete travaille sur les nouvelles valeurs.
        SecuritySetting::oublier();

        AuthLog::enregistrer(
            AuthLog::PARAMETRES_MODIFIES,
            $requete->user(),
            null,
            $this->resumerModifications($ancien, $parametres->getAttributes())
        );

        return redirect()
            ->route('admin.parametres.edit')
            ->with('statut', 'Les parametres de securite ont ete enregistres.');
    }

    /**
     * Resume les parametres reellement modifies, pour le journal de securite.
     *
     * @param  array<string, mixed>  $ancien  Valeurs avant modification.
     * @param  array<string, mixed>  $nouveau  Valeurs apres modification.
     * @return string Un resume lisible, ou une mention si rien n'a change.
     */
    private function resumerModifications(array $ancien, array $nouveau): string
    {
        $changements = [];

        foreach ($nouveau as $champ => $valeur) {
            if (in_array($champ, ['id', 'created_at', 'updated_at'], true)) {
                continue;
            }

            if (array_key_exists($champ, $ancien) && (string) $ancien[$champ] !== (string) $valeur) {
                $changements[] = "{$champ} : {$ancien[$champ]} -> {$valeur}";
            }
        }

        return $changements === [] ? 'Aucune valeur modifiee' : implode(' ; ', $changements);
    }
}
