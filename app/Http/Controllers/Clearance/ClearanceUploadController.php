<?php

namespace App\Http\Controllers\Clearance;

use App\Domain\Clearance\Services\ClearanceExcelImportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkExcelUploadRequest;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClearanceUploadController extends Controller
{
    public function __construct(
        protected ClearanceExcelImportService $importService,
        protected DocumentStorageService $storageService
    ) {
    }

    // Display the Excel Requisition Ingestion interface with AWS Cloud status.
    public function index(): View
    {
        $awsConfigured = $this->storageService->isCloudConfigured();
        $awsBucket = $this->storageService->getCloudBucket();
        $awsRegion = $this->storageService->getCloudRegion();

        return view('clearance.upload', compact('awsConfigured', 'awsBucket', 'awsRegion'));
    }

    // Verify live AWS S3 connectivity on demand from the UI.
    public function testAwsConnection(): RedirectResponse
    {
        $result = $this->storageService->testCloudConnection();

        if ($result['connected']) {
            return back()->with('success', "AWS S3 Connected: {$result['message']}");
        }

        return back()->withErrors(['aws_connection' => "AWS S3 Connection Failed: {$result['message']}"]);
    }

    // Handle single requisition Excel file import with associated reference and evidence attachments.

    public function importSingle(BulkExcelUploadRequest $request): RedirectResponse
    {
        if (!$request->hasFile('single_record')) {
            return back()->withErrors(['single_record' => 'Please select an Excel requisition file to upload.']);
        }

        try {
            $attachedFiles = [
                'reference_file_1' => $request->file('reference_file_1'),
                'reference_file_2' => $request->file('reference_file_2'),
                'hs_evidence_file_1' => $request->file('hs_evidence_file_1'),
                'hs_evidence_file_2' => $request->file('hs_evidence_file_2'),
                'hs_evidence_file_3' => $request->file('hs_evidence_file_3'),
                'hs_evidence_file_4' => $request->file('hs_evidence_file_4'),
            ];

            $jobNo = $this->importService->importSingle(
                $request->file('single_record'),
                $attachedFiles,
                $request->input('imp_exp')
            );

            // Count uploaded attachments
            $attachmentCount = count(array_filter($attachedFiles));
            $cloudNote = '';
            if ($this->storageService->isCloudConfigured() && $attachmentCount > 0) {
                $cloudNote = " ({$attachmentCount} file(s) backed up to AWS S3: {$this->storageService->getCloudBucket()})";
            }

            // Pass upload_debug session array with destination address and S3 status
            $uploadHistory = $this->storageService->getUploadHistory();
            $redirect = redirect()->route('clearance.index')->with('success', "CCD record {$jobNo} imported successfully{$cloudNote}.");
            if (!empty($uploadHistory)) {
                $redirect->with('upload_debug', $uploadHistory);
            }

            return $redirect;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->withErrors(['single_record' => 'Import failed: ' . $e->getMessage()])->withInput();
        }
    }

    // public function importSingle(BulkExcelUploadRequest $request): RedirectResponse
    // {
    //     return $this->importMulti($request);
    // }

    // Handle multiple requisitions batch Excel upload with shared reference and evidence attachments.

    public function importMulti(BulkExcelUploadRequest $request): RedirectResponse
    {
        if (!$request->hasFile('multi_record')) {
            return back()->withErrors(['multi_record' => 'Please select a batch Excel file to upload.']);
        }

        try {
            $attachedFiles = [
                'reference_file_1' => $request->file('reference_file_1'),
                'reference_file_2' => $request->file('reference_file_2'),
                'hs_evidence_file_1' => $request->file('hs_evidence_file_1'),
                'hs_evidence_file_2' => $request->file('hs_evidence_file_2'),
                'hs_evidence_file_3' => $request->file('hs_evidence_file_3'),
                'hs_evidence_file_4' => $request->file('hs_evidence_file_4'),
            ];

            $jobNos = $this->importService->importMulti(
                $request->file('multi_record'),
                $attachedFiles
            );

            $count = count($jobNos);
            $attachmentCount = count(array_filter($attachedFiles));

            /*
            echo 'DEBUG::Successfully imported CCD records in batch mode. ';
            return redirect()->route('clearance.index')->with('success', "Successfully imported {$count} CCD records in batch mode.");
            */
            $cloudNote = '';
            if ($this->storageService->isCloudConfigured() && $attachmentCount > 0) {
                $cloudNote = " with {$attachmentCount} shared attachment(s) backed up to AWS S3 ({$this->storageService->getCloudBucket()})";
            }

            $uploadHistory = $this->storageService->getUploadHistory();
            $redirect = redirect()->route('clearance.index')->with('success', "Successfully imported {$count} CCD records in batch mode{$cloudNote}.");
            if (!empty($uploadHistory)) {
                $redirect->with('upload_debug', $uploadHistory);
            }

            return $redirect;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->withErrors(['multi_record' => 'Batch import failed: ' . $e->getMessage()])->withInput();
        }
    }
}

