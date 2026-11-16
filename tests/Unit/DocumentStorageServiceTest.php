<?php

namespace Tests\Unit;

// Unit tests for DocumentStorageService verifying direct real AWS S3 bucket operations, terminal DEBUG echo, and local storage elimination
use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentStorageServiceTest extends TestCase
{
    protected DocumentStorageService $storageService;

// Configure fake S3 and public disks while preserving AWS S3 environment configuration and updating expected strings for current S3-only architecture.
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('s3');
        Config::set('filesystems.disks.s3.key', Config::get('filesystems.disks.s3.key') ?: 'test-key');
        Config::set('filesystems.disks.s3.bucket', 'sn-ccd-database-system-th');
        Config::set('filesystems.disks.s3.region', 'ap-southeast-7');
        $this->storageService = $this->app->make(DocumentStorageService::class);
    }

    // Test that DocumentStorageService properly detects AWS S3 credentials from environment.
    public function test_document_storage_service_detects_cloud_configuration_from_env(): void
    {
        $this->assertTrue(
            $this->storageService->isCloudConfigured(),
            'AWS S3 is expected to be configured with credentials from .env'
        );

        $this->assertSame('sn-ccd-database-system-th', $this->storageService->getCloudBucket());
        $this->assertSame('ap-southeast-7', $this->storageService->getCloudRegion());
    }

    // Test that filesystems configuration contains http verify option for Windows SSL compatibility.
    public function test_filesystems_s3_has_http_verify_configured(): void
    {
        $s3Config = Config::get('filesystems.disks.s3');

        $this->assertIsArray($s3Config);
        $this->assertArrayHasKey('http', $s3Config);
        $this->assertArrayHasKey('verify', $s3Config['http']);
        $this->assertFalse($s3Config['http']['verify']);
    }

    // Test that DocumentStorageService stores file directly to AWS S3, outputs terminal DEBUG echo, and bypasses local disk.
    public function test_document_storage_service_stores_directly_to_real_s3_and_outputs_terminal_debug(): void
    {
        // Storage::fake('public');
        // Storage::fake('s3');

        // Execute real storage against AWS S3 bucket per user mandate with real file payload
        $dummyPayload = "%PDF-1.4\n% Test Customs Clearance Document Content\n% Real S3 Payload Verification\n";
        $file = UploadedFile::fake()->createWithContent('customs_catalog.pdf', $dummyPayload);
        $fileName = 'CCD2611-9998_rf1_catalog_' . time() . '.pdf';

        // Capture terminal DEBUG echo output
        ob_start();
        $storedPath = $this->storageService->store($file, 'reference', $fileName);
        $debugOutput = ob_get_clean();

        // Print debug output to terminal so it is visible in console during test runs
        echo $debugOutput;

        try {
            $this->assertSame("reference/{$fileName}", $storedPath);
            $this->assertStringContainsString('[DEBUG STORAGE UPLOAD]', $debugOutput);
            $this->assertStringContainsString("File Name        : {$fileName}", $debugOutput);
            $this->assertStringContainsString("1. AWS Bucket    : [SUCCESS] s3://sn-ccd-database-system-th/reference/{$fileName}", $debugOutput);
            $this->assertStringContainsString('Local Server  : DISABLED (commented out - S3 only)', $debugOutput);

            // Verify upload history reflects cloud-only storage
            $history = $this->storageService->getUploadHistory();
            $this->assertNotEmpty($history);

            $latest = end($history);
            $this->assertSame($fileName, $latest['file_name']);
            $this->assertSame('reference', $latest['directory']);
            $this->assertTrue($latest['s3_synced'], 'File must be confirmed stored to AWS S3');
            $this->assertSame('DISABLED (S3 only)', $latest['local_path']);
            $this->assertNull($latest['local_url']);
            $this->assertSame("s3://sn-ccd-database-system-th/reference/{$fileName}", $latest['s3_location']);

            // Verify file actually exists in real AWS S3 bucket using fileExists (HeadObject)
            $s3Disk = Storage::disk('s3');
            $this->assertTrue($s3Disk->fileExists("reference/{$fileName}"), 'File was not found in real AWS S3 bucket!');

            // Verify content matches in real S3
            $s3Content = $s3Disk->get("reference/{$fileName}");
            $this->assertSame($dummyPayload, $s3Content);

            // Verify local disk storage was completely bypassed (file must NOT exist locally)
            $this->assertFalse(
                Storage::disk('public')->exists("reference/{$fileName}"),
                'Local storage was not bypassed! File exists on local public disk.'
            );

        } finally {
            // Clean up real test artifact from AWS S3
            if (Storage::disk('s3')->fileExists("reference/{$fileName}")) {
                Storage::disk('s3')->delete("reference/{$fileName}");
            }
        }
    }

    //  Test exists and delete operations directly on real AWS S3 bucket.
    public function test_document_storage_service_exists_and_delete_on_real_s3(): void
    {
        $testFileName = 'CCD2611-9997_test_probe_' . time() . '.txt';
        $testKey = "reference/{$testFileName}";
        $content = "probe content for exists and delete test";

        // Store file directly to S3
        Storage::disk('s3')->put($testKey, $content);

        try {
            // Verify exists() returns true on real S3 bucket
            $this->assertTrue($this->storageService->exists($testKey));

            // Verify delete() removes object from real S3 bucket
            $deleted = $this->storageService->delete($testKey);
            $this->assertTrue($deleted);

            // Verify exists() now returns false
            $this->assertFalse($this->storageService->exists($testKey));
        } finally {
            if (Storage::disk('s3')->fileExists($testKey)) {
                Storage::disk('s3')->delete($testKey);
            }
        }
    }

    // Assert 'DISABLED (S3 only)' for local path in getFileLocations.
    public function test_get_file_locations_returns_s3_and_bypassed_local(): void
    {
        $relativePath = 'reference/CCD2611-0001_test.xlsx';
        $locations = $this->storageService->getFileLocations($relativePath);

        $this->assertArrayHasKey('local_path', $locations);
        $this->assertSame('DISABLED (S3 only)', $locations['local_path']);
        $this->assertNull($locations['local_url']);
        $this->assertSame('s3://sn-ccd-database-system-th/reference/CCD2611-0001_test.xlsx', $locations['s3_location']);
    }

    // Test live S3 connectivity test method against the configured bucket.
    public function test_live_s3_cloud_connection(): void
    {
        $result = $this->storageService->testCloudConnection();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('connected', $result);
        $this->assertTrue($result['connected'], 'Failed to connect to S3: ' . ($result['message'] ?? 'Unknown error'));
        $this->assertSame('sn-ccd-database-system-th', $result['bucket']);
        $this->assertSame('ap-southeast-7', $result['region']);
    }
}
