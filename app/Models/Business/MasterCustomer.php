<?php

namespace App\Models\Business;

// Gemini : MasterCustomer schema matching actual database
use Illuminate\Database\Eloquent\Model;

class MasterCustomer extends Model
{
    protected $connection = 'mysql';
    protected $table = 'master_customers';

    public $timestamps = true;

    protected $fillable = [
        'company_name',
        'tax_id',
        'remark',
        'is_enable',
    ];
}

