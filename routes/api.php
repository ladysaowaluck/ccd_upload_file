<?php

use App\Http\Controllers\Api\SequenceLookupApiController;
use Illuminate\Support\Facades\Route;

// Gemini : API route matching legacy /api/ccd_get_ym/{yymm} and /api/sequence/{prefix}/{yymm}
Route::get('/ccd_get_ym/{yymm}', [SequenceLookupApiController::class, 'getByYm'])->name('api.sequence.ym');
Route::get('/sequence/{type?}/{yymm?}', [SequenceLookupApiController::class, 'getSequence'])->name('api.sequence.lookup');

