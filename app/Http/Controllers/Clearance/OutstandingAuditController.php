<?php

namespace App\Http\Controllers\Clearance;

// Gemini : OutstandingAuditController listing records missing hs_evidence_file_1..3 with document upload action
use App\Domain\Compliance\Queries\GetOutstandingClearanceQuery;
use App\Http\Controllers\Controller;
use App\Models\Business\CustomerClearanceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OutstandingAuditController extends Controller
{
    // Lady : Inline query logic in controller violates Single Responsibility and hinders reuse across reporting.
    /*
    // [Previous Version - Inline Query in Controller]
    public function index(Request $request): View
    {
        $query = CustomerClearanceData::with(['customer', 'branch'])
            ->whereNull('hs_evidence_file_1')
            ->whereNull('hs_evidence_file_2')
            ->whereNull('hs_evidence_file_3');

        if ($request->filled('job_no')) {
            $query->where('job_no', 'LIKE', '%' . trim((string)$request->input('job_no')) . '%');
        }

        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'LIKE', '%' . trim((string)$request->input('customer_name')) . '%');
        }

        $records = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return view('clearance.outstanding', compact('records'));
    }
    */
    // Gemini : GAP-06.3 — Extract outstanding records query into dedicated GetOutstandingClearanceQuery object
    public function index(Request $request, GetOutstandingClearanceQuery $query): View
    {
        $records = $query->execute($request->only(['job_no', 'customer_name']));

        return view('clearance.outstanding', compact('records'));
    }

    // Gemini : Inject DocumentStorageService to mirror uploaded evidence files to AWS S3
    public function attachEvidence(Request $request, int $id, \App\Services\Storage\DocumentStorageService $storageService): RedirectResponse
    {
        $record = CustomerClearanceData::findOrFail($id);

        $request->validate([
            'evidence_slot' => 'required|in:1,2,3,4',
            'evidence_file' => 'required|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
        ]);

        $slot = (int) $request->input('evidence_slot');
        $file = $request->file('evidence_file');

        $cleanOriginal = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
        $filename = "{$record->job_no}_hs{$slot}_{$cleanOriginal}";

        // Lady : Direct file->storeAs('hs_evidence', ..., 'public') only writes to local disk and fails to mirror compliance files to AWS S3 as required by corporate policy.
        /*
        $filename = "{$record->job_no}_hs{$slot}_" . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('hs_evidence', $filename, 'public');
        */
        // Gemini : Use DocumentStorageService for local storage with automatic AWS S3 cloud synchronization
        $path = $storageService->store($file, 'hs_evidence', $filename);

        $field = "hs_evidence_file_{$slot}";
        $record->{$field} = $path;
        $record->update_date = date('Y-m-d');
        $record->save();

        $cloudNote = $storageService->isCloudConfigured() ? ' (backed up to AWS S3)' : '';
        // Gemini : Flash upload debug data containing destination address and S3 status
        $uploadHistory = $storageService->getUploadHistory();
        $redirect = back()->with('success', "Evidence attached successfully to slot {$slot} for Job {$record->job_no}{$cloudNote}.");
        if (!empty($uploadHistory)) {
            $redirect->with('upload_debug', $uploadHistory);
        }

        return $redirect;
    }
}

