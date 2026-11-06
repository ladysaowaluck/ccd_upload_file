<?php

namespace App\Models\Business;

// Gemini : MasterUnit matching actual unit column
use Illuminate\Database\Eloquent\Model;

class MasterUnit extends Model
{
    protected $connection = 'mysql';
    protected $table = 'master_units';

    public $timestamps = true;

    protected $fillable = [
        'unit',
    ];
}

