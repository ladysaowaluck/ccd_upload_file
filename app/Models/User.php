<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// User model configured for Laravel Sanctum authentication, account status, and verification
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'status',
        'verification_token',
        'verification_token_expires_at',
        'email_verified_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_token_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Default name to username when not supplied — prevents NOT NULL constraint failure on insert
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->name)) {
                $user->name = $user->username ?: ($user->email ?: 'User');
            }
        });
    }

    /**
     * Determine whether the user account is approved and verified.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved' && !empty($this->email_verified_at);
    }

    /**
     * Determine whether the user account is still pending verification.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
