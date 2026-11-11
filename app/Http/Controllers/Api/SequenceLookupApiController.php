<?php

namespace App\Http\Controllers\Api;

use App\Domain\Clearance\Actions\GenerateJobSequenceAction;
use App\Domain\Clearance\Services\ClearanceSequenceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SequenceLookupApiController extends Controller
{
    /*
    protected GenerateJobSequenceAction $generator;

    public function __construct(GenerateJobSequenceAction $generator)
    {
        $this->generator = $generator;
    }
    */
    public function __construct(
        protected ClearanceSequenceService $sequenceService
    ) {
    }


    // ndpoint: /api/ccd_get_ym/{yymm}

    public function getByYm(string $yymm): JsonResponse
    {
        $jobNo = $this->sequenceService->getNextJobNo('CCD', $yymm);
        return response()->json([
            'status' => 'success',
            'job_no' => $jobNo,
        ]);
    }


    // /api/sequence/{type}/{yymm}

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

