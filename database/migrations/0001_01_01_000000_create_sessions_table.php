<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table de stockage des sessions cote serveur (Partie 3).
 *
 * Remplace la migration create_users_table generee par Laravel : la table
 * users est definie par notre propre migration, et la table
 * password_reset_tokens n'est pas utilisee, la reinitialisation d'un mot de
 * passe oublie passant obligatoirement par l'administrateur.
 *
 * Avec le pilote de session « database », le navigateur ne recoit qu'un
 * identifiant de session opaque : la totalite du contenu de la session reste
 * sur le serveur, dans la colonne payload. Celle-ci est en outre chiffree
 * lorsque SESSION_ENCRYPT vaut true.
 *
 * last_activity stocke un horodatage Unix et sert a expirer les sessions
 * inactives ; il est indexe car le ramasse-miettes de session le balaye.
 */
return new class extends Migration
{
    /**
     * Cree la table sessions.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Supprime la table sessions.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
