<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements AuthenticatableContract
{
    use Notifiable;

    protected $fillable = ['username', 'full_name', 'email'];

    protected $hidden = ['password_hash', 'salt', 'remember_token'];

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

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class)->latest('created_at');
    }

    public function authLogs(): HasMany
    {
        return $this->hasMany(AuthLog::class)->latest('created_at');
    }

    public function hasRole(string $nomRole): bool
    {
        return $this->roles->contains('name', $nomRole);
    }

    public function hasAnyRole(array $nomsRoles): bool
    {
        foreach ($nomsRoles as $nomRole) {
            if ($this->hasRole($nomRole)) {
                return true;
            }
        }

        return false;
    }

    public function isAdministrateur(): bool
    {
        return $this->hasRole(Role::ADMINISTRATEUR);
    }

    public function libelleRoles(): string
    {
        return $this->roles->pluck('label')->join(', ') ?: '—';
    }
}
