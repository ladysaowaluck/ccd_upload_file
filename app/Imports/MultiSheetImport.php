<?php

namespace App\Imports;

// Gemini : GAP-03 — Excel Multi Record Import implementing batch tabular parsing and validation
use App\Domain\Clearance\Actions\GenerateJobSequenceAction;
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterBranch;
use App\Models\Business\MasterCustomer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MultiSheetImport implements ToCollection, WithHeadingRow
{
    /** @var array<string> */
    public array $importedJobNos = [];

    public function collection(Collection $rows)
    {
        $today = Carbon::today()->format('Y-m-d');
        $sequenceAction = new GenerateJobSequenceAction();

        foreach ($rows as $index => $row) {
            // Skip empty rows
            if (empty($row['customer']) && empty($row['english_description']) && empty($row['hs_code_8_digits'])) {
                continue;
            }

            $lineNo = $index + 2; // header is row 1

            $validator = Validator::make($row->toArray(), [
                'customer'                    => ['required', 'string'],
                'snc_branch'                  => ['required', 'string'],
                'english_description'         => ['required', 'string', 'max:300'],
                'thai_description'            => ['required', 'string', 'max:300'],
                'product_code_1'              => ['nullable', 'string', 'max:300'],
                'brand_name'                  => ['nullable', 'string', 'max:50'],
                'materials'                   => ['nullable', 'string', 'max:300'],
                'usage'                       => ['nullable', 'string', 'max:300'],
                'function'                    => ['nullable', 'string', 'max:300'],
                'country_of_origin'           => ['nullable', 'string', 'max:50'],
                'hs_code_8_digits'            => ['required', 'numeric', 'digits:8'],
                'sequence_code_5_digits'      => ['nullable', 'numeric'],
                'statistic_code_3_digits'     => ['required', 'numeric', 'digits:3'],
                'quantity_unit_3_digits'      => ['nullable', 'string', 'max:10'],
                'privilege_code_3_digits'     => ['nullable', 'string', 'max:10'],
                'tariff_rate_ad_valorem_rate' => ['nullable', 'numeric', 'max:100'],
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
            $rawJobNo = trim((string)($row['job_no'] ?? ''));
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
                $rawImpExp = strtolower(trim((string)$row['import_export']));
                if (in_array($rawImpExp, ['exp', 'export', '1'], true)) {
                    $impExp = 1;
                } elseif (in_array($rawImpExp, ['imp', 'import', '0'], true)) {
                    $impExp = 0;
                }
            }

            $record = CustomerClearanceData::create([
                'job_no'                 => $jobNo,
                'master_customer_id'     => $customer->id,
                'customer_name'          => $customer->company_name,
                'master_branch_id'       => $branch->id,
                'imp_exp'                => $impExp,
                'product_description_en' => $row['english_description'],
                'product_description_th' => $row['thai_description'],
                'product_code_1'         => $row['product_code_1'] ?? null,
                'brand_name'             => $row['brand_name'] ?? null,
                'materials'              => $row['materials'] ?? null,
                'usage'                  => $row['usage'] ?? null,
                'function'               => $row['function'] ?? null,
                'country_of_origin'      => $row['country_of_origin'] ?? null,
                'note'                   => $row['note'] ?? null,
                // Lady : Un-trimmed batch Excel cells risk exceeding column limits and triggering SQLSTATE[22001] errors.
                /*
                'hs_code'                => $row['hs_code_8_digits'],
                'sequence_code'          => $row['sequence_code_5_digits'] ?? null,
                'statistic_code'         => $row['statistic_code_3_digits'],
                'quantity_unit'          => $row['quantity_unit_3_digits'] ?? null,
                'privilege_code'         => $row['privilege_code_3_digits'] ?? null,
                */
                // Gemini : Safely trim and truncate strings to database column widths
                'hs_code'                => !empty($row['hs_code_8_digits']) ? mb_substr(trim((string)$row['hs_code_8_digits']), 0, 8) : '',
                'sequence_code'          => !empty($row['sequence_code_5_digits']) ? mb_substr(trim((string)$row['sequence_code_5_digits']), 0, 5) : null,
                'statistic_code'         => !empty($row['statistic_code_3_digits']) ? mb_substr(trim((string)$row['statistic_code_3_digits']), 0, 3) : '000',
                'quantity_unit'          => !empty($row['quantity_unit_3_digits']) ? mb_substr(trim((string)$row['quantity_unit_3_digits']), 0, 3) : null,
                'privilege_code'         => !empty($row['privilege_code_3_digits']) ? mb_substr(trim((string)$row['privilege_code_3_digits']), 0, 3) : null,
                'tariff_ad_valorem'      => $row['tariff_rate_ad_valorem_rate'] ?? null,
                'create_date'            => $today,
                'update_date'            => $today,
                'edit_user_id'           => auth()->id() ?? 1,
            ]);

            $this->importedJobNos[] = $jobNo;
        }

        session()->put('multi_job_no', $this->importedJobNos);
    }
}

