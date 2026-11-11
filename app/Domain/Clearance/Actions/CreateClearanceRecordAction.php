<?php

namespace App\Domain\Clearance\Actions;

use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterCustomer;
use App\Models\Business\MasterLicense;
use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\UploadedFile;

class CreateClearanceRecordAction
{
    public function __construct(
        protected DocumentStorageService $storageService
    ) {
    }

    // Create a new clearance record with uploaded files and customer metadata.
     
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
                // Strip prior job prefixes to prevent filename nesting when re-uploading documents
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
                // Strip prior job prefixes to prevent filename nesting when re-uploading documents
                $baseName = preg_replace('/^(?:CCD\d{4}-\d{4}_(?:reference_file_\d|rf\d|hs_evidence_file_\d|hs\d)_)+/', '', $cleanName);
                $fileName = "{$jobNo}_{$hsField}_{$baseName}";
                $data[$hsField] = $this->storageService->store($file, 'hs_evidence', $fileName);
            }
        }

        if (isset($data['quantity_unit_id'])) {
            $data['quantity_unit_id'] = substr(trim((string) $data['quantity_unit_id']), 0, 3);
        }
        if (isset($data['statistic_code_id'])) {
            $data['statistic_code_id'] = substr(trim((string) $data['statistic_code_id']), 0, 3);
        }
        if (isset($data['privilege_code'])) {
            $data['privilege_code'] = substr(trim((string) $data['privilege_code']), 0, 3);
        }
        if (isset($data['sequence_code'])) {
            $data['sequence_code'] = substr(trim((string) $data['sequence_code']), 0, 5);
        }
        if (isset($data['hs_code'])) {
            $data['hs_code'] = substr(trim((string) $data['hs_code']), 0, 8);
        }
        if (isset($data['brand_name'])) {
            $data['brand_name'] = substr(trim((string) $data['brand_name']), 0, 50);
        }
        if (isset($data['country_of_origin'])) {
            $data['country_of_origin'] = substr(trim((string) $data['country_of_origin']), 0, 30);
        }

        if (isset($data['usage']) && !isset($data['usages'])) {
            $data['usages'] = $data['usage'];
        }
        if (isset($data['function']) && !isset($data['functions'])) {
            $data['functions'] = $data['function'];
        }

        /*
        if (!empty($data['statistic_code']) && empty($data['statistic_code_id'])) {
            $data['statistic_code_id'] = MasterStatistic::where('statistic_code_id', $data['statistic_code_id'])->value('id');
        }
        if (!empty($data['quantity_unit_id']) && empty($data['quantity_unit_id'])) {
            $data['quantity_unit_id'] = MasterUnit::where('unit', $data['quantity_unit_id'])->value('id');
        }
        if (!empty($data['tariff_specific_rate_unit']) && empty($data['tariff_specific_rate_unit_id'])) {
            $data['tariff_specific_rate_unit_id'] = MasterUnit::where('unit', $data['tariff_specific_rate_unit'])->value('id');
        }
        */

        // Safely map statistic_code and quantity_unit input values to their respective schema fields.
        if (!empty($data['statistic_code']) && empty($data['statistic_code_id'])) {
            $data['statistic_code_id'] = substr(trim((string) $data['statistic_code']), 0, 3);
        }
        if (!empty($data['quantity_unit']) && empty($data['quantity_unit_id'])) {
            $data['quantity_unit_id'] = substr(trim((string) $data['quantity_unit']), 0, 3);
        }
        if (!empty($data['tariff_specific_rate_unit']) && empty($data['tariff_specific_rate_unit_id'])) {
            $data['tariff_specific_rate_unit_id'] = substr(trim((string) $data['tariff_specific_rate_unit']), 0, 10);
        }

        foreach ([1, 2, 3, 4] as $idx) {
            $fkKey = "license{$idx}_name_id";
            if (!empty($data[$fkKey]) && !is_numeric($data[$fkKey])) {
                $licName = trim((string) $data[$fkKey]);
                $data[$fkKey] = MasterLicense::where('license_en', $licName)
                    ->orWhere('license_th', $licName)
                    ->value('id');
            }
        }

        $data['product_description_th'] = $data['product_description_th'] ?? ($data['product_description_en'] ?? '');
        $data['statistic_code_id'] = $data['statistic_code_id'] ?? '000';

        $data['create_date'] = $data['create_date'] ?? date('Y-m-d');
        $data['update_date'] = date('Y-m-d');
        $data['edit_user_id'] = auth()->id() ?: 1;

        return CustomerClearanceData::create($data);
    }
}

