<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Client de l'entreprise, residentiel ou d'affaire.
 *
 * Modele repris du squelette fourni et enrichi d'un discriminant « type »,
 * qui determine quelle liste chaque role a le droit de consulter.
 */
class Client extends Model
{
    /** Client residentiel : visible par le prepose residentiel et l'administrateur. */
    public const TYPE_RESIDENTIEL = 'residentiel';

    /** Client d'affaire : visible par le prepose aux affaires et l'administrateur. */
    public const TYPE_AFFAIRE = 'affaire';

    /**
     * Attributs remplissables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'type',
        'company_name',
        'email',
        'phone',
        'city',
    ];

    /**
     * Restreint la requete a un type de client.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $requete  Requete en cours.
     * @param  string  $type  Client::TYPE_RESIDENTIEL ou Client::TYPE_AFFAIRE.
     * @return \Illuminate\Database\Eloquent\Builder La requete filtree.
     */
    public function scopeOfType(Builder $requete, string $type): Builder
    {
        return $requete->where('type', $type);
    }
}
