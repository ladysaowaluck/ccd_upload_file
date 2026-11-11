<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClearanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $recordId = $this->route('id');

        // Allow job_no up to 20 characters so sequence numbers above 9999 (e.g. CCD2611-10000) pass validation.
        return [
            'job_no' => [
                'required',
                'string',
                'max:20',
                Rule::unique('mysql.customer_clearance_data', 'job_no')->ignore($recordId),
            ],
            'master_customer_id' => 'required|exists:mysql.master_customers,id',
            'master_branch_id' => 'required|exists:mysql.master_branches,id',
            'imp_exp' => 'required|in:0,1',
            'product_description_en' => 'required|string|max:300',
            'product_description_th' => 'nullable|string|max:300',
            'product_code_1' => 'nullable|string|max:300',
            'brand_name' => 'nullable|string|max:50',
            'materials' => 'nullable|string|max:300',
            'usages' => 'nullable|string|max:300',
            'usage' => 'nullable|string|max:300',
            'functions' => 'nullable|string|max:300',
            'country_of_origin' => 'nullable|string|max:30',
            'hs_code' => 'required|string|max:8',
            'sequence_code' => 'nullable|string|max:5',
            'statistic_code_id' => 'nullable|integer',
            'quantity_unit_id' => 'nullable|integer',
            'privilege_code' => 'nullable|string|max:3',
            'tariff_ad_valorem' => 'nullable|numeric|max:100',
            'tariff_specific_rate_unit_id' => 'nullable|integer',
            'tariff_specific_rate_baht' => 'nullable|numeric',
            'reference_file_1' => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'reference_file_2' => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_1' => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_2' => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_3' => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_4' => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'anti_dumping_ad_valorem' => 'nullable|numeric|max:100',
            'excise_tax_ad_valorem' => 'nullable|numeric|max:100',
            'excise_tax_rate_unit_id' => 'nullable|string|max:10',
            'excise_tax_rate_baht' => 'nullable|numeric',
            'other_tax1_name_of_tax' => 'nullable|string|max:255',
            'other_tax1_ad_valorem' => 'nullable|numeric|max:100',
            'other_tax1_rate_unit_id' => 'nullable|string|max:10',
            'other_tax1_rate_baht' => 'nullable|numeric',
            'other_tax2_name_of_tax' => 'nullable|string|max:255',
            'other_tax2_ad_valorem' => 'nullable|numeric|max:100',
            'other_tax2_rate_unit_id' => 'nullable|string|max:10',
            'other_tax2_rate_baht' => 'nullable|numeric',
            'license1_name_id' => 'nullable|exists:mysql.master_licenses,id',
            'license1_note' => 'nullable|string',
            'license2_name_id' => 'nullable|exists:mysql.master_licenses,id',
            'license2_note' => 'nullable|string',
            'license3_name_id' => 'nullable|exists:mysql.master_licenses,id',
            'license3_note' => 'nullable|string',
            'license4_name_id' => 'nullable|exists:mysql.master_licenses,id',
            'license4_note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity_unit.max' => 'The quantity unit cannot exceed 3 characters (e.g. C62, KGM, LTR).',
            'statistic_code.max' => 'The statistical code cannot exceed 3 characters (e.g. 000, 001).',
            'privilege_code.max' => 'The privilege code cannot exceed 3 characters (e.g. 000).',
            'hs_code.max' => 'The HS Code cannot exceed 8 characters.',
            'sequence_code.max' => 'The sequence code cannot exceed 5 characters.',
            'product_description_en.max' => 'The English product description cannot exceed 300 characters.',
            'product_description_th.max' => 'The Thai product description cannot exceed 300 characters.',
            'brand_name.max' => 'The brand name cannot exceed 50 characters.',
            'country_of_origin.max' => 'The country of origin cannot exceed 30 characters.',
        ];
    }
}

