<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des roles du modele RBAC.
 *
 * Un role regroupe implicitement un ensemble de permissions : les pages
 * auxquelles il donne acces. Les roles sont crees par RoleSeeder et ne sont pas
 * modifiables depuis l'interface, car ils font partie de la definition du
 * systeme et non de sa configuration.
 */
return new class extends Migration
{
    /**
     * Cree la table roles.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            // Nom technique utilise dans le code et les routes (ex. 'administrateur').
            $table->string('name', 50)->unique();
            // Libelle affiche a l'utilisateur (ex. 'Prepose aux clients residentiels').
            $table->string('label', 100);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table roles.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
