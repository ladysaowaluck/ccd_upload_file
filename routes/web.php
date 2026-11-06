<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\MasterCustomerController;
// Gemini : GAP-09 — MasterDataController for branches, units, statistics, licenses views
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Auth\AuthPasswordController;
use App\Http\Controllers\Auth\AuthSessionController;
use App\Http\Controllers\Clearance\ClearanceCreateController;
use App\Http\Controllers\Clearance\ClearanceGridController;
// Gemini : MISS-01 — ClearanceReplicateController for duplicate-CCD workflow
use App\Http\Controllers\Clearance\ClearanceReplicateController;
use App\Http\Controllers\Clearance\ClearanceUploadController;
use App\Http\Controllers\Clearance\OutstandingAuditController;
use App\Http\Controllers\Export\WeeklyHsExportController;
use Illuminate\Support\Facades\Route;

// Gemini : Root redirect to clearance repository
Route::redirect('/', '/clearance');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthSessionController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthSessionController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthSessionController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthSessionController::class, 'register'])
        ->middleware('ensure.domain')
        ->name('register.post');

    // Gemini : Forgot Password — 2-step self-service reset (no email token, verify by corporate email in DB)
    Route::get('/forgot-password', [AuthPasswordController::class, 'showForgot'])->name('password.forgot');
    Route::post('/forgot-password', [AuthPasswordController::class, 'submitForgot'])->name('password.forgot.submit');
    Route::get('/reset-password', [AuthPasswordController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthPasswordController::class, 'submitReset'])->name('password.reset.submit');
});

Route::post('/logout', [AuthSessionController::class, 'logout'])->name('logout');

// Lady : Leaving these route groups unprotected without 'auth' middleware introduces a critical
// security vulnerability (GAP-01), allowing unauthenticated public internet users to view, create,
// and modify sensitive customs clearance data, compliance records, and customer master lists.
// All operational routes must be secured behind authentication.
/*
// [Previous Version - Unprotected Routes]
Route::prefix('clearance')->group(function () {
    Route::get('/', [ClearanceGridController::class, 'index'])->name('clearance.index');
    Route::get('/create', [ClearanceCreateController::class, 'create'])->name('clearance.create');
    Route::post('/store', [ClearanceCreateController::class, 'store'])->name('clearance.store');
    Route::get('/{id}', [ClearanceGridController::class, 'show'])->name('clearance.show')->where('id', '[0-9]+');
});

Route::prefix('compliance')->group(function () {
    Route::get('/outstanding', [OutstandingAuditController::class, 'index'])->name('compliance.outstanding');
    Route::post('/outstanding/{id}/attach', [OutstandingAuditController::class, 'attachEvidence'])->name('compliance.outstanding.attach');
});

Route::prefix('admin')->group(function () {
    Route::get('/customers', [MasterCustomerController::class, 'index'])->name('admin.customers.index');
    Route::post('/customers/sync', [MasterCustomerController::class, 'syncNames'])->name('admin.customers.sync');
});
*/

