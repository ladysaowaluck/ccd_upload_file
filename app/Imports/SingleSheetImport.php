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

class SingleSheetImport implements WithMappedCells, ToModel
{
    public ?string $importedJobNo = null;

    public function mapping(): array
    {
        return [
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
            'statistic_code' => 'J37',
            'quantity_unit' => 'K37',
            'privilege_code' => 'G38',
            'tariff_ad_valorem' => 'N37',
        ];
    }

    public function model(array $row)
    {
        // 1. Validate cell inputs
        $validator = Validator::make($row, [
            'customer_name' => ['required', 'string'],
            'master_branch_name' => ['required', 'string'],
            'product_description_en' => ['required', 'string', 'max:300'],
            'product_description_th' => ['required', 'string', 'max:300'],
            'product_code_1' => ['nullable', 'string', 'max:300'],
            'brand_name' => ['nullable', 'string', 'max:50'],
            'materials' => ['nullable', 'string', 'max:300'],
            'usages' => ['nullable', 'string', 'max:300'],
            'country_of_origin' => ['nullable', 'string', 'max:50'],
            'hs_code' => ['required', 'numeric', 'digits:8'],
            'sequence_code' => ['nullable', 'numeric'],
            'statistic_code' => ['required', 'numeric', 'digits:3'],
            'quantity_unit' => ['nullable', 'string', 'max:10'],
            'privilege_code' => ['nullable', 'string', 'max:10'],
            'tariff_ad_valorem' => ['nullable', 'numeric', 'max:100'],
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

        // 4. Duplicate Product Code check within this customer
        if (!empty($row['product_code_1'])) {
            $duplicateCount = CustomerClearanceData::where('master_customer_id', $customer->id)
                ->where(function ($q) use ($row) {
                    $q->where('product_code_1', trim($row['product_code_1']))
                        ->orWhere('product_code_2', trim($row['product_code_1']));
                })
                ->count();

            if ($duplicateCount > 0) {
                throw ValidationException::withMessages([
                    'product_code_1' => "Product code '{$row['product_code_1']}' already exists for customer '{$customer->company_name}'.",
                ]);
            }
        }

        // 5. Concatenate Functions & Notes
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

        // 6. Job Number Handling (auto-generate if blank, else validate format)
        $rawJobNo = trim((string) ($row['job_no'] ?? ''));
        if (empty($rawJobNo)) {
            $action = new GenerateJobSequenceAction();
            $jobNo = $action->execute('CCD');
        } else {
            $prefix = mb_substr($rawJobNo, 0, 3);
            if (!in_array($prefix, ['CCD', 'NTB', 'CST'], true) || mb_strlen($rawJobNo) !== 12) {
                throw ValidationException::withMessages([
                    'job_no' => "Invalid Job No '{$rawJobNo}'. Format must be 12 characters starting with CCD, NTB, or CST.",
                ]);
            }

            if (CustomerClearanceData::where('job_no', $rawJobNo)->exists()) {
                throw ValidationException::withMessages([
                    'job_no' => "Duplicate Job No '{$rawJobNo}' already exists in database.",
                ]);
            }

            $jobNo = $rawJobNo;
        }

        $this->importedJobNo = $jobNo;
        session()->put('single_job_no', $jobNo);

        $today = Carbon::today()->format('Y-m-d');

        return new CustomerClearanceData([
            'job_no' => $jobNo,
            'master_customer_id' => $customer->id,
            'customer_name' => $customer->company_name,
            'master_branch_id' => $branch->id,
            'imp_exp' => 1, // Default Import
            'product_description_en' => $row['product_description_en'],
            'product_description_th' => $row['product_description_th'],
            'product_code_1' => $row['product_code_1'] ?? null,
            'brand_name' => $row['brand_name'] ?? null,
            'materials' => $row['materials'] ?? null,
            'usage' => $row['usages'] ?? null,
            'function' => $functionText ?: null,
            'country_of_origin' => $row['country_of_origin'] ?? null,
            'note' => $noteText ?: null,
            // Lady : Passing raw un-trimmed Excel cell strings into database fields causes SQLSTATE[22001] string truncation errors when user templates contain trailing whitespace or longer codes.
            /*
            'hs_code'                => $row['hs_code'],
            'sequence_code'          => $row['sequence_code'] ?? null,
            'statistic_code'         => $row['statistic_code'],
            'quantity_unit'          => $row['quantity_unit'] ?? null,
            'privilege_code'         => $row['privilege_code'] ?? null,
            */
            // Gemini : Safely trim and truncate strings to database column widths
            'hs_code' => !empty($row['hs_code']) ? mb_substr(trim((string) $row['hs_code']), 0, 8) : '',
            'sequence_code' => !empty($row['sequence_code']) ? mb_substr(trim((string) $row['sequence_code']), 0, 5) : null,
            'statistic_code' => !empty($row['statistic_code']) ? mb_substr(trim((string) $row['statistic_code']), 0, 3) : '000',
            'quantity_unit' => !empty($row['quantity_unit']) ? mb_substr(trim((string) $row['quantity_unit']), 0, 3) : null,
            'privilege_code' => !empty($row['privilege_code']) ? mb_substr(trim((string) $row['privilege_code']), 0, 3) : null,
            'tariff_ad_valorem' => $row['tariff_ad_valorem'] ?? null,
            'create_date' => $today,
            'update_date' => $today,
            'edit_user_id' => auth()->id() ?? 1,
        ]);
    }
}

