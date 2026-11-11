<?php

namespace App\Domain\Clearance\Actions;

use App\Models\Business\CustomerClearanceData;

class GenerateJobSequenceAction
{
    /**
     * Generate the next formatted job sequence (e.g. CCD2305-0001, CST2305-0001, NTB2305-0001).
     *
     * @param string $type The clearance prefix (default: CCD)
     * @param string|null $yymm Optional year-month (YYMM); defaults to current date('ym')
     * @return string
     */
    public function execute(string $type = 'CCD', ?string $yymm = null): string
    {
        $yymm = $yymm ?: date('ym');
        $prefix = "{$type}{$yymm}-";

        /*
        $lastRecord = CustomerClearanceData::where('job_no', 'LIKE', "{$prefix}%")
            ->orderBy('job_no', 'desc')
            ->first();
        */

        // Order by LENGTH(job_no) DESC, job_no DESC to ensure numeric sequence ordering when numbers exceed 4 digits (e.g. 10000+).
        $lastRecord = CustomerClearanceData::where('job_no', 'LIKE', "{$prefix}%")
            ->orderByRaw('LENGTH(job_no) DESC, job_no DESC')
            ->first();

        if (!$lastRecord || empty($lastRecord->job_no)) {
            return "{$prefix}0001";
        }

        $rawSeq = substr($lastRecord->job_no, strlen($prefix));
        $lastSequence = is_numeric($rawSeq) ? (int) $rawSeq : 0;

        /*
        if ($lastSequence >= 9999) {
            throw new \OverflowException(
                "Monthly {$type} sequence limit reached (9999) for period {$yymm}. Contact IT support."
            );
        }
        */

        // Guarantee unique sequence by checking existence in a loop to eliminate collisions.
        do {
            $lastSequence++;
            $nextSequence = str_pad((string) $lastSequence, 4, '0', STR_PAD_LEFT);
            $candidateJobNo = "{$prefix}{$nextSequence}";
        } while (CustomerClearanceData::where('job_no', $candidateJobNo)->exists());

        return $candidateJobNo;
    }
}

