<?php

namespace Tests\Feature\Clearance;

use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterBranch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Gemini: Regression test for D-01 — file responses (S3 download stream) must not crash in
 * PreventBackOnLogout and must still carry the no-cache headers.
 */
class DocumentDownloadTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mysql'];

    private function admin(): AdminUser
    {
        $user = new AdminUser(['name' => 'Download Tester', 'email' => 'download_test@th.nissin-asia.com']);
        $user->id = 987654323;
        $user->setRelation('roles', collect([new AdminRole(['slug' => 'administrator'])]));

        return $user;
    }

    public function test_attached_document_downloads_with_no_cache_headers(): void
    {
        Storage::fake('s3');
        Storage::fake('public');
        config(['filesystems.disks.s3.key' => 'test-key', 'filesystems.disks.s3.bucket' => 'test-bucket']);
        Storage::disk('s3')->put('reference/download_probe.pdf', '%PDF-1.4 probe');

        $branch = MasterBranch::first() ?? MasterBranch::create(['id' => 1, 'branch_code' => 'TST', 'branch_name' => 'Test']);
        $record = CustomerClearanceData::create([
            'job_no' => 'CCD' . date('ym') . '-' . random_int(1000, 9999),
            'master_branch_id' => $branch->id,
            'imp_exp' => 0,
            'product_description_en' => 'Download probe',
            'hs_code' => '84713020',
            'reference_file_1' => 'reference/download_probe.pdf',
            'create_date' => date('Y-m-d'),
            'update_date' => date('Y-m-d'),
            'edit_user_id' => 1,
        ]);

        $response = $this->actingAs($this->admin())->get("/clearance/{$record->id}/download/reference_file_1");

        $this->assertSame(200, $response->baseResponse->getStatusCode());
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_normal_pages_still_carry_no_cache_headers(): void
    {
        $response = $this->actingAs($this->admin())->get('/clearance');

        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
    }
}
