<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\Role;
use App\Models\User;
use App\Services\PasswordHasher;
use App\Services\PasswordManager;
use App\Services\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Gestion des utilisateurs, reservee au role Administrateur.
 *
 * La restriction d'acces n'est pas verifiee ici mais declaree sur le groupe de
 * routes, via le middleware role:administrateur (voir routes/web.php).
 */
class UserController extends Controller
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
     * Affiche la liste des utilisateurs et de leurs roles.
     *
     * @return \Illuminate\View\View La vue de la liste.
     */
    public function index(): View
    {
        return view('admin.users.index', [
            // Chargement anticipe des roles : sans lui, afficher le role de
            // chaque ligne declencherait une requete par utilisateur (N+1).
            'utilisateurs' => User::with('roles')->orderBy('username')->get(),
        ]);
    }

    /**
     * Affiche le formulaire de creation d'un utilisateur.
     *
     * @return \Illuminate\View\View La vue du formulaire.
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::orderBy('label')->get(),
        ]);
    }

    /**
     * Cree un utilisateur et lui attribue un role.
     *
     * L'administrateur ne choisit pas le mot de passe : le systeme en genere un
     * temporaire, conforme a la politique, affiche une seule fois. Le compte est
     * marque must_change_password, donc le nouvel utilisateur devra le remplacer
     * des sa premiere connexion. L'administrateur ne connait ainsi jamais le mot
     * de passe definitif de ses utilisateurs.
     *
     * La creation d'un compte etant une fonction critique, elle exige une
     * reauthentification de l'administrateur.
     *
     * @param  \Illuminate\Http\Request  $requete  Requete contenant username, full_name, email, role et admin_password.
     * @return \Illuminate\Http\RedirectResponse Redirection vers la liste, avec le mot de passe temporaire.
     *
     * @throws \Illuminate\Validation\ValidationException Si la reauthentification echoue ou les donnees sont invalides.
     */
    public function store(Request $requete): RedirectResponse
    {
        $administrateur = $requete->user();

        $donnees = $requete->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')],
            // Rule::in interdit toute valeur de role hors des trois prevues,
            // meme si le champ <select> du formulaire est manipule cote client.
            'role' => ['required', 'string', Rule::in(Role::noms())],
            'admin_password' => ['required', 'string'],
        ], [], [
            'username' => 'identifiant',
            'full_name' => 'nom complet',
            'role' => 'role',
            'admin_password' => 'votre mot de passe',
        ]);

        // --- Reauthentification de l'administrateur ---
        $reauthOk = $this->hacheur->verifier(
            $donnees['admin_password'],
            $administrateur->password_hash,
            $administrateur->salt,
            $administrateur->hash_iterations
        );

        if (! $reauthOk) {
            AuthLog::enregistrer(
                AuthLog::CONNEXION_ECHOUEE,
                $administrateur,
                $administrateur->username,
                "Reauthentification echouee lors d'une creation d'utilisateur"
            );

            throw ValidationException::withMessages([
                'admin_password' => 'Mot de passe incorrect. La creation a ete annulee.',
            ]);
        }

        $motDePasseTemporaire = $this->politique->genererMotDePasseTemporaire();

        $utilisateur = DB::transaction(function () use ($donnees, $motDePasseTemporaire) {
            // Le modele n'est pas enregistre ici : password_hash est NOT NULL,
            // une insertion sans empreinte echouerait. C'est definir() qui
            // renseigne l'empreinte, le sel et les iterations, active
            // must_change_password, puis enregistre le compte.
            $utilisateur = new User([
                'username' => $donnees['username'],
                'full_name' => $donnees['full_name'],
                'email' => $donnees['email'] ?? null,
            ]);

            $this->gestionnaire->definir($utilisateur, $motDePasseTemporaire, forcerChangement: true);

            $role = Role::where('name', $donnees['role'])->firstOrFail();
            $utilisateur->roles()->attach($role);

            return $utilisateur;
        });

        AuthLog::enregistrer(
            AuthLog::UTILISATEUR_CREE,
            $administrateur,
            $administrateur->username,
            "Compte cree : {$utilisateur->username} (role : {$donnees['role']})"
        );

        return redirect()
            ->route('admin.utilisateurs.index')
            // Transmis par la session flash : la valeur n'apparait ni dans
            // l'URL ni dans les journaux du serveur web.
            ->with('motDePasseTemporaire', $motDePasseTemporaire)
            ->with('utilisateurCree', $utilisateur->username);
    }

    public function debloquer(Request $requete): RedirectResponse
    {
        $donnees = $requete->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $utilisateur = User::find($donnees['user_id']);

        // Utiliser la méthode de génération de mot de passe temporaire de PasswordPolicy
        $motDePasseTemporaire = $this->politique->genererMotDePasseTemporaire();

        // Réinitialiser les tentatives échouées, débloquer le compte et définir le mot de passe temporaire
        $utilisateur->forceFill([
            'failed_attempts' => 0,
            'last_failed_at' => null,
            'is_locked' => false,
            'locked_at' => null,
        ]);

        $this->gestionnaire->definir($utilisateur, $motDePasseTemporaire, forcerChangement: true);

        // Enregistrer l'action dans les logs
        AuthLog::enregistrer(
            AuthLog::COMPTE_DEBLOQUE,
            $utilisateur,
            $utilisateur->username,
            'Compte débloqué et mot de passe réinitialisé par un administrateur'
        );

        return redirect()->route('admin.utilisateurs.index')->with([
            'statut' => "Le compte de l'utilisateur « {$utilisateur->username} » a été débloqué et son mot de passe réinitialisé.",
            'motDePasseTemporaire' => $motDePasseTemporaire,
            'utilisateurDebloque' => $utilisateur->username,
        ]);
    }
}
