<?php

namespace App\Domain\Clearance\Actions;

// Gemini : Job number auto-sequence action replicating legacy /api/ccd_get_ym/{yymm} pattern
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

        $lastRecord = CustomerClearanceData::where('job_no', 'LIKE', "{$prefix}%")
            ->orderBy('job_no', 'desc')
            ->first();

        if (!$lastRecord || empty($lastRecord->job_no)) {
            return "{$prefix}0001";
        }

        $rawSeq = substr($lastRecord->job_no, strlen($prefix));
        $lastSequence = is_numeric($rawSeq) ? (int) $rawSeq : 0;

        // Lady : Throwing an OverflowException when sequence reaches 9999 causes system interruption and breaks creation workflows when monthly volume is high. The legacy system (apiCcdController and ReplicateRecordCcd) never crashed; it safely incremented without hard caps.
        /*
        if ($lastSequence >= 9999) {
            throw new \OverflowException(
                "Monthly {$type} sequence limit reached (9999) for period {$yymm}. Contact IT support."
            );
        }
        */

        // Gemini : Safely increment running sequence with minimum 4-digit zero-padding matching legacy behavior
        $nextSequence = str_pad((string)($lastSequence + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$nextSequence}";
    }
}

