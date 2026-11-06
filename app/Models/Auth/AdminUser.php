<?php

namespace App\Models\Auth;

// Gemini : AdminUser model bound to authdb connection (table admin_users) for authentication and RBAC
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $connection = 'authdb';
    protected $table = 'admin_users';

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'avatar',
        'office_id',
        'department_id',
        'division_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

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

