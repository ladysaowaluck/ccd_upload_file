<?php

namespace App\Http\Controllers\Clearance;

use App\Domain\Compliance\Queries\GetOutstandingClearanceQuery;
use App\Http\Controllers\Controller;
use App\Models\Business\CustomerClearanceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OutstandingAuditController extends Controller
{
    /*  

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
    // Modeled after CustomerClearanceOutStandingController with full search filters, master branch/statistic lookups, and direct evidence attachment
    public function index(Request $request, GetOutstandingClearanceQuery $query): View
    {
        $filters = $request->only([
            'job_no',
            'customer_name',
            'master_branch_id',
            'imp_exp',
            'hs_code',
            'statistic_code_id',
            'product_code_1',
            'product_description',
            'product_description_en',
            'product_description_th',
            'create_date',
            'status',
            'keyword',
            'exist_reference_file_1',
            'exist_reference_file_2',
            'exist_hs_evidence_file_1',
            'exist_hs_evidence_file_2',
            'exist_hs_evidence_file_3',
            'exist_hs_evidence_file_4',
            'sort_by',
            'sort_dir',
        ]);

        $records = $query->execute($filters);
        $branches = \App\Models\Business\MasterBranch::orderBy('branch_code')->get();
        $statistics = \App\Models\Business\MasterStatistic::orderBy('statistics')->get();

        return view('clearance.outstanding', compact('records', 'filters', 'branches', 'statistics'));
    }

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
        $path = $storageService->store($file, 'hs_evidence', $filename);

        $field = "hs_evidence_file_{$slot}";
        $record->{$field} = $path;
        $record->update_date = date('Y-m-d');
        $record->save();

        $cloudNote = $storageService->isCloudConfigured() ? ' (backed up to AWS S3)' : '';
        $uploadHistory = $storageService->getUploadHistory();
        $redirect = back()->with('success', "Evidence attached successfully to slot {$slot} for Job {$record->job_no}{$cloudNote}.");
        if (!empty($uploadHistory)) {
            $redirect->with('upload_debug', $uploadHistory);
        }

        return $redirect;
    }
}

