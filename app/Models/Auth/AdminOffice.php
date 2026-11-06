<?php

namespace App\Models\Auth;

// Gemini : AdminOffice, AdminDepartment, AdminDivision organizational tree models on authdb
use Illuminate\Database\Eloquent\Model;

class AdminOffice extends Model
{
    protected $connection = 'authdb';
    protected $table = 'admin_offices';

    protected $fillable = [
        'name',
        'code',
    ];
}

