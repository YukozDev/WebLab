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
                'username' => "Ce compte est bloque. Communiquez avec l'administrateur pour le reactiver.",
            ]);
        }

        $motDePasseValide = $this->hacheur->verifier(
            $donnees['password'],
            $utilisateur->password_hash,
            $utilisateur->salt,
            $utilisateur->hash_iterations
        );

        if (! $motDePasseValide) {
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
