<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;

class MasterBranch extends Model
{
    protected $connection = 'mysql';
    protected $table = 'master_branches';

    public $timestamps = false;

    protected $fillable = [
        // Add id to fillable since master_branches table does not have AUTO_INCREMENT on id column
        'id',
        'branch_code',
        'branch_name',
    ];
}

