<?php

namespace App\Imports;

use App\Domain\Clearance\Actions\GenerateJobSequenceAction;
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterBranch;
use App\Models\Business\MasterCustomer;
use App\Models\Business\MasterLicense;
use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MultiSheetImport implements ToCollection, WithHeadingRow
{
    public array $importedJobNos = [];

    public function collection(Collection $rows)
    {
        $today = Carbon::today()->format('Y-m-d');
        $sequenceAction = new GenerateJobSequenceAction();

        foreach ($rows as $index => $row) {
            if ($this->isSubHeaderRow($row)) {
                continue;
            }

            // Skip fully empty rows
            if (empty($row['customer']) && empty($row['english_description']) && empty($row['hs_code_8_digits'])) {
                continue;
            }

            $lineNo = $index + 2; // header is row 1

            $row = $this->sanitizeRow($row);
            $validator = Validator::make($row->toArray(), [
                'customer' => ['required', 'string'],
                'snc_branch' => ['required', 'string'],
                'english_description' => ['required', 'string', 'max:300'],
                'thai_description' => ['nullable', 'string', 'max:300'],
                'product_code_1' => ['nullable', 'string', 'max:300'],
                'brand_name' => ['nullable', 'string', 'max:50'],
                'materials' => ['nullable', 'string', 'max:300'],
                'usage' => ['nullable', 'string', 'max:300'],
                'function' => ['nullable', 'string', 'max:300'],
                'country_of_origin' => ['nullable', 'string', 'max:50'],
                'hs_code_8_digits' => ['nullable', 'string', 'max:10'],
                'sequence_code_5_digits' => ['nullable', 'string'],
                'statistic_code_3_digits' => ['nullable', 'string', 'max:5'],
                'quantity_unit_3_digits' => ['nullable', 'string', 'max:10'],
                'privilege_code_3_digits' => ['nullable', 'string', 'max:20'],
                'tariff_rate_ad_valorem_rate' => ['nullable', 'numeric'],
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'multi_record' => "Row {$lineNo} validation error: " . implode(', ', $validator->errors()->all()),
                ]);
            }

            // Customer check
            $customer = MasterCustomer::where('company_name', trim($row['customer']))
                ->where('is_enable', 0)
                ->first();

            if (!$customer) {
                throw ValidationException::withMessages([
                    'multi_record' => "Row {$lineNo}: Customer '{$row['customer']}' not found or is disabled in master data.",
                ]);
            }

            // Branch check
            $branch = MasterBranch::where('branch_code', trim($row['snc_branch']))
                ->orWhere('branch_name', trim($row['snc_branch']))
                ->first();

            if (!$branch) {
                throw ValidationException::withMessages([
                    'multi_record' => "Row {$lineNo}: Branch '{$row['snc_branch']}' not found in master data.",
                ]);
            }

            // Job No
            $rawJobNo = trim((string) ($row['job_no'] ?? ''));
            if (empty($rawJobNo)) {
                $jobNo = $sequenceAction->execute('CCD');
            } else {
                if (CustomerClearanceData::where('job_no', $rawJobNo)->exists()) {
                    throw ValidationException::withMessages([
                        'multi_record' => "Row {$lineNo}: Duplicate Job No '{$rawJobNo}' already exists.",
                    ]);
                }
                $jobNo = $rawJobNo;
            }

            $impExp = 1; // default Import
            if (isset($row['import_export'])) {
                $rawImpExp = strtolower(trim((string) $row['import_export']));
                if (in_array($rawImpExp, ['exp', 'export', '1'], true)) {
                    $impExp = 1;
                } elseif (in_array($rawImpExp, ['imp', 'import', '0'], true)) {
                    $impExp = 0;
                }
            }

            $statId = null;
            if (!empty($row['statistic_code_3_digits'])) {
                $statCode = mb_substr(trim((string) $row['statistic_code_3_digits']), 0, 3);
                $statId = MasterStatistic::where('statistics', $statCode)->value('id');
            }

            $unitId = null;
            if (!empty($row['quantity_unit_3_digits'])) {
                $unitCode = mb_substr(trim((string) $row['quantity_unit_3_digits']), 0, 3);
                $unitId = MasterUnit::where('unit', $unitCode)->value('id');
            }

            $tariffUnitId = null;
            if (!empty($row['tariff_rate_specific_rate_unit'])) {
                $tariffUnitCode = mb_substr(trim((string) $row['tariff_rate_specific_rate_unit']), 0, 3);
                $tariffUnitId = MasterUnit::where('unit', $tariffUnitCode)->value('id');
            }

            $lic1Id = null;
            if (!empty($row['license1_name_id'])) {
                $lic1Id = is_numeric($row['license1_name_id'])
                    ? (int) $row['license1_name_id']
                    : MasterLicense::where('license_en', trim((string) $row['license1_name_id']))
                        ->orWhere('license_th', trim((string) $row['license1_name_id']))
                        ->value('id');
            }
            $lic2Id = null;
            if (!empty($row['license2_name_id'])) {
                $lic2Id = is_numeric($row['license2_name_id'])
                    ? (int) $row['license2_name_id']
                    : MasterLicense::where('license_en', trim((string) $row['license2_name_id']))
                        ->orWhere('license_th', trim((string) $row['license2_name_id']))
                        ->value('id');
            }
            $lic3Id = null;
            if (!empty($row['license3_name_id'])) {
                $lic3Id = is_numeric($row['license3_name_id'])
                    ? (int) $row['license3_name_id']
                    : MasterLicense::where('license_en', trim((string) $row['license3_name_id']))
                        ->orWhere('license_th', trim((string) $row['license3_name_id']))
                        ->value('id');
            }
            $lic4Id = null;
            if (!empty($row['license4_name_id'])) {
                $lic4Id = is_numeric($row['license4_name_id'])
                    ? (int) $row['license4_name_id']
                    : MasterLicense::where('license_en', trim((string) $row['license4_name_id']))
                        ->orWhere('license_th', trim((string) $row['license4_name_id']))
                        ->value('id');
            }

            CustomerClearanceData::create([
                'job_no' => $jobNo,
                'master_customer_id' => $customer->id,
                'customer_name' => $customer->company_name,
                'master_branch_id' => $branch->id,
                'imp_exp' => $impExp,
                'product_description_en' => $row['english_description'],
                'product_description_th' => $row['thai_description'],
                'product_code_1' => $row['product_code_1'] ?? null,
                'brand_name' => $row['brand_name'] ?? null,
                'materials' => $row['materials'] ?? null,
                'usages' => $row['usage'] ?? null,
                'functions' => $row['function'] ?? null,
                'country_of_origin' => $row['country_of_origin'] ?? null,
                'note' => $row['note'] ?? null,
                'hs_code' => $row['hs_code_8_digits'],
                'sequence_code' => $row['sequence_code_5_digits'] ?? null,
                'statistic_code_id' => $statId,
                'quantity_unit_id' => $unitId,
                // 'quantity_unit'                => !empty($row['quantity_unit_3_digits']) ? mb_substr(trim((string) $row['quantity_unit_3_digits']), 0, 3) : null,
                'privilege_code' => $row['privilege_code_3_digits'] ?? null,
                'tariff_ad_valorem' => $row['tariff_rate_ad_valorem_rate'] ?? null,
                'tariff_specific_rate_unit_id' => $tariffUnitId,
                'tariff_specific_rate_baht' => $row['tariff_rate_specific_rate_bath'] ?? null,
                'anti_dumping_ad_valorem' => $row['anti_dumping_ad_valorem'] ?? null,
                'excise_tax_ad_valorem' => $row['excise_tax_ad_valorem'] ?? null,
                'excise_tax_rate_baht' => $row['excise_tax_rate_baht'] ?? null,
                'other_tax1_name_of_tax' => $row['other_tax1_name_of_tax'] ?? null,
                'other_tax1_ad_valorem' => $row['other_tax1_ad_valorem'] ?? null,
                'other_tax1_rate_baht' => $row['other_tax1_rate_baht'] ?? null,
                'other_tax2_name_of_tax' => $row['other_tax2_name_of_tax'] ?? null,
                'other_tax2_ad_valorem' => $row['other_tax2_ad_valorem'] ?? null,
                'other_tax2_rate_baht' => $row['other_tax2_rate_baht'] ?? null,
                'license1_name_id' => $lic1Id,
                'license1_note' => $row['license1_note'] ?? null,
                'license2_name_id' => $lic2Id,
                'license2_note' => $row['license2_note'] ?? null,
                'license3_name_id' => $lic3Id,
                'license3_note' => $row['license3_note'] ?? null,
                'license4_name_id' => $lic4Id,
                'license4_note' => $row['license4_note'] ?? null,
                'create_date' => $today,
                'update_date' => $today,
                'edit_user_id' => auth()->id() ?? 1,
            ]);

            $this->importedJobNos[] = $jobNo;
        }

        session()->put('multi_job_no', $this->importedJobNos);
    }

    private const FIXED_COLUMN_INDEXES = [
        'no' => [0, 'A'],
        'job_no' => [1, 'B'],
        'customer' => [2, 'C'],
        'snc_branch' => [3, 'D'],
        'import_export' => [4, 'E'],
        'english_description' => [5, 'F'],
        'thai_description' => [6, 'G'],
        'product_code_1' => [7, 'H'],
        'product_code_2' => [8, 'I'],
        'product_year' => [9, 'J'],
        'brand_name' => [10, 'K'],
        'model' => [11, 'L'],
        'materials' => [12, 'M'],
        'usage' => [13, 'N'],
        'function' => [14, 'O'],
        'position' => [15, 'P'],
        'country_of_origin' => [16, 'Q'],
        'note' => [17, 'R'],
        'hs_code_8_digits' => [18, 'S'],
        'sequence_code_5_digits' => [19, 'T'],
        'statistic_code_3_digits' => [20, 'U'],
        'quantity_unit_3_digits' => [21, 'V'],
        'privilege_code_3_digits' => [22, 'W'],
        'tariff_rate_ad_valorem_rate' => [23, 'X'],
        'tariff_rate_specific_rate_unit' => [24, 'Y'],
        'tariff_rate_specific_rate_bath' => [25, 'Z'],
        'anti_dumping_ad_valorem' => [26, 'AA'],
        'excise_tax_ad_valorem' => [27, 'AB'],
        'excise_tax_rate_unit' => [28, 'AC'],
        'excise_tax_rate_baht' => [29, 'AD'],
        'other_tax1_name_of_tax' => [30, 'AE'],
        'other_tax1_ad_valorem' => [31, 'AF'],
        'other_tax1_rate_unit' => [32, 'AG'],
        'other_tax1_rate_baht' => [33, 'AH'],
        'other_tax2_name_of_tax' => [34, 'AI'],
        'other_tax2_ad_valorem' => [35, 'AJ'],
        'other_tax2_rate_unit' => [36, 'AK'],
        'other_tax2_rate_baht' => [37, 'AL'],
        'license1_name_id' => [38, 'AM'],
        'license1_note' => [39, 'AN'],
        'license2_name_id' => [40, 'AO'],
        'license2_note' => [41, 'AP'],
        'license3_name_id' => [42, 'AQ'],
        'license3_note' => [43, 'AR'],
        'license4_name_id' => [44, 'AS'],
        'license4_note' => [45, 'AT'],
    ];

    // Retrieve value from row using either heading key, column index, or column letter.
    private function getField(array $data, string $key): mixed
    {
        if (isset($data[$key]) && $data[$key] !== '') {
            return $data[$key];
        }

        if (isset(self::FIXED_COLUMN_INDEXES[$key])) {
            foreach (self::FIXED_COLUMN_INDEXES[$key] as $idx) {
                if (isset($data[$idx]) && $data[$idx] !== '') {
                    return $data[$idx];
                }
            }
        }

        return null;
    }

    private function isSubHeaderRow($row): bool
    {
        $raw = is_array($row) ? $row : $row->toArray();
        $hsCode = (string) ($this->getField($raw, 'hs_code_8_digits') ?? '');
        $customer = (string) ($this->getField($raw, 'customer') ?? '');

        // A sub-header row has text labels in numeric-only columns
        return (!is_numeric($hsCode) && !empty($hsCode))
            || strtolower(trim($customer)) === 'customer';
    }
    private function sanitizeRow($row): \Illuminate\Support\Collection
    {
        $raw = is_array($row) ? $row : $row->toArray();
        $data = [];

        // Normalize every field using fixed cell column map
        foreach (self::FIXED_COLUMN_INDEXES as $key => $indexes) {
            $data[$key] = $this->getField($raw, $key);
        }

        // Also preserve any existing raw keys passed
        foreach ($raw as $k => $v) {
            if (!isset($data[$k]) && $v !== '') {
                $data[$k] = $v;
            }
        }

        // hs_code: strip non-digit characters
        if (isset($data['hs_code_8_digits'])) {
            $cleaned = preg_replace('/[^0-9]/', '', (string) $data['hs_code_8_digits']);
            $data['hs_code_8_digits'] = $cleaned !== '' ? $cleaned : null;
        }

        // sequence_code: null if empty or non-numeric
        if (isset($data['sequence_code_5_digits'])) {
            $seq = trim((string) $data['sequence_code_5_digits']);
            $data['sequence_code_5_digits'] = ($seq !== '' && is_numeric($seq)) ? $seq : null;
        }

        // statistic_code: default to "000" when empty
        // if (empty($data['statistic_code_3_digits'])) {
        //     $data['statistic_code_3_digits'] = '000';
        // } else {
        //     $data['statistic_code_3_digits'] = mb_substr(trim((string) $data['statistic_code_3_digits']), 0, 3);
        // }

        // privilege_code: null if empty, else truncate to 10 chars
        if (isset($data['privilege_code_3_digits'])) {
            $priv = trim((string) $data['privilege_code_3_digits']);
            $data['privilege_code_3_digits'] = $priv !== '' ? mb_substr($priv, 0, 10) : null;
        }

        // tariff_ad_valorem: null if empty string
        if (isset($data['tariff_rate_ad_valorem_rate']) && trim((string) $data['tariff_rate_ad_valorem_rate']) === '') {
            $data['tariff_rate_ad_valorem_rate'] = null;
        }

        return collect($data);
    }
}
