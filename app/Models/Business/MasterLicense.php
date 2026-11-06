<?php

namespace App\Models\Business;

// Gemini : MasterLicense model bound to mysql connection
use Illuminate\Database\Eloquent\Model;

class MasterLicense extends Model
{
    protected $connection = 'mysql';
    protected $table = 'master_licenses';

    protected $fillable = [
        'license_code',
        'license_name',
    ];
}

