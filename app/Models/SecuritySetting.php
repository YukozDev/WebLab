<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuritySetting extends Model
{
    protected static ?SecuritySetting $instance = null;

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

    public static function courants(): self
    {
        return self::$instance ??= self::firstOrCreate(['id' => 1]);
    }

    public static function oublier(): void
    {
        self::$instance = null;
    }
}
