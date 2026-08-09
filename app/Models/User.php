<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Utilisateur de l'application.
 *
 * L'authentification s'appuie sur username et non sur email : l'enonce impose
 * les identifiants « Administrateur », « Utilisateur1 » et « Utilisateur2 ».
 *
 * Le mot de passe est verifie par App\Services\PasswordHasher (PBKDF2 avec sel
 * par utilisateur) et non par le hacheur bcrypt de Laravel. Les attributs
 * password_hash et salt ne sont donc jamais manipules directement en dehors de
 * ce service.
 */
class User extends Authenticatable implements AuthenticatableContract
{
    use Notifiable;

    /**
     * Attributs remplissables en masse.
     *
     * password_hash, salt, is_locked et must_change_password en sont
     * volontairement exclus : ils ne doivent jamais pouvoir etre imposes par
     * une requete HTTP, seulement par les services de securite.
     *
     * @var array<int, string>
     */
    protected $fillable = ['username', 'full_name', 'email'];

    /**
     * Attributs masques lors de la serialisation (JSON, logs, dd()).
     *
     * @var array<int, string>
     */
    protected $hidden = ['password_hash', 'salt', 'remember_token'];

    /**
     * Conversions de types des attributs.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'must_change_password' => 'boolean',
            'last_failed_at' => 'datetime',
            'locked_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Indique a Laravel quelle colonne contient l'empreinte du mot de passe.
     *
     * Le schema utilise password_hash plutot que la colonne password attendue
     * par defaut, afin d'expliciter qu'aucun mot de passe en clair n'y transite.
     *
     * @return string L'empreinte du mot de passe.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Roles attribues a cet utilisateur.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Anciens mots de passe de cet utilisateur, du plus recent au plus ancien.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class)->latest('created_at');
    }

    /**
     * Evenements de securite associes a cet utilisateur.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function authLogs(): HasMany
    {
        return $this->hasMany(AuthLog::class)->latest('created_at');
    }

    /**
     * Verifie si l'utilisateur possede un role donne.
     *
     * La relation est lue via l'accesseur $this->roles, qui met le resultat en
     * cache pour la duree de la requete : appeler la methode plusieurs fois
     * (menu, route, vue) ne genere qu'une seule interrogation de la base.
     *
     * @param  string  $nomRole  Nom technique du role, ex. Role::ADMINISTRATEUR.
     * @return bool Vrai si l'utilisateur possede ce role.
     */
    public function hasRole(string $nomRole): bool
    {
        return $this->roles->contains('name', $nomRole);
    }

    /**
     * Verifie si l'utilisateur possede au moins un des roles fournis.
     *
     * @param  array<int, string>  $nomsRoles  Noms techniques des roles acceptes.
     * @return bool Vrai si au moins un des roles est possede.
     */
    public function hasAnyRole(array $nomsRoles): bool
    {
        foreach ($nomsRoles as $nomRole) {
            if ($this->hasRole($nomRole)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Raccourci de lisibilite pour le role administrateur.
     *
     * @return bool Vrai si l'utilisateur est administrateur.
     */
    public function isAdministrateur(): bool
    {
        return $this->hasRole(Role::ADMINISTRATEUR);
    }

    /**
     * Libelles des roles de l'utilisateur, pour affichage.
     *
     * @return string Les libelles separes par une virgule, ou un tiret si aucun.
     */
    public function libelleRoles(): string
    {
        return $this->roles->pluck('label')->join(', ') ?: '—';
    }
}
