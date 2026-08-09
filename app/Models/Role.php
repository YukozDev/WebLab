<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Role du modele RBAC.
 *
 * Les trois roles du laboratoire sont identifies par les constantes ci-dessous.
 * On ne reference jamais un role par son identifiant numerique : celui-ci
 * depend de l'ordre d'execution des seeders et differerait d'un poste a l'autre.
 */
class Role extends Model
{
    /** Acces complet : configuration de la securite et les deux listes de clients. */
    public const ADMINISTRATEUR = 'administrateur';

    /** Acces a la liste des clients residentiels uniquement. */
    public const PREPOSE_RESIDENTIEL = 'prepose_residentiel';

    /** Acces a la liste des clients d'affaire uniquement. */
    public const PREPOSE_AFFAIRE = 'prepose_affaire';

    /**
     * Attributs remplissables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'label', 'description'];

    /**
     * Utilisateurs qui possedent ce role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Liste des noms techniques des trois roles du systeme.
     *
     * Sert notamment a valider le role choisi dans le formulaire de creation
     * d'utilisateur, afin qu'aucune valeur arbitraire ne soit acceptee.
     *
     * @return array<int, string> Les noms techniques des roles.
     */
    public static function noms(): array
    {
        return [
            self::ADMINISTRATEUR,
            self::PREPOSE_RESIDENTIEL,
            self::PREPOSE_AFFAIRE,
        ];
    }
}
