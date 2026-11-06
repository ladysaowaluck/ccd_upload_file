<?php

namespace App\Http\Controllers\Clearance;

// Gemini : ClearanceGridController providing modern search, filtering, and pagination for CCD records
use App\Domain\Clearance\Actions\UpdateClearanceRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClearanceRecordRequest;
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterBranch;
use App\Models\Business\MasterCustomer;
use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClearanceGridController extends Controller
{
    public function index(Request $request): View
    {
        $query = CustomerClearanceData::with(['customer', 'branch']);

        if ($request->filled('job_no')) {
            $query->where('job_no', 'LIKE', '%' . trim((string)$request->input('job_no')) . '%');
        }

        if ($request->filled('hs_code')) {
            $query->where('hs_code', 'LIKE', trim((string)$request->input('hs_code')) . '%');
        }

        if ($request->filled('customer_name')) {
            $customerTerm = trim((string)$request->input('customer_name'));
            $query->where('customer_name', 'LIKE', '%' . $customerTerm . '%');
        }

        if ($request->filled('branch_id')) {
            $query->where('master_branch_id', $request->input('branch_id'));
        }

        if ($request->filled('imp_exp') && in_array($request->input('imp_exp'), ['0', '1'], true)) {
            $query->where('imp_exp', (int)$request->input('imp_exp'));
        }

        if ($request->filled('keyword')) {
            $term = trim((string)$request->input('keyword'));
            $query->where(function ($sub) use ($term) {
                $sub->where('product_description_en', 'LIKE', '%' . $term . '%')
                    ->orWhere('product_description_th', 'LIKE', '%' . $term . '%')
                    ->orWhere('product_code_1', 'LIKE', '%' . $term . '%');
            });
        }

        $records = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();
        $branches = MasterBranch::orderBy('branch_name')->get();

        return view('clearance.index', compact('records', 'branches'));
    }

    public function show(int $id): View
    {
        $record = CustomerClearanceData::with(['customer', 'branch'])->findOrFail($id);
        return view('clearance.show', compact('record'));
    }

    // Gemini : GAP-05 — Render clearance edit form pre-populated with master data
    public function edit(int $id): View
    {
        $record = CustomerClearanceData::with(['customer', 'branch'])->findOrFail($id);
        $branches = MasterBranch::orderBy('branch_name')->get();
        $customers = MasterCustomer::where('is_enable', 0)->orderBy('company_name')->get();
        $units = MasterUnit::orderBy('unit')->get();
        $statistics = MasterStatistic::orderBy('statistics')->get();

        return view('clearance.edit', compact('record', 'branches', 'customers', 'units', 'statistics'));
    }

    // Gemini : GAP-05 — Execute clearance record update via UpdateClearanceRecordAction
    public function update(
        StoreClearanceRecordRequest $request,
        int $id,
        UpdateClearanceRecordAction $action,
        \App\Services\Storage\DocumentStorageService $storageService
    ): RedirectResponse {
        $record = CustomerClearanceData::findOrFail($id);
        $action->execute($record, $request->all());

        // Gemini : Flash upload debug data containing destination address and S3 status
        $uploadHistory = $storageService->getUploadHistory();
        $redirect = redirect()->route('clearance.show', $record->id)->with('success', "Clearance record {$record->job_no} updated successfully.");
        if (!empty($uploadHistory)) {
            $redirect->with('upload_debug', $uploadHistory);
        }

        return $redirect;
    }

    // Gemini : Secure streaming download endpoint fetching documents directly from AWS S3 bucket
    public function downloadDocument(
        int $id,
        string $field,
        \App\Services\Storage\DocumentStorageService $storageService
    ) {
        $allowedFields = [
            'reference_file_1', 'reference_file_2',
            'hs_evidence_file_1', 'hs_evidence_file_2', 'hs_evidence_file_3', 'hs_evidence_file_4',
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
}

