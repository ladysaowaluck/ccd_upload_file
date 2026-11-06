<?php

namespace App\Domain\Clearance\Actions;

// Gemini : MISS-01 — ReplicateClearanceRecordAction copies tariff classification to a new job number.
// File attachments are intentionally NOT copied — the new record starts with blank evidence slots.
use App\Models\Business\CustomerClearanceData;
use Carbon\Carbon;

class ReplicateClearanceRecordAction
{
    // Fields intentionally excluded from copy (IDs, files, timestamps must be fresh on duplicate)
    private const SKIP_FIELDS = [
        'id', 'job_no',
        'product_code_1', 'product_code_2',
        'reference_file_1', 'reference_file_2',
        'hs_evidence_file_1', 'hs_evidence_file_2', 'hs_evidence_file_3', 'hs_evidence_file_4',
        'created_at', 'updated_at', 'create_date', 'update_date', 'edit_user_id',
    ];

    /**
     * Duplicate a clearance record into a new job number.
     * All tariff/classification fields are copied. File slots are blank.
     *
     * @param CustomerClearanceData $source  Original record to copy from
     * @param string $newJobNo               New unique job number e.g. CCD2611-0001
     * @param string|null $newCode1          Optional new product_code_1
     * @param string|null $newCode2          Optional new product_code_2
     */
    public function execute(
        CustomerClearanceData $source,
        string $newJobNo,
        ?string $newCode1,
        ?string $newCode2
    ): CustomerClearanceData {

        // Start from source fields, then remove what must NOT be copied
        $data = $source->toArray();
        foreach (self::SKIP_FIELDS as $field) {
            unset($data[$field]);
        }

        // Set the new identity fields
        $data['job_no']         = $newJobNo;
        $data['product_code_1'] = $newCode1 ?: null;
        $data['product_code_2'] = $newCode2 ?: null;
        $data['create_date']    = Carbon::now()->toDateString();
        $data['update_date']    = Carbon::now()->toDateString();
        $data['edit_user_id']   = auth()->id() ?: 1;

        return CustomerClearanceData::create($data);
    }
}

