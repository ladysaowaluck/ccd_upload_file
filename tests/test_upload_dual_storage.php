<?php
/**
 * Test dual storage upload to verify:
 * 1. Attempt AWS S3 upload
 * 2. Save file to local storage in /reference and /evidence
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

echo "=== Dual Storage Upload Test ===\n\n";

$storageService = new DocumentStorageService();

// Create temp files to simulate UploadedFile
$tempRefPath = sys_get_temp_dir() . '/test_sample_ref_' . time() . '.pdf';
file_put_contents($tempRefPath, "%PDF-1.4 Mock reference document content created at " . date('Y-m-d H:i:s'));
$uploadedRefFile = new UploadedFile($tempRefPath, 'sample_reference.pdf', 'application/pdf', null, true);

$tempEvidencePath = sys_get_temp_dir() . '/test_sample_evi_' . time() . '.pdf';
file_put_contents($tempEvidencePath, "%PDF-1.4 Mock HS evidence document content created at " . date('Y-m-d H:i:s'));
$uploadedEvidenceFile = new UploadedFile($tempEvidencePath, 'sample_evidence.pdf', 'application/pdf', null, true);

// Test 1: Upload to /reference
echo "--- Test 1: Store file in 'reference' ---\n";
$refStored = $storageService->store($uploadedRefFile, 'reference', 'TEST_JOB001_rf1_sample.pdf');
echo "Stored Path Return: {$refStored}\n";

$refLocalExists = Storage::disk('public')->exists($refStored);
$refFullPath = Storage::disk('public')->path($refStored);
echo "Local File Exists : " . ($refLocalExists ? "YES ✅" : "NO ❌") . "\n";
echo "Local Full Path   : {$refFullPath}\n";
echo "Local URL         : " . $storageService->getUrl($refStored) . "\n\n";

// Test 2: Upload to 'hs_evidence' (which mirrors to 'evidence')
echo "--- Test 2: Store file in 'hs_evidence' ---\n";
$eviStored = $storageService->store($uploadedEvidenceFile, 'hs_evidence', 'TEST_JOB001_hs1_sample.pdf');
echo "Stored Path Return: {$eviStored}\n";

$eviLocalExists = Storage::disk('public')->exists($eviStored);
$eviFullPath = Storage::disk('public')->path($eviStored);
echo "Local File Exists in hs_evidence : " . ($eviLocalExists ? "YES ✅" : "NO ❌") . "\n";
echo "Local Full Path                  : {$eviFullPath}\n";

// Check if mirrored to /evidence
$mirroredPath = 'evidence/' . basename($eviStored);
$mirrorExists = Storage::disk('public')->exists($mirroredPath);
echo "Mirrored to /evidence/           : " . ($mirrorExists ? "YES ✅" : "NO ❌") . "\n";
if ($mirrorExists) {
    echo "Evidence Full Path               : " . Storage::disk('public')->path($mirroredPath) . "\n";
}

echo "\n--- Upload History / Debug Inspection ---\n";
print_r($storageService->getUploadHistory());

// Cleanup temp files
@unlink($tempRefPath);
@unlink($tempEvidencePath);

echo "\n=== Test Complete ===\n";

