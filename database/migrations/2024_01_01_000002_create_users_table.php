<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des utilisateurs.
 *
 * Le mot de passe n'est jamais stocke en clair. Seuls sont conserves :
 *  - password_hash  : l'empreinte PBKDF2-HMAC-SHA256 (hexadecimal) ;
 *  - salt           : un sel aleatoire propre a chaque utilisateur ;
 *  - hash_iterations: le nombre d'iterations utilise lors du dernier hachage.
 *
 * Conserver le nombre d'iterations par utilisateur plutot qu'une constante
 * globale permet de durcir le parametre depuis la page d'administration sans
 * invalider les mots de passe deja enregistres : chaque empreinte reste
 * verifiable avec le cout qui a servi a la produire.
 */
return new class extends Migration
{
    /**
     * Cree la table users.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Identifiant de connexion (ex. 'Administrateur', 'Utilisateur1').
            $table->string('username', 50)->unique();
            $table->string('full_name', 100);
            $table->string('email', 150)->nullable()->unique();

            // --- Stockage securise du mot de passe (Partie 2) ---
            $table->string('password_hash', 255);
            // Sel unique : rend les tables arc-en-ciel inutilisables et empeche
            // de deduire que deux utilisateurs ont le meme mot de passe.
            $table->string('salt', 64);
            $table->unsignedInteger('hash_iterations')->default(210000);

            // --- Protection contre la force brute (Partie 2) ---
            // Echecs consecutifs depuis la derniere connexion reussie.
            $table->unsignedSmallInteger('failed_attempts')->default(0);
            // Instant du dernier echec : sert a imposer un delai entre 2 essais.
            $table->timestamp('last_failed_at')->nullable();
            // Blocage definitif : seul un administrateur peut le lever.
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();

            // --- Cycle de vie du mot de passe (Partie 2) ---
            // Force le changement au prochain acces : premiere connexion,
            // reinitialisation par l'administrateur, oubli, expiration.
            $table->boolean('must_change_password')->default(true);
            $table->timestamp('password_changed_at')->nullable();

            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table users.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
