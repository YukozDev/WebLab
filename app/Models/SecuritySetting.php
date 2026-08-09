<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Parametres de securite configurables par l'administrateur.
 *
 * La table ne contient qu'une seule ligne. On y accede toujours par
 * SecuritySetting::courants(), qui la cree au besoin avec les valeurs par
 * defaut et la memorise pour la duree de la requete HTTP.
 */
class SecuritySetting extends Model
{
    /**
     * Instance memorisee pour la duree de la requete.
     *
     * Evite de reinterroger la base a chaque verification de politique
     * (complexite, tentatives, expiration...) lors d'une meme requete.
     *
     * @var \App\Models\SecuritySetting|null
     */
    protected static ?SecuritySetting $instance = null;

    /**
     * Attributs remplissables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'max_login_attempts',
        'failed_attempt_delay_seconds',
        'password_min_length',
        'require_uppercase',
        'require_lowercase',
        'require_digit',
        'require_special',
        'password_history_count',
        'password_expiry_days',
        'session_idle_timeout_minutes',
        'hash_iterations',
    ];

    /**
     * Conversions de types des attributs.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_login_attempts' => 'integer',
            'failed_attempt_delay_seconds' => 'integer',
            'password_min_length' => 'integer',
            'require_uppercase' => 'boolean',
            'require_lowercase' => 'boolean',
            'require_digit' => 'boolean',
            'require_special' => 'boolean',
            'password_history_count' => 'integer',
            'password_expiry_days' => 'integer',
            'session_idle_timeout_minutes' => 'integer',
            'hash_iterations' => 'integer',
        ];
    }

    /**
     * Retourne la ligne unique de parametres, en la creant si necessaire.
     *
     * @return \App\Models\SecuritySetting Les parametres de securite en vigueur.
     */
    public static function courants(): self
    {
        return self::$instance ??= self::firstOrCreate(['id' => 1]);
    }

    /**
     * Oublie l'instance memorisee.
     *
     * A appeler apres une mise a jour des parametres pour que le reste de la
     * requete travaille avec les nouvelles valeurs.
     *
     * @return void
     */
    public static function oublier(): void
    {
        self::$instance = null;
    }
}
