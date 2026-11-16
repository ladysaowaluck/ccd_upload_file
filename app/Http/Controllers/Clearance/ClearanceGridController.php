<?php

namespace App\Http\Controllers\Clearance;

use App\Domain\Clearance\Actions\UpdateClearanceRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClearanceRecordRequest;
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterBranch;
use App\Models\Business\MasterCustomer;
use App\Models\Business\MasterLicense;
use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;
use App\Services\Storage\DocumentStorageService;
use App\Services\System\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClearanceGridController extends Controller
{
    public function index(Request $request, AnnouncementService $announcementService): View
    {
        $query = CustomerClearanceData::with(['customer', 'branch']);

        if ($request->filled('job_no')) {
            $query->where('job_no', 'LIKE', '%' . trim((string) $request->input('job_no')) . '%');
        }

        if ($request->filled('hs_code')) {
            $query->where('hs_code', 'LIKE', trim((string) $request->input('hs_code')) . '%');
        }

        if ($request->filled('customer_name')) {
            $customerTerm = trim((string) $request->input('customer_name'));
            $query->where('customer_name', 'LIKE', '%' . $customerTerm . '%');
        }

        if ($request->filled('branch_id')) {
            $query->where('master_branch_id', $request->input('branch_id'));
        }

        if ($request->filled('imp_exp') && in_array($request->input('imp_exp'), ['0', '1'], true)) {
            $query->where('imp_exp', (int) $request->input('imp_exp'));
        }

        if ($request->filled('keyword')) {
            $term = trim((string) $request->input('keyword'));
            $query->where(function ($sub) use ($term) {
                $sub->where('product_description_en', 'LIKE', '%' . $term . '%')
                    ->orWhere('product_description_th', 'LIKE', '%' . $term . '%')
                    ->orWhere('product_code_1', 'LIKE', '%' . $term . '%');
            });
        }

        $records  = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();
        $branches = MasterBranch::orderBy('branch_name')->get();

        // Dashboard Summary Statistics
        $totalRecords         = CustomerClearanceData::count();
        $missingEvidenceCount = CustomerClearanceData::where(function ($q) {
            $q->whereNull('hs_evidence_file_1')->orWhere('hs_evidence_file_1', '');
        })->count();
        $verifiedCount        = max(0, $totalRecords - $missingEvidenceCount);
        $activeCustomersCount = MasterCustomer::where('is_enable', 0)->count();

        $dashboardStats = [
            'total'     => $totalRecords,
            'verified'  => $verifiedCount,
            'missing'   => $missingEvidenceCount,
            'customers' => $activeCustomersCount,
        ];

        $announcement = $announcementService->get();

        return view('clearance.index', compact('records', 'branches', 'dashboardStats', 'announcement'));
    }

    public function show(int $id): View
    {
        $record = CustomerClearanceData::with(['customer', 'branch'])->findOrFail($id);
        return view('clearance.show', compact('record'));
    }

    public function edit(int $id): View
    {
        $record = CustomerClearanceData::with(['customer', 'branch'])->findOrFail($id);
        $branches = MasterBranch::orderBy('branch_name')->get();
        $customers = MasterCustomer::where('is_enable', 0)->orderBy('company_name')->get();
        $units = MasterUnit::orderBy('unit')->get();
        $statistics = MasterStatistic::orderBy('statistics')->get();
        $licenses = MasterLicense::orderBy('license_en')->get();

        return view('clearance.edit', compact('record', 'branches', 'customers', 'units', 'statistics', 'licenses'));
    }

    public function update(
        StoreClearanceRecordRequest $request,
        int $id,
        UpdateClearanceRecordAction $action,
        \App\Services\Storage\DocumentStorageService $storageService
    ): RedirectResponse {
        $record = CustomerClearanceData::findOrFail($id);
        $action->execute($record, $request->all());

        $uploadHistory = $storageService->getUploadHistory();
        $redirect = redirect()->route('clearance.show', $record->id)->with('success', "Clearance record {$record->job_no} updated successfully.");
        if (!empty($uploadHistory)) {
            $redirect->with('upload_debug', $uploadHistory);
        }

        return $redirect;
    }

    public function downloadDocument(
        int $id,
        string $field,
        \App\Services\Storage\DocumentStorageService $storageService
    ) {
        $allowedFields = [
            'reference_file_1',
            'reference_file_2',
            'hs_evidence_file_1',
            'hs_evidence_file_2',
            'hs_evidence_file_3',
            'hs_evidence_file_4',
        ];

        if (!in_array($field, $allowedFields, true)) {
            abort(404, 'Invalid attachment field.');
        }

        $record = CustomerClearanceData::findOrFail($id);
        $filePath = $record->{$field};

        if (empty($filePath)) {
            abort(404, 'No document attached to this slot.');
        }

        // Clean download display name (strip technical repeated job and slot prefixes if present)
        $cleanFileName = basename($filePath);
        $cleanFileName = preg_replace('/^(?:CCD\d{4}-\d{4}_(?:reference_file_\d|rf\d|hs_evidence_file_\d|hs\d)_)+/', '', $cleanFileName);

        return $storageService->download($filePath, $cleanFileName);
    }

    // Delete a clearance record and redirect back to the index list.
    public function destroy(int $id): RedirectResponse
    {
        $record = CustomerClearanceData::findOrFail($id);
        $jobNo = $record->job_no;
        $record->delete();

        return redirect()->route('clearance.index')
            ->with('success', "Clearance record {$jobNo} has been deleted.");
    }

    // Open a file inline in the browser via a temporary S3 pre-signed URL.
    public function viewDocument(int $id, string $field, DocumentStorageService $storageService)
    {
        $allowedFields = [
            'reference_file_1',
            'reference_file_2',
            'hs_evidence_file_1',
            'hs_evidence_file_2',
            'hs_evidence_file_3',
            'hs_evidence_file_4',
        ];

        if (!in_array($field, $allowedFields, true)) {
            abort(404, 'Invalid attachment field.');
        }

        $record = CustomerClearanceData::findOrFail($id);
        $filePath = $record->{$field};

        if (empty($filePath)) {
            abort(404, 'No document attached to this slot.');
        }

        $viewUrl = $storageService->getTemporaryUrl($filePath);

        if (!$viewUrl) {
            abort(404, 'File not available for viewing on S3.');
        }

        return redirect($viewUrl);
    }

    public function updateAnnouncement(Request $request, AnnouncementService $announcementService): RedirectResponse
    {
        $user = auth()->user();
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();

        if (!$isAdmin) {
            abort(403, 'Unauthorized. Only administrators can update the home announcement.');
        }

        $data = $request->validate([
            'title'     => 'required|string|max:150',
            'message'   => 'required|string|max:1000',
            'type'      => 'nullable|string|in:info,warning,success,danger',
            'is_active' => 'nullable',
        ]);

        $data['is_active'] = $request->has('is_active');
        $author = $user->name ?: ($user->username ?: 'Administrator');

        $announcementService->update($data, $author);

        return redirect()->route('clearance.index')
            ->with('success', 'Home announcement updated successfully.');
    }
}

