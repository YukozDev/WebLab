<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique des anciens mots de passe (Partie 2).
 *
 * Permet d'interdire la reutilisation des X derniers mots de passe. On y
 * conserve l'empreinte ET le sel utilises a l'epoque : sans le sel d'origine,
 * il serait impossible de recalculer l'empreinte d'un mot de passe candidat
 * pour la comparer a une entree historique.
 *
 * Aucun mot de passe en clair n'est stocke ici.
 */
return new class extends Migration
{
    /**
     * Cree la table password_histories.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('password_hash', 255);
            $table->string('salt', 64);
            $table->unsignedInteger('hash_iterations');
            $table->timestamp('created_at')->nullable();

            // Les entrees sont toujours lues par utilisateur, de la plus
            // recente a la plus ancienne.
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Supprime la table password_histories.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('password_histories');
    }
};
