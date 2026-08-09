<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    public const TYPE_RESIDENTIEL = 'residentiel';

    public const TYPE_AFFAIRE = 'affaire';

    protected $fillable = [
        'first_name',
        'last_name',
        'type',
        'company_name',
        'email',
        'phone',
        'city',
    ];

    public function scopeOfType(Builder $requete, string $type): Builder
    {
        return $requete->where('type', $type);
    }
}
