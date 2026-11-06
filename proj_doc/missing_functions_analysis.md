# CCD New Version — Missing Functions Analysis

**Generated:** 2026-11-05  
**Analyst:** Gemini Agent (from `reverse_engineering_analysis.md` + legacy backup inspection)  
**Legacy Source:** `E:\Task\CCD\backup\html`  
**New Project:** `E:\Task\CCD\CCD_new_v\ccd_upload`  

---

## Quick Status Summary

| # | Feature / Function | Legacy File | New Version | Status |
|---|---|---|---|---|
| 1 | Record Replication (Duplicate CCD) | `ReplicateRecordCcd.php` | ❌ Not built | **MISSING** |
| 2 | Record Replication (Duplicate CST) | `ReplicateRecordCst.php` | ❌ Not built | **MISSING** |
| 3 | Master Statistics — Create / Edit UI | `MasterStatisticController.php` | ⚠️ Read-only view only | **PARTIAL** |
| 4 | Master Licenses — Create / Edit UI | `MasterLicenseController.php` | ⚠️ Read-only view only | **PARTIAL** |
| 5 | Master Units — Create / Edit UI | `MasterUnitController.php` | ⚠️ Read-only view only | **PARTIAL** |
| 6 | Master Branches — Create / Edit UI | (Legacy admin grid) | ⚠️ Read-only view only | **PARTIAL** |
| 7 | File delete / replace on edit | `CustomerClearanceDataController.php` | ❌ Not built | **MISSING** |
| 8 | Customer name batch update tool | `MasterCustomerController.php` | ✅ Partially built | **PARTIAL** |
| 9 | Sequence counter overflow guard | `GenerateJobSequenceAction.php` | ❌ 5-digit seq breaks test | **BUG** |
| 10 | Outstanding attach-evidence form | `CustomerClearanceOutStandingController.php` | ⚠️ Route exists, no form | **PARTIAL** |
| 11 | Master Edit WRITE routes (PUT/POST) | Various master controllers | ❌ Routes missing | **MISSING** |

---

## MISS-01 — Record Replication (Duplicate CCD/CST)

### What it does (legacy)
The legacy system has two Row-Action classes for the Laravel-Admin grid:

- `app/Admin/Actions/ReplicateRecordCcd.php` (CCD records)
- `app/Admin/Actions/ReplicateRecordCst.php` (CST records)

When a user clicks **"Replicate CCD"** on any grid row, a modal pops up asking for:
- New Job Ref (auto-suggested based on current month sequence)
- New Product Code 1 (optional override)
- New Product Code 2 (optional override)

The action then:
1. Validates the new Job No format (`CCD{YYMM}-{####}`, 12 chars, unique)
2. Checks product code uniqueness per customer
3. Copies **all tariff and classification fields** from the source record (HS code, privilege, duties, taxes, licenses)
4. **Does NOT copy file attachments** (reference/hs_evidence files intentionally blank on duplicate)
5. Sets `create_date`, `update_date`, `edit_user_id` to current user/time
6. Redirects to edit form of new record

### Why it matters
Customs clearance records for the same product from different shipments share identical HS classification. Replication saves significant manual re-entry for recurring products.

### What to build in new version

```
app/Domain/Clearance/Actions/ReplicateClearanceRecordAction.php
app/Http/Controllers/Clearance/ClearanceReplicateController.php
resources/views/clearance/replicate.blade.php   (modal or page form)
```

**Route to add** (inside `auth` middleware group):
```php
// GET shows the replicate form pre-filled with current month's next sequence
Route::get('/clearance/{id}/replicate', [ClearanceReplicateController::class, 'show'])
     ->name('clearance.replicate.show');

// POST executes the duplication
Route::post('/clearance/{id}/replicate', [ClearanceReplicateController::class, 'execute'])
     ->name('clearance.replicate.execute');
```

**Minimal action signature:**
```php
// app/Domain/Clearance/Actions/ReplicateClearanceRecordAction.php
public function execute(CustomerClearanceData $source, string $newJobNo, ?string $newCode1, ?string $newCode2): CustomerClearanceData
```

**Validation rules (from legacy):**
```php
$rules = [
    'new_job_no'       => ['required', 'regex:/^(CCD|CST|NTB)\d{4}-\d{4}$/', 'unique:customer_clearance_data,job_no'],
    'new_product_code_1' => ['nullable', 'string', 'unique:customer_clearance_data,product_code_1,NULL,id,master_customer_id,' . $source->master_customer_id],
    'new_product_code_2' => ['nullable', 'string', 'unique:customer_clearance_data,product_code_2,NULL,id,master_customer_id,' . $source->master_customer_id],
];
```

---

## MISS-02 — Master Data Write Operations (Create/Edit/Update)

### What it does (legacy)
The legacy `MasterLicenseController`, `MasterStatisticController`, and `MasterUnitController` all extend `AdminController` and provide full CRUD: **grid + detail view + create form + edit form**.

