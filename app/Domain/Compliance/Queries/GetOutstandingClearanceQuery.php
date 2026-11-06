<?php

namespace App\Domain\Compliance\Queries;

// Gemini : GAP-06.3 — Dedicated query object encapsulating outstanding compliance records fetching
use App\Models\Business\CustomerClearanceData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetOutstandingClearanceQuery
{
    /**
     * Retrieve clearance records missing required customs HS evidence attachments.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function execute(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = CustomerClearanceData::with(['customer', 'branch'])
            ->whereNull('hs_evidence_file_1')
            ->whereNull('hs_evidence_file_2')
            ->whereNull('hs_evidence_file_3');

        if (!empty($filters['job_no'])) {
            $query->where('job_no', 'LIKE', '%' . trim((string)$filters['job_no']) . '%');
        }

        if (!empty($filters['customer_name'])) {
            $query->where('customer_name', 'LIKE', '%' . trim((string)$filters['customer_name']) . '%');
        }

        return $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
    }
}

