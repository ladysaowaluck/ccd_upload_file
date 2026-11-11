<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class BulkExcelUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'single_record' => 'nullable|file|mimes:xlsx,xls|max:10240',
            'multi_record' => 'nullable|file|mimes:xlsx,xls|max:10240',
            'imp_exp' => 'nullable|string|in:Import,Export,0,1',
            'reference_file_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,xls,doc,docx|max:10240',
            'reference_file_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,xls,doc,docx|max:10240',
            'hs_evidence_file_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,xls,doc,docx|max:10240',
            'hs_evidence_file_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,xls,doc,docx|max:10240',
            'hs_evidence_file_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,xls,doc,docx|max:10240',
            'hs_evidence_file_4' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,xls,doc,docx|max:10240',

        ];
    }

    public function messages(): array
    {
        return [
            'single_record.mimes' => 'The uploaded requisition file must be a valid Excel (.xlsx, .xls) file.',
            'multi_record.mimes' => 'The uploaded batch requisition file must be a valid Excel (.xlsx, .xls) file.',
        ];
    }
}

