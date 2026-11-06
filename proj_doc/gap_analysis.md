# CCD System Rebuild — Gap Analysis

**Generated:** 2026-11-02  
**Reference Docs:** `reverse_engineering_analysis.md`, `ccd_system_rebuild_guide_blueprint.md`  
**Current Project:** `e:\Task\CCD\CCD_new_v\ccd_upload\`  
**Legacy Reference:** `e:\Task\CCD\html\`

---

## Summary Status

| Phase | Blueprint Phase | Status | Priority |
|---|---|---|---|
| Scaffolding & DB Config | Phase 1 | ✅ Complete | — |
| Auth & Domain Restriction | Phase 2 | ✅ Complete | — |
| CCD Core Grid & Lookup | Phase 3 | ✅ Complete | — |
| Job Sequence & Requisition (Excel Import) | Phase 4 | ❌ Missing | **P1 Critical** |
| Compliance & Auditing | Phase 5 | ⚠️ Partial | P2 |
| Reports & Master Admin | Phase 6 | ❌ Missing | **P1 Critical** |
| Security (Auth middleware, RBAC) | Cross-cutting | ❌ Missing | **P0 Urgent** |
| Domain Architecture (DTOs, Services, Queries) | Cross-cutting | ⚠️ Partial | P2 |

---

## GAP-01 — Security: Auth Middleware on Protected Routes (P0 🔴)

**Status:** ✅ COMPLETE — All clearance/compliance/admin/export routes are guarded behind `auth` middleware in `routes/web.php`. Unauthenticated requests redirect to `/login`.

**Required:** Every route group under `/clearance`, `/compliance`, `/admin`, `/export` must be wrapped with `auth` middleware.

**Fix:**
```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::resource('clearance', ClearanceGridController::class);
    Route::get('/compliance/outstanding', ...);
    Route::prefix('admin')->group(...);
    Route::prefix('export')->group(...);
});
```

---

## GAP-02 — Security: RoleAuthorizationMiddleware (P0 🔴)

**Status:** ✅ COMPLETE — Implemented `RoleAuthorizationMiddleware`, registered route alias `role` in `bootstrap/app.php`, and applied `role:administrator` to `/admin/*` routes. Non-admin access returns 403.

**Blueprint specifies:** `app/Http/Middleware/RoleAuthorizationMiddleware.php`

**Legacy RBAC roles (from reverse_engineering_analysis.md):**
- `administrator` — full access
- `manager` — read + compliance + export
- `staff` — read + upload only

**Fix:** Create middleware checking `auth()->user()->hasRole(...)`, register alias `role` in `bootstrap/app.php`, apply to admin-only routes.

---

## GAP-03 — Excel Import / Upload Module (P1 🔴)

**Status:** ✅ COMPLETE — Installed `maatwebsite/excel:^3.1`, built `SingleSheetImport`, `MultiSheetImport`, `ClearanceExcelImportService`, `ClearanceUploadController`, and `upload.blade.php`. Verified via automated end-to-end import test.

### Legacy Excel Cell Mapping (from `CustomerClearanceDataSingleImportFirstSheet.php`):

| Cell | Field |
|---|---|
| `N1` | `job_no` |
| `D4` | `customer_name` |
| `O2` | `master_branch_name` (branch_code) |
| `G10` | `product_description_en` |
| `G12` | `product_description_th` |
| `G8` | `product_code_1` |
| `F25` | `brand_name` |
| `G14` | `materials` |
| `G16` | `usages` |
| `G18`–`A23` | `function1`–`function5` (concat → `functions`) |
| `F24` | `country_of_origin` |
| `L5`,`H26`,`J28`–`J32` | `note1`–`note7` (concat → `note`) |
| `G37` | `hs_code` |
| `H37` | `sequence_code` |
| `J37` | `statistic_code` |
| `K37` | `quantity_unit` |
| `G38` | `privilege_code` |
| `N37` | `tariff_ad_valorem` |

### Validation rules from legacy:
- `customer_name` must `exists:master_customers,company_name` AND `is_enable=0`
- `master_branch_name` must `exists:master_branches,branch_code`
- Job No: prefix `CCD`/`NTB`/`CST` + `YYMM` + `-` + 4-digit seq, total 12 chars, unique
- `hs_code`: 8-digit numeric
- `statistic_code`: 3-digit numeric
- Product code duplicate check across `product_code_1` and `product_code_2`
- `functions` concat total length ≤ 300 chars
- `note` concat total length ≤ 500 chars

### Files to create:
- `app/Domain/Clearance/Services/ClearanceExcelImportService.php`
- `app/Imports/SingleSheetImport.php` (implements `WithMappedCells`, `ToModel`)
- `app/Imports/MultiSheetImport.php`
- `app/Http/Controllers/Clearance/ClearanceUploadController.php`
- `app/Http/Requests/BulkExcelUploadRequest.php`
- `resources/views/clearance/upload.blade.php`
- **Requires:** `composer require maatwebsite/excel:^3.1`

### Routes to add:
```
GET  /clearance/upload          → ClearanceUploadController@index
POST /clearance/import/single   → ClearanceUploadController@importSingle
POST /clearance/import/multi    → ClearanceUploadController@importMulti
```

### File upload (reference + hs_evidence):
- Single import: attach up to 2 reference files + 4 hs_evidence files
- Multi import: same files applied to all imported job_nos
- File naming: `{job_no}_rf1_{originalName}`, stored in `storage/app/public/reference/`
- Production: also sync to S3 via `DocumentStorageService`

---

## GAP-04 — Weekly HS Code Excel Export (P1 🔴)

**Status:** ✅ COMPLETE — Built `WeeklyHsExportService` (with dual-sheet Ranking + HS Record export), `WeeklyHsExportController`, `export.weekly` view, and routes. Verified via automated download tests.

**Legacy source:** `ExcelCreateController::ExportWeekklyHsCode`

**Filter parameters:**
- Job Category: CCD / CST / NTB (prefix of `job_no`)
- Date/Number range: from/to on `job_no` sequence

**Output:** `HS_Code{MM}-{From}_{MM}-{To}.xlsx`

### Files to create:
- `app/Domain/Clearance/Services/WeeklyHsExportService.php`
- `app/Http/Controllers/Export/WeeklyHsExportController.php`
- `resources/views/export/weekly.blade.php`
- **Requires:** `maatwebsite/excel:^3.1` (same as GAP-03)

### Routes to add:
```
GET  /export/weekly   → WeeklyHsExportController@index
POST /export/weekly   → WeeklyHsExportController@export
```

---

## GAP-05 — Clearance Edit/Update (P1 🔴)

**Status:** ✅ COMPLETE — Built `StoreClearanceRecordRequest`, `UpdateClearanceRecordAction`, `edit.blade.php`, and added `edit`/`update` endpoints to `ClearanceGridController`. Verified via automated tests.

**Blueprint reference:** `UpdateClearanceRecordAction.php` listed in directory structure.

### Files to create:
- `app/Domain/Clearance/Actions/UpdateClearanceRecordAction.php`
- `app/Http/Requests/StoreClearanceRecordRequest.php` (shared with create)
- `resources/views/clearance/edit.blade.php`

### Routes to add:
```
GET   /clearance/{id}/edit   → ClearanceGridController@edit
PUT   /clearance/{id}        → ClearanceGridController@update
```

---

## GAP-06 — Domain Architecture: Missing DTOs, Services, Queries (P2 🟡)

**Status:** RESOLVED ✅

### 6.1 — `app/Domain/Clearance/DTOs/ClearanceRecordData.php`
- Created typed DTO with `fromArray()`, `toArray()`, getters, and strict validation.
- Unit & Feature tested.

### 6.2 — `app/Domain/Clearance/Services/ClearanceSequenceService.php`
- Created dedicated sequence domain service wrapping `GenerateJobSequenceAction`.
- Injected into `ClearanceCreateController` and `SequenceLookupApiController`.

### 6.3 — `app/Domain/Compliance/Queries/GetOutstandingClearanceQuery.php`
- Extracted outstanding compliance query logic into dedicated query object with customer filtering and branch eager loading.
- Injected into `OutstandingAuditController`.

### 6.4 — `app/Domain/MasterData/Services/BatchCustomerNameSyncService.php`
- Extracted batch customer name sync across `customer_clearance_data` into dedicated transaction-safe service.
- Injected into `MasterCustomerController`.

---

## GAP-07 — DocumentStorageService (P2 🟡)

**Status:** MISSING — `app/Services/Storage/DocumentStorageService.php` does not exist.

**Required behavior:**
- Local: store file to `storage/app/public/{directory}/{filename}`
- Production: also sync to S3 via `Storage::disk('s3')->putFileAs(...)`
- Requires: `composer require league/flysystem-aws-s3-v3:^3.0`

---

## GAP-08 — AdminUserController (P2 🟡)

**Status:** RESOLVED ✅

**Blueprint specifies:** `app/Http/Controllers/Admin/AdminUserController.php`

### Files created:
- `app/Http/Controllers/Admin/AdminUserController.php` — `index`, `show`, `updateRoles` actions
- `resources/views/admin/users/index.blade.php` — paginated directory with search + role filter
- `resources/views/admin/users/show.blade.php` — profile detail + RBAC role assignment form

### Routes added (inside `role:administrator` middleware group):
```
GET  /admin/users            → AdminUserController@index  (admin.users.index)
GET  /admin/users/{id}       → AdminUserController@show   (admin.users.show)
PUT  /admin/users/{id}/roles → AdminUserController@updateRoles (admin.users.roles.update)
```

### Sidebar:
- "User Management" link added to `layouts/app.blade.php` inside `@if isAdmin()` block.

### Tests:
- `test_admin_users_index_renders_for_admin` ✅
- `test_non_admin_cannot_access_user_management` ✅
- `test_admin_users_show_renders_for_admin` ✅

---

## GAP-09 — MasterDataController (P3 🟢)

**Status:** MISSING — Only `MasterCustomerController` exists. No UI for branches, units, statistics, licenses.

**Blueprint specifies:** `app/Http/Controllers/Admin/MasterDataController.php`

### Views to create:
- `resources/views/admin/master/branches.blade.php`
- `resources/views/admin/master/units.blade.php`
- `resources/views/admin/master/statistics.blade.php`
- `resources/views/admin/master/licenses.blade.php`

---

## GAP-10 — Form Request Classes (P2 🟡)

**Status:** MISSING — All validation is inline in controllers.

**Blueprint specifies:**
- `app/Http/Requests/StoreClearanceRecordRequest.php`
- `app/Http/Requests/BulkExcelUploadRequest.php`
- `app/Http/Requests/MasterCustomerRequest.php`

---

## GAP-11 — `CreateClearanceRecordAction` (P2 🟡)

**Status:** MISSING — Blueprint specifies `app/Domain/Clearance/Actions/CreateClearanceRecordAction.php`.  
Currently the create logic is inline in `ClearanceCreateController`.

---

## GAP-12 — Storage Public Link (P3 🟢)

**Status:** NOT RUN — `php artisan storage:link` has not been executed.  
Required for serving files from `storage/app/public/` via web URL.

---

## GAP-13 — Sidebar Navigation Links (P2 🟡)

**Status:** Current `layouts/app.blade.php` sidebar is missing links for:
- Excel Requisition Ingestion → `route('clearance.upload')`
- Weekly HS Exports → `route('export.weekly')`
- User Management → `route('admin.users.index')` (admin-only)

Blueprint layout (Section 7.2) shows exact sidebar structure with all 5 nav links.

---

## Composer Packages Required

| Package | Version | Status | Required For |
|---|---|---|---|
| `maatwebsite/excel` | `^3.1` | ❌ Not installed | GAP-03, GAP-04 |
| `league/flysystem-aws-s3-v3` | `^3.0` | ❌ Not installed | GAP-07 |

**Install command:**
```bash
composer require maatwebsite/excel:^3.1 league/flysystem-aws-s3-v3:^3.0
```
> Use `BypassSandbox: true` in this environment.

---

## What IS Already Built (Complete)

| Feature | Status |
|---|---|
| Dual-DB config (mysql + authdb) | ✅ |
| All 12 Eloquent models | ✅ |
| Auth login/register/logout | ✅ |
| Domain email whitelist middleware | ✅ |
| CCD grid with filters + pagination | ✅ |
| CCD detail view (show) | ✅ |
| Manual CCD create + auto-sequence | ✅ |
| Outstanding compliance audit view | ✅ |
| Sequence Lookup API (`/api/ccd_get_ym`) | ✅ |
| Customer registry (read-only) | ✅ |
| 7 automated tests passing | ✅ |
| Tailwind CDN + Alpine.js CDN layout | ✅ |

---

## Priority Order for Implementation

| Order | GAP | Description |
|---|---|---|
| 1 | GAP-01 | Add `auth` middleware to all protected routes |
| 2 | GAP-02 | Create `RoleAuthorizationMiddleware` |
| 3 | GAP-03 | Excel Import (install composer + create service + controller + view) |
| 4 | GAP-05 | Clearance Edit/Update |
| 5 | GAP-04 | Weekly HS Export |
| 6 | GAP-10 | Form Request classes |
| 7 | GAP-11 | `CreateClearanceRecordAction` + `UpdateClearanceRecordAction` |
| 8 | GAP-06 | Domain DTOs, Services, Queries |
| 9 | GAP-07 | `DocumentStorageService` |
| 10 | GAP-08 | `AdminUserController` + user list view |
| 11 | GAP-13 | Update sidebar nav links |
| 12 | GAP-12 | Run `storage:link` |
| 13 | GAP-09 | `MasterDataController` + 4 master views |

