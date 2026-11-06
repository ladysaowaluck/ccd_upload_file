<?php

namespace App\Domain\Clearance\Actions;

// Gemini : GAP-05 — Action executing domain updates on CustomerClearanceData models with file handling
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterCustomer;
use Illuminate\Http\UploadedFile;

// Gemini : GAP-07 — Inject DocumentStorageService for unified file storage and cloud sync
use App\Services\Storage\DocumentStorageService;

class UpdateClearanceRecordAction
{
    // Gemini : GAP-07 — Injected DocumentStorageService dependency
    public function __construct(
        protected DocumentStorageService $storageService
    ) {}

    /**
     * Update an existing clearance record.
     *
     * @param CustomerClearanceData $record
     * @param array $data
     * @return CustomerClearanceData
     */
    public function execute(CustomerClearanceData $record, array $data): CustomerClearanceData
    {
        // Sync customer name if master_customer_id is modified
        if (!empty($data['master_customer_id'])) {
            $customer = MasterCustomer::find($data['master_customer_id']);
            if ($customer) {
                $data['customer_name'] = $customer->company_name;
            }
        }

        // Process file uploads
        foreach (['reference_file_1', 'reference_file_2'] as $refField) {
            if (!empty($data[$refField]) && $data[$refField] instanceof UploadedFile) {
                $file = $data[$refField];
                $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                // Gemini : Strip prior job prefixes to prevent filename nesting when re-uploading documents
                $baseName = preg_replace('/^(?:CCD\d{4}-\d{4}_(?:reference_file_\d|rf\d|hs_evidence_file_\d|hs\d)_)+/', '', $cleanName);
                $fileName = "{$record->job_no}_{$refField}_{$baseName}";
                // Lady : Direct file->storeAs('public/reference') tightly couples action to local storage and bypasses S3 sync.
                /*
                $file->storeAs('public/reference', $fileName);
                $data[$refField] = "reference/{$fileName}";
                */
                // Gemini : MISS-03 — Delete the old S3 object before uploading replacement to prevent orphaned bucket files
                if (!empty($record->$refField)) {
                    $this->storageService->delete($record->$refField);
                }
                // Gemini : Use DocumentStorageService for local storage and optional S3 synchronization
                $data[$refField] = $this->storageService->store($file, 'reference', $fileName);
            } else {
                unset($data[$refField]);
            }
        }

        foreach (['hs_evidence_file_1', 'hs_evidence_file_2', 'hs_evidence_file_3', 'hs_evidence_file_4'] as $hsField) {
            if (!empty($data[$hsField]) && $data[$hsField] instanceof UploadedFile) {
                $file = $data[$hsField];
                $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                // Gemini : Strip prior job prefixes to prevent filename nesting when re-uploading documents
                $baseName = preg_replace('/^(?:CCD\d{4}-\d{4}_(?:reference_file_\d|rf\d|hs_evidence_file_\d|hs\d)_)+/', '', $cleanName);
                $fileName = "{$record->job_no}_{$hsField}_{$baseName}";
                // Lady : Direct file->storeAs('public/hs_evidence') lacks centralized cloud sync abstraction.
                /*
                $file->storeAs('public/hs_evidence', $fileName);
                $data[$refField] = "hs_evidence/{$fileName}";
                */
                // Gemini : MISS-03 — Delete the old S3 object before uploading replacement to prevent orphaned bucket files
                if (!empty($record->$hsField)) {
                    $this->storageService->delete($record->$hsField);
                }
                // Gemini : Use DocumentStorageService for local storage and optional S3 synchronization
                $data[$hsField] = $this->storageService->store($file, 'hs_evidence', $fileName);
            } else {
                unset($data[$hsField]);
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

        $data['update_date'] = date('Y-m-d');
        $data['edit_user_id'] = auth()->id() ?: 1;

        $record->update($data);

        return $record->fresh();
    }
}

