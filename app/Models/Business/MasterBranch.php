<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;

class MasterBranch extends Model
{
    protected $connection = 'mysql';
    protected $table = 'master_branches';

    protected $fillable = [
        'branch_code',
        'branch_name',
    ];
}

