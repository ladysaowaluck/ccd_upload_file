# CCD Database System — Redesign Architecture & Modification Plan

**Date:** 2026-11-05  
**Based on:** `reverse_engineering_analysis.md` (Legacy: `E:\Task\CCD\backup\html`)  
**Target Project:** `E:\Task\CCD\CCD_new_v\ccd_upload`  
**Guiding Principle:** Simple and clean code, easy to read, strict SOLID compliance without over-engineering.

---

## 1. Executive Summary & Design Philosophy

The legacy CCD system (`E:\Task\CCD\backup\html`) was tightly coupled to the `encore/laravel-admin` admin scaffolding package. While it served its purpose initially, it introduced severe maintainability bottlenecks:
1. Massive "god controllers" (e.g. `CustomerClearanceDataController.php` with >1,500 lines combining grid UI, form rendering, import routines, validation, and file saving).
2. Tightly bound HTML and grid rendering logic hidden inside vendor callbacks.
3. Brittle file handling that stored documents locally in `public/storage/` and tried to sync to S3 as a secondary side-effect.

### The Redesign Philosophy
We redesign the application with a **practical, readable, and SOLID-compliant architecture**:
- **No Over-Engineering:** No unnecessary CQRS message buses, heavy repositories, or artificial abstraction layers. Simple Laravel controllers that delegate to focused **Domain Actions** and **Services**.
- **Clean Separation of Concerns:** Business logic lives in `app/Domain/`, file operations are isolated in `DocumentStorageService`, HTTP routing & validation live in standard Controllers and Form Requests.
- **Strict Invariants Preserved:**
  - Zero destructive database operations (never run `migrate:fresh` or wipe tables).
  - Dual-database architecture preserved (`mysql` for business records, `authdb` for identity/RBAC).
  - Corporate email whitelist (`@th.nissin-asia.com`).
  - S3-only cloud storage (all uploads stream directly to AWS S3, streaming downloads).

---

## 2. SOLID Architecture Breakdown

| SOLID Principle | Practical Application in CCD Redesign | Example in Codebase |
|---|---|---|
| **S — Single Responsibility** | Controllers only handle HTTP request/response flow. Dedicated Actions encapsulate distinct business operations (creating, updating, replicating, sequencing). | `CreateClearanceRecordAction` only creates records and formats filenames; `ClearanceGridController` only handles grid requests. |
| **O — Open / Closed** | New workflows (like Record Replication or new Excel templates) are added as new Actions or Imports without modifying existing grid or update logic. | Adding `ReplicateClearanceRecordAction` did not touch or modify `CreateClearanceRecordAction`. |
| **L — Liskov Substitution** | Models and storage abstractions adhere to standard contracts and Eloquent behavior without breaking polymorphic expectations. | `DocumentStorageService` provides a clean contract for S3 operations whether in testing or production. |
| **I — Interface Segregation** | Form validation is broken into focused Form Requests rather than one monolithic validator. Clients depend only on what they need. | `StoreClearanceRecordRequest`, `BulkExcelUploadRequest`. |
| **D — Dependency Inversion** | Controllers depend on Action and Service abstractions injected via Laravel's service container, avoiding hardcoded static instantiation or direct driver coupling. | Injected `DocumentStorageService $storageService` in Action and Controller constructors. |

---

## 3. Core Modules: Legacy vs. Redesigned State

### Module 1: Product Registration (CCD Entry)
* **Legacy (`reverse_engineering_analysis.md`):** Monolithic controller handled manual form, single Excel import (`hs_requisition_form_original.xlsx`), and multi-record Excel import (`hs_multi_original.xlsx`).
* **Redesign:**
  - Manual creation: `ClearanceCreateController` + `CreateClearanceRecordAction`.
  - Excel ingestion: `ClearanceUploadController` + `ClearanceExcelImportService` + `SingleSheetImport` / `MultiSheetImport` (via `maatwebsite/excel`).
  - Filename sanitization and technical prefix stacking prevention handled automatically.