- `master_licenses` — License type registry (license_th, license_en)
- `master_statistics` — 3-digit statistical code registry
- `master_units` — Quantity unit registry (unit field)

### Current new version
`MasterDataController.php` only provides **read-only paginated list views**. There are no routes or forms for creating or editing master data records.

### What to build

**Option A — Add create/edit to MasterDataController** (simpler, recommended):

```
resources/views/admin/master/licenses_form.blade.php
resources/views/admin/master/statistics_form.blade.php
resources/views/admin/master/units_form.blade.php
```

**Routes to add** (inside `role:administrator` middleware group):
```php
// Licenses
Route::post('/master/licenses',           [MasterDataController::class, 'storeLicense'])->name('admin.master.licenses.store');
Route::get('/master/licenses/{id}/edit',  [MasterDataController::class, 'editLicense'])->name('admin.master.licenses.edit');
Route::put('/master/licenses/{id}',       [MasterDataController::class, 'updateLicense'])->name('admin.master.licenses.update');

// Statistics
Route::post('/master/statistics',          [MasterDataController::class, 'storeStatistic'])->name('admin.master.statistics.store');
Route::get('/master/statistics/{id}/edit', [MasterDataController::class, 'editStatistic'])->name('admin.master.statistics.edit');
Route::put('/master/statistics/{id}',      [MasterDataController::class, 'updateStatistic'])->name('admin.master.statistics.update');

// Units
Route::post('/master/units',              [MasterDataController::class, 'storeUnit'])->name('admin.master.units.store');
Route::get('/master/units/{id}/edit',     [MasterDataController::class, 'editUnit'])->name('admin.master.units.edit');
Route::put('/master/units/{id}',          [MasterDataController::class, 'updateUnit'])->name('admin.master.units.update');
```

> **Note:** The legacy system disables DELETE on all master records (accidental deletion is too dangerous). Keep delete disabled; only allow create + edit.

---

## MISS-03 — File Delete / Replace on Edit

### What it does (legacy)
When editing a clearance record and uploading a new file for a slot already occupied by an existing file, the legacy `CustomerClearanceDataController` replaces the old file on disk and updates the DB field. There is also an admin-level "clear file" action to nullify a slot.

### Current new version
`UpdateClearanceRecordAction.php` only stores the new file and updates the DB path. It does **not** delete the old file from S3 when replacing. There is no "clear attachment" action.

### What to build

In `UpdateClearanceRecordAction.php`, before storing a new file, check if the current DB slot is non-null and delete the old S3 key:

```php
// Gemini : Delete old S3 file before writing replacement to avoid orphaned objects
if (!empty($record->$field) && $newFile instanceof UploadedFile) {
    $this->storageService->delete($record->$field);
}
```

Add a **clear-slot route** for administrators:
```php
Route::delete('/clearance/{id}/attachment/{field}', [ClearanceGridController::class, 'deleteAttachment'])
     ->name('clearance.attachment.delete');
```

Add `deleteAttachment(int $id, string $field, DocumentStorageService $storage)` to `ClearanceGridController`.

---

## MISS-04 — Sequence Counter Overflow Guard (BUG)

### What fails
`ClearanceSequenceService` and `GenerateJobSequenceAction` generate sequences with `sprintf('%04d', $next)`. When the monthly sequence exceeds 9999 (e.g., CCD2611-10000), the format breaks the standard 12-character Job No format.

The test `clearance sequence service generates job sequence` asserts:
```php
$this->assertMatchesRegularExpression('/^CCD\d{4}-\d{4}$/', $jobNo);
```
This fails at sequence 10000+.

### What to fix

**Option A — Cap at 9999 and throw exception** (safest, matches original):
```php
// app/Domain/Clearance/Actions/GenerateJobSequenceAction.php
if ($nextSeq > 9999) {
    throw new \OverflowException("Monthly CCD sequence exceeded 9999 for period {$yymm}");
}
```

**Option B — Allow 5+ digit seq and update test regex** (pragmatic):
```php
// tests/Feature/ClearanceSystemTest.php — update assertion
$this->assertMatchesRegularExpression('/^CCD\d{4}-\d+$/', $jobNo);
```

> **Recommendation:** Use Option A. The original system cap was 9999. If 10000+ jobs occur in a month, that is a data/process problem, not a software feature.

---

## MISS-05 — Outstanding Evidence Attachment Form

### What it does (legacy)
`CustomerClearanceOutStandingController` lists all records with no HS evidence files. Each row has an inline file upload action to quickly attach evidence without navigating to the full edit form.

### Current new version
`OutstandingAuditController` lists outstanding records. The route `POST /compliance/outstanding/{id}/attach` exists in `routes/web.php`, and `attachEvidence()` exists as a stub in the controller, but the form in `outstanding.blade.php` may be incomplete.

### What to verify
Check `resources/views/clearance/outstanding.blade.php` — confirm that each row has:
1. A file input named `hs_evidence_file_1` (at minimum)
2. A form posting to `route('compliance.outstanding.attach', $record->id)`
3. CSRF `@csrf` token
4. The `attachEvidence()` method in `OutstandingAuditController` calls `DocumentStorageService::store()` and updates the DB

