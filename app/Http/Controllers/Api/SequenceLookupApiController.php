<?php

namespace App\Http\Controllers\Api;

// Gemini : SequenceLookupApiController handling auto-sequence generation for clearance job numbers
use App\Domain\Clearance\Actions\GenerateJobSequenceAction;
use App\Domain\Clearance\Services\ClearanceSequenceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SequenceLookupApiController extends Controller
{
    // Lady : Direct controller coupling to GenerateJobSequenceAction bypasses domain service abstraction.
    /*
    // [Previous Version - Direct Action Injection]
    protected GenerateJobSequenceAction $generator;

    public function __construct(GenerateJobSequenceAction $generator)
    {
        $this->generator = $generator;
    }
    */
    // Gemini : GAP-06.2 — Decouple controller via ClearanceSequenceService domain service
    public function __construct(
        protected ClearanceSequenceService $sequenceService
    ) {}

    /**
     * Legacy compatible endpoint: /api/ccd_get_ym/{yymm}
     */
    public function getByYm(string $yymm): JsonResponse
    {
        $jobNo = $this->sequenceService->getNextJobNo('CCD', $yymm);
        return response()->json([
            'status' => 'success',
            'job_no' => $jobNo,
        ]);
    }

    /**
     * Flexible lookup endpoint: /api/sequence/{type}/{yymm}
     */
    public function getSequence(?string $type = 'CCD', ?string $yymm = null): JsonResponse
    {
        $jobNo = $this->sequenceService->getNextJobNo($type ?: 'CCD', $yymm);
        return response()->json([
            'status' => 'success',
            'type' => $type,
            'job_no' => $jobNo,
        ]);
    }
}

