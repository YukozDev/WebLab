<?php

namespace Database\Seeders;

use App\Models\SecuritySetting;
use Illuminate\Database\Seeder;

/**
 * Cree la ligne unique de parametres de securite.
 *
 * Les valeurs proviennent des valeurs par defaut declarees dans la migration,
 * elles-memes alignees sur les recommandations de l'OWASP et du NIST.
 * firstOrCreate() garantit qu'un second passage ne remplace pas les reglages
 * que l'administrateur aurait deja modifies depuis l'interface.
 */
class SecuritySettingSeeder extends Seeder
{
    /**
     * Insere les parametres de securite s'ils n'existent pas encore.
     *
     * @return void
     */
    public function run(): void
    {
        SecuritySetting::firstOrCreate(['id' => 1]);
    }
}
