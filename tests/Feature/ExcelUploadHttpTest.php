<?php

namespace Tests\Feature;

use App\Models\Auth\AdminUser;
use App\Models\Business\CustomerClearanceData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExcelUploadHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('s3');
        Config::set('filesystems.disks.s3.key', 'test-key');
        Config::set('filesystems.disks.s3.bucket', 'sn-ccd-database-system-th');
        Config::set('filesystems.disks.s3.region', 'ap-southeast-7');

        $this->admin = AdminUser::first();
    }

    /**
     * Verify clearance upload page renders both Single and Batch manifest upload forms.
     */
    public function test_clearance_upload_screen_renders_with_both_single_and_batch_forms(): void
    {
        $response = $this->actingAs($this->admin)->get('/clearance/upload');

        $response->assertStatus(200);
        $response->assertSee('Single Requisition Ingestion');
        $response->assertSee('Batch Multi-Record Ingestion (Tabular)');
        $response->assertSee('/clearance/import/single');
        $response->assertSee('/clearance/import/multi');
    }

    /**
     * Test HTTP POST /clearance/import/single with test_single_sheet.xlsx.
     */
    public function test_http_import_single_sheet_without_ziparchive(): void
    {
        $filePath = base_path('test_single_sheet.xlsx');
        $this->assertFileExists($filePath);

        //Use fake UploadedFile with real content from test_single_sheet.xlsx to isolate test runtime and preserve source file
        $file = UploadedFile::fake()->createWithContent(
            'test_single_sheet.xlsx',
            file_get_contents($filePath)
        );

        $response = $this->actingAs($this->admin)->post('/clearance/import/single', [
            'single_record' => $file,
        ]);

        $response->assertRedirect('/clearance');
        $response->assertSessionHas('success');

        // Confirm database record created
        $record = CustomerClearanceData::where('job_no', 'CCD2610-0113')->first();
        $this->assertNotNull($record);
        $this->assertStringContainsString('SHARP', $record->customer_name);
        $this->assertSame('39269099', $record->hs_code);
    }

    /**
     * Test HTTP POST /clearance/import/multi with test_multi_sheet.xlsx.
     */
    public function test_http_import_multi_sheet_without_ziparchive(): void
    {
        $filePath = base_path('test_multi_sheet.xlsx');
        $this->assertFileExists($filePath);

        //Use fake UploadedFile with real content from test_multi_sheet.xlsx to isolate test runtime and preserve source file
        $file = UploadedFile::fake()->createWithContent(
            'test_multi_sheet.xlsx',
            file_get_contents($filePath)
        );

        $response = $this->actingAs($this->admin)->post('/clearance/import/multi', [
            'multi_record' => $file,
        ]);

        $response->assertRedirect('/clearance');
        $response->assertSessionHas('success');

        // Confirm database records created / upserted
        $firstRecord = CustomerClearanceData::where('job_no', 'CCD2610-0107')->first();
        $this->assertNotNull($firstRecord);
        $this->assertStringContainsString('SHARP', $firstRecord->customer_name);
        $this->assertSame('85371099', $firstRecord->hs_code);

        $secondRecord = CustomerClearanceData::where('job_no', 'CCD2610-0108')->first();
        $this->assertNotNull($secondRecord);
    }

    /**
     * Test that unauthenticated guest cannot access import routes.
     */
    public function test_guest_cannot_access_import_routes(): void
    {
        $responseSingle = $this->post('/clearance/import/single');
        $responseSingle->assertRedirect('/login');

        $responseMulti = $this->post('/clearance/import/multi');
        $responseMulti->assertRedirect('/login');
    }
}
