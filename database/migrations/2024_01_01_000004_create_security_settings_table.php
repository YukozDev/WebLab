<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parametres de securite configurables par l'administrateur.
 *
 * La table ne contient qu'une seule ligne (id = 1), creee par le seeder.
 * Le choix de colonnes nommees plutot qu'un magasin cle/valeur generique est
 * volontaire : chaque parametre est type par la base, validable par une regle
 * Laravel, et le schema documente lui-meme la politique de securite.
 *
 * Les valeurs par defaut suivent les recommandations de l'OWASP
 * (Authentication Cheat Sheet) et du NIST SP 800-63B.
 */
return new class extends Migration
{
    /**
     * Cree la table security_settings.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('security_settings', function (Blueprint $table) {
            $table->id();

            // --- Protection contre la force brute ---
            // Nombre d'echecs consecutifs avant le blocage definitif du compte.
            $table->unsignedSmallInteger('max_login_attempts')->default(5);
            // Delai impose entre deux tentatives apres un echec (secondes).
            $table->unsignedSmallInteger('failed_attempt_delay_seconds')->default(5);

            // --- Norme de complexite des mots de passe ---
            $table->unsignedSmallInteger('password_min_length')->default(12);
            $table->boolean('require_uppercase')->default(true);
            $table->boolean('require_lowercase')->default(true);
            $table->boolean('require_digit')->default(true);
            $table->boolean('require_special')->default(true);

            // --- Gestion du cycle de vie du mot de passe ---
            // Nombre d'anciens mots de passe interdits a la reutilisation.
            $table->unsignedSmallInteger('password_history_count')->default(5);
            // Duree de validite d'un mot de passe en jours (0 = pas d'expiration).
            $table->unsignedSmallInteger('password_expiry_days')->default(90);

            // --- Session (Partie 3) ---
            // Duree d'inactivite avant expiration de la session, en minutes.
            $table->unsignedSmallInteger('session_idle_timeout_minutes')->default(15);

            // --- Cout du hachage (Partie 2) ---
            // Nombre d'iterations PBKDF2 applique aux nouveaux mots de passe.
            $table->unsignedInteger('hash_iterations')->default(210000);

            $table->timestamps();
        });
    }

    /**
     * Supprime la table security_settings.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('security_settings');
    }
};
