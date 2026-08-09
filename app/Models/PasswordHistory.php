<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entree de l'historique des mots de passe d'un utilisateur.
 *
 * Chaque changement de mot de passe archive ici l'empreinte remplacee, avec le
 * sel et le nombre d'iterations qui ont servi a la produire. Ces trois valeurs
 * sont indispensables pour pouvoir, plus tard, recalculer l'empreinte d'un mot
 * de passe candidat et detecter une reutilisation.
 */
class PasswordHistory extends Model
{
    /**
     * Seul created_at est gere : une entree d'historique n'est jamais modifiee.
     *
     * @var bool
     */
    public const UPDATED_AT = null;

    /**
     * Attributs remplissables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = ['user_id', 'password_hash', 'salt', 'hash_iterations'];

    /**
     * Attributs masques lors de la serialisation.
     *
     * @var array<int, string>
     */
    protected $hidden = ['password_hash', 'salt'];

    /**
     * Utilisateur auquel appartient cette entree d'historique.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
