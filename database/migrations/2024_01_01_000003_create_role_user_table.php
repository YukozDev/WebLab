<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pivot de l'association utilisateur <-> role (relation plusieurs-a-plusieurs).
 *
 * Le laboratoire n'exige qu'un role par utilisateur, mais le modele RBAC
 * classique en autorise plusieurs. Une table pivot plutot qu'une colonne
 * role_id dans users permet cette evolution sans migration de donnees.
 */
return new class extends Migration
{
    /**
     * Cree la table role_user.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            // Cle primaire composee : empeche d'attribuer deux fois le meme role.
            $table->primary(['user_id', 'role_id']);
        });
    }

    /**
     * Supprime la table role_user.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