### Module 2: Database Search & Management (CCD Grid)
* **Legacy:** `CustomerClearanceDataController` rendered `encore/admin` HTML grid with sortable columns, modal dialogs, and asset links.
* **Redesign:**
  - `ClearanceGridController`: Filterable query supporting Job No, HS Code, Customer, Branch, Import/Export flag, and full-text keyword search across Thai and English descriptions.
  - Detail View: Clean Tailwind CSS show screen with clear document download badges and compliance status.
  - Streaming Download: `downloadDocument()` streaming files directly from S3 via `DocumentStorageService::download()` (bypassing public webroot exposure).
  - Record Replication: `ClearanceReplicateController` + `ReplicateClearanceRecordAction` (copies tariff fields, leaves file slots clean for fresh evidence).

### Module 3: Compliance & Outstanding Monitor
* **Legacy:** `CustomerClearanceOutStandingController` filtered where `hs_evidence_file_1..3` were null.
* **Redesign:**
  - Query Object: `GetOutstandingClearanceQuery` isolates compliance query logic.
  - Audit Controller: `OutstandingAuditController` renders pending verification records.
  - Quick Attachment: Row-level modal allows direct file upload to specific evidence slots without navigating away from the compliance dashboard.

### Module 4: Master Data Governance
* **Legacy:** Admin grids for `master_customers`, `master_branches`, `master_units`, `master_statistics`, `master_licenses`.
* **Redesign:**
  - `MasterCustomerController`: Customer directory + `BatchCustomerNameSyncService` for harmonizing historical company name changes.
  - `MasterDataController`: Read-only views for branches, units, statistics, and licenses.
  - Planned: Lightweight create/edit forms for licenses, units, and statistics under `role:administrator`.

### Module 5: Reporting, APIs & Cloud Storage
* **Legacy:** `ExcelCreateController::ExportWeekklyHsCode` generated weekly reports by category and sequence range.
* **Redesign:**
  - `WeeklyHsExportService` + `WeeklyHsExportController`: Exports multi-sheet `.xlsx` files with category filtering (`CCD`, `CST`, `NTB`) and customer rankings.
  - Sequence Generator: `GenerateJobSequenceAction` + `SequenceLookupApiController` with auto-padded zero formatting and sequence overflow protection.
  - Cloud Storage: `DocumentStorageService` streaming directly to AWS S3 bucket `sn-ccd-database-system-th` in `ap-southeast-7`.

### Module 6: Identity, Security & RBAC
* **Legacy:** Multi-guard model (`web` and `admin`), whitelisting `@th.nissin-asia.com`.
* **Redesign:**
  - `EnsureDomainWhitelisted` middleware on registration.
  - `RoleAuthorizationMiddleware` enforcing `role:administrator` on all `/admin/*` routes.
  - `auth` middleware guarding all operational `/clearance`, `/compliance`, `/admin`, and `/export` endpoints.

---

## 4. Plan to Modify Present Project (Action Roadmap)

```
+-----------------------------------------------------------------------------------------+
|                              IMPLEMENTATION ROADMAP                                     |
+-----------------------------------------------------------------------------------------+
| PHASE 1: STABILITY & CORE BUG FIXES (DONE)                                              |
| - Cap sequence counter at 9999 to maintain 12-char job number standard.                  |
| - Delete existing S3 object before storing replacement files to prevent bucket clutter.  |
| - Verify real AWS S3 connectivity without using Storage::fake().                        |
+-----------------------------------------------------------------------------------------+
| PHASE 2: MISSING FUNCTIONALITY RESTORATION (IN PROGRESS)                                |
| - ReplicateClearanceRecordAction + Controller + Blade View (Completed).                 |
| - Add master data write forms (Licenses, Statistics, Units) for administrators.         |
| - Complete navigation links in layouts/app.blade.php.                                   |
+-----------------------------------------------------------------------------------------+
| PHASE 3: QUALITY & SOLID VERIFICATION                                                   |
| - Run automated test suite with DatabaseTransactions on dual DBs.                       |
| - Verify compliance with Gemini : and Lady : coding directives.                         |
+-----------------------------------------------------------------------------------------+
```

