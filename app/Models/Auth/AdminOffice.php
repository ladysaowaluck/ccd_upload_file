<?php

namespace App\Models\Auth;

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

