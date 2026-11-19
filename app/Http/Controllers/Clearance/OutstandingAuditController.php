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
            'product_code_2',
            'brand_name',
            'model_name',
            'country_of_origin',
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

    // Universal document & evidence attachment method. Supports uploading BOTH Reference Files
    // (Catalog, Specification, SDS 1 & 2) and HS Compliance Evidence (Slots 1 to 4) directly from the audit grid.
    public function attachEvidence(Request $request, int $id, \App\Services\Storage\DocumentStorageService $storageService): RedirectResponse
    {
        $record = CustomerClearanceData::findOrFail($id);

        // Check PHP-level upload errors BEFORE validation.
        // 1. If entire POST payload exceeds post_max_size, PHP discards both $_POST and $_FILES.
        if (empty($_FILES) && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $postLimit = ini_get('post_max_size');
            return back()->withErrors([
                'evidence_file' => "The uploaded file and form data exceeded the server's post_max_size limit ({$postLimit}).",
            ]);
        }

        // 2. When a single file exceeds php.ini upload_max_filesize, PHP sets UPLOAD_ERR_INI_SIZE.
        $phpUploadError = $_FILES['evidence_file']['error'] ?? $_FILES['document_file']['error'] ?? UPLOAD_ERR_OK;
        if ($phpUploadError === UPLOAD_ERR_INI_SIZE || $phpUploadError === UPLOAD_ERR_FORM_SIZE) {
            $serverLimit = ini_get('upload_max_filesize');
            return back()->withErrors([
                'evidence_file' => "The file is too large for the server. Server upload limit is {$serverLimit}. Please reduce the file size or increase server limits.",
            ]);
        }
        if ($phpUploadError !== UPLOAD_ERR_OK && $phpUploadError !== UPLOAD_ERR_NO_FILE) {
            return back()->withErrors([
                'evidence_file' => "Upload failed at PHP layer with error code {$phpUploadError}.",
            ]);
        }

        $request->validate([
            'evidence_slot' => 'nullable|string|in:1,2,3,4,ref1,ref2,reference_1,reference_2,evidence_1,evidence_2,evidence_3,evidence_4',
            'document_slot' => 'nullable|string|in:1,2,3,4,ref1,ref2,reference_1,reference_2,evidence_1,evidence_2,evidence_3,evidence_4',
            'evidence_file' => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'document_file' => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
        ]);

        $slot = (string) ($request->input('document_slot') ?? $request->input('evidence_slot', '1'));
        $file = $request->file('document_file') ?? $request->file('evidence_file');

        if (!$file) {
            return back()->withErrors(['evidence_file' => 'Please select a file to upload.']);
        }

        $cleanOriginal = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());

        // Wrap store+save in try-catch so an S3 PutObject failure (403/5xx) returns
        // a user-friendly validation error instead of an unhandled 500 response.
        try {
            // Determine destination: Reference File vs HS Evidence File
            if (in_array($slot, ['ref1', 'reference_1'], true)) {
                $field = 'reference_file_1';
                $filename = "{$record->job_no}_ref1_{$cleanOriginal}";
                $path = $storageService->store($file, 'reference', $filename);
                $successMsg = "Reference Document (Catalog/SDS 1) attached successfully for Job {$record->job_no}";
            } elseif (in_array($slot, ['ref2', 'reference_2'], true)) {
                $field = 'reference_file_2';
                $filename = "{$record->job_no}_ref2_{$cleanOriginal}";
                $path = $storageService->store($file, 'reference', $filename);
                $successMsg = "Reference Document (Catalog/SDS 2) attached successfully for Job {$record->job_no}";
            } else {
                // Standard HS Evidence (1..4 or evidence_1..4)
                $numericSlot = (int) str_replace('evidence_', '', $slot);
                if ($numericSlot < 1 || $numericSlot > 4) {
                    $numericSlot = 1;
                }
                $field = "hs_evidence_file_{$numericSlot}";
                $filename = "{$record->job_no}_hs{$numericSlot}_{$cleanOriginal}";
                $path = $storageService->store($file, 'hs_evidence', $filename);
                $successMsg = "Evidence attached successfully to slot {$numericSlot} for Job {$record->job_no}";
            }

            if (!$path) {
                return back()->withErrors(['evidence_file' => 'File could not be stored. Please try again.']);
            }

            $record->{$field} = $path;
            $record->update_date = date('Y-m-d');
            $record->save();
        } catch (\Throwable $e) {
            Log::error("OutstandingAuditController: attachEvidence failed for record [{$record->job_no}]: " . $e->getMessage());
            return back()->withErrors(['evidence_file' => 'File upload failed. Please try again later.']);
        }

        $cloudNote = $storageService->isCloudConfigured() ? ' ' : '';
        $uploadHistory = $storageService->getUploadHistory();
        $redirect = back()->with('success', "{$successMsg}{$cloudNote}.");
        if (!empty($uploadHistory)) {
            $redirect->with('upload_debug', $uploadHistory);
        }

        return $redirect;
    }
}

