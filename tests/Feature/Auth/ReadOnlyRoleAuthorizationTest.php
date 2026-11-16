<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterBranch;
use App\Models\Business\MasterCustomer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReadOnlyRoleAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    protected AdminUser $readOnlyUser;
    protected AdminUser $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an unassigned user with zero roles
        $this->readOnlyUser = AdminUser::create([
            'username' => 'readonly_' . Str::random(8),
            'name' => 'Read Only User',
            'email' => 'readonly_' . Str::random(8) . '@th.nissin-asia.com',
            'password' => Hash::make('Secret123!'),
            'email_verified_at' => now(),
        ]);

        // Find or create an admin user with administrator role
        $this->adminUser = AdminUser::find(1) ?? AdminUser::create([
            'username' => 'admin_' . Str::random(8),
            'name' => 'Admin User',
            'email' => 'admin_' . Str::random(8) . '@th.nissin-asia.com',
            'password' => Hash::make('Secret123!'),
            'email_verified_at' => now(),
        ]);
    }

    public function test_unassigned_user_has_read_only_capabilities(): void
    {
        $this->assertFalse($this->readOnlyUser->hasAnyRole());
        $this->assertTrue($this->readOnlyUser->isReadOnly());
        $this->assertFalse($this->readOnlyUser->canWrite());
        $this->assertFalse($this->readOnlyUser->canDownload());
    }

    public function test_unassigned_user_can_view_clearance_home_dashboard(): void
    {
        $response = $this->actingAs($this->readOnlyUser)->get('/clearance');

        $response->assertStatus(200);
        $response->assertSee('Read-Only Access');
        $response->assertSee('Read-Only Mode');

        // Verify creation and upload buttons/shortcuts are NOT visible
        $response->assertDontSee('Register CCD');
        $response->assertDontSee('Upload Requisition');
        $response->assertDontSee('Weekly Export');
        $response->assertDontSee('Manual New CCD');
    }

    public function test_unassigned_user_cannot_access_create_screen(): void
    {
        $response = $this->actingAs($this->readOnlyUser)->get('/clearance/create');

        $response->assertStatus(403);
    }

    public function test_unassigned_user_cannot_access_upload_screen(): void
    {
        $response = $this->actingAs($this->readOnlyUser)->get('/clearance/upload');

        $response->assertStatus(403);
    }

    public function test_unassigned_user_cannot_submit_store_action(): void
    {
        $response = $this->actingAs($this->readOnlyUser)->post('/clearance/store', [
            'job_no' => 'CCD2699-9999',
            'customer_name' => 'Test Corp',
        ]);

        $response->assertStatus(403);
    }

    public function test_unassigned_user_can_view_show_details_without_edit_or_download_buttons(): void
    {
        $existing = CustomerClearanceData::first();
        if ($existing) {
            $record = $existing->replicate();
            $record->job_no = 'CCD' . date('ym') . '-' . rand(1000, 9999);
            $record->reference_file_1 = 'reference/test_ref_1.pdf';
            $record->save();
        } else {
            $record = CustomerClearanceData::create([
                'job_no' => 'CCD' . date('ym') . '-' . rand(1000, 9999),
                'master_customer_id' => 1,
                'customer_name' => 'Authorization Test Corp',
                'master_branch_id' => 1,
                'imp_exp' => 0,
                'product_description_en' => 'Test Laptop Computer',
                'product_description_th' => 'คอมพิวเตอร์พกพา',
                'hs_code' => '84713020',
                'statistic_code' => '000',
                'reference_file_1' => 'reference/test_ref_1.pdf',
            ]);
        }

        $response = $this->actingAs($this->readOnlyUser)->get('/clearance/' . $record->id);

        $response->assertStatus(200);
        // Record details should be visible
        $response->assertSee($record->job_no);
        $response->assertSee('View');

        // Edit Record button must NOT display
        $response->assertDontSee('Edit Record');
        // Download button must NOT display
        $response->assertDontSee('Download');
    }

    public function test_unassigned_user_cannot_access_edit_screen(): void
    {
        $record = CustomerClearanceData::first();
        if (!$record) {
            $this->markTestSkipped('No clearance records available for edit test.');
        }

        $response = $this->actingAs($this->readOnlyUser)->get('/clearance/' . $record->id . '/edit');

        $response->assertStatus(403);
    }

    public function test_unassigned_user_cannot_access_download_routes(): void
    {
        $record = CustomerClearanceData::first();
        if (!$record) {
            $this->markTestSkipped('No clearance records available for download test.');
        }

        $response = $this->actingAs($this->readOnlyUser)->get('/clearance/' . $record->id . '/download/reference_file_1');

        $response->assertStatus(403);
    }

    public function test_unassigned_user_cannot_access_export_weekly(): void
    {
        $response = $this->actingAs($this->readOnlyUser)->get('/export/weekly');

        $response->assertStatus(403);
    }

    public function test_unassigned_user_cannot_attach_evidence_to_outstanding(): void
    {
        $record = CustomerClearanceData::first();
        if (!$record) {
            $this->markTestSkipped('No clearance records available.');
        }

        $response = $this->actingAs($this->readOnlyUser)->post('/compliance/outstanding/' . $record->id . '/attach', [
            'evidence_slot' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_unassigned_user_sees_no_create_edit_attach_or_download_buttons_in_outstanding(): void
    {
        $response = $this->actingAs($this->readOnlyUser)->get('/compliance/outstanding');

        $response->assertStatus(200);

        // Header create button should not appear
        $response->assertDontSee('>Create<', false);

        // Table edit and attach buttons should not appear
        $response->assertDontSee('title="Edit Link"', false);
        $response->assertDontSee('+ Attach');
        $response->assertDontSee('Upload Customs Evidence');

        // Download links should not appear
        $response->assertDontSee('title="Download Reference File 1', false);
        $response->assertDontSee('title="Download Signed Req Form', false);
    }

    public function test_authorized_user_can_see_write_and_download_buttons(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/clearance');

        $response->assertStatus(200);
        $response->assertSee('Register CCD');
        $response->assertSee('Upload Requisition');
        $response->assertSee('Weekly Export');
    }

    public function test_standard_user_can_read_create_download_upload_but_cannot_edit_or_delete(): void
    {
        $standardRole = AdminRole::firstOrCreate(
            ['slug' => 'standard'],
            ['name' => 'Standard User']
        );

        $standardUser = AdminUser::create([
            'username' => 'std_' . Str::random(8),
            'name' => 'Standard User',
            'email' => 'std_' . Str::random(8) . '@th.nissin-asia.com',
            'password' => Hash::make('Secret123!'),
            'email_verified_at' => now(),
        ]);
        $standardUser->roles()->attach($standardRole->id);
        $standardUser->load('roles');

        // Verify capabilities
        $this->assertTrue($standardUser->isStandard());
        $this->assertFalse($standardUser->isReadOnly());
        $this->assertTrue($standardUser->canCreate());
        $this->assertTrue($standardUser->canUpload());
        $this->assertTrue($standardUser->canDownload());
        $this->assertFalse($standardUser->canEdit());
        $this->assertFalse($standardUser->canDelete());

        // 1. Can view home dashboard and sees create/upload/download shortcuts
        $response = $this->actingAs($standardUser)->get('/clearance');
        $response->assertStatus(200);
        $response->assertSee('Register CCD');
        $response->assertSee('Upload Requisition');
        $response->assertSee('Weekly Export');
        $response->assertSee('Manual New CCD');

        // 2. Can access create and upload screens
        $this->actingAs($standardUser)->get('/clearance/create')->assertStatus(200);
        $this->actingAs($standardUser)->get('/clearance/upload')->assertStatus(200);
        $this->actingAs($standardUser)->get('/export/weekly')->assertStatus(200);

        // 3. In detail view: sees Download, but Edit Record button does NOT display
        $record = CustomerClearanceData::first();
        if ($record) {
            $showResponse = $this->actingAs($standardUser)->get('/clearance/' . $record->id);
            $showResponse->assertStatus(200);
            $showResponse->assertDontSee('Edit Record');

            // 4. In outstanding compliance: sees Create, but Edit Link does NOT display
            $outstandingResponse = $this->actingAs($standardUser)->get('/compliance/outstanding');
            $outstandingResponse->assertStatus(200);
            $outstandingResponse->assertSee('Create');
            $outstandingResponse->assertDontSee('title="Edit Link"', false);

            // 5. Direct access to edit/update/delete returns 403 Forbidden
            $this->actingAs($standardUser)->get('/clearance/' . $record->id . '/edit')->assertStatus(403);
            $this->actingAs($standardUser)->put('/clearance/' . $record->id, [])->assertStatus(403);
            $this->actingAs($standardUser)->delete('/clearance/' . $record->id)->assertStatus(403);
        }
    }
}
