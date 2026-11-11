<?php

namespace App\Domain\Clearance\Services;

use App\Domain\Clearance\Actions\GenerateJobSequenceAction;

class ClearanceSequenceService
{
    public function __construct(
        protected GenerateJobSequenceAction $generator
    ) {
    }

    /**
     * @param string|null $type Prefix category (CCD, CST, NTB)
     * @param string|null $yymm Format YYMM (defaults to current date)
     * @return string
     */
    public function getNextJobNo(?string $type = 'CCD', ?string $yymm = null): string
    {
        return $this->generator->execute($type ?: 'CCD', $yymm);
    }
}

