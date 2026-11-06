# CCD System — Redesign Plan

**Date:** 2026-11-05  
**Based on:** `reverse_engineering_analysis.md` + `missing_functions_analysis.md`  
**Current Project:** `e:\Task\CCD\CCD_new_v\ccd_upload`  
**Rule:** No destructive migrations. No local disk storage. Simple & clean code.

---

## What We Are Fixing

The current project is **~80% complete**. We are NOT starting over.  
We are adding the missing 20% on top of what already works.

```
CURRENT STATE                        TARGET STATE
─────────────────────────────────    ────────────────────────────────────
✅ Auth + Domain whitelist           ✅ (keep)
✅ CCD Grid + Search                 ✅ (keep)
✅ CCD Create (manual)               ✅ (keep)
✅ CCD Edit + Update                 ✅ + Fix: delete old S3 file on replace
✅ Excel Import (single + multi)     ✅ (keep)
✅ S3-only file storage              ✅ (keep)
✅ S3 download stream                ✅ (keep)
✅ Outstanding audit view            ✅ + Fix: wire up attach-evidence form
✅ Weekly HS Export                  ✅ (keep)
✅ Sequence API                      ✅ + Fix: overflow guard at 9999
✅ Admin User management             ✅ (keep)
✅ Master data read-only views       ✅ + Add: create/edit forms
❌ Record Replication (Duplicate)    ADD: ReplicateClearanceRecordAction
❌ File delete on S3 when replacing  FIX: UpdateClearanceRecordAction
❌ Sequence overflow guard           FIX: GenerateJobSequenceAction
❌ Master data write (create/edit)   ADD: MasterDataController methods
❌ Outstanding attach-evidence form  FIX: OutstandingAuditController stub
```

---

## Change Plan — 5 Tasks

Each task lists: **what file to touch**, **what to add/change**, **why**.

---

### TASK 1 — Fix Sequence Overflow (BUG — 30 min)

