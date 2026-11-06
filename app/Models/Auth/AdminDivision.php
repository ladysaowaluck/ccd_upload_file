<?php

namespace App\Models\Auth;

// Gemini : AdminDivision model bound to authdb connection
use Illuminate\Database\Eloquent\Model;

class AdminDivision extends Model
{
    protected $connection = 'authdb';
    protected $table = 'admin_divisions';

    protected $fillable = [
        'name',
        'department_id',
    ];
}

