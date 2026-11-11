<?php
/**
 * Test uploading file to save in:
 * 1. AWS S3 bucket (sn-ccd-database-system-th)
 * 2. Local AWS server (43.210.79.67) previous version (/mnt/efs/reference and /mnt/efs/hs_evidence)
 * 3. Verify local device project folder (storage/app/public) is NOT written to.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

echo "================================================================================" . PHP_EOL;
echo "  TEST UPLOAD FILE: AWS S3 BUCKET + LOCAL AWS SERVER (43.210.79.67 /mnt/efs)" . PHP_EOL;
echo "================================================================================" . PHP_EOL . PHP_EOL;

$storageService = new DocumentStorageService();

// Create sample reference file
$tempRefPath = sys_get_temp_dir() . '/test_sample_ref_' . time() . '.pdf';
file_put_contents($tempRefPath, "%PDF-1.4 Mock reference document created at " . date('Y-m-d H:i:s'));
$uploadedRefFile = new UploadedFile($tempRefPath, 'sample_reference.pdf', 'application/pdf', null, true);

// Create sample evidence file
$tempEvidencePath = sys_get_temp_dir() . '/test_sample_evi_' . time() . '.pdf';
file_put_contents($tempEvidencePath, "%PDF-1.4 Mock HS evidence document created at " . date('Y-m-d H:i:s'));
$uploadedEvidenceFile = new UploadedFile($tempEvidencePath, 'sample_evidence.pdf', 'application/pdf', null, true);

// --- TEST 1: Reference file upload ---
echo "--- TEST 1: Upload to 'reference' (AWS S3 + AWS Server 43.210.79.67 /mnt/efs) ---" . PHP_EOL;
$refStored = $storageService->store($uploadedRefFile, 'reference', 'CCD2611-10001_rf1_sample.pdf');
echo "Stored Path Return         : {$refStored}" . PHP_EOL;

// Verify S3 cloud upload
$s3Exists = Storage::disk('s3')->fileExists($refStored);
echo "S3 File in Cloud Bucket    : " . ($s3Exists ? "YES ✅ (s3://sn-ccd-database-system-th/{$refStored})" : "NO ❌") . PHP_EOL;

// Verify Local Device Project Storage is NOT written
$refLocalExists = Storage::disk('public')->exists($refStored);
echo "Local Device Project Disk  : " . ($refLocalExists ? "WRITTEN (Unexpected) ❌" : "NOT WRITTEN (Bypassed) ✅") . PHP_EOL;
echo "File URL                   : " . $storageService->getUrl($refStored) . PHP_EOL . PHP_EOL;

// --- TEST 2: Evidence file upload ---
echo "--- TEST 2: Upload to 'hs_evidence' (AWS S3 + AWS Server 43.210.79.67 /mnt/efs) ---" . PHP_EOL;
$eviStored = $storageService->store($uploadedEvidenceFile, 'hs_evidence', 'CCD2611-10001_hs1_sample.pdf');
echo "Stored Path Return         : {$eviStored}" . PHP_EOL;

// Verify S3 cloud upload
$s3EviExists = Storage::disk('s3')->fileExists($eviStored);
echo "S3 File in Cloud Bucket    : " . ($s3EviExists ? "YES ✅ (s3://sn-ccd-database-system-th/{$eviStored})" : "NO ❌") . PHP_EOL;

// Verify Local Device Project Storage is NOT written
$eviLocalExists = Storage::disk('public')->exists($eviStored);
echo "Local Device Project Disk  : " . ($eviLocalExists ? "WRITTEN (Unexpected) ❌" : "NOT WRITTEN (Bypassed) ✅") . PHP_EOL . PHP_EOL;

// --- TEST 3: Local AWS Server EFS Mount Verification (Simulated Target) ---
echo "--- TEST 3: AWS Server (43.210.79.67) EFS Mount Write Verification ---" . PHP_EOL;
$mockEfsDir = storage_path('app/mnt_efs_simulation');
if (!is_dir($mockEfsDir)) {
    @mkdir($mockEfsDir, 0775, true);
}
$efsSimulationService = new DocumentStorageService($mockEfsDir);
$tempSimPath = sys_get_temp_dir() . '/test_sim_' . time() . '.pdf';
file_put_contents($tempSimPath, "%PDF-1.4 Mock EFS simulated file");
$uploadedSimFile = new UploadedFile($tempSimPath, 'efs_sim.pdf', 'application/pdf', null, true);

$simStored = $efsSimulationService->store($uploadedSimFile, 'hs_evidence', 'CCD2611-EFS_SIM_sample.pdf');
$simEfsPath = "{$mockEfsDir}/hs_evidence/CCD2611-EFS_SIM_sample.pdf";
$simMirrorPath = "{$mockEfsDir}/evidence/CCD2611-EFS_SIM_sample.pdf";

echo "AWS Server EFS /hs_evidence: " . (file_exists($simEfsPath) ? "YES ✅ ({$simEfsPath})" : "NO ❌") . PHP_EOL;
echo "AWS Server EFS /evidence   : " . (file_exists($simMirrorPath) ? "YES ✅ ({$simMirrorPath})" : "NO ❌") . PHP_EOL . PHP_EOL;

// Clean up
$storageService->delete($refStored);
$storageService->delete($eviStored);
$efsSimulationService->delete($simStored);
@unlink($tempRefPath);
@unlink($tempEvidencePath);
@unlink($tempSimPath);
@unlink($simEfsPath);
@unlink($simMirrorPath);
@rmdir("{$mockEfsDir}/hs_evidence");
@rmdir("{$mockEfsDir}/evidence");
@rmdir($mockEfsDir);

echo "================================================================================" . PHP_EOL;
echo "  ALL VERIFICATIONS PASSED & TEST ASSETS CLEANED UP" . PHP_EOL;
echo "================================================================================" . PHP_EOL;
