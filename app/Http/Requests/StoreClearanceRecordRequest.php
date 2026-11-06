<?php

namespace App\Http\Requests;

// Gemini : GAP-05 / GAP-10 — Form request validating clearance record creation and updates
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

        return [
            'job_no'                    => [
                'required',
                'string',
                Rule::unique('mysql.customer_clearance_data', 'job_no')->ignore($recordId),
            ],
            'master_customer_id'        => 'required|exists:mysql.master_customers,id',
            'master_branch_id'          => 'required|exists:mysql.master_branches,id',
            'imp_exp'                   => 'required|in:0,1',
            // Lady : Validation max lengths were exceeding physical database column limits (e.g. quantity_unit max:10 vs varchar(3), hs_code max:20 vs varchar(8)), causing SQLSTATE[22001] string data right truncation crashes in production.
            /*
            'product_description_en'    => 'required|string|max:500',
            'product_description_th'    => 'nullable|string|max:500',
            'product_code_1'            => 'nullable|string|max:100',
            'brand_name'                => 'nullable|string|max:100',
            'materials'                 => 'nullable|string|max:300',
            'usage'                     => 'nullable|string|max:300',
            'function'                  => 'nullable|string|max:300',
            'country_of_origin'         => 'nullable|string|max:50',
            'hs_code'                   => 'required|string|max:20',
            'sequence_code'             => 'nullable|string|max:20',
            'statistic_code'            => 'nullable|string|max:10',
            'quantity_unit'             => 'nullable|string|max:10',
            'privilege_code'            => 'nullable|string|max:20',
            */
            // Gemini : Enforce exact database column constraints to prevent SQLSTATE[22001] truncation errors
            'product_description_en'    => 'required|string|max:300',
            'product_description_th'    => 'nullable|string|max:300',
            'product_code_1'            => 'nullable|string|max:300',
            'brand_name'                => 'nullable|string|max:50',
            'materials'                 => 'nullable|string|max:300',
            'usage'                     => 'nullable|string|max:300',
            'function'                  => 'nullable|string|max:300',
            'country_of_origin'         => 'nullable|string|max:30',
            'hs_code'                   => 'required|string|max:8',
            'sequence_code'             => 'nullable|string|max:5',
            'statistic_code'            => 'nullable|string|max:3',
            'quantity_unit'             => 'nullable|string|max:3',
            'privilege_code'            => 'nullable|string|max:3',
            'tariff_ad_valorem'         => 'nullable|numeric|max:100',
            'tariff_specific_rate_baht' => 'nullable|numeric',
            'reference_file_1'          => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'reference_file_2'          => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_1'        => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_2'        => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_3'        => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_4'        => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
        ];
    }

    // Gemini : Custom validation messages for field length limits matching database schema
    public function messages(): array
    {
        return [
            'quantity_unit.max'          => 'The quantity unit cannot exceed 3 characters (e.g. C62, KGM, LTR).',
            'statistic_code.max'         => 'The statistical code cannot exceed 3 characters (e.g. 000, 001).',
            'privilege_code.max'         => 'The privilege code cannot exceed 3 characters (e.g. 000).',
            'hs_code.max'                => 'The HS Code cannot exceed 8 characters.',
            'sequence_code.max'          => 'The sequence code cannot exceed 5 characters.',
            'product_description_en.max' => 'The English product description cannot exceed 300 characters.',
            'product_description_th.max' => 'The Thai product description cannot exceed 300 characters.',
            'brand_name.max'             => 'The brand name cannot exceed 50 characters.',
            'country_of_origin.max'      => 'The country of origin cannot exceed 30 characters.',
        ];
    }
}