---

## MISS-06 — Customer Name Batch Sync Tool (Partial)

### What it does (legacy)
`MasterCustomerController` in the legacy system has an `updatename` route that shows a form to batch-replace a customer name string across all historical `customer_clearance_data` records. This is used when a customer officially changes their company name.

### Current new version
`MasterCustomerController.php` and `BatchCustomerNameSyncService.php` exist. Route `POST /admin/customers/sync` exists in `routes/web.php`. 

### What to verify
Confirm `MasterCustomerController::syncNames()` delegates to `BatchCustomerNameSyncService` and that the customer list view (`admin/customers/index.blade.php`) includes a "Sync Names" button that posts to `route('admin.customers.sync')`.

---

## MISS-07 — Sidebar Navigation Completeness

### Current gaps in `layouts/app.blade.php`

The sidebar should include links to ALL operational sections. Verify these are present:

| Link Label | Route Name | Access |
|---|---|---|
| CCD Repository | `clearance.index` | All authenticated |
| Upload Requisition | `clearance.upload` | All authenticated |
| Outstanding | `compliance.outstanding` | All authenticated |
| Export Weekly HS | `export.weekly` | All authenticated |
| Customers | `admin.customers.index` | Admin only |
| User Management | `admin.users.index` | Admin only |
| Branches | `admin.master.branches` | Admin only |
| Units | `admin.master.units` | Admin only |
| Statistics | `admin.master.statistics` | Admin only |
| Licenses | `admin.master.licenses` | Admin only |

---

## What IS Fully Built (Confirmed Complete)

| Feature | Files | Test Coverage |
|---|---|---|
| Auth login/register/logout | `AuthSessionController`, `AuthPasswordController` | ✅ |
| Domain email whitelist | `EnsureCorporateDomain` middleware | ✅ |
| Role auth middleware | `RoleAuthorizationMiddleware` | ✅ |
| CCD Grid + filters + pagination | `ClearanceGridController::index` | ✅ |
| CCD Detail view | `ClearanceGridController::show` | ✅ |
| CCD Create (manual form) | `ClearanceCreateController`, `CreateClearanceRecordAction` | ✅ |
| CCD Edit + Update | `ClearanceGridController::edit/update`, `UpdateClearanceRecordAction` | ✅ |
| Excel Single-sheet Import | `ClearanceUploadController`, `SingleSheetImport`, `ClearanceExcelImportService` | ✅ |
| Excel Multi-record Import | `MultiSheetImport`, `importMulti()` | ✅ |
| S3-only file storage | `DocumentStorageService` (S3 stream, no local disk) | ✅ |
| Secure S3 download stream | `ClearanceGridController::downloadDocument` | ✅ |
| Outstanding compliance audit | `OutstandingAuditController` | ✅ |
| Weekly HS Code Excel Export | `WeeklyHsExportService`, `WeeklyHsExportController` | ✅ |
| Job sequence generation | `GenerateJobSequenceAction`, `ClearanceSequenceService` | ✅ (overflow unresolved) |
| Sequence Lookup API | `SequenceLookupApiController` | ✅ |
| Admin User list + role edit | `AdminUserController` | ✅ |
| Master Customers (read) | `MasterCustomerController` | ✅ |
| Master Branches (read-only) | `MasterDataController::branches` | ✅ |
| Master Units (read-only) | `MasterDataController::units` | ✅ |
| Master Statistics (read-only) | `MasterDataController::statistics` | ✅ |
| Master Licenses (read-only) | `MasterDataController::licenses` | ✅ |
| Dual-DB config (mysql + authdb) | `config/database.php` | ✅ |
| All 12 Eloquent models | `app/Models/Business/` | ✅ |
| Domain DTOs | `ClearanceRecordData` | ✅ |
| Automated test suite (real S3) | `DocumentStorageServiceTest`, `ClearanceExcelImportServiceTest`, `ClearanceSystemTest` | ✅ (1 overflow fail) |

---

## Priority Implementation Order

| Priority | MISS # | Feature | Effort |
|---|---|---|---|
| 🔴 P0 | MISS-04 | Fix sequence overflow bug | Small — 1 file |
| 🔴 P1 | MISS-01 | Record Replication (Duplicate CCD) | Medium — 3 files + route |
| 🔴 P1 | MISS-03 | File delete on S3 when replacing | Small — 1 method in UpdateAction + 1 route |
| 🟡 P2 | MISS-02 | Master Data write (create/edit) | Medium — 3 controller methods + views + routes |
| 🟡 P2 | MISS-05 | Outstanding attach-evidence form | Small — verify/fix view + controller stub |
| 🟢 P3 | MISS-06 | Customer name batch sync verify | Small — verify only |
| 🟢 P3 | MISS-07 | Sidebar nav completeness | Small — blade template |

