<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Point d'entree du peuplement de la base.
 *
 * L'ordre est significatif : les parametres de securite doivent exister avant
 * UserSeeder, car PasswordManager y lit le nombre d'iterations de hachage, et
 * les roles doivent exister avant d'etre attribues aux utilisateurs.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Execute les seeders de l'application.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            SecuritySettingSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
        ]);
    }
}
