<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/**
 * Evenement du journal de securite.
 *
 * Les enregistrements se font exclusivement par la methode statique
 * enregistrer(), afin que chaque trace comporte systematiquement l'adresse IP
 * et l'agent utilisateur, sans avoir a y penser sur chaque appel.
 */
class AuthLog extends Model
{
    /**
     * Une trace d'audit n'est jamais modifiee apres son ecriture.
     *
     * @var bool
     */
    public const UPDATED_AT = null;

    // --- Types d'evenements consignes ---
    public const CONNEXION_REUSSIE = 'connexion_reussie';
    public const CONNEXION_ECHOUEE = 'connexion_echouee';
    public const DECONNEXION = 'deconnexion';
    public const COMPTE_BLOQUE = 'compte_bloque';
    public const COMPTE_DEBLOQUE = 'compte_debloque';
    public const MOT_DE_PASSE_MODIFIE = 'mot_de_passe_modifie';
    public const MOT_DE_PASSE_REINITIALISE = 'mot_de_passe_reinitialise';
    public const UTILISATEUR_CREE = 'utilisateur_cree';
    public const PARAMETRES_MODIFIES = 'parametres_modifies';
    public const ACCES_REFUSE = 'acces_refuse';

    /**
     * Attributs remplissables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'username_attempted',
        'event',
        'details',
        'ip_address',
        'user_agent',
    ];

    /**
     * Utilisateur concerne, s'il a pu etre identifie.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Consigne un evenement de securite dans le journal.
     *
     * @param  string  $evenement  Une des constantes de cette classe.
     * @param  \App\Models\User|null  $utilisateur  Utilisateur concerne, si connu.
     * @param  string|null  $identifiantSaisi  Identifiant saisi, utile quand le compte n'existe pas.
     * @param  string|null  $details  Complement lisible (motif d'echec, champ modifie...).
     * @return \App\Models\AuthLog L'entree de journal creee.
     */
    public static function enregistrer(
        string $evenement,
        ?User $utilisateur = null,
        ?string $identifiantSaisi = null,
        ?string $details = null
    ): self {
        /** @var Request $requete */
        $requete = request();

        return self::create([
            'user_id' => $utilisateur?->id,
            'username_attempted' => $identifiantSaisi ?? $utilisateur?->username,
            'event' => $evenement,
            'details' => $details,
            'ip_address' => $requete?->ip(),
            // Tronque a la taille de la colonne : un agent utilisateur peut etre
            // arbitrairement long et est entierement controle par le client.
            'user_agent' => substr((string) $requete?->userAgent(), 0, 255),
        ]);
    }

    /**
     * Libelle lisible de l'evenement, pour affichage dans le journal.
     *
     * @return string Le libelle en francais.
     */
    public function libelleEvenement(): string
    {
        return match ($this->event) {
            self::CONNEXION_REUSSIE => 'Connexion reussie',
            self::CONNEXION_ECHOUEE => 'Connexion echouee',
            self::DECONNEXION => 'Deconnexion',
            self::COMPTE_BLOQUE => 'Compte bloque',
            self::COMPTE_DEBLOQUE => 'Compte debloque',
            self::MOT_DE_PASSE_MODIFIE => 'Mot de passe modifie',
            self::MOT_DE_PASSE_REINITIALISE => 'Mot de passe reinitialise',
            self::UTILISATEUR_CREE => 'Utilisateur cree',
            self::PARAMETRES_MODIFIES => 'Parametres de securite modifies',
            self::ACCES_REFUSE => 'Acces refuse',
            default => $this->event,
        };
    }
}
