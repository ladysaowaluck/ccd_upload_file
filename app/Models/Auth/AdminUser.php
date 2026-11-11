<?php

namespace App\Models\Auth;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\Auth\VerifyEmailNotification;
use App\Notifications\Auth\ResetPasswordNotification;

// AdminUser updated with HasApiTokens, MustVerifyEmail, HasFactory, and notification hooks
class AdminUser extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'authdb';
    protected $table = 'admin_users';

    protected $fillable = [
        'username',
        'name',
        'email',
        'email_verified_at',
        'password',
        'avatar',
        'office_id',
        'department_id',
        'division_id',
        // Allow email_token and email_token_at for registration verification flow
        'email_token',
        'email_token_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Send email verification notification using custom signed API route.
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    // Send password reset notification using custom API reset link.
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminRole::class,
            'ccd_admin_role_users',
            'user_id',
            'role_id'
        );
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(AdminOffice::class, 'office_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(AdminDepartment::class, 'department_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(AdminDivision::class, 'division_id');
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->roles->contains('slug', $roleSlug);
    }

    public function isAdmin(): bool
    {
        return $this->roles->contains('id', 1) || $this->hasRole('administrator');
    }
}