**File:** [`app/Domain/Clearance/Actions/GenerateJobSequenceAction.php`](file:///e:/Task/CCD/CCD_new_v/ccd_upload/app/Domain/Clearance/Actions/GenerateJobSequenceAction.php)

**Problem:** When seq > 9999, `str_pad` returns 5+ chars → job_no becomes `CCD2611-10000` (13 chars, not 12). This breaks the format and fails the unit test.

**Fix:** Cap at 9999 and throw `OverflowException`. Match original system behavior.

```php
// CHANGE in GenerateJobSequenceAction::execute()
// After: $lastSequence = ...

if ($lastSequence >= 9999) {
    throw new \OverflowException(
        "Monthly CCD sequence limit reached (9999) for period {$yymm}. Contact IT."
    );
}
$nextSequence = str_pad((string)($lastSequence + 1), 4, '0', STR_PAD_LEFT);
```

**Test fix** (`tests/Feature/ClearanceSystemTest.php`):  
The test that seeds 10000+ records must be changed to assert the OverflowException is thrown instead of asserting a 5-digit format.

---

### TASK 2 — Delete Old S3 File When Replacing (15 min)

**File:** [`app/Domain/Clearance/Actions/UpdateClearanceRecordAction.php`](file:///e:/Task/CCD/CCD_new_v/ccd_upload/app/Domain/Clearance/Actions/UpdateClearanceRecordAction.php)

**Problem:** When a user uploads a new file for a slot already occupied, the old S3 object is never deleted. Orphaned files accumulate in the bucket forever.

**Fix:** Before storing the new file, delete the existing S3 key from the record.

Add this block inside each file loop **before** `$this->storageService->store(...)`:

```php
// Gemini : Delete old S3 object before storing replacement to avoid orphaned files
if (!empty($record->$refField)) {
    $this->storageService->delete($record->$refField);
}
```

> `DocumentStorageService::delete($s3Key)` already exists — just call it.

**Files touched:** `UpdateClearanceRecordAction.php` only.  
**No new files needed.**

---

### TASK 3 — Wire Up Outstanding Evidence Attach Form (1 hour)

**Files:**
- [`app/Http/Controllers/Clearance/OutstandingAuditController.php`](file:///e:/Task/CCD/CCD_new_v/ccd_upload/app/Http/Controllers/Clearance/OutstandingAuditController.php)
- [`resources/views/clearance/outstanding.blade.php`](file:///e:/Task/CCD/CCD_new_v/ccd_upload/resources/views/clearance/outstanding.blade.php)

**Problem:** Route `POST /compliance/outstanding/{id}/attach` exists but `attachEvidence()` is a stub. The view may not have the file input wired up.

**Fix in controller** — implement `attachEvidence()`:

```php
// Gemini : Attach hs_evidence_file_1 directly from outstanding list without opening edit form
public function attachEvidence(Request $request, int $id, DocumentStorageService $storage): RedirectResponse
{
    $record = CustomerClearanceData::findOrFail($id);

    $request->validate([
        'hs_evidence_file_1' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,xlsx', 'max:20480'],
    ]);

    $file     = $request->file('hs_evidence_file_1');
    $baseName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
    $fileName = "{$record->job_no}_hs_evidence_file_1_{$baseName}";
    $s3Key    = $storage->store($file, 'hs_evidence', $fileName);

    $record->update(['hs_evidence_file_1' => $s3Key]);

    return redirect()->route('compliance.outstanding')
        ->with('success', "Evidence attached to {$record->job_no}.");
}
```

**Fix in view** — add per-row upload form:

```html
{{-- Each record row in outstanding.blade.php --}}
<form method="POST"
      action="{{ route('compliance.outstanding.attach', $record->id) }}"
      enctype="multipart/form-data"
      class="flex items-center gap-2 mt-1">
    @csrf
    <input type="file" name="hs_evidence_file_1"
           accept=".pdf,.jpg,.jpeg,.png,.xlsx"
           class="text-xs text-slate-600">
    <button type="submit"
            class="text-xs bg-brand-600 text-white px-2 py-1 rounded hover:bg-brand-700">
        Attach
    </button>
</form>
```

---

### TASK 4 — Record Replication / Duplicate CCD (3 hours)

**New files to create:**

| File | Purpose |
|---|---|
| `app/Domain/Clearance/Actions/ReplicateClearanceRecordAction.php` | Domain logic — copy fields, blank files |
| `app/Http/Controllers/Clearance/ClearanceReplicateController.php` | Show form + execute |
| `resources/views/clearance/replicate.blade.php` | Simple form: new job no + optional product codes |

**Routes to add** in `routes/web.php` inside `auth` group:

```php
// Gemini : MISS-01 — Record replication — copy tariff classification to a new job no
Route::get('/clearance/{id}/replicate',  [ClearanceReplicateController::class, 'show'])->name('clearance.replicate.show');
Route::post('/clearance/{id}/replicate', [ClearanceReplicateController::class, 'execute'])->name('clearance.replicate.execute');
```

**Action class (simple, clean):**

```php
// app/Domain/Clearance/Actions/ReplicateClearanceRecordAction.php

namespace App\Domain\Clearance\Actions;

use App\Models\Business\CustomerClearanceData;
use Carbon\Carbon;

class ReplicateClearanceRecordAction
{
    // Fields intentionally NOT copied (file attachments stay with original record)
    private const SKIP_FIELDS = [
        'id', 'job_no', 'product_code_1', 'product_code_2',
        'reference_file_1', 'reference_file_2',
        'hs_evidence_file_1', 'hs_evidence_file_2',
        'hs_evidence_file_3', 'hs_evidence_file_4',
        'created_at', 'updated_at', 'create_date', 'update_date', 'edit_user_id',
    ];

    public function execute(
        CustomerClearanceData $source,
        string $newJobNo,
        ?string $newCode1,
        ?string $newCode2
    ): CustomerClearanceData {

        // Copy all tariff/classification fields except files and IDs
        $data = $source->toArray();
        foreach (self::SKIP_FIELDS as $skip) {
            unset($data[$skip]);
        }

        $data['job_no']          = $newJobNo;
        $data['product_code_1']  = $newCode1 ?: null;
        $data['product_code_2']  = $newCode2 ?: null;
        $data['create_date']     = Carbon::now()->toDateString();
        $data['update_date']     = Carbon::now()->toDateString();
        $data['edit_user_id']    = auth()->id() ?: 1;

        return CustomerClearanceData::create($data);
    }
}
```

**Controller (thin, delegates to action):**

```php
// app/Http/Controllers/Clearance/ClearanceReplicateController.php

namespace App\Http\Controllers\Clearance;

use App\Domain\Clearance\Actions\GenerateJobSequenceAction;
use App\Domain\Clearance\Actions\ReplicateClearanceRecordAction;
use App\Http\Controllers\Controller;
use App\Models\Business\CustomerClearanceData;
use Illuminate\Http\Request;

class ClearanceReplicateController extends Controller
{
    public function show(int $id, GenerateJobSequenceAction $sequence)
    {
        $source     = CustomerClearanceData::findOrFail($id);
        $suggestedNo = $sequence->execute('CCD');  // suggest next seq for this month

        return view('clearance.replicate', compact('source', 'suggestedNo'));
    }

    public function execute(Request $request, int $id, ReplicateClearanceRecordAction $action)
    {
        $source = CustomerClearanceData::findOrFail($id);

        $request->validate([
            'new_job_no' => [
                'required',
                'regex:/^(CCD|CST|NTB)\d{4}-\d{4}$/',
                'unique:customer_clearance_data,job_no',
            ],
            'new_product_code_1' => ['nullable', 'string', 'max:50'],
            'new_product_code_2' => ['nullable', 'string', 'max:50'],
        ]);

        $newRecord = $action->execute(
            $source,
            $request->input('new_job_no'),
            $request->input('new_product_code_1'),
            $request->input('new_product_code_2'),
        );

        return redirect()
            ->route('clearance.edit', $newRecord->id)
            ->with('success', "Record replicated as {$newRecord->job_no}. Please verify and add evidence files.");
    }
}
```

**Add "Replicate" button** to `clearance/show.blade.php` and `clearance/index.blade.php`:

```html
{{-- In show.blade.php action bar --}}
<a href="{{ route('clearance.replicate.show', $record->id) }}"
   class="text-xs bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1.5 rounded-lg hover:bg-amber-100">
    Duplicate as New CCD
</a>
```

---

### TASK 5 — Master Data Write UI (Create / Edit) (2 hours)

**Files to modify:**
- [`app/Http/Controllers/Admin/MasterDataController.php`](file:///e:/Task/CCD/CCD_new_v/ccd_upload/app/Http/Controllers/Admin/MasterDataController.php) — add store/edit/update methods
- [`routes/web.php`](file:///e:/Task/CCD/CCD_new_v/ccd_upload/routes/web.php) — add write routes under `role:administrator`

**Files to create:**
- `resources/views/admin/master/form.blade.php` — single reusable form partial for all 3 master types

**Routes to add** (inside `role:administrator` group):

```php
// Gemini : MISS-02 — Master data write routes (create/edit/update)
// Licenses
Route::get('/master/licenses/create',   [MasterDataController::class, 'createLicense'])->name('admin.master.licenses.create');
Route::post('/master/licenses',         [MasterDataController::class, 'storeLicense'])->name('admin.master.licenses.store');
Route::get('/master/licenses/{id}/edit',[MasterDataController::class, 'editLicense'])->name('admin.master.licenses.edit');
Route::put('/master/licenses/{id}',     [MasterDataController::class, 'updateLicense'])->name('admin.master.licenses.update');

// Statistics
Route::get('/master/statistics/create',    [MasterDataController::class, 'createStatistic'])->name('admin.master.statistics.create');
Route::post('/master/statistics',          [MasterDataController::class, 'storeStatistic'])->name('admin.master.statistics.store');
Route::get('/master/statistics/{id}/edit', [MasterDataController::class, 'editStatistic'])->name('admin.master.statistics.edit');
Route::put('/master/statistics/{id}',      [MasterDataController::class, 'updateStatistic'])->name('admin.master.statistics.update');

// Units
Route::get('/master/units/create',    [MasterDataController::class, 'createUnit'])->name('admin.master.units.create');
Route::post('/master/units',          [MasterDataController::class, 'storeUnit'])->name('admin.master.units.store');
Route::get('/master/units/{id}/edit', [MasterDataController::class, 'editUnit'])->name('admin.master.units.edit');
Route::put('/master/units/{id}',      [MasterDataController::class, 'updateUnit'])->name('admin.master.units.update');
```

**Methods to add to MasterDataController** (pattern is identical for all 3 entities — keep it simple):

```php
// Gemini : MISS-02 — License create/edit/update (same pattern for statistics and units)
public function createLicense(): View
{
    return view('admin.master.licenses', ['licenses' => MasterLicense::orderBy('license_code')->paginate(20), 'editing' => null]);
}

public function storeLicense(Request $request): RedirectResponse
{
    $request->validate(['license_code' => 'required|max:10', 'license_name' => 'required|max:100']);
    MasterLicense::create($request->only('license_code', 'license_name', 'license_th', 'license_en'));
    return redirect()->route('admin.master.licenses')->with('success', 'License created.');
}

public function editLicense(int $id): View
{
    $editing  = MasterLicense::findOrFail($id);
    $licenses = MasterLicense::orderBy('license_code')->paginate(20);
    return view('admin.master.licenses', compact('licenses', 'editing'));
}

public function updateLicense(Request $request, int $id): RedirectResponse
{
    $request->validate(['license_code' => 'required|max:10', 'license_name' => 'required|max:100']);
    MasterLicense::findOrFail($id)->update($request->only('license_code', 'license_name', 'license_th', 'license_en'));
    return redirect()->route('admin.master.licenses')->with('success', 'License updated.');
}
```

> Same pattern repeated for statistics (`statistics` field) and units (`unit` field).

---

## Sidebar — Add Missing Links

**File:** `resources/views/layouts/app.blade.php`

Add the master data section links inside the `@if(auth()->user()->isAdmin())` block:

```html
{{-- Gemini : MISS-07 — Add master data management links to admin sidebar --}}
<a href="{{ route('admin.master.branches') }}"   class="...">Branches</a>
<a href="{{ route('admin.master.units') }}"      class="...">Quantity Units</a>
<a href="{{ route('admin.master.statistics') }}" class="...">Statistics Codes</a>
<a href="{{ route('admin.master.licenses') }}"   class="...">Import Licenses</a>
```

---

## Implementation Order

Do these in order — each one is independent and testable on its own.

| Order | Task | Files Changed | Time Est. |
|---|---|---|---|
| 1 | Fix sequence overflow | `GenerateJobSequenceAction.php` + test | 30 min |
| 2 | Delete old S3 on replace | `UpdateClearanceRecordAction.php` | 15 min |
| 3 | Wire outstanding attach form | `OutstandingAuditController.php` + `outstanding.blade.php` | 1 hr |
| 4 | Record replication | 2 new files + routes + 2 view edits | 3 hr |
| 5 | Master data write UI | `MasterDataController.php` + routes + existing views | 2 hr |
| 6 | Sidebar nav links | `layouts/app.blade.php` only | 15 min |

**Total estimated: ~7 hours of focused work.**

---

## What We Are NOT Changing

These files stay exactly as they are — do not touch:

| File | Reason |
|---|---|
| `DocumentStorageService.php` | S3 upload/download/delete works correctly |
| `ClearanceExcelImportService.php` | Excel import tested and working |
| `WeeklyHsExportService.php` | Export tested and working |
| `ClearanceGridController.php` | Grid, show, edit, update, download all working |
| `ClearanceCreateController.php` | Create + sequence working |
| All migrations | Zero destructive migrations rule |
| All models in `app/Models/` | Schema invariant — never alter |
| `config/database.php` | Dual-DB config correct |
| `config/auth.php` | Auth on `authdb` correct |

