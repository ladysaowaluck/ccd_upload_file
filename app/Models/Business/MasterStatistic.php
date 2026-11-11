<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;

class MasterStatistic extends Model
{
    protected $connection = 'mysql';
    protected $table = 'master_statistics';

    public $timestamps = true;

    protected $fillable = [
        'statistics',
    ];
}

