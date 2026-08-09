<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

/**
 * Alimente les deux listes de clients avec des donnees de demonstration.
 *
 * Les deux categories sont peuplees afin de pouvoir montrer, pendant la demo,
 * qu'un prepose ne voit que la liste correspondant a son role.
 */
class ClientSeeder extends Seeder
{
    /**
     * Insere les clients de demonstration.
     *
     * @return void
     */
    public function run(): void
    {
        $residentiels = [
            ['first_name' => 'Marc', 'last_name' => 'Bergeron', 'email' => 'marc.bergeron@courriel.ca', 'phone' => '514-555-0142', 'city' => 'Montreal'],
            ['first_name' => 'Sophie', 'last_name' => 'Cote', 'email' => 'sophie.cote@courriel.ca', 'phone' => '450-555-0188', 'city' => 'Laval'],
            ['first_name' => 'Julien', 'last_name' => 'Fortin', 'email' => 'julien.fortin@courriel.ca', 'phone' => '418-555-0127', 'city' => 'Quebec'],
            ['first_name' => 'Amelie', 'last_name' => 'Roy', 'email' => 'amelie.roy@courriel.ca', 'phone' => '819-555-0163', 'city' => 'Gatineau'],
            ['first_name' => 'Nicolas', 'last_name' => 'Belanger', 'email' => 'nicolas.belanger@courriel.ca', 'phone' => '514-555-0175', 'city' => 'Longueuil'],
        ];

        foreach ($residentiels as $client) {
            Client::firstOrCreate(
                ['email' => $client['email']],
                $client + ['type' => Client::TYPE_RESIDENTIEL]
            );
        }

        $affaires = [
            ['company_name' => 'Boulangerie Saint-Laurent inc.', 'first_name' => 'Isabelle', 'last_name' => 'Dubois', 'email' => 'contact@boulangeriesl.ca', 'phone' => '514-555-0201', 'city' => 'Montreal'],
            ['company_name' => 'Transport Nord-Sud ltee', 'first_name' => 'Patrick', 'last_name' => 'Ouellet', 'email' => 'p.ouellet@transportns.ca', 'phone' => '450-555-0219', 'city' => 'Boucherville'],
            ['company_name' => 'Clinique dentaire Bellevue', 'first_name' => 'Karine', 'last_name' => 'Lemieux', 'email' => 'admin@dentairebellevue.ca', 'phone' => '418-555-0234', 'city' => 'Levis'],
            ['company_name' => 'Groupe Technologique Meridien', 'first_name' => 'Simon', 'last_name' => 'Girard', 'email' => 's.girard@gtmeridien.ca', 'phone' => '819-555-0247', 'city' => 'Sherbrooke'],
        ];

        foreach ($affaires as $client) {
            Client::firstOrCreate(
                ['email' => $client['email']],
                $client + ['type' => Client::TYPE_AFFAIRE]
            );
        }
    }
}
