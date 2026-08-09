<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des clients (reprise du squelette fourni, enrichie).
 *
 * La colonne type distingue les clients residentiels des clients d'affaire.
 * C'est elle qui determine quelle liste chaque role a le droit de consulter :
 *  - 'residentiel' : visible par le prepose aux clients residentiels et l'administrateur ;
 *  - 'affaire'     : visible par le prepose aux clients d'affaire et l'administrateur.
 *
 * Une seule table avec discriminant est preferee a deux tables distinctes :
 * les deux listes ont exactement la meme structure, et le controle d'acces
 * porte sur la route, pas sur le schema.
 */
return new class extends Migration
{
    /**
     * Cree la table clients.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            // Discriminant : 'residentiel' ou 'affaire'.
            $table->string('type', 20)->index();
            // Renseigne uniquement pour les clients d'affaire.
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Supprime la table clients.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
