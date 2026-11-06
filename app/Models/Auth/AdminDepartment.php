<?php

namespace App\Models\Auth;

// Gemini : AdminDepartment model bound to authdb connection
use Illuminate\Database\Eloquent\Model;

class AdminDepartment extends Model
{
    protected $connection = 'authdb';
    protected $table = 'admin_departments';

    protected $fillable = [
        'name',
        'office_id',
    ];
}

