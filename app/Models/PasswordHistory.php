<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'password_hash', 'salt', 'hash_iterations'];

    protected $hidden = ['password_hash', 'salt'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
