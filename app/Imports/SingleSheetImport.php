<?php

namespace App\Imports;

use App\Domain\Clearance\Actions\GenerateJobSequenceAction;
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterBranch;
use App\Models\Business\MasterCustomer;
use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithMappedCells;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

// Implement WithMultipleSheets so that single-sheet requisition import targets only the primary data sheet (index 0), ignoring master/lookup sheets
class SingleSheetImport implements WithMappedCells, ToModel, WithMultipleSheets
{
    public ?string $importedJobNo = null;

    // Only process sheet at index 0 for single requisition import.

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    public function mapping(): array
    {
        return [
            // Standard vertical form layout mapping (single printable requisition form)
            'job_no' => 'N1',
            'customer_name' => 'D4',
            'master_branch_name' => 'O2',
            'product_description_en' => 'G10',
            'product_description_th' => 'G12',
            'product_code_1' => 'G8',
            'brand_name' => 'F25',
            'materials' => 'G14',
            'usages' => 'G16',
            'function1' => 'G18',
            'function2' => 'A20',
            'function3' => 'A21',
            'function4' => 'A22',
            'function5' => 'A23',
            'country_of_origin' => 'F24',
            'note1' => 'L5',
            'note2' => 'H26',
            'note3' => 'J28',
            'note4' => 'J29',
            'note5' => 'J30',
            'note6' => 'J31',
            'note7' => 'J32',
            'hs_code' => 'G37',
            'sequence_code' => 'H37',
            'statistic_code_id' => 'J37',
            'quantity_unit' => 'K37',
            'privilege_code' => 'G38',
            'tariff_ad_valorem' => 'N37',

            // Fallback cell coordinates for tabular format sheets (e.g. test_single_sheet.xlsx)
            // Row 3 data (standard tabular export/batch template with header row 1 and subheader row 2)
            'tab_r3_job_no' => 'B3',
            'tab_r3_customer_name' => 'C3',
            'tab_r3_master_branch_name' => 'D3',
            'tab_r3_imp_exp' => 'E3',
            'tab_r3_product_description_en' => 'F3',
            'tab_r3_product_description_th' => 'G3',
            'tab_r3_product_code_1' => 'H3',
            'tab_r3_materials' => 'M3',
            'tab_r3_usages' => 'N3',
            'tab_r3_functions' => 'O3',
            'tab_r3_country_of_origin' => 'Q3',
            'tab_r3_note' => 'R3',
            'tab_r3_hs_code' => 'S3',
            'tab_r3_sequence_code' => 'T3',
            'tab_r3_statistic_code_id' => 'U3',
            'tab_r3_quantity_unit' => 'V3',
            'tab_r3_privilege_code' => 'W3',
            'tab_r3_tariff_ad_valorem' => 'X3',

            // Row 2 data (tabular template with single header row on row 1)
            'tab_r2_job_no' => 'B2',
            'tab_r2_customer_name' => 'C2',
            'tab_r2_master_branch_name' => 'D2',
            'tab_r2_imp_exp' => 'E2',
            'tab_r2_product_description_en' => 'F2',
            'tab_r2_product_description_th' => 'G2',
            'tab_r2_product_code_1' => 'H2',
            'tab_r2_materials' => 'M2',
            'tab_r2_usages' => 'N2',
            'tab_r2_functions' => 'O2',
            'tab_r2_country_of_origin' => 'Q2',
            'tab_r2_note' => 'R2',
            'tab_r2_hs_code' => 'S2',
            'tab_r2_sequence_code' => 'T2',
            'tab_r2_statistic_code_id' => 'U2',
            'tab_r2_quantity_unit' => 'V2',
            'tab_r2_privilege_code' => 'W2',
            'tab_r2_tariff_ad_valorem' => 'X2',
        ];
    }

