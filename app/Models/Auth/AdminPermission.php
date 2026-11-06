<?php

namespace App\Models\Auth;

// Gemini : AdminPermission model bound to authdb connection (table ccd_admin_permissions)
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminPermission extends Model
{
    protected $connection = 'authdb';
    protected $table = 'ccd_admin_permissions';

    protected $fillable = [
        'name',
        'slug',
        'http_method',
        'http_path',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminRole::class,
            'ccd_admin_role_permissions',
            'permission_id',
            'role_id'
        );
    }
}

