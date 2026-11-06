<?php

namespace App\Models\Business;

// Gemini : CustomerClearanceData aligned with exact legacy schema
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerClearanceData extends Model
{
    protected $connection = 'mysql';
    protected $table = 'customer_clearance_data';

    public $timestamps = true;

    protected $fillable = [
        'job_no',
        'master_customer_id',
        'customer_name',
        'master_branch_id',
        'imp_exp',
        'product_description_en',
        'product_description_th',
        'product_code_1',
        'product_code_2',
        'product_year',
        'brand_name',
        'model_name',
        'materials',
        'usage',
        'function',
        'position',
        'country_of_origin',
        'note',
        'hs_code',
        'sequence_code',
        'statistic_code',
        'quantity_unit',
        'privilege_code',
        'tariff_ad_valorem',
        'tariff_specific_rate_unit',
        'tariff_specific_rate_baht',
        'reference_file_1',
        'reference_file_2',
        'hs_evidence_file_1',
        'hs_evidence_file_2',
        'hs_evidence_file_3',
        'hs_evidence_file_4',
        'create_date',
        'update_date',
        'edit_user_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(MasterCustomer::class, 'master_customer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(MasterBranch::class, 'master_branch_id');
    }

    /**
     * Check if clearance record has zero uploaded evidence files
     */
    public function isMissingEvidence(): bool
    {
        return empty($this->hs_evidence_file_1)
            && empty($this->hs_evidence_file_2)
            && empty($this->hs_evidence_file_3);
    }
}

