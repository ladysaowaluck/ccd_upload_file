<?php

namespace Tests\Unit;

use App\Domain\Clearance\Services\ClearanceExcelImportService;
use App\Models\Business\CustomerClearanceData;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PureExcelImportWithoutZipArchiveTest extends TestCase
{
    use DatabaseTransactions;

    protected ClearanceExcelImportService $importService;
    protected DocumentStorageService $storageService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('s3');
        Config::set('filesystems.disks.s3.key', Config::get('filesystems.disks.s3.key') ?: 'test-key');
        Config::set('filesystems.disks.s3.bucket', 'sn-ccd-database-system-th');
        Config::set('filesystems.disks.s3.region', 'ap-southeast-7');

        $this->storageService = $this->app->make(DocumentStorageService::class);
        $this->importService = $this->app->make(ClearanceExcelImportService::class);
    }

    /**
     * Test importing test_single_sheet.xlsx without ZipArchive.
     */
    public function test_import_single_sheet_without_ziparchive_using_test_single_sheet_file(): void
    {
        $filePath = base_path('test_single_sheet.xlsx');
        $this->assertFileExists($filePath, 'test_single_sheet.xlsx must be present in project root');

        // Create UploadedFile from real test_single_sheet.xlsx file
        $uploadedFile = new UploadedFile(
            $filePath,
            'test_single_sheet.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true // test mode
        );

        $importedJobNo = $this->importService->importSingle($uploadedFile);

        $this->assertNotEmpty($importedJobNo);
        $this->assertSame('CCD2610-0113', $importedJobNo);

        // Verify the record was persisted in database
        $record = CustomerClearanceData::where('job_no', $importedJobNo)->first();
        $this->assertNotNull($record, 'Record must exist in customer_clearance_data');
        $this->assertStringContainsString('SHARP', $record->customer_name);
        $this->assertSame('CAB BAND', $record->product_description_en);
        $this->assertSame('39269099', $record->hs_code);

        // Verify requisition file was archived to S3 without local storage
        $this->assertNotNull($record->reference_file_1);
        $this->assertTrue(Storage::disk('s3')->exists($record->reference_file_1));
        $this->assertFalse(Storage::disk('public')->exists($record->reference_file_1));
    }

    /**
     * Test importing test_multi_sheet.xlsx without ZipArchive.
     */
    public function test_import_multi_sheet_without_ziparchive_using_test_multi_sheet_file(): void
    {
        $filePath = base_path('test_multi_sheet.xlsx');
        $this->assertFileExists($filePath, 'test_multi_sheet.xlsx must be present in project root');

        // Create UploadedFile from real test_multi_sheet.xlsx file
        $uploadedFile = new UploadedFile(
            $filePath,
            'test_multi_sheet.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true // test mode
        );

        $importedJobNos = $this->importService->importMulti($uploadedFile);

        $this->assertIsArray($importedJobNos);
        $this->assertNotEmpty($importedJobNos, 'Must import records from test_multi_sheet.xlsx');
        $this->assertContains('CCD2610-0107', $importedJobNos);
        $this->assertContains('CCD2610-0108', $importedJobNos);
        $this->assertContains('CCD2610-0109', $importedJobNos);

        // Verify first record was persisted in database
        $record = CustomerClearanceData::where('job_no', 'CCD2610-0107')->first();
        $this->assertNotNull($record, 'CCD2610-0107 must exist in customer_clearance_data');
        $this->assertStringContainsString('SHARP', $record->customer_name);
        $this->assertSame('OP PANEL UN EX', $record->product_description_en);
        $this->assertSame('85371099', $record->hs_code);

        // Verify batch requisition file was archived to S3 without local storage
        $this->assertNotNull($record->reference_file_1);
        $this->assertTrue(Storage::disk('s3')->exists($record->reference_file_1));
        $this->assertFalse(Storage::disk('public')->exists($record->reference_file_1));
    }
}
