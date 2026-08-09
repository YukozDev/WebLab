<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des evenements de securite (Partie 2).
 *
 * Consigne les connexions reussies et echouees ainsi que les changements lies
 * a la securite (modification et reinitialisation de mot de passe, blocage et
 * deblocage de compte, creation d'utilisateur, modification des parametres).
 *
 * user_id est nullable et username_attempted est toujours renseigne : lorsqu'un
 * identifiant inexistant est saisi, il n'y a aucun utilisateur a referencer,
 * mais la tentative doit tout de meme etre tracee.
 *
 * Le journal ne contient jamais de mot de passe, ni en clair ni sous forme
 * d'empreinte : une trace d'audit est souvent moins bien protegee que la table
 * des utilisateurs.
 */
return new class extends Migration
{
    /**
     * Cree la table auth_logs.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('auth_logs', function (Blueprint $table) {
            $table->id();
            // Null si l'identifiant saisi ne correspond a aucun compte.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Identifiant saisi, conserve meme si le compte n'existe pas.
            $table->string('username_attempted', 50)->nullable();
            // Type d'evenement, voir les constantes de App\Models\AuthLog.
            $table->string('event', 50);
            // Complement lisible : motif de l'echec, parametre modifie, etc.
            $table->string('details', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['event', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Supprime la table auth_logs.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_logs');
    }
};
