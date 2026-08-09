<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const ADMINISTRATEUR = 'administrateur';

    public const PREPOSE_RESIDENTIEL = 'prepose_residentiel';

    public const PREPOSE_AFFAIRE = 'prepose_affaire';

    protected $fillable = ['name', 'label', 'description'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public static function noms(): array
    {
        return [
            self::ADMINISTRATEUR,
            self::PREPOSE_RESIDENTIEL,
            self::PREPOSE_AFFAIRE,
        ];
    }
}
