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
        // customer_clearance_data.customer_name is varchar(60); keep the first 60 characters.
        if (isset($data['customer_name'])) {
            $data['customer_name'] = mb_substr(trim((string) $data['customer_name']), 0, 60);
        }

        // Sanitize job_no to prevent filesystem or S3 key issues with special characters
        $cleanJobNo = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $record->job_no);

        // Process file uploads
        foreach (['reference_file_1', 'reference_file_2'] as $refField) {
            if (!empty($data[$refField]) && $data[$refField] instanceof UploadedFile) {
                $file = $data[$refField];
                $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                $baseName = preg_replace('/^(?:CCD\d{4}-\d{4}_(?:reference_file_\d|rf\d|hs_evidence_file_\d|hs\d)_)+/', '', $cleanName);
                $fileName = "{$cleanJobNo}_{$refField}_{$baseName}";
                /*
                $file->storeAs('public/reference', $fileName);
                $data[$refField] = "reference/{$fileName}";
                */
                // Wrap old file deletion in defensive try-catch so failure to delete old S3 object never blocks attaching new file
                if (!empty($record->$refField)) {
                    try {
                        $this->storageService->delete($record->$refField);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("UpdateClearanceRecordAction: Could not delete old reference file: " . $e->getMessage());
                    }
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
                $fileName = "{$cleanJobNo}_{$hsField}_{$baseName}";
                /*
                $file->storeAs('public/hs_evidence', $fileName);
                $data[$refField] = "hs_evidence/{$fileName}";
                */
                // Wrap old file deletion in defensive try-catch so failure to delete old S3 object never blocks attaching new file
                if (!empty($record->$hsField)) {
                    try {
                        $this->storageService->delete($record->$hsField);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("UpdateClearanceRecordAction: Could not delete old evidence file: " . $e->getMessage());
                    }
                }
                $data[$hsField] = $this->storageService->store($file, 'hs_evidence', $fileName);
            } else {
                unset($data[$hsField]);
            }
        }

        // Handle removal of existing files if delete_{field} flag is passed during clearance update
        $allAttachmentFields = [
            'reference_file_1',
            'reference_file_2',
            'hs_evidence_file_1',
            'hs_evidence_file_2',
            'hs_evidence_file_3',
            'hs_evidence_file_4',
        ];
        foreach ($allAttachmentFields as $delField) {
            if (!empty($data["delete_{$delField}"])) {
                if (!empty($record->$delField)) {
                    // Guard delete flag execution with try-catch
                    try {
                        $this->storageService->delete($record->$delField);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("UpdateClearanceRecordAction: Could not delete file for {$delField}: " . $e->getMessage());
                    }
                }
                $data[$delField] = null;
            }
        }

        // if (isset($data['quantity_unit_id'])) {
        //     $data['quantity_unit_id'] = substr(trim((string) $data['quantity_unit_id']), 0, 3);
        // }
        // if (isset($data['statistic_code_id'])) {
        //     $data['statistic_code_id'] = substr(trim((string) $data['statistic_code_id']), 0, 3);
        // }
        // Lady: These fields now come from master data selectors and hold IDs. Cutting them to 3 characters
        // would turn ID 1234 into 123 (a different master row). Keep the full ID; empty -> null.
        foreach (['quantity_unit_id', 'statistic_code_id'] as $idField) {
            if (array_key_exists($idField, $data)) {
                $data[$idField] = is_numeric($data[$idField]) ? (int) $data[$idField] : null;
            }
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
        // Normalize product_year to integer or null for defensive persistence.
        if (array_key_exists('product_year', $data)) {
            $data['product_year'] = (isset($data['product_year']) && is_numeric($data['product_year']))
                ? (int) $data['product_year']
                : null;
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
        // if (!empty($data['statistic_code']) && empty($data['statistic_code_id'])) {
        //     $data['statistic_code_id'] = substr(trim((string) $data['statistic_code']), 0, 3);
        // }
        // if (!empty($data['quantity_unit']) && empty($data['quantity_unit_id'])) {
        //     $data['quantity_unit_id'] = substr(trim((string) $data['quantity_unit']), 0, 3);
        // }
        // Resolve the typed code to the master ID (same as create and the Excel import).
        // If the code is not found (legacy value), the stored ID is left unchanged instead of being overwritten.
        if (!empty($data['statistic_code']) && empty($data['statistic_code_id'])) {
            $statisticId = app(\App\Domain\Clearance\Services\TariffCodeResolver::class)->statisticId($data['statistic_code']);
            if ($statisticId !== null) {
                $data['statistic_code_id'] = $statisticId;
            }
        }
        if (!empty($data['quantity_unit']) && empty($data['quantity_unit_id'])) {
            $unitId = app(\App\Domain\Clearance\Services\TariffCodeResolver::class)->unitId($data['quantity_unit']);
            if ($unitId !== null) {
                $data['quantity_unit_id'] = $unitId;
            }
        }
        // if (!empty($data['tariff_specific_rate_unit']) && empty($data['tariff_specific_rate_unit_id'])) {
        //     $data['tariff_specific_rate_unit_id'] = substr(trim((string) $data['tariff_specific_rate_unit']), 0, 10);
        // }
        // Unknown code -> keep the stored value. Empty input -> null (field is optional).
        if (empty($data['tariff_specific_rate_unit_id']) && !empty($data['tariff_specific_rate_unit'])) {
            $data['tariff_specific_rate_unit_id'] = $data['tariff_specific_rate_unit'];
        }
        if (!empty($data['tariff_specific_rate_unit_id']) && !is_numeric($data['tariff_specific_rate_unit_id'])) {
            $unitId = app(\App\Domain\Clearance\Services\TariffCodeResolver::class)->unitId($data['tariff_specific_rate_unit_id']);
            if ($unitId !== null) {
                $data['tariff_specific_rate_unit_id'] = $unitId;
            } else {
                unset($data['tariff_specific_rate_unit_id']);
            }
        }
        unset($data['tariff_specific_rate_unit']);

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

