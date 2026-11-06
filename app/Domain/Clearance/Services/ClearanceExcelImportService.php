<?php

namespace App\Domain\Clearance\Services;

// Gemini : GAP-03 — Service orchestrating Excel parsing and associated evidence/reference file attachments
use App\Imports\MultiSheetImport;
use App\Imports\SingleSheetImport;
use App\Models\Business\CustomerClearanceData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
// Gemini : GAP-07 — Inject DocumentStorageService to eliminate duplicate file sync logic
use App\Services\Storage\DocumentStorageService;

class ClearanceExcelImportService
{
    // Gemini : GAP-07 — Injected DocumentStorageService
    public function __construct(
        protected DocumentStorageService $storageService
    ) {}

    /**
     * Import a single CCD record from an Excel template sheet.
     *
     * @param UploadedFile $file
     * @param array<string, UploadedFile|null> $attachedFiles
     * @param string|null $impExp
     * @return string The generated or imported job number
     */
    public function importSingle(UploadedFile $file, array $attachedFiles = [], ?string $impExp = null): string
    {
        $import = new SingleSheetImport();
        Excel::import($import, $file);

        $jobNo = $import->importedJobNo ?? session()->get('single_job_no');

        if (!$jobNo) {
            throw new \RuntimeException('Failed to determine Job Number from imported requisition.');
        }

        // Update imp_exp flag if specified
        if ($impExp !== null) {
            $flag = (strtolower($impExp) === 'export' || $impExp === '1') ? 1 : 0;
            CustomerClearanceData::where('job_no', $jobNo)->update(['imp_exp' => $flag]);
        }

        // Attach files
        $this->attachFilesToJob($jobNo, $attachedFiles);

        // Gemini : Store and sync primary requisition Excel file to local storage and AWS S3 bucket
        $cleanOriginal = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
        $excelFileName = "{$jobNo}_requisition_{$cleanOriginal}";
        $excelPath = $this->storageService->store($file, 'reference', $excelFileName);

        $record = CustomerClearanceData::where('job_no', $jobNo)->first();
        if ($record && empty($record->reference_file_1) && empty($attachedFiles['reference_file_1'])) {
            $record->update(['reference_file_1' => $excelPath]);
        }

        session()->forget('single_job_no');

        return $jobNo;
    }

    /**
     * Import multiple CCD records from a tabular Excel sheet.
     *
     * @param UploadedFile $file
     * @param array<string, UploadedFile|null> $attachedFiles
     * @return array<string> List of imported job numbers
     */
    public function importMulti(UploadedFile $file, array $attachedFiles = []): array
    {
        $import = new MultiSheetImport();
        Excel::import($import, $file);

        // Lady : Using null-coalescing on an array initialized to [] never falls back to session data when Excel import is mocked or returns empty.
        // $jobNos = $import->importedJobNos ?? session()->get('multi_job_no', []);
        // Gemini : Fall back to session('multi_job_no') when importedJobNos is empty
        $jobNos = !empty($import->importedJobNos) ? $import->importedJobNos : session()->get('multi_job_no', []);

        foreach ($jobNos as $jobNo) {
            $this->attachFilesToJob($jobNo, $attachedFiles);
        }

        // Gemini : Store and sync batch requisition Excel file to local storage and AWS S3 bucket
        if (!empty($jobNos)) {
            $firstJob = $jobNos[0];
            $cleanOriginal = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $excelFileName = "{$firstJob}_batch_{$cleanOriginal}";
            $excelPath = $this->storageService->store($file, 'reference', $excelFileName);

            foreach ($jobNos as $jobNo) {
                $record = CustomerClearanceData::where('job_no', $jobNo)->first();
                if ($record && empty($record->reference_file_1) && empty($attachedFiles['reference_file_1'])) {
                    $record->update(['reference_file_1' => $excelPath]);
                }
            }
        }

        session()->forget('multi_job_no');

        return $jobNos;
    }

    /**
     * Store and associate uploaded reference and HS evidence files with a job record.
     *
     * @param string $jobNo
     * @param array<string, UploadedFile|null> $files
     */
    public function attachFilesToJob(string $jobNo, array $files): void
    {
        $updates = [];

        // Reference files
        for ($i = 1; $i <= 2; $i++) {
            $key = "reference_file_{$i}";
            if (!empty($files[$key]) && $files[$key] instanceof UploadedFile) {
                $file = $files[$key];
                $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                $fileName = "{$jobNo}_rf{$i}_{$cleanName}";

                // Lady : Manual storeAs combined with hardcoded production S3 sync duplicates storage logic across multiple services.
                /*
                $file->storeAs('public/reference', $fileName);
                $updates[$key] = "reference/{$fileName}";

                if (config('app.env') === 'production') {
                    $localPath = storage_path("app/public/reference/{$fileName}");
                    if (file_exists($localPath)) {
                        try {
                            Storage::disk('s3')->putFileAs('/reference/', $localPath, $fileName);
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning("S3 sync failed for {$fileName}: " . $e->getMessage());
                        }
                    }
                }
                */
                // Gemini : GAP-07 — Delegate file storage and cloud sync to DocumentStorageService
                $updates[$key] = $this->storageService->store($file, 'reference', $fileName);
            }
        }

        // HS Evidence files
        for ($i = 1; $i <= 4; $i++) {
            $key = "hs_evidence_file_{$i}";
            if (!empty($files[$key]) && $files[$key] instanceof UploadedFile) {
                $file = $files[$key];
                $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
                $fileName = "{$jobNo}_hs{$i}_{$cleanName}";

                // Lady : Duplicated manual S3 upload logic across controllers and services violates DRY.
                /*
                $file->storeAs('public/hs_evidence', $fileName);
                $updates[$key] = "hs_evidence/{$fileName}";

                if (config('app.env') === 'production') {
                    $localPath = storage_path("app/public/hs_evidence/{$fileName}");
                    if (file_exists($localPath)) {
                        try {
                            Storage::disk('s3')->putFileAs('/hs_evidence/', $localPath, $fileName);
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning("S3 sync failed for {$fileName}: " . $e->getMessage());
                        }
                    }
                }
                */
                // Gemini : GAP-07 — Delegate file storage and cloud sync to DocumentStorageService
                $updates[$key] = $this->storageService->store($file, 'hs_evidence', $fileName);
            }
        }

        if (!empty($updates)) {
            CustomerClearanceData::where('job_no', $jobNo)->update($updates);
        }
    }
}

