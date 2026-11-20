<?php

namespace App\Domain\Clearance\Services;

use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;

/**
 * Resolves the codes typed in the clearance form (e.g. statistic "001", unit "C62")
 * to master table IDs, the same way the Excel imports do, so every entry path stores
 * customer_clearance_data.statistic_code_id / quantity_unit_id consistently.
 */
class TariffCodeResolver
{
    public function statisticId(?string $code): ?int
    {
        $code = $this->clean($code, 3);

        return $code === null ? null : MasterStatistic::where('statistics', $code)->value('id');
    }

    public function unitId(?string $code): ?int
    {
        $code = $this->clean($code, 3);

        return $code === null ? null : MasterUnit::where('unit', $code)->value('id');
    }

    private function clean(?string $code, int $length): ?string
    {
        $code = trim((string) $code);

        return $code === '' ? null : mb_substr($code, 0, $length);
    }
}
