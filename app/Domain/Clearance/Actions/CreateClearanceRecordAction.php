<?php

namespace App\Domain\Clearance\Actions;

// Gemini : GAP-11 — CreateClearanceRecordAction encapsulates domain creation logic and file storage
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterCustomer;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\UploadedFile;

class CreateClearanceRecordAction
{
    public function __construct(
        protected DocumentStorageService $storageService
    ) {}

    /**
     * Create a new clearance record with uploaded files and customer metadata.
     *
     * @param array $data
     * @return CustomerClearanceData
     */
    public function execute(array $data): CustomerClearanceData
    {
        // Resolve customer name from master customer ID if not provided
        if (!empty($data['master_customer_id']) && empty($data['customer_name'])) {
            $customer = MasterCustomer::find($data['master_customer_id']);
            if ($customer) {
                $data['customer_name'] = $customer->company_name;
            }
        }

        $jobNo = $data['job_no'] ?? 'TEMP';

        // Process reference files via DocumentStorageService
        foreach (['reference_file_1', 'reference_file_2'] as $refField) {
            if (!empty($data[$refField]) && $data[$refField] instanceof UploadedFile) {
                $file = $data[$refField];
                $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                // Gemini : Strip prior job prefixes to prevent filename nesting when re-uploading documents
                $baseName = preg_replace('/^(?:CCD\d{4}-\d{4}_(?:reference_file_\d|rf\d|hs_evidence_file_\d|hs\d)_)+/', '', $cleanName);
                $fileName = "{$jobNo}_{$refField}_{$baseName}";
                $data[$refField] = $this->storageService->store($file, 'reference', $fileName);
            }
        }

        // Process HS evidence files via DocumentStorageService
        foreach (['hs_evidence_file_1', 'hs_evidence_file_2', 'hs_evidence_file_3', 'hs_evidence_file_4'] as $hsField) {
            if (!empty($data[$hsField]) && $data[$hsField] instanceof UploadedFile) {
                $file = $data[$hsField];
                $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                // Gemini : Strip prior job prefixes to prevent filename nesting when re-uploading documents
                $baseName = preg_replace('/^(?:CCD\d{4}-\d{4}_(?:reference_file_\d|rf\d|hs_evidence_file_\d|hs\d)_)+/', '', $cleanName);
                $fileName = "{$jobNo}_{$hsField}_{$baseName}";
                $data[$hsField] = $this->storageService->store($file, 'hs_evidence', $fileName);
            }
        }

        // Gemini : Defensive truncation against SQLSTATE[22001] column limit overflow
        if (isset($data['quantity_unit'])) {
            $data['quantity_unit'] = substr(trim((string)$data['quantity_unit']), 0, 3);
        }
        if (isset($data['statistic_code'])) {
            $data['statistic_code'] = substr(trim((string)$data['statistic_code']), 0, 3);
        }
        if (isset($data['privilege_code'])) {
            $data['privilege_code'] = substr(trim((string)$data['privilege_code']), 0, 3);
        }
        if (isset($data['sequence_code'])) {
            $data['sequence_code'] = substr(trim((string)$data['sequence_code']), 0, 5);
        }
        if (isset($data['hs_code'])) {
            $data['hs_code'] = substr(trim((string)$data['hs_code']), 0, 8);
        }
        if (isset($data['brand_name'])) {
            $data['brand_name'] = substr(trim((string)$data['brand_name']), 0, 50);
        }
        if (isset($data['country_of_origin'])) {
            $data['country_of_origin'] = substr(trim((string)$data['country_of_origin']), 0, 30);
        }

        // Gemini : Ensure non-nullable database text columns have default values to avoid MySQL error 1364
        $data['product_description_th'] = $data['product_description_th'] ?? ($data['product_description_en'] ?? '');
        $data['statistic_code'] = $data['statistic_code'] ?? '000';

        $data['create_date'] = $data['create_date'] ?? date('Y-m-d');
        $data['update_date'] = date('Y-m-d');
        $data['edit_user_id'] = auth()->id() ?: 1;

        return CustomerClearanceData::create($data);
    }
}

