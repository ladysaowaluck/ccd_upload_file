<?php

namespace App\Domain\MasterData\Services;

// Gemini : GAP-06.4 — Service synchronizing customer name updates across historical clearance records
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterCustomer;

class BatchCustomerNameSyncService
{
    /**
     * Propagate the current master customer company name across all linked clearance records.
     *
     * @param int $masterCustomerId
     * @return int Number of affected records
     */
    public function sync(int $masterCustomerId): int
    {
        $customer = MasterCustomer::findOrFail($masterCustomerId);

        return CustomerClearanceData::where('master_customer_id', $customer->id)
            ->update([
                'customer_name' => $customer->company_name,
                'update_date'   => date('Y-m-d'),
            ]);
    }
}

