<?php

use App\Http\Controllers\Api\SanctumAuthController;
use App\Http\Controllers\Api\SequenceLookupApiController;
use Illuminate\Support\Facades\Route;

// Public Authentication Routes with Rate Limiting (Laravel Sanctum)
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/register', [SanctumAuthController::class, 'register'])->name('api.register');
    Route::get('/email/verify/{token}', [SanctumAuthController::class, 'verifyEmail'])->name('api.email.verify');
    Route::post('/login', [SanctumAuthController::class, 'login'])->name('api.login');
    Route::post('/forgot-password', [SanctumAuthController::class, 'forgotPassword'])->name('api.password.forgot');
    Route::post('/reset-password', [SanctumAuthController::class, 'resetPassword'])->name('api.password.reset');
});

// Protected API Routes (Laravel Sanctum auth:sanctum)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [SanctumAuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [SanctumAuthController::class, 'user'])->name('api.user');
});

// Existing Sequence Lookup Endpoints
Route::get('/ccd_get_ym/{yymm}', [SequenceLookupApiController::class, 'getByYm'])->name('api.sequence.ym');
Route::get('/sequence/{type?}/{yymm?}', [SequenceLookupApiController::class, 'getSequence'])->name('api.sequence.lookup');


