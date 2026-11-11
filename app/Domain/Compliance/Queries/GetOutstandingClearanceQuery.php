<?php

namespace App\Domain\Compliance\Queries;

// CustomerClearanceOutStandingController with full multi-column search, file existence checkboxes, and free keyword query
use App\Models\Business\CustomerClearanceData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetOutstandingClearanceQuery
{
    /**
     * Retrieve clearance records from customer_clearance_data table
     * matching CustomerClearanceOutStandingController features.
     */
    public function execute(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = CustomerClearanceData::with([
            'customer',
            'branch',
            'statistic',
            'quantityUnit',
            'license1',
            'license2',
            'license3',
            'license4',
        ]);

        // 1. Evidence compliance status filter (default: 'all' to show all customer_clearance_data records)
        // Default status to 'all' so the /outstanding page displays all records from customer_clearance_data, even if evidence or other fields do not exist.
        $status = $filters['status'] ?? 'all';
        if ($status === 'outstanding') {
            $query->where(function ($sub) {
                $sub->whereNull('hs_evidence_file_1')
                    ->whereNull('hs_evidence_file_2')
                    ->whereNull('hs_evidence_file_3');
            });
        } elseif ($status === 'completed') {
            $query->where(function ($sub) {
                $sub->whereNotNull('hs_evidence_file_1')
                    ->orWhereNotNull('hs_evidence_file_2')
                    ->orWhereNotNull('hs_evidence_file_3')
                    ->orWhereNotNull('hs_evidence_file_4');
            });
        }

        // 2. Job Number filter (Job ref.)
        if (!empty($filters['job_no'])) {
            $query->where('job_no', 'LIKE', '%' . trim((string) $filters['job_no']) . '%');
        }

        // 3. Customer Name filter (searches customer_name and master_customer relation)
        // Search customer name both directly in customer_clearance_data and through master_customer relation if direct field is empty
        if (!empty($filters['customer_name'])) {
            $custName = trim((string) $filters['customer_name']);
            $query->where(function ($sub) use ($custName) {
                $sub->where('customer_name', 'LIKE', '%' . $custName . '%')
                    ->orWhereHas('customer', function ($q) use ($custName) {
                        $q->where('company_name', 'LIKE', '%' . $custName . '%');
                    });
            });
        }

        // 4. Branch filter (master_branch_id)
        if (!empty($filters['master_branch_id'])) {
            $query->where('master_branch_id', (int) $filters['master_branch_id']);
        }

        // 5. Operation / Declaration Type (0 = Import, 1 = Export)
        if (isset($filters['imp_exp']) && $filters['imp_exp'] !== '') {
            $impExp = (string) $filters['imp_exp'];
            if ($impExp === '0' || strtolower($impExp) === 'import' || strtolower($impExp) === 'imp') {
                $query->where('imp_exp', 0);
            } elseif ($impExp === '1' || strtolower($impExp) === 'export' || strtolower($impExp) === 'exp') {
                $query->where('imp_exp', 1);
            }
        }

        // 6. HS Code filter
        if (!empty($filters['hs_code'])) {
            $cleanedHs = preg_replace('/[^0-9]/', '', (string) $filters['hs_code']);
            $query->where('hs_code', 'LIKE', ($cleanedHs ?: trim((string) $filters['hs_code'])) . '%');
        }

        // 7. Statistic Code filter (statistic_code_id)
        if (!empty($filters['statistic_code_id'])) {
            $query->where('statistic_code_id', (int) $filters['statistic_code_id']);
        }

        // 8. Product Code 1 filter
        if (!empty($filters['product_code_1'])) {
            $query->where('product_code_1', 'LIKE', '%' . trim((string) $filters['product_code_1']) . '%');
        }

        // 9. Product Description En filter
        if (!empty($filters['product_description_en'])) {
            $query->where('product_description_en', 'LIKE', '%' . trim((string) $filters['product_description_en']) . '%');
        }

        // 10. Product Description Th filter
        if (!empty($filters['product_description_th'])) {
            $query->where('product_description_th', 'LIKE', '%' . trim((string) $filters['product_description_th']) . '%');
        }

        // 11. General Product Description filter (searches En or Th independently)
        if (!empty($filters['product_description'])) {
            $desc = trim((string) $filters['product_description']);
            $query->where(function ($sub) use ($desc) {
                $sub->where('product_description_en', 'LIKE', '%' . $desc . '%')
                    ->orWhere('product_description_th', 'LIKE', '%' . $desc . '%');
            });
        }

        // 12. Create Date filter
        if (!empty($filters['create_date'])) {
            $query->where('create_date', 'LIKE', '%' . trim((string) $filters['create_date']) . '%');
        }

        // 13. File existence filters (matching CustomerClearanceOutStandingController checkboxes)
        if (!empty($filters['exist_reference_file_1'])) {
            $query->whereNotNull('reference_file_1')->where('reference_file_1', '!=', '');
        }
        if (!empty($filters['exist_reference_file_2'])) {
            $query->whereNotNull('reference_file_2')->where('reference_file_2', '!=', '');
        }
        if (!empty($filters['exist_hs_evidence_file_1'])) {
            $query->whereNotNull('hs_evidence_file_1')->where('hs_evidence_file_1', '!=', '');
        }
        if (!empty($filters['exist_hs_evidence_file_2'])) {
            $query->whereNotNull('hs_evidence_file_2')->where('hs_evidence_file_2', '!=', '');
        }
        if (!empty($filters['exist_hs_evidence_file_3'])) {
            $query->whereNotNull('hs_evidence_file_3')->where('hs_evidence_file_3', '!=', '');
        }
        if (!empty($filters['exist_hs_evidence_file_4'])) {
            $query->whereNotNull('hs_evidence_file_4')->where('hs_evidence_file_4', '!=', '');
        }

        // 14. Keyword search (matching CustomerClearanceOutStandingController free keyword filter)
        if (!empty($filters['keyword'])) {
            $kw = trim((string) $filters['keyword']);
            $query->where(function ($q) use ($kw) {
                $q->where('job_no', 'LIKE', "%{$kw}%")
                    ->orWhere('customer_name', 'LIKE', "%{$kw}%")
                    ->orWhere('product_description_en', 'LIKE', "%{$kw}%")
                    ->orWhere('product_description_th', 'LIKE', "%{$kw}%")
                    ->orWhere('product_code_1', 'LIKE', "%{$kw}%")
                    ->orWhere('product_code_2', 'LIKE', "%{$kw}%")
                    ->orWhere('brand_name', 'LIKE', "%{$kw}%")
                    ->orWhere('model_name', 'LIKE', "%{$kw}%")
                    ->orWhere('materials', 'LIKE', "%{$kw}%")
                    ->orWhere('usages', 'LIKE', "%{$kw}%")
                    ->orWhere('functions', 'LIKE', "%{$kw}%")
                    ->orWhere('country_of_origin', 'LIKE', "%{$kw}%")
                    ->orWhere('note', 'LIKE', "%{$kw}%")
                    ->orWhere('hs_code', 'LIKE', "%{$kw}%");
            });
        }

        // 15. Sorting (default: job_no asc, matching CustomerClearanceOutStandingController)
        $sortBy = $filters['sort_by'] ?? 'job_no';
        $sortDir = strtolower($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $allowedSorts = [
            'id', 'job_no', 'customer_name', 'master_branch_id', 'imp_exp',
            'hs_code', 'tariff_ad_valorem', 'statistic_code_id', 'quantity_unit_id',
            'product_code_1', 'product_description_en', 'product_description_th',
            'create_date', 'update_date'
        ];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('job_no', 'asc');
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
