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
            'product_code_2' => 'nullable|string|max:300',
            'product_year' => 'nullable|integer|digits:4|between:1900,2100',
            'brand_name' => 'nullable|string|max:50',
            // 'model_name' => 'nullable|string|max:100',
            'model_name' => 'nullable|string|max:50',
            'materials' => 'nullable|string|max:300',
            'usages' => 'nullable|string|max:300',
            'usage' => 'nullable|string|max:300',
            'functions' => 'nullable|string|max:300',
            'function' => 'nullable|string|max:300',
            'position' => 'nullable|string|max:300',
            'country_of_origin' => 'nullable|string|max:30',
            // 'note' => 'nullable|string',
            // Match customer_clearance_data.note varchar(500).
            'note' => 'nullable|string|max:500',
            'hs_code' => 'required|string|max:8',
            'sequence_code' => 'nullable|string|max:5',
            'statistic_code_id' => 'nullable|integer',
            'quantity_unit_id' => 'nullable|integer',
            'statistic_code' => 'nullable|string|max:3',
            'quantity_unit' => 'nullable|string|max:3',
            'privilege_code' => 'nullable|string|max:3',
            'tariff_ad_valorem' => 'nullable|numeric|max:100',
            // 'tariff_specific_rate_unit_id' => 'nullable|integer',
            // The action resolves the code to master_units.id (unknown code -> null).
            'tariff_specific_rate_unit_id' => 'nullable|string|max:10',
            // 'tariff_specific_rate_baht' => 'nullable|numeric',
            'tariff_specific_rate_baht' => 'nullable|numeric|max:99999999.99',
            // Allow attachment files up to 25MB (25600 KB) matching server configuration
            'reference_file_1' => 'nullable|file|max:25600|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'reference_file_2' => 'nullable|file|max:25600|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_1' => 'nullable|file|max:25600|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_2' => 'nullable|file|max:25600|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_3' => 'nullable|file|max:25600|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_4' => 'nullable|file|max:25600|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            // Allow optional deletion flag for each attachment slot during record update
            'delete_reference_file_1' => 'nullable|boolean',
            'delete_reference_file_2' => 'nullable|boolean',
            'delete_hs_evidence_file_1' => 'nullable|boolean',
            'delete_hs_evidence_file_2' => 'nullable|boolean',
            'delete_hs_evidence_file_3' => 'nullable|boolean',
            'delete_hs_evidence_file_4' => 'nullable|boolean',
            'anti_dumping_ad_valorem' => 'nullable|numeric|max:100',
            'excise_tax_ad_valorem' => 'nullable|numeric|max:100',
            'excise_tax_rate_unit_id' => 'nullable|string|max:10',
            // 'excise_tax_rate_baht' => 'nullable|numeric',
            'excise_tax_rate_baht' => 'nullable|numeric|max:99999999.99',
            'other_tax1_name_of_tax' => 'nullable|string|max:255',
            'other_tax1_ad_valorem' => 'nullable|numeric|max:100',
            'other_tax1_rate_unit_id' => 'nullable|string|max:10',
            // 'other_tax1_rate_baht' => 'nullable|numeric',
            'other_tax1_rate_baht' => 'nullable|numeric|max:99999999.99',
            'other_tax2_name_of_tax' => 'nullable|string|max:255',
            'other_tax2_ad_valorem' => 'nullable|numeric|max:100',
            'other_tax2_rate_unit_id' => 'nullable|string|max:10',
            // 'other_tax2_rate_baht' => 'nullable|numeric',
            'other_tax2_rate_baht' => 'nullable|numeric|max:99999999.99',
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

    // Detect PHP layer upload_max_filesize and post_max_size overflow before validation runs
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($_FILES) && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                $postLimit = ini_get('post_max_size');
                $validator->errors()->add('upload_error', "The uploaded file and form data exceeded the server's post_max_size limit ({$postLimit}).");
                return;
            }

            // Check individual file slots for PHP upload_max_filesize errors
            $fileFields = [
                'reference_file_1', 'reference_file_2',
                'hs_evidence_file_1', 'hs_evidence_file_2', 'hs_evidence_file_3', 'hs_evidence_file_4',
            ];
            foreach ($fileFields as $field) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_INI_SIZE) {
                    $uploadLimit = ini_get('upload_max_filesize');
                    $validator->errors()->add($field, "The file exceeds the server's upload limit (php.ini upload_max_filesize = {$uploadLimit}). Please reduce file size or increase server limit.");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'quantity_unit.max' => 'The quantity unit cannot exceed 3 characters (e.g. C62, KGM, LTR).',
            'statistic_code.max' => 'The statistical code cannot exceed 3 characters (e.g. 000, 001).',
            'model_name.max' => 'The model name cannot exceed 50 characters.',
            'note.max' => 'The note cannot exceed 500 characters.',
            'tariff_specific_rate_baht.max' => 'The specific rate (Baht) cannot exceed 99,999,999.99.',
            'excise_tax_rate_baht.max' => 'The excise tax rate (Baht) cannot exceed 99,999,999.99.',
            'other_tax1_rate_baht.max' => 'The other tax 1 rate (Baht) cannot exceed 99,999,999.99.',
            'other_tax2_rate_baht.max' => 'The other tax 2 rate (Baht) cannot exceed 99,999,999.99.',
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

