<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\MasterCustomerController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Auth\AuthPasswordController;
use App\Http\Controllers\Auth\AuthSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailRequestSystemAuth;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Clearance\ClearanceCreateController;
use App\Http\Controllers\Clearance\ClearanceGridController;
use App\Http\Controllers\Clearance\ClearanceReplicateController;
use App\Http\Controllers\Clearance\ClearanceUploadController;
use App\Http\Controllers\Clearance\OutstandingAuditController;
use App\Http\Controllers\Export\WeeklyHsExportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/clearance');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthSessionController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthSessionController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthSessionController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthSessionController::class, 'register'])
        ->middleware('ensure.domain')
        ->name('register.post');

    Route::get('/forgot-password', [AuthPasswordController::class, 'showForgot'])->name('password.forgot');
    Route::post('/forgot-password', [AuthPasswordController::class, 'submitForgot'])->name('password.forgot.submit');
    Route::get('/reset-password', [AuthPasswordController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthPasswordController::class, 'submitReset'])->name('password.reset.submit');

    // Resend email verification notification (referenced from backup EmailVerificationNotificationController)
    Route::get('/verify-email-resend', [EmailVerificationNotificationController::class, 'show'])->name('verification.resend');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->name('verification.send');

    // Email verification prompt & verification routes referenced from backup html
    Route::get('/verify-email', [EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, 'verify'])->name('verification.verify');

    // System authorization request routes referenced from backup EmailRequestSystemAuth
    Route::get('/req_system_auth', [EmailRequestSystemAuth::class, 'SystemAuth'])->name('req.system_auth');
    Route::post('/req_system_auth', [EmailRequestSystemAuth::class, 'SystemAuthReq'])->name('req.system_auth.post');
    Route::get('/req_system_auth_verify/{time}/{token}', [EmailRequestSystemAuth::class, 'SystemAuthVerify'])->name('req.system_auth.verify');
});

// Confirm password routes for authenticated users referenced from backup ConfirmablePasswordController
Route::middleware('auth')->group(function () {
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);
});

Route::post('/logout', [AuthSessionController::class, 'logout'])->name('logout');
/*
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

Route::middleware(['auth'])->group(function () {
    /*
    Route::prefix('clearance')->group(function () {
        Route::get('/', [ClearanceGridController::class, 'index'])->name('clearance.index');
        Route::get('/create', [ClearanceCreateController::class, 'create'])->name('clearance.create');
        Route::post('/store', [ClearanceCreateController::class, 'store'])->name('clearance.store');
        Route::get('/{id}', [ClearanceGridController::class, 'show'])->name('clearance.show')->where('id', '[0-9]+');
    });
    */
    Route::prefix('clearance')->group(function () {
        Route::get('/', [ClearanceGridController::class, 'index'])->name('clearance.index');
        Route::get('/create', [ClearanceCreateController::class, 'create'])->name('clearance.create');
        Route::post('/store', [ClearanceCreateController::class, 'store'])->name('clearance.store');

        // Excel Requisition Ingestion Routes (defined before {id} to avoid parameter collisions)
        Route::get('/upload', [ClearanceUploadController::class, 'index'])->name('clearance.upload');
        Route::post('/upload/test-aws', [ClearanceUploadController::class, 'testAwsConnection'])->name('clearance.upload.test-aws');
        Route::post('/import/single', [ClearanceUploadController::class, 'importSingle'])->name('clearance.import.single');
        Route::post('/import/multi', [ClearanceUploadController::class, 'importMulti'])->name('clearance.import.multi');

        /*
        Route::get('/{id}', [ClearanceGridController::class, 'show'])->name('clearance.show')->where('id', '[0-9]+');
        */
        Route::get('/{id}', [ClearanceGridController::class, 'show'])->name('clearance.show')->where('id', '[0-9]+');
        Route::get('/{id}/edit', [ClearanceGridController::class, 'edit'])->name('clearance.edit')->where('id', '[0-9]+');
        Route::put('/{id}', [ClearanceGridController::class, 'update'])->name('clearance.update')->where('id', '[0-9]+');
        Route::get('/{id}/download/{field}', [ClearanceGridController::class, 'downloadDocument'])->name('clearance.download')->where('id', '[0-9]+');
        Route::get('/{id}/replicate', [ClearanceReplicateController::class, 'show'])->name('clearance.replicate.show')->where('id', '[0-9]+');
        Route::post('/{id}/replicate', [ClearanceReplicateController::class, 'execute'])->name('clearance.replicate.execute')->where('id', '[0-9]+');
    });

    // Compliance & Auditing Routes
    Route::prefix('compliance')->group(function () {
        Route::get('/outstanding', [OutstandingAuditController::class, 'index'])->name('compliance.outstanding');
        Route::post('/outstanding/{id}/attach', [OutstandingAuditController::class, 'attachEvidence'])->name('compliance.outstanding.attach');
    });

    /*
    Route::prefix('admin')->group(function () {
        Route::get('/customers', [MasterCustomerController::class, 'index'])->name('admin.customers.index');
        Route::post('/customers/sync', [MasterCustomerController::class, 'syncNames'])->name('admin.customers.sync');
    });
    */
    Route::middleware(['role:administrator'])->prefix('admin')->group(function () {
        Route::get('/customers', [MasterCustomerController::class, 'index'])->name('admin.customers.index');
        Route::post('/customers/sync', [MasterCustomerController::class, 'syncNames'])->name('admin.customers.sync');

        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show')->where('id', '[0-9]+');
        Route::put('/users/{id}/roles', [AdminUserController::class, 'updateRoles'])->name('admin.users.roles.update')->where('id', '[0-9]+');

        Route::get('/master/branches', [MasterDataController::class, 'branches'])->name('admin.master.branches');
        Route::get('/master/units', [MasterDataController::class, 'units'])->name('admin.master.units');
        Route::get('/master/statistics', [MasterDataController::class, 'statistics'])->name('admin.master.statistics');
        Route::get('/master/licenses', [MasterDataController::class, 'licenses'])->name('admin.master.licenses');
    });

    /*
    // [Previous Version - Export placeholder]
    Route::prefix('export')->group(function () {
        // Placeholder for GAP-04 Weekly HS exports
    });
    */
    Route::prefix('export')->group(function () {
        Route::get('/weekly', [WeeklyHsExportController::class, 'index'])->name('export.weekly');
        Route::post('/weekly', [WeeklyHsExportController::class, 'export'])->name('export.weekly.post');
    });
});

