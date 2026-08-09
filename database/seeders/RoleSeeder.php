<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Cree les trois roles du modele RBAC exiges par l'enonce.
 *
 * updateOrCreate() est utilise plutot que create() pour que le seeder soit
 * idempotent : le relancer sur une base existante met a jour les libelles sans
 * dupliquer les roles ni casser les associations role_user deja etablies.
 */
class RoleSeeder extends Seeder
{
    /**
     * Insere ou met a jour les roles.
     *
     * @return void
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::ADMINISTRATEUR,
                'label' => 'Administrateur',
                'description' => "Configure les parametres de securite, gere les comptes "
                    . "et consulte les deux listes de clients.",
            ],
            [
                'name' => Role::PREPOSE_RESIDENTIEL,
                'label' => 'Prepose aux clients residentiels',
                'description' => 'Consulte uniquement la liste des clients residentiels.',
            ],
            [
                'name' => Role::PREPOSE_AFFAIRE,
                'label' => "Prepose aux clients d'affaire",
                'description' => "Consulte uniquement la liste des clients d'affaire.",
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
