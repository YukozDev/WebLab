<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\SecuritySetting;
use App\Models\User;
use App\Services\PasswordHasher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Authentification des utilisateurs : affichage du formulaire, connexion, deconnexion.
 *
 * La verification du mot de passe n'utilise pas Auth::attempt(), qui s'appuie
 * sur le hacheur bcrypt de Laravel. Elle passe par PasswordHasher (PBKDF2 avec
 * sel explicite par utilisateur), comme l'exige le laboratoire. Une fois
 * l'identite etablie, Auth::login() est utilise pour que la session soit geree
 * par le garde natif de Laravel.
 */
class LoginController extends Controller
{
    /**
     * @param  \App\Services\PasswordHasher  $hacheur  Service de hachage PBKDF2.
     */
    public function __construct(private readonly PasswordHasher $hacheur)
    {
    }

    /**
     * Affiche le formulaire de connexion.
     *
     * @return \Illuminate\View\View La vue du formulaire.
     */
    public function afficherFormulaire(): View
    {
        return view('auth.login');
    }

    /**
     * Traite une tentative de connexion.
     *
     * @param  \Illuminate\Http\Request  $requete  La requete contenant username et password.
     * @return \Illuminate\Http\RedirectResponse Redirection vers le tableau de bord ou retour au formulaire.
     *
     * @throws \Illuminate\Validation\ValidationException Si les identifiants sont invalides.
     */
    public function connecter(Request $requete): RedirectResponse
    {
        $donnees = $requete->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $identifiant = $donnees['username'];
        $utilisateur = User::where('username', $identifiant)->first();
        $parametres = SecuritySetting::courants();

        // Identifiant inconnu : on consomme malgre tout le temps d'un hachage
        // avant de repondre, pour ne pas reveler quels comptes existent.
        if ($utilisateur === null) {
            $this->hacheur->hachageFactice($parametres->hash_iterations);

            AuthLog::enregistrer(
                AuthLog::CONNEXION_ECHOUEE,
                null,
                $identifiant,
                'Identifiant inconnu'
            );

            $this->echouer();
        }

        // Compte bloque par depassement de la limite de tentatives : seul un
        // administrateur peut le reactiver.
        if ($utilisateur->is_locked) {
            AuthLog::enregistrer(
                AuthLog::CONNEXION_ECHOUEE,
                $utilisateur,
                $identifiant,
                'Tentative sur un compte bloque'
            );

            throw ValidationException::withMessages([
                'username' => "Ce compte est bloqué. Communiquez avec l'administrateur pour le réactiver.",
            ]);
        }

        // Delai impose entre deux tentatives (Partie 2) : un echec recent
        // interdit tout nouvel essai avant l'ecoulement de
        // failed_attempt_delay_seconds. Ceci ralentit une attaque par force
        // brute independamment du throttle par IP deja applique sur la route.
        if ($utilisateur->last_failed_at !== null) {
            $finDuDelai = $utilisateur->last_failed_at->addSeconds($parametres->failed_attempt_delay_seconds);

            if (now()->lessThan($finDuDelai)) {
                $secondesRestantes = now()->diffInSeconds($finDuDelai);

                AuthLog::enregistrer(
                    AuthLog::CONNEXION_ECHOUEE,
                    $utilisateur,
                    $identifiant,
                    'Tentative pendant le delai de blocage'
                );

                throw ValidationException::withMessages([
                    'username' => "Trop de tentatives rapprochées. Reessayez dans {$secondesRestantes} seconde(s).",
                ]);
            }
        }

        $motDePasseValide = $this->hacheur->verifier(
            $donnees['password'],
            $utilisateur->password_hash,
            $utilisateur->salt,
            $utilisateur->hash_iterations
        );

        if (! $motDePasseValide) {
            // Chaque echec rapproche le compte du blocage definitif : le
            // compteur est incremente et l'instant de l'echec retenu pour
            // faire respecter le delai ci-dessus a la prochaine tentative.
            $utilisateur->failed_attempts++;
            $utilisateur->last_failed_at = now();

            // Seuil atteint : le compte est verrouille. Seul un administrateur
            // pourra le reactiver (voir la verification is_locked plus haut).
            if ($utilisateur->failed_attempts >= $parametres->max_login_attempts) {
                $utilisateur->is_locked = true;
                $utilisateur->locked_at = now();
            }

            $utilisateur->save();

            if ($utilisateur->is_locked) {
                AuthLog::enregistrer(
                    AuthLog::COMPTE_BLOQUE,
                    $utilisateur,
                    $identifiant,
                    "Blocage automatique apres {$utilisateur->failed_attempts} echecs"
                );

                // Ce blocage vient d'etre declenche par la tentative en cours :
                // le message doit le refleter plutot que rester generique.
                throw ValidationException::withMessages([
                    'username' => "Ce compte vient d'être bloqué après plusieurs échecs. Communiquez avec l'administrateur pour le réactiver.",
                ]);
            }

            AuthLog::enregistrer(
                AuthLog::CONNEXION_ECHOUEE,
                $utilisateur,
                $identifiant,
                'Mot de passe invalide'
            );

            $this->echouer();
        }

        // --- Authentification reussie ---
        Auth::login($utilisateur);

        // Regeneration de l'identifiant de session (Partie 3) : l'identifiant
        // anonyme utilise avant la connexion est abandonne au profit d'un
        // nouveau. Sans cela, un attaquant ayant impose un identifiant de
        // session a la victime avant sa connexion le retrouverait valide
        // apres coup (fixation de session).
        $requete->session()->regenerate();

        $utilisateur->forceFill([
            'failed_attempts' => 0,
            'last_failed_at' => null,
            'last_login_at' => now(),
        ])->save();

        AuthLog::enregistrer(AuthLog::CONNEXION_REUSSIE, $utilisateur);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Deconnecte l'utilisateur et detruit sa session.
     *
     * @param  \Illuminate\Http\Request  $requete  La requete de deconnexion.
     * @return \Illuminate\Http\RedirectResponse Redirection vers le formulaire de connexion.
     */
    public function deconnecter(Request $requete): RedirectResponse
    {
        AuthLog::enregistrer(AuthLog::DECONNEXION, $requete->user());

        Auth::logout();

        // invalidate() detruit les donnees de session cote serveur et emet un
        // nouvel identifiant ; regenerateToken() renouvelle le jeton CSRF.
        // Sans ces deux appels, l'identifiant de session reste valide apres la
        // deconnexion et pourrait etre rejoue.
        $requete->session()->invalidate();
        $requete->session()->regenerateToken();

        return redirect()->route('login')->with('statut', 'Vous avez ete deconnecte.');
    }

    /**
     * Interrompt la connexion avec un message volontairement generique.
     *
     * Le message ne distingue pas « identifiant inconnu » de « mot de passe
     * invalide » : preciser lequel des deux est en cause permettrait a un
     * attaquant de valider une liste d'identifiants avant de s'attaquer aux
     * mots de passe.
     *
     * @return never
     *
     * @throws \Illuminate\Validation\ValidationException Toujours levee.
     */
    private function echouer(): never
    {
        throw ValidationException::withMessages([
            'username' => 'Identifiant ou mot de passe invalide.',
        ]);
    }
}