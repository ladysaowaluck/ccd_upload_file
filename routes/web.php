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

    // forgot-password form/submit stay inside guest middleware (only for non-authenticated users).
    // reset-password routes are moved OUTSIDE guest middleware so that an already-logged-in user
    // (e.g. User B in Browser B) can still follow a reset link meant for User A without being
    // silently redirected away by the guest guard. See standalone routes below.

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

// Password reset routes — standalone (no guest/auth middleware) so they work
// whether the visitor is logged in or not. Session logic is handled in the controller.
Route::get('/reset-password', [AuthPasswordController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password', [AuthPasswordController::class, 'submitReset'])->name('password.reset.submit');
// Confirm-logout page: shown when another user is logged in and opens a reset link.
Route::get('/reset-password/confirm-logout', [AuthPasswordController::class, 'showConfirmLogout'])->name('password.reset.confirm-logout');
// Agree button POST: logs out the current session, then redirects to the reset form.
Route::post('/reset-password/confirm-logout', [AuthPasswordController::class, 'confirmLogout'])->name('password.reset.logout-confirm');

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
        Route::post('/announcement', [ClearanceGridController::class, 'updateAnnouncement'])->name('clearance.announcement.update');
        Route::get('/create', [ClearanceCreateController::class, 'create'])->name('clearance.create');
        Route::post('/store', [ClearanceCreateController::class, 'store'])->name('clearance.store');
        Route::get('/{id}', [ClearanceGridController::class, 'show'])->name('clearance.show')->where('id', '[0-9]+');
    });
    */
    Route::prefix('clearance')->group(function () {
        // Read/View routes accessible to all authenticated users (including unassigned read-only accounts)
        Route::get('/', [ClearanceGridController::class, 'index'])->name('clearance.index');
        Route::get('/{id}', [ClearanceGridController::class, 'show'])->name('clearance.show')->where('id', '[0-9]+');
        Route::get('/{id}/view/{field}', [ClearanceGridController::class, 'viewDocument'])->name('clearance.view')->where('id', '[0-9]+');

        // Download routes protected by can.download middleware to enforce read(view)-only for unassigned users.
        Route::middleware(['can.download'])->group(function () {
            Route::get('/{id}/download/{field}', [ClearanceGridController::class, 'downloadDocument'])->name('clearance.download')->where('id', '[0-9]+');
        });

        // Routes requiring create/upload permissions (accessible to standard users and elevated users)
        Route::middleware(['can.write'])->group(function () {
            Route::get('/create', [ClearanceCreateController::class, 'create'])->name('clearance.create');
            Route::post('/store', [ClearanceCreateController::class, 'store'])->name('clearance.store');

            // Excel Requisition Ingestion Routes (defined before {id} to avoid parameter collisions)
            Route::get('/upload', [ClearanceUploadController::class, 'index'])->name('clearance.upload');
            Route::post('/upload/test-aws', [ClearanceUploadController::class, 'testAwsConnection'])->name('clearance.upload.test-aws');
            Route::post('/import/single', [ClearanceUploadController::class, 'importSingle'])->name('clearance.import.single');
            Route::post('/import/multi', [ClearanceUploadController::class, 'importMulti'])->name('clearance.import.multi');

            Route::get('/{id}/replicate', [ClearanceReplicateController::class, 'show'])->name('clearance.replicate.show')->where('id', '[0-9]+');
            Route::post('/{id}/replicate', [ClearanceReplicateController::class, 'execute'])->name('clearance.replicate.execute')->where('id', '[0-9]+');
        });

        // Standard users can read/create/download/upload but CANNOT edit or delete.
        //  Restrict edit routes to can.edit middleware so standard users are denied edit access.
        Route::middleware(['can.edit'])->group(function () {
            Route::post('/announcement', [ClearanceGridController::class, 'updateAnnouncement'])->name('clearance.announcement.update');
            Route::get('/{id}/edit', [ClearanceGridController::class, 'edit'])->name('clearance.edit')->where('id', '[0-9]+');
            Route::put('/{id}', [ClearanceGridController::class, 'update'])->name('clearance.update')->where('id', '[0-9]+');
            //Route to delete an individual attached file (reference or evidence) from a clearance record
            Route::delete('/{id}/file/{field}', [ClearanceGridController::class, 'deleteDocument'])->name('clearance.file.destroy')->where('id', '[0-9]+');
        });

        // Restrict record deletion to can.delete middleware so standard users are denied delete access.
        Route::middleware(['can.delete'])->group(function () {
            Route::delete('/{id}', [ClearanceGridController::class, 'destroy'])->name('clearance.destroy')->where('id', '[0-9]+');
        });
    });

    // Compliance & Auditing Routes
    Route::prefix('compliance')->group(function () {
        Route::get('/outstanding', [OutstandingAuditController::class, 'index'])->name('compliance.outstanding');
        // Guard evidence attachment with can.write middleware for unassigned role restriction
        Route::post('/outstanding/{id}/attach', [OutstandingAuditController::class, 'attachEvidence'])
            ->middleware(['can.write'])
            ->name('compliance.outstanding.attach');
    });

    /*
    Route::prefix('admin')->group(function () {
        Route::get('/customers', [MasterCustomerController::class, 'index'])->name('admin.customers.index');
        Route::post('/customers/sync', [MasterCustomerController::class, 'syncNames'])->name('admin.customers.sync');
        // CRUD routes for master customers
        Route::post('/customers', [MasterCustomerController::class, 'store'])->name('admin.customers.store');
        Route::get('/customers/{id}/edit', [MasterCustomerController::class, 'edit'])->name('admin.customers.edit')->where('id', '[0-9]+');
        Route::put('/customers/{id}', [MasterCustomerController::class, 'update'])->name('admin.customers.update')->where('id', '[0-9]+');
        Route::delete('/customers/{id}', [MasterCustomerController::class, 'destroy'])->name('admin.customers.destroy')->where('id', '[0-9]+');
    });
    */
    // Route::middleware(['role:administrator'])->prefix('admin')->group(function () {
    // Allow 'power user' (supporting power_user and powerUser slugs) and administrator to create, read, update, delete master data (Customer, Branches, Units, Statistical codes, Licenses).
    Route::middleware(['role:administrator,power_user,powerUser'])->prefix('admin')->group(function () {
        // Master Data Routes (Customers moved to /admin/master/customers + full CRUD for all master resources)
        Route::prefix('master')->group(function () {
            // Master Customers
            Route::get('/customers', [MasterCustomerController::class, 'index'])->name('admin.master.customers.index');
            Route::post('/customers', [MasterCustomerController::class, 'store'])->name('admin.master.customers.store');
            Route::post('/customers/sync', [MasterCustomerController::class, 'syncNames'])->name('admin.master.customers.sync');
            Route::get('/customers/{id}/edit', [MasterCustomerController::class, 'edit'])->name('admin.master.customers.edit')->where('id', '[0-9]+');
            Route::put('/customers/{id}', [MasterCustomerController::class, 'update'])->name('admin.master.customers.update')->where('id', '[0-9]+');
            Route::delete('/customers/{id}', [MasterCustomerController::class, 'destroy'])->name('admin.master.customers.destroy')->where('id', '[0-9]+');

            // Master Branches
            Route::get('/branches', [MasterDataController::class, 'branches'])->name('admin.master.branches');
            Route::post('/branches', [MasterDataController::class, 'storeBranch'])->name('admin.master.branches.store');
            Route::put('/branches/{id}', [MasterDataController::class, 'updateBranch'])->name('admin.master.branches.update')->where('id', '[0-9]+');
            Route::delete('/branches/{id}', [MasterDataController::class, 'destroyBranch'])->name('admin.master.branches.destroy')->where('id', '[0-9]+');

            // Master Units
            Route::get('/units', [MasterDataController::class, 'units'])->name('admin.master.units');
            Route::post('/units', [MasterDataController::class, 'storeUnit'])->name('admin.master.units.store');
            Route::put('/units/{id}', [MasterDataController::class, 'updateUnit'])->name('admin.master.units.update')->where('id', '[0-9]+');
            Route::delete('/units/{id}', [MasterDataController::class, 'destroyUnit'])->name('admin.master.units.destroy')->where('id', '[0-9]+');

            // Master Statistics
            Route::get('/statistics', [MasterDataController::class, 'statistics'])->name('admin.master.statistics');
            Route::post('/statistics', [MasterDataController::class, 'storeStatistic'])->name('admin.master.statistics.store');
            Route::put('/statistics/{id}', [MasterDataController::class, 'updateStatistic'])->name('admin.master.statistics.update')->where('id', '[0-9]+');
            Route::delete('/statistics/{id}', [MasterDataController::class, 'destroyStatistic'])->name('admin.master.statistics.destroy')->where('id', '[0-9]+');

            // Master Licenses
            Route::get('/licenses', [MasterDataController::class, 'licenses'])->name('admin.master.licenses');
            Route::post('/licenses', [MasterDataController::class, 'storeLicense'])->name('admin.master.licenses.store');
            Route::put('/licenses/{id}', [MasterDataController::class, 'updateLicense'])->name('admin.master.licenses.update')->where('id', '[0-9]+');
            Route::delete('/licenses/{id}', [MasterDataController::class, 'destroyLicense'])->name('admin.master.licenses.destroy')->where('id', '[0-9]+');
        });

        // Redirect old /admin/customers to /admin/master/customers for backwards compatibility
        Route::redirect('/customers', '/admin/master/customers')->name('admin.customers.index');
    });
    // Route::middleware(['role:administrator'])->prefix('admin')->group(function () {
    // User management is restricted strictly to administrator only
    Route::middleware(['role:administrator'])->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show')->where('id', '[0-9]+');
        Route::put('/users/{id}/roles', [AdminUserController::class, 'updateRoles'])->name('admin.users.roles.update')->where('id', '[0-9]+');
    });

    /*
    // [Previous Version - Export placeholder]
    Route::prefix('export')->group(function () {
        // Placeholder for GAP-04 Weekly HS exports
    });
    */
    // Restrict export routes with can.download middleware so only authorized users can download reports.
    Route::prefix('export')->middleware(['can.download'])->group(function () {
        Route::get('/weekly', [WeeklyHsExportController::class, 'index'])->name('export.weekly');
        Route::post('/weekly', [WeeklyHsExportController::class, 'export'])->name('export.weekly.post');
    });
});