// Gemini : GAP-01 — Wrap clearance, compliance, admin, and export routes with 'auth' middleware to enforce authentication across all sensitive operations.
Route::middleware(['auth'])->group(function () {
    // Lady : The clearance route group was missing Excel ingestion endpoints (upload form, single sheet import, and multi-record batch import).
    /*
    // [Previous Version - Clearance routes without Excel ingestion]
    Route::prefix('clearance')->group(function () {
        Route::get('/', [ClearanceGridController::class, 'index'])->name('clearance.index');
        Route::get('/create', [ClearanceCreateController::class, 'create'])->name('clearance.create');
        Route::post('/store', [ClearanceCreateController::class, 'store'])->name('clearance.store');
        Route::get('/{id}', [ClearanceGridController::class, 'show'])->name('clearance.show')->where('id', '[0-9]+');
    });
    */
    // Gemini : GAP-03 — Core Clearance Repository Routes including Excel requisition ingestion endpoints
    Route::prefix('clearance')->group(function () {
        Route::get('/', [ClearanceGridController::class, 'index'])->name('clearance.index');
        Route::get('/create', [ClearanceCreateController::class, 'create'])->name('clearance.create');
        Route::post('/store', [ClearanceCreateController::class, 'store'])->name('clearance.store');

        // Excel Requisition Ingestion Routes (defined before {id} to avoid parameter collisions)
        Route::get('/upload', [ClearanceUploadController::class, 'index'])->name('clearance.upload');
        Route::post('/upload/test-aws', [ClearanceUploadController::class, 'testAwsConnection'])->name('clearance.upload.test-aws');
        Route::post('/import/single', [ClearanceUploadController::class, 'importSingle'])->name('clearance.import.single');
        Route::post('/import/multi', [ClearanceUploadController::class, 'importMulti'])->name('clearance.import.multi');

        // Lady : The clearance routes lacked edit form display and record update endpoints.
        /*
        // [Previous Version - Show-only endpoint]
        Route::get('/{id}', [ClearanceGridController::class, 'show'])->name('clearance.show')->where('id', '[0-9]+');
        */
        // Gemini : GAP-05 — Clearance view, edit, and update endpoints
        Route::get('/{id}', [ClearanceGridController::class, 'show'])->name('clearance.show')->where('id', '[0-9]+');
        Route::get('/{id}/edit', [ClearanceGridController::class, 'edit'])->name('clearance.edit')->where('id', '[0-9]+');
        Route::put('/{id}', [ClearanceGridController::class, 'update'])->name('clearance.update')->where('id', '[0-9]+');
        // Gemini : Secure streaming download route from AWS S3 bucket
        Route::get('/{id}/download/{field}', [ClearanceGridController::class, 'downloadDocument'])->name('clearance.download')->where('id', '[0-9]+');
        // Gemini : MISS-01 — Duplicate CCD record to a new job number (show form + execute duplication)
        Route::get('/{id}/replicate', [ClearanceReplicateController::class, 'show'])->name('clearance.replicate.show')->where('id', '[0-9]+');
        Route::post('/{id}/replicate', [ClearanceReplicateController::class, 'execute'])->name('clearance.replicate.execute')->where('id', '[0-9]+');
    });

    // Compliance & Auditing Routes
    Route::prefix('compliance')->group(function () {
        Route::get('/outstanding', [OutstandingAuditController::class, 'index'])->name('compliance.outstanding');
        Route::post('/outstanding/{id}/attach', [OutstandingAuditController::class, 'attachEvidence'])->name('compliance.outstanding.attach');
    });

    // Lady : The admin routes were not protected by role authorization, allowing any authenticated
    // user (including staff) to access or modify customer master data and batch synchronization.
    /*
    // [Previous Version - Un-role-restricted admin routes]
    Route::prefix('admin')->group(function () {
        Route::get('/customers', [MasterCustomerController::class, 'index'])->name('admin.customers.index');
        Route::post('/customers/sync', [MasterCustomerController::class, 'syncNames'])->name('admin.customers.sync');
    });
    */
    // Gemini : GAP-02 — Apply 'role:administrator' middleware to ensure only administrators can manage master data
    Route::middleware(['role:administrator'])->prefix('admin')->group(function () {
        Route::get('/customers', [MasterCustomerController::class, 'index'])->name('admin.customers.index');
        Route::post('/customers/sync', [MasterCustomerController::class, 'syncNames'])->name('admin.customers.sync');

        // Gemini : GAP-08 — User management directory and role assignment routes
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show')->where('id', '[0-9]+');
        Route::put('/users/{id}/roles', [AdminUserController::class, 'updateRoles'])->name('admin.users.roles.update')->where('id', '[0-9]+');

        // Gemini : GAP-09 — Master data read-only views (branches, units, statistics, licenses)
        Route::get('/master/branches', [MasterDataController::class, 'branches'])->name('admin.master.branches');
        Route::get('/master/units', [MasterDataController::class, 'units'])->name('admin.master.units');
        Route::get('/master/statistics', [MasterDataController::class, 'statistics'])->name('admin.master.statistics');
        Route::get('/master/licenses', [MasterDataController::class, 'licenses'])->name('admin.master.licenses');
    });

    // Lady : The export route group was only an empty placeholder without weekly HS Code export endpoints.
    /*
    // [Previous Version - Export placeholder]
    Route::prefix('export')->group(function () {
        // Placeholder for GAP-04 Weekly HS exports
    });
    */
    // Gemini : GAP-04 — Weekly HS Code report filter and Excel download routes
    Route::prefix('export')->group(function () {
        Route::get('/weekly', [WeeklyHsExportController::class, 'index'])->name('export.weekly');
        Route::post('/weekly', [WeeklyHsExportController::class, 'export'])->name('export.weekly.post');
    });
});

