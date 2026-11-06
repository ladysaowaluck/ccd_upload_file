<?php

namespace App\Domain\Clearance\Services;

// Gemini : GAP-06.2 — Domain service wrapping job sequence auto-generation logic
use App\Domain\Clearance\Actions\GenerateJobSequenceAction;

class ClearanceSequenceService
{
    public function __construct(
        protected GenerateJobSequenceAction $generator
    ) {}

    /**
     * Generate the next running job sequence for the specified category and year-month.
     *
     * @param string|null $type Prefix category (CCD, CST, NTB)
     * @param string|null $yymm Format YYMM (defaults to current date)
     * @return string
     */
    public function getNextJobNo(?string $type = 'CCD', ?string $yymm = null): string
    {
        return $this->generator->execute($type ?: 'CCD', $yymm);
    }
}