### Detailed Step-by-Step Task Breakdown

#### Step 1: Sequence Overflow Guard (Completed)
- **File:** `app/Domain/Clearance/Actions/GenerateJobSequenceAction.php`
- **Change:** Added check `$lastSequence >= 9999` throwing `\OverflowException`. Prevents invalid 13+ character job numbers (e.g. `CCD2611-10000`).

#### Step 2: S3 Replace & Cleanup (Completed)
- **File:** `app/Domain/Clearance/Actions/UpdateClearanceRecordAction.php`
- **Change:** Before calling `$storageService->store()`, invoke `$storageService->delete($record->$field)` so replaced files do not leave orphaned S3 objects.

#### Step 3: Record Replication Workflow (Completed)
- **Files Created:**
  - `app/Domain/Clearance/Actions/ReplicateClearanceRecordAction.php`
  - `app/Http/Controllers/Clearance/ClearanceReplicateController.php`
  - `resources/views/clearance/replicate.blade.php`
- **Routes:** `GET /clearance/{id}/replicate` and `POST /clearance/{id}/replicate`.
- **UI:** Added "Duplicate as New CCD" button in `resources/views/clearance/show.blade.php`.

#### Step 4: Master Data CRUD UI (Next Step)
- Add create/edit/update methods in `MasterDataController.php` for:
  - Customs Licenses (`master_licenses`)
  - Statistical Codes (`master_statistics`)
  - Measurement Units (`master_units`)
- Restrict to `role:administrator` to preserve master data integrity.

#### Step 5: Admin Navigation Sidebar Polish (Next Step)
- Update `resources/views/layouts/app.blade.php` to include direct links to all master entities.

---

## 5. Architectural Directory Map

```
ccd_upload/
├── app/
│   ├── Domain/
│   │   ├── Clearance/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateClearanceRecordAction.php       # Single creation & file naming
│   │   │   │   ├── UpdateClearanceRecordAction.php       # Update with S3 delete-before-replace
│   │   │   │   ├── GenerateJobSequenceAction.php         # Next sequence with overflow guard
│   │   │   │   └── ReplicateClearanceRecordAction.php    # Clean tariff duplication
│   │   │   ├── DTOs/
│   │   │   │   └── ClearanceRecordData.php               # Strongly typed data transfer object
│   │   │   └── Services/
│   │   │       ├── ClearanceSequenceService.php          # Sequence orchestration
│   │   │       ├── ClearanceExcelImportService.php       # Single & batch Excel processing
│   │   │       └── WeeklyHsExportService.php             # Multi-sheet HS report generator
│   │   ├── Compliance/
│   │   │   └── Queries/
│   │   │       └── GetOutstandingClearanceQuery.php      # Missing evidence query object
│   │   └── MasterData/
│   │       └── Services/
│   │           └── BatchCustomerNameSyncService.php      # Bulk customer name harmonization
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Clearance/
│   │   │   │   ├── ClearanceGridController.php           # Grid, search, show, edit, download
│   │   │   │   ├── ClearanceCreateController.php         # Manual registration form
│   │   │   │   ├── ClearanceUploadController.php         # Excel requisition ingestion
│   │   │   │   ├── OutstandingAuditController.php        # Compliance audit & quick attach
│   │   │   │   └── ClearanceReplicateController.php      # Record duplication
│   │   │   ├── Admin/
│   │   │   │   ├── AdminUserController.php               # RBAC user management
│   │   │   │   ├── MasterCustomerController.php          # Customer registry & name sync
│   │   │   │   └── MasterDataController.php              # Branches, units, stats, licenses
│   │   │   └── Export/
│   │   │       └── WeeklyHsExportController.php          # Weekly Excel download endpoint
│   │   └── Middleware/
│   │       ├── EnsureDomainWhitelisted.php               # @th.nissin-asia.com gate
│   │       └── RoleAuthorizationMiddleware.php           # RBAC role verification
│   └── Services/
│       └── Storage/
│           └── DocumentStorageService.php                # Direct AWS S3 streaming engine
```

