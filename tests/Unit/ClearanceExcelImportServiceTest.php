<?php

namespace Tests\Unit;

// Gemini : Unit tests for ClearanceExcelImportService verifying automatic Excel archiving to real AWS S3 bucket without local storage
use App\Domain\Clearance\Services\ClearanceExcelImportService;
use App\Models\Business\CustomerClearanceData;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ClearanceExcelImportServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected ClearanceExcelImportService $importService;
    protected DocumentStorageService $storageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageService = $this->app->make(DocumentStorageService::class);
        $this->importService = $this->app->make(ClearanceExcelImportService::class);
    }

    /**
     * Test that importSingle saves the uploaded requisition Excel file directly to real S3 and links reference_file_1.
     */
    public function test_import_single_archives_requisition_excel_to_s3_and_links_reference_file_1(): void
    {
        // Lady : Using Storage::fake('public') or Storage::fake('s3') prevents testing real AWS S3 bucket operations.
        // Storage::fake('public');
        // Storage::fake('s3');
        Excel::fake();

        $testJobNo = 'CCD2611-' . rand(7000, 7999);

        // Pre-create record simulating SingleSheetImport creation in database
        $record = CustomerClearanceData::create([
            'job_no'                 => $testJobNo,
            'master_customer_id'     => 1,
            'master_branch_id'       => 1,
            'customer_name'          => 'Test Customer Co., Ltd.',
            'product_description_en' => 'Test Transformer',
            'product_description_th' => 'หม้อแปลงไฟฟ้า',
            'hs_code'                => '85043100',
            'statistic_code'         => '000',
            'quantity_unit'          => 'C62',
            'imp_exp'                => 0,
            'reference_file_1'       => null,
            'create_date'            => date('Y-m-d'),
            'update_date'            => date('Y-m-d'),
            'edit_user_id'           => 1,
        ]);

        // Place job_no in session as SingleSheetImport does
        session()->put('single_job_no', $testJobNo);

        $dummyContent = "PK\x03\x04SingleRequisitionExcelDummyContent_" . time();
        $fakeExcel = UploadedFile::fake()->createWithContent(
            'hs_requisition_form_test.xlsx',
            $dummyContent
        );

        $uploadedS3Path = null;
        try {
            $returnedJobNo = $this->importService->importSingle($fakeExcel, []);

            $this->assertSame($testJobNo, $returnedJobNo);

            // Verify the record's reference_file_1 is updated with the archived Excel requisition path
            $record->refresh();
            $uploadedS3Path = $record->reference_file_1;
            $this->assertNotNull($uploadedS3Path);
            $this->assertStringContainsString('reference/', $uploadedS3Path);
            $this->assertStringContainsString($testJobNo, $uploadedS3Path);
            $this->assertStringContainsString('requisition_hs_requisition_form_test.xlsx', $uploadedS3Path);

            // Verify upload history captured S3 URI and bypassed local storage
            $history = $this->storageService->getUploadHistory();
            $this->assertNotEmpty($history);
            $latest = end($history);
            $this->assertTrue($latest['s3_synced'], 'Excel requisition must be confirmed stored to real AWS S3');
            $this->assertSame('Bypassed (Cloud-Only)', $latest['local_path']);
            $this->assertStringContainsString('s3://sn-ccd-database-system-th/reference/', $latest['s3_location']);

            // Gemini : Verify file actually exists in real AWS S3 bucket using fileExists (HeadObject)
            $s3Disk = Storage::disk('s3');
            $this->assertTrue($s3Disk->fileExists($uploadedS3Path), 'Archived Excel was not found in real AWS S3!');

            // Verify content matches in real S3
            $s3Content = $s3Disk->get($uploadedS3Path);
            $this->assertSame($dummyContent, $s3Content);

            // Gemini : Verify file does NOT exist on local public storage disk
            $this->assertFalse(
                Storage::disk('public')->exists($uploadedS3Path),
                'File must not exist on local disk; storage must be S3 bucket only.'
            );
        } finally {
            // Gemini : Clean up real S3 test file
            if (!empty($uploadedS3Path) && Storage::disk('s3')->fileExists($uploadedS3Path)) {
                Storage::disk('s3')->delete($uploadedS3Path);
            }
        }
    }

    /**
     * Test that importMulti archives batch Excel file to S3 and associates reference_file_1 across records.
     */
    public function test_import_multi_archives_batch_excel_to_s3_and_links_reference_file_1(): void
    {
        // Lady : Using Storage::fake('public') or Storage::fake('s3') prevents testing real AWS S3 bucket operations.
        // Storage::fake('public');
        // Storage::fake('s3');
        Excel::fake();

        $jobNo1 = 'CCD2611-' . rand(6000, 6499);
        $jobNo2 = 'CCD2611-' . rand(6500, 6999);

        // Pre-create records simulating MultiSheetImport creation
        $record1 = CustomerClearanceData::create([
            'job_no'                 => $jobNo1,
            'master_customer_id'     => 1,
            'master_branch_id'       => 1,
            'customer_name'          => 'Batch Customer 1',
            'product_description_en' => 'Batch Item 1',
            'product_description_th' => 'สินค้าทดสอบ 1',
            'hs_code'                => '85043100',
            'statistic_code'         => '000',
            'quantity_unit'          => 'C62',
            'imp_exp'                => 0,
            'reference_file_1'       => null,
            'create_date'            => date('Y-m-d'),
            'update_date'            => date('Y-m-d'),
            'edit_user_id'           => 1,
        ]);

        $record2 = CustomerClearanceData::create([
            'job_no'                 => $jobNo2,
            'master_customer_id'     => 1,
            'master_branch_id'       => 1,
            'customer_name'          => 'Batch Customer 2',
            'product_description_en' => 'Batch Item 2',
            'product_description_th' => 'สินค้าทดสอบ 2',
            'hs_code'                => '85043100',
            'statistic_code'         => '000',
            'quantity_unit'          => 'C62',
            'imp_exp'                => 0,
            'reference_file_1'       => null,
            'create_date'            => date('Y-m-d'),
            'update_date'            => date('Y-m-d'),
            'edit_user_id'           => 1,
        ]);

        session()->put('multi_job_no', [$jobNo1, $jobNo2]);

        $dummyBatchContent = "PK\x03\x04BatchManifestExcelContent_" . time();
        $fakeBatchExcel = UploadedFile::fake()->createWithContent(
            'batch_manifest.xlsx',
            $dummyBatchContent
        );

        $uploadedBatchPath = null;
        try {
            $returnedJobNos = $this->importService->importMulti($fakeBatchExcel, []);

            $this->assertCount(2, $returnedJobNos);

            // Verify both records reference the shared batch requisition Excel file
            $record1->refresh();
            $record2->refresh();

            $uploadedBatchPath = $record1->reference_file_1;
            $this->assertNotNull($uploadedBatchPath);
            $this->assertNotNull($record2->reference_file_1);
            $this->assertSame($uploadedBatchPath, $record2->reference_file_1);
            $this->assertStringContainsString('batch_batch_manifest.xlsx', $uploadedBatchPath);

            // Gemini : Verify batch file actually exists in real AWS S3 bucket (using fileExists which executes HeadObject)
            $s3Disk = Storage::disk('s3');
            $this->assertTrue($s3Disk->fileExists($uploadedBatchPath), 'Batch Excel was not found in real AWS S3!');

            // Verify content matches in real S3
            $this->assertSame($dummyBatchContent, $s3Disk->get($uploadedBatchPath));

            // Gemini : Verify file does NOT exist on local public storage disk
            $this->assertFalse(
                Storage::disk('public')->exists($uploadedBatchPath),
                'Batch file must not exist on local disk; storage must be S3 bucket only.'
            );
        } finally {
            // Gemini : Clean up real S3 test file
            if (!empty($uploadedBatchPath) && Storage::disk('s3')->fileExists($uploadedBatchPath)) {
                Storage::disk('s3')->delete($uploadedBatchPath);
            }
        }
    }

    /**
     * Test attachFilesToJob uploads reference and HS evidence files directly to real AWS S3 without local storage.
     */
    public function test_attach_files_to_job_stores_directly_to_real_s3_without_local_storage(): void
    {
        $testJobNo = 'CCD2611-' . rand(8000, 8999);

        $record = CustomerClearanceData::create([
            'job_no'                 => $testJobNo,
            'master_customer_id'     => 1,
            'master_branch_id'       => 1,
            'customer_name'          => 'Attach Test Customer',
            'product_description_en' => 'Attach Test Item',
            'product_description_th' => 'สินค้าทดสอบไฟล์แนบ',
            'hs_code'                => '85043100',
            'statistic_code'         => '000',
            'quantity_unit'          => 'C62',
            'imp_exp'                => 0,
            'create_date'            => date('Y-m-d'),
            'update_date'            => date('Y-m-d'),
            'edit_user_id'           => 1,
        ]);

        $refPayload = "%PDF-1.4 Reference Document " . time();
        $hsPayload = "%PDF-1.4 HS Evidence Document " . time();

        $refFile = UploadedFile::fake()->createWithContent('ref_invoice.pdf', $refPayload);
        $hsFile = UploadedFile::fake()->createWithContent('hs_spec.pdf', $hsPayload);

        $files = [
            'reference_file_1'   => $refFile,
            'hs_evidence_file_1' => $hsFile,
        ];

        $refPath = null;
        $hsPath = null;
        try {
            $this->importService->attachFilesToJob($testJobNo, $files);

            $record->refresh();
            $refPath = $record->reference_file_1;
            $hsPath = $record->hs_evidence_file_1;

            $this->assertNotNull($refPath);
            $this->assertNotNull($hsPath);

            $s3Disk = Storage::disk('s3');

            // Verify both files exist in real AWS S3 bucket
            $this->assertTrue($s3Disk->fileExists($refPath), 'Reference file must exist in real S3');
            $this->assertTrue($s3Disk->fileExists($hsPath), 'HS evidence file must exist in real S3');

            // Verify content matches in real S3
            $this->assertSame($refPayload, $s3Disk->get($refPath));
            $this->assertSame($hsPayload, $s3Disk->get($hsPath));

            // Verify both files do NOT exist on local public disk
            $this->assertFalse(Storage::disk('public')->exists($refPath));
            $this->assertFalse(Storage::disk('public')->exists($hsPath));
        } finally {
            if (!empty($refPath) && Storage::disk('s3')->fileExists($refPath)) {
                Storage::disk('s3')->delete($refPath);
            }
            if (!empty($hsPath) && Storage::disk('s3')->fileExists($hsPath)) {
                Storage::disk('s3')->delete($hsPath);
            }
        }
    }
}
