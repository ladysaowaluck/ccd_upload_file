<?php

namespace App\Domain\Clearance\Actions;

use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterCustomer;
use App\Models\Business\MasterLicense;
use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;
use Illuminate\Http\UploadedFile;

use App\Services\Storage\DocumentStorageService;

class UpdateClearanceRecordAction
{
    public function __construct(
        protected DocumentStorageService $storageService
    ) {
    }


    // Update an existing clearance record.
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
                $baseName = preg_replace('/^(?:CCD\d{4}-\d{4}_(?:reference_file_\d|rf\d|hs_evidence_file_\d|hs\d)_)+/', '', $cleanName);
                $fileName = "{$record->job_no}_{$refField}_{$baseName}";
                /*
                $file->storeAs('public/reference', $fileName);
                $data[$refField] = "reference/{$fileName}";
                */
                if (!empty($record->$refField)) {
                    $this->storageService->delete($record->$refField);
                }
                $data[$refField] = $this->storageService->store($file, 'reference', $fileName);
            } else {
                unset($data[$refField]);
            }
        }

        foreach (['hs_evidence_file_1', 'hs_evidence_file_2', 'hs_evidence_file_3', 'hs_evidence_file_4'] as $hsField) {
            if (!empty($data[$hsField]) && $data[$hsField] instanceof UploadedFile) {
                $file = $data[$hsField];
                $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                $baseName = preg_replace('/^(?:CCD\d{4}-\d{4}_(?:reference_file_\d|rf\d|hs_evidence_file_\d|hs\d)_)+/', '', $cleanName);
                $fileName = "{$record->job_no}_{$hsField}_{$baseName}";
                /*
                $file->storeAs('public/hs_evidence', $fileName);
                $data[$refField] = "hs_evidence/{$fileName}";
                */
                if (!empty($record->$hsField)) {
                    $this->storageService->delete($record->$hsField);
                }
                $data[$hsField] = $this->storageService->store($file, 'hs_evidence', $fileName);
            } else {
                unset($data[$hsField]);
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

        $data['update_date'] = date('Y-m-d');
        $data['edit_user_id'] = auth()->id() ?: 1;

        $record->update($data);

        return $record->fresh();
    }
}

