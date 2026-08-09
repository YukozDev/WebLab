<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Services\PasswordHasher;
use App\Services\PasswordManager;
use App\Services\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Changement de mot de passe par l'utilisateur lui-meme.
 *
 * Cette operation est consideree comme critique : elle exige une
 * reauthentification, c'est-a-dire la saisie du mot de passe actuel en plus du
 * nouveau. Une session volee ou un poste laisse sans surveillance ne suffit
 * alors pas a s'approprier definitivement le compte.
 */
class PasswordController extends Controller
{
    /**
     * @param  \App\Services\PasswordHasher  $hacheur  Service de hachage PBKDF2.
     * @param  \App\Services\PasswordManager  $gestionnaire  Service de cycle de vie du mot de passe.
     * @param  \App\Services\PasswordPolicy  $politique  Norme applicable aux mots de passe.
     */
    public function __construct(
        private readonly PasswordHasher $hacheur,
        private readonly PasswordManager $gestionnaire,
        private readonly PasswordPolicy $politique
    ) {
    }

    /**
     * Affiche le formulaire de changement de mot de passe.
     *
     * @return \Illuminate\View\View La vue du formulaire, avec les exigences en vigueur.
     */
    public function afficherFormulaire(): View
    {
        return view('auth.change-password', [
            'exigences' => $this->politique->exigences(),
        ]);
    }

    /**
     * Change le mot de passe de l'utilisateur connecte.
     *
     * @param  \Illuminate\Http\Request  $requete  Requete contenant current_password, password et sa confirmation.
     * @return \Illuminate\Http\RedirectResponse Redirection vers le tableau de bord.
     *
     * @throws \Illuminate\Validation\ValidationException Si la reauthentification echoue ou si la politique n'est pas respectee.
     */
    public function modifier(Request $requete): RedirectResponse
    {
        $utilisateur = $requete->user();

        $donnees = $requete->validate([
            'current_password' => ['required', 'string'],
            // confirmed exige un champ password_confirmation identique.
            'password' => ['required', 'string', 'confirmed', $this->politique->regle()],
        ], [], [
            'current_password' => 'mot de passe actuel',
            'password' => 'nouveau mot de passe',
        ]);

        // --- Reauthentification ---
        $motDePasseActuelValide = $this->hacheur->verifier(
            $donnees['current_password'],
            $utilisateur->password_hash,
            $utilisateur->salt,
            $utilisateur->hash_iterations
        );

        if (! $motDePasseActuelValide) {
            AuthLog::enregistrer(
                AuthLog::CONNEXION_ECHOUEE,
                $utilisateur,
                $utilisateur->username,
                'Reauthentification echouee lors du changement de mot de passe'
            );

            throw ValidationException::withMessages([
                'current_password' => 'Le mot de passe actuel est incorrect.',
            ]);
        }

        // --- Interdiction de reutiliser un ancien mot de passe ---
        if ($this->gestionnaire->estDejaUtilise($utilisateur, $donnees['password'])) {
            throw ValidationException::withMessages([
                'password' => 'Ce mot de passe a deja ete utilise recemment. Choisissez-en un autre.',
            ]);
        }

        $this->gestionnaire->definir($utilisateur, $donnees['password']);

        // Le changement de mot de passe est un point de bascule d'authentification :
        // on renouvelle l'identifiant de session pour qu'un identifiant capture
        // avant le changement ne reste pas exploitable.
        $requete->session()->regenerate();

        AuthLog::enregistrer(AuthLog::MOT_DE_PASSE_MODIFIE, $utilisateur);

        return redirect()
            ->route('dashboard')
            ->with('statut', 'Votre mot de passe a ete modifie.');
    }
}
