<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\PasswordManager;
use Illuminate\Database\Seeder;

/**
 * Cree les trois utilisateurs exiges par l'enonce et leur attribue un role.
 *
 * Les mots de passe initiaux sont volontairement documentes ici : ce sont des
 * comptes de demonstration, sur une base locale, dont les identifiants sont
 * imposes par l'enonce. Dans un systeme reel, un compte d'amorcage recevrait un
 * mot de passe aleatoire assorti de must_change_password.
 *
 * Le mot de passe n'est pas ecrit directement dans la table : il passe par
 * PasswordManager::definir(), donc chaque compte recoit un sel distinct et le
 * nombre d'iterations configure, exactement comme un compte cree en production.
 */
class UserSeeder extends Seeder
{
    /**
     * Definition des comptes de demonstration.
     *
     * @var array<int, array{username: string, full_name: string, email: string, password: string, role: string}>
     */
    private const COMPTES = [
        [
            'username' => 'Administrateur',
            'full_name' => 'Alice Tremblay',
            'email' => 'administrateur@gti619.local',
            'password' => 'Admin!GTI619#2024',
            'role' => Role::ADMINISTRATEUR,
        ],
        [
            'username' => 'Utilisateur1',
            'full_name' => 'Bruno Lavoie',
            'email' => 'utilisateur1@gti619.local',
            'password' => 'Residentiel!619#2024',
            'role' => Role::PREPOSE_RESIDENTIEL,
        ],
        [
            'username' => 'Utilisateur2',
            'full_name' => 'Chantal Gagnon',
            'email' => 'utilisateur2@gti619.local',
            'password' => 'Affaire!619#2024',
            'role' => Role::PREPOSE_AFFAIRE,
        ],
    ];

    /**
     * Cree les comptes et leurs associations de roles.
     *
     * @param  \App\Services\PasswordManager  $gestionnaire  Injecte par le conteneur de Laravel.
     * @return void
     */
    public function run(PasswordManager $gestionnaire): void
    {
        foreach (self::COMPTES as $compte) {
            $utilisateur = User::firstOrNew(['username' => $compte['username']]);
            $utilisateur->full_name = $compte['full_name'];
            $utilisateur->email = $compte['email'];

            // Un compte deja present garde son mot de passe : relancer le
            // seeder ne doit pas reinitialiser un mot de passe change depuis.
            //
            // definir() se charge lui-meme d'enregistrer le modele. On ne
            // sauvegarde donc pas avant l'appel : la colonne password_hash est
            // NOT NULL et une insertion prealable echouerait.
            if (! $utilisateur->exists) {
                $gestionnaire->definir($utilisateur, $compte['password'], forcerChangement: false);
            } else {
                $utilisateur->save();
            }

            $role = Role::where('name', $compte['role'])->firstOrFail();
            // sync() plutot que attach() : evite l'erreur de cle dupliquee si le
            // seeder est relance, et garantit exactement un role par compte.
            $utilisateur->roles()->sync([$role->id]);
        }
    }
}