    public function model(array $row)
    {
        // Detect if uploaded file uses tabular row layout (e.g. test_single_sheet.xlsx) or vertical form layout (D4, G10, O2)
        $row = $this->normalizeRowLayout($row);

        $row['hs_code'] = $this->cleanNumeric($row['hs_code'] ?? null);
        $row['statistic_code_id'] = $this->defaultStatistic($row['statistic_code_id'] ?? null);
        $row['sequence_code'] = $this->cleanNumeric($row['sequence_code'] ?? null);
        $row['privilege_code'] = $this->nullIfEmpty($row['privilege_code'] ?? null);
        $row['tariff_ad_valorem'] = $this->nullIfEmpty($row['tariff_ad_valorem'] ?? null);

        $validator = Validator::make($row, [
            'customer_name' => ['required', 'string'],
            'master_branch_name' => ['required', 'string'],
            'product_description_en' => ['required', 'string', 'max:300'],
            'product_description_th' => ['nullable', 'string', 'max:300'],
            'product_code_1' => ['nullable', 'string', 'max:300'],
            'brand_name' => ['nullable', 'string', 'max:50'],
            'materials' => ['nullable', 'string', 'max:300'],
            'usages' => ['nullable', 'string', 'max:300'],
            'country_of_origin' => ['nullable', 'string', 'max:50'],
            'hs_code' => ['nullable', 'string', 'max:10'],
            'sequence_code' => ['nullable', 'string'],
            'statistic_code_id' => ['nullable', 'string', 'max:5'],
            'quantity_unit' => ['nullable', 'string', 'max:10'],
            'privilege_code' => ['nullable', 'string', 'max:20'],
            'tariff_ad_valorem' => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 2. Resolve Master Customer (active only)
        $customer = MasterCustomer::where('company_name', trim($row['customer_name']))
            ->where('is_enable', 0)
            ->first();

        if (!$customer) {
            throw ValidationException::withMessages([
                'customer_name' => "Customer '{$row['customer_name']}' was not found or is currently disabled in master data.",
            ]);
        }

        // 3. Resolve Master Branch
        $branch = MasterBranch::where('branch_code', trim($row['master_branch_name']))
            ->orWhere('branch_name', trim($row['master_branch_name']))
            ->first();

        if (!$branch) {
            throw ValidationException::withMessages([
                'master_branch_name' => "Branch '{$row['master_branch_name']}' was not found in branch master.",
            ]);
        }

        // 4. Resolve Job No & Existing Record for upsert
        $rawJobNo = trim((string) ($row['job_no'] ?? ''));
        $existingRecord = null;

        if (!empty($rawJobNo)) {
            $prefix = mb_substr($rawJobNo, 0, 3);
            /*
            if (!in_array($prefix, ['CCD', 'NTB', 'CST'], true) || mb_strlen($rawJobNo) !== 12) {
                throw ValidationException::withMessages([
                    'job_no' => "Invalid Job No '{$rawJobNo}'. Format must be 12 characters starting with CCD, NTB, or CST.",
                ]);
            }
            */
            // Allow job numbers between 12 and 20 characters starting with CCD, NTB, or CST.
            if (!in_array($prefix, ['CCD', 'NTB', 'CST'], true) || mb_strlen($rawJobNo) < 12 || mb_strlen($rawJobNo) > 20) {
                throw ValidationException::withMessages([
                    'job_no' => "Invalid Job No '{$rawJobNo}'. Format must start with CCD, NTB, or CST (12-20 characters).",
                ]);
            }
            $existingRecord = CustomerClearanceData::where('job_no', $rawJobNo)->first();
            $jobNo = $rawJobNo;
        } else {
            $action = new GenerateJobSequenceAction();
            $jobNo = $action->execute('CCD');
        }

        // 5. Duplicate Product Code check within this customer (excluding current record if updating)
        if (!empty($row['product_code_1'])) {
            $duplicateQuery = CustomerClearanceData::where('master_customer_id', $customer->id)
                ->where(function ($q) use ($row) {
                    $q->where('product_code_1', trim($row['product_code_1']))
                        ->orWhere('product_code_2', trim($row['product_code_1']));
                });

            // Exclude existing record from duplicate product code count when updating an existing job record.
            if ($existingRecord) {
                $duplicateQuery->where('id', '!=', $existingRecord->id);
            }

            if ($duplicateQuery->count() > 0) {
                throw ValidationException::withMessages([
                    'product_code_1' => "Product code '{$row['product_code_1']}' already exists for customer '{$customer->company_name}'.",
                ]);
            }
        }

        // 6. Concatenate Functions & Notes
        $functionText = trim(implode(' ', array_filter([
            $row['function1'] ?? null,
            $row['function2'] ?? null,
            $row['function3'] ?? null,
            $row['function4'] ?? null,
            $row['function5'] ?? null,
        ])));

        if (mb_strlen($functionText) > 300) {
            throw ValidationException::withMessages([
                'functions' => 'Combined function description exceeds 300 characters.',
            ]);
        }

        $noteText = trim(implode("\n", array_filter([
            $row['note1'] ?? null,
            $row['note2'] ?? null,
            $row['note3'] ?? null,
            $row['note4'] ?? null,
            $row['note5'] ?? null,
            $row['note6'] ?? null,
            $row['note7'] ?? null,
        ])));

        if (mb_strlen($noteText) > 500) {
            throw ValidationException::withMessages([
                'notes' => 'Combined notes exceed 500 characters.',
            ]);
        }

        $this->importedJobNo = $jobNo;
        session()->put('single_job_no', $jobNo);

        $today = Carbon::today()->format('Y-m-d');

        $statId = null;
        if (!empty($row['statistic_code_id'])) {
            $statCode = mb_substr(trim((string) $row['statistic_code_id']), 0, 3);
            $statId = MasterStatistic::where('statistics', $statCode)->value('id');
        }

        $unitId = null;
        if (!empty($row['quantity_unit'])) {
            $unitCode = mb_substr(trim((string) $row['quantity_unit']), 0, 3);
            $unitId = MasterUnit::where('unit', $unitCode)->value('id');
        }

        $impExp = 0; // Default Import (0: Import, 1: Export)
        if (isset($row['imp_exp'])) {
            $rawImpExp = strtolower(trim((string) $row['imp_exp']));
            if (in_array($rawImpExp, ['exp', 'export', '1'], true)) {
                $impExp = 1;
            } elseif (in_array($rawImpExp, ['imp', 'import', '0'], true)) {
                $impExp = 0;
            }
        }

        $attributes = [
            'master_customer_id' => $customer->id,
            'customer_name' => $customer->company_name,
            'master_branch_id' => $branch->id,
            'imp_exp' => $impExp,
            'product_description_en' => $row['product_description_en'],
            'product_description_th' => $row['product_description_th'],
            'product_code_1' => $row['product_code_1'] ?? null,
            'brand_name' => $row['brand_name'] ?? null,
            'materials' => $row['materials'] ?? null,
            'usages' => $row['usages'] ?? null,
            'functions' => $functionText ?: null,
            'country_of_origin' => $row['country_of_origin'] ?? null,
            'note' => $noteText ?: null,
            'hs_code' => !empty($row['hs_code']) ? mb_substr(trim((string) $row['hs_code']), 0, 8) : '',
            'sequence_code' => !empty($row['sequence_code']) ? mb_substr(trim((string) $row['sequence_code']), 0, 5) : null,
            'statistic_code_id' => $statId,
            'quantity_unit_id' => $unitId,
            'privilege_code' => !empty($row['privilege_code']) ? mb_substr(trim((string) $row['privilege_code']), 0, 3) : null,
            'tariff_ad_valorem' => $row['tariff_ad_valorem'] ?? null,
            'update_date' => $today,
            'edit_user_id' => auth()->id() ?? 1,
        ];

        // Upsert pattern: update if job already exists, create new record if it is a new job sequence.
        if ($existingRecord) {
            $existingRecord->update($attributes);
            return $existingRecord;
        }

        $attributes['job_no'] = $jobNo;
        $attributes['create_date'] = $today;

        return new CustomerClearanceData($attributes);
    }

    // Normalize row data by detecting whether file uses vertical form layout (D4/G10) or tabular layout (Row 3 / Row 2)
    private function normalizeRowLayout(array $row): array
    {
        // If customer_name (D4) and product_description_en (G10) are present, assume form layout
        if (!empty(trim((string) ($row['customer_name'] ?? ''))) && !empty(trim((string) ($row['product_description_en'] ?? '')))) {
            return $row;
        }

        // Check Row 3 tabular data (e.g. test_single_sheet.xlsx with 2 header rows)
        if (!empty(trim((string) ($row['tab_r3_customer_name'] ?? '')))) {
            $row['customer_name'] = trim((string) $row['tab_r3_customer_name']);
            $row['master_branch_name'] = trim((string) ($row['tab_r3_master_branch_name'] ?? ''));
            $row['product_description_en'] = trim((string) ($row['tab_r3_product_description_en'] ?? ''));
            $row['product_description_th'] = $this->nullIfEmpty($row['tab_r3_product_description_th'] ?? null);
            $row['product_code_1'] = $this->nullIfEmpty($row['tab_r3_product_code_1'] ?? null);
            $row['materials'] = $this->nullIfEmpty($row['tab_r3_materials'] ?? null);
            $row['usages'] = $this->nullIfEmpty($row['tab_r3_usages'] ?? null);
            $row['function1'] = $this->nullIfEmpty($row['tab_r3_functions'] ?? null);
            $row['country_of_origin'] = $this->nullIfEmpty($row['tab_r3_country_of_origin'] ?? null);
            $row['note1'] = $this->nullIfEmpty($row['tab_r3_note'] ?? null);
            $row['hs_code'] = $this->nullIfEmpty($row['tab_r3_hs_code'] ?? null);
            $row['sequence_code'] = $this->nullIfEmpty($row['tab_r3_sequence_code'] ?? null);
            $row['statistic_code_id'] = $this->nullIfEmpty($row['tab_r3_statistic_code_id'] ?? null);
            $row['quantity_unit'] = $this->nullIfEmpty($row['tab_r3_quantity_unit'] ?? null);
            $row['privilege_code'] = $this->nullIfEmpty($row['tab_r3_privilege_code'] ?? null);
            $row['tariff_ad_valorem'] = $this->nullIfEmpty($row['tab_r3_tariff_ad_valorem'] ?? null);
            $row['job_no'] = $this->nullIfEmpty($row['tab_r3_job_no'] ?? null);
            if (!empty($row['tab_r3_imp_exp'])) {
                $row['imp_exp'] = $row['tab_r3_imp_exp'];
            }
            return $row;
        }

        // Check Row 2 tabular data (single header row)
        $r2Cust = trim((string) ($row['tab_r2_customer_name'] ?? ''));
        if (!empty($r2Cust) && strtolower($r2Cust) !== 'customer' && $r2Cust !== 'ลูกค้า') {
            $row['customer_name'] = $r2Cust;
            $row['master_branch_name'] = trim((string) ($row['tab_r2_master_branch_name'] ?? ''));
            $row['product_description_en'] = trim((string) ($row['tab_r2_product_description_en'] ?? ''));
            $row['product_description_th'] = $this->nullIfEmpty($row['tab_r2_product_description_th'] ?? null);
            $row['product_code_1'] = $this->nullIfEmpty($row['tab_r2_product_code_1'] ?? null);
            $row['materials'] = $this->nullIfEmpty($row['tab_r2_materials'] ?? null);
            $row['usages'] = $this->nullIfEmpty($row['tab_r2_usages'] ?? null);
            $row['function1'] = $this->nullIfEmpty($row['tab_r2_functions'] ?? null);
            $row['country_of_origin'] = $this->nullIfEmpty($row['tab_r2_country_of_origin'] ?? null);
            $row['note1'] = $this->nullIfEmpty($row['tab_r2_note'] ?? null);
            $row['hs_code'] = $this->nullIfEmpty($row['tab_r2_hs_code'] ?? null);
            $row['sequence_code'] = $this->nullIfEmpty($row['tab_r2_sequence_code'] ?? null);
            $row['statistic_code_id'] = $this->nullIfEmpty($row['tab_r2_statistic_code_id'] ?? null);
            $row['quantity_unit'] = $this->nullIfEmpty($row['tab_r2_quantity_unit'] ?? null);
            $row['privilege_code'] = $this->nullIfEmpty($row['tab_r2_privilege_code'] ?? null);
            $row['tariff_ad_valorem'] = $this->nullIfEmpty($row['tab_r2_tariff_ad_valorem'] ?? null);
            $row['job_no'] = $this->nullIfEmpty($row['tab_r2_job_no'] ?? null);
            if (!empty($row['tab_r2_imp_exp'])) {
                $row['imp_exp'] = $row['tab_r2_imp_exp'];
            }
            return $row;
        }

        return $row;
    }

    private function cleanNumeric(mixed $value): ?string
    {
        $cleaned = preg_replace('/[^0-9]/', '', (string) $value);
        return $cleaned !== '' ? $cleaned : null;
    }

    private function defaultStatistic(mixed $value): string
    {
        $v = trim((string) $value);
        return $v !== '' ? mb_substr($v, 0, 3) : '000';
    }

    private function nullIfEmpty(mixed $value): mixed
    {
        $v = trim((string) $value);
        return $v !== '' ? $v : null;
    }
}
