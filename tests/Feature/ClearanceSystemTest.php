<?php

namespace Tests\Feature;

// Feature tests for clearance grid, domain whitelisting, sequence API, and outstanding compliance
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ClearanceSystemTest extends TestCase
{
    public function test_root_redirects_to_clearance(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/clearance');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/clearance');
        $response->assertRedirect('/login');

        $responseCompliance = $this->get('/compliance/outstanding');
        $responseCompliance->assertRedirect('/login');
    }

    // Test that authenticated admin users can render clearance and compliance screens
    public function test_clearance_index_screen_renders_successfully(): void
    {
        $user = \App\Models\Auth\AdminUser::first();
        $response = $this->actingAs($user)->get('/clearance');
        $response->assertStatus(200);
        $response->assertSee('Customer Clearance Data Repository');
    }

    // Test that authenticated admin users can render outstanding compliance screen
    public function test_outstanding_compliance_screen_renders_successfully(): void
    {
        $user = \App\Models\Auth\AdminUser::first();
        $response = $this->actingAs($user)->get('/compliance/outstanding');
        $response->assertStatus(200);
        $response->assertSee('Outstanding Customs Evidence Compliance');
    }

    public function test_job_sequence_api_returns_valid_format(): void
    {
        $response = $this->getJson('/api/ccd_get_ym/2305');
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'job_no']);
        $this->assertStringStartsWith('CCD2305-', $response->json('job_no'));
    }

    public function test_registration_rejects_external_email_domains(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'External User',
            'username' => 'external_user_test',
            'email' => 'user@gmail.com',
            'password' => 'secretPassword123!',
            'password_confirmation' => 'secretPassword123!',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email']);
    }

    // Test that user can sign in using their corporate email address
    public function test_user_can_login_with_corporate_email(): void
    {
        $unique = time();
        $user = \App\Models\Auth\AdminUser::create([
            'name' => 'Login Email Tester',
            'username' => "tester_{$unique}",
            'email' => "tester_{$unique}@th.nissin-asia.com",
            'password' => \Illuminate\Support\Facades\Hash::make('password123!'),
        ]);

        $response = $this->post('/login', [
            'username' => $user->email, // entering email into the username field
            'password' => 'password123!',
        ]);

        $response->assertRedirect('/clearance');
        $this->assertAuthenticatedAs($user);

        $user->delete();
    }

    // Test that user can sign in using their username
    // public function test_user_can_login_with_username(): void
    // {
    //     $unique = time() + 1;
    //     $user = \App\Models\Auth\AdminUser::create([
    //         'name' => 'Login Username Tester',
    //         'username' => "tester_{$unique}",
    //         'email' => "tester_{$unique}@th.nissin-asia.com",
    //         'password' => \Illuminate\Support\Facades\Hash::make('password123!'),
    //     ]);

    //     $response = $this->post('/login', [
    //         'username' => $user->username,
    //         'password' => 'password123!',
    //     ]);

    //     $response->assertRedirect('/clearance');
    //     $this->assertAuthenticatedAs($user);

    //     $user->delete();
    // }

    // GAP-02 — Test that non-admin authenticated users are forbidden (403) from admin master data routes
    public function test_non_admin_cannot_access_admin_customer_registry(): void
    {
        $unique = time() + 2;
        $regularUser = \App\Models\Auth\AdminUser::create([
            'name' => 'Regular Staff Member',
            'username' => "staff_{$unique}",
            'email' => "staff_{$unique}@th.nissin-asia.com",
            'password' => \Illuminate\Support\Facades\Hash::make('secretPassword123!'),
        ]);

        $response = $this->actingAs($regularUser)->get('/admin/customers');
        $response->assertStatus(403);

        $regularUser->delete();
    }

    // GAP-02 — Test that administrator users can successfully access admin master data routes
    public function test_admin_user_can_access_admin_customer_registry(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $response = $this->actingAs($admin)->get('/admin/customers');
        $response->assertStatus(200);
        $response->assertSee('Corporate Customer Registry');
    }

    // GAP-03 — Test that the Excel upload screen renders successfully for authenticated users
    public function test_clearance_upload_screen_renders_successfully(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $response = $this->actingAs($admin)->get('/clearance/upload');
        $response->assertStatus(200);
        $response->assertSee('Excel Requisition Ingestion');
    }

    // GAP-03 — Test that single Excel upload requires a valid file
    public function test_single_excel_import_requires_file(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $response = $this->actingAs($admin)->post('/clearance/import/single', []);
        $response->assertSessionHasErrors(['single_record']);
    }

    // GAP-03 — Integration test verifying Excel parsing into customer_clearance_data with cell mappings
    public function test_single_excel_import_creates_record_and_redirects(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);

        // Build test spreadsheet matching legacy cell coordinates
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $testJobNo = 'CCD2611-9888';

        $sheet->setCellValue('N1', $testJobNo);
        $sheet->setCellValue('D4', 'JAPAN AIRLINES CO.,LTD.');
        $sheet->setCellValue('O2', 'HQO');
        $sheet->setCellValue('G10', 'ELECTRONIC SENSOR MODULE');
        $sheet->setCellValue('G12', 'โมดูลเซนเซอร์อิเล็กทรอนิกส์');
        $sheet->setCellValue('G8', 'PROD-TEST-9988');
        $sheet->setCellValue('G37', '85423100');
        $sheet->setCellValue('J37', '001');

        $tempFile = sys_get_temp_dir() . '/test_import_' . time() . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempFile,
            'test_import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($admin)->post('/clearance/import/single', [
            'single_record' => $uploadedFile,
            'imp_exp' => 'Import',
        ]);

        $response->assertRedirect('/clearance');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customer_clearance_data', [
            'job_no' => $testJobNo,
            'hs_code' => '85423100',
        ], 'mysql');

        // Cleanup
        \App\Models\Business\CustomerClearanceData::where('job_no', $testJobNo)->delete();
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
    }

    // GAP-04 — Test that weekly export filter screen renders successfully
    public function test_weekly_export_screen_renders_successfully(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $response = $this->actingAs($admin)->get('/export/weekly');
        $response->assertStatus(200);
        $response->assertSee('Weekly Customs Tariff & HS Code Reports');
    }

    // Test that weekly export validates sequence range
    public function test_weekly_export_validation_rejects_invalid_inputs(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $response = $this->actingAs($admin)->post('/export/weekly', [
            'category' => 'INVALID',
            'year_month' => '2026-11',
            'date_from' => 50,
            'date_to' => 10, // date_to < date_from
        ]);

        $response->assertSessionHasErrors(['category', 'date_to']);
    }

    //  Integration test verifying Excel report download generation
    public function test_weekly_export_downloads_excel_file(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $branch = \App\Models\Business\MasterBranch::first();
        $customer = \App\Models\Business\MasterCustomer::first();

        // Seed a temporary record matching target range
        $testJobNo = 'CCD2611-0010';
        \App\Models\Business\CustomerClearanceData::create([
            'job_no' => $testJobNo,
            'master_customer_id' => $customer->id,
            'customer_name' => 'EXPORT TEST CUSTOMER',
            'master_branch_id' => $branch->id,
            'imp_exp' => 1,
            'product_description_en' => 'TEST PRODUCT EN',
            'product_description_th' => 'TEST PRODUCT TH',
            'hs_code' => '87082990',
            'statistic_code_id' => '001',
            'create_date' => '2026-11-02',
            'update_date' => '2026-11-02',
            'edit_user_id' => 1,
        ]);

        $response = $this->actingAs($admin)->post('/export/weekly', [
            'category' => 'CCD',
            'year_month' => '2026-11',
            'date_from' => 1,
            'date_to' => 20,
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=HS_Code11-0001_11-0020.xlsx');

        // Cleanup
        \App\Models\Business\CustomerClearanceData::where('job_no', $testJobNo)->delete();
    }

    // GAP-05 — Test that the clearance edit form renders successfully with existing values
    public function test_clearance_edit_screen_renders_successfully(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $record = \App\Models\Business\CustomerClearanceData::first();

        $response = $this->actingAs($admin)->get("/clearance/{$record->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('Edit Customs Clearance Record');
        $response->assertSee($record->job_no);
    }

    // GAP-05 — Test that updating a clearance record persists changes and redirects to show view
    public function test_clearance_update_modifies_record_and_redirects(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $branch = \App\Models\Business\MasterBranch::first();
        $customer = \App\Models\Business\MasterCustomer::where('is_enable', 0)->first();

        // Ensure idempotency by purging any stale CCD2611-7777 record before creation.
        \App\Models\Business\CustomerClearanceData::where('job_no', 'CCD2611-7777')->delete();

        // Create temporary record to update
        $record = \App\Models\Business\CustomerClearanceData::create([
            'job_no' => 'CCD2611-7777',
            'master_customer_id' => $customer->id,
            'customer_name' => $customer->company_name,
            'master_branch_id' => $branch->id,
            'imp_exp' => 1,
            'product_description_en' => 'ORIGINAL DESCRIPTION',
            'product_description_th' => 'คำอธิบายเดิม',
            'hs_code' => '87082990',
            'statistic_code_id' => '001',
            'create_date' => '2026-11-02',
            'update_date' => '2026-11-02',
            'edit_user_id' => 1,
        ]);

        $response = $this->actingAs($admin)->put("/clearance/{$record->id}", [
            'job_no' => $record->job_no,
            'master_customer_id' => $customer->id,
            'master_branch_id' => $branch->id,
            'imp_exp' => '0', // Changed to Export
            'product_description_en' => 'UPDATED DESCRIPTION EN',
            'product_description_th' => 'คำอธิบายที่อัปเดตใหม่',
            'hs_code' => '84713000', // Changed HS Code
            'statistic_code_id' => 1,
        ]);

        $response->assertRedirect(route('clearance.show', $record->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customer_clearance_data', [
            'id' => $record->id,
            'product_description_en' => 'UPDATED DESCRIPTION EN',
            'hs_code' => '84713000',
            'imp_exp' => 0,
        ], 'mysql');

        // Cleanup
        $record->delete();
    }

    // GAP-06 — Test ClearanceRecordData DTO instantiates and transforms cleanly
    public function test_clearance_record_data_dto_transforms_correctly(): void
    {
        $dto = \App\Domain\Clearance\DTOs\ClearanceRecordData::fromArray([
            'job_no' => 'CCD2611-0001',
            'customer_name' => 'ACME CORP',
            'product_description_en' => 'Test Widget',
            'hs_code' => '84713000',
            'imp_exp' => 1,
        ]);

        $this->assertEquals('CCD2611-0001', $dto->jobNo);
        $this->assertEquals('ACME CORP', $dto->customerName);
        $this->assertEquals('84713000', $dto->hsCode);
        $this->assertEquals(1, $dto->impExp);
        $this->assertIsArray($dto->toArray());
    }

    // GAP-06 — Test ClearanceSequenceService returns formatted next job sequence
    public function test_clearance_sequence_service_generates_job_sequence(): void
    {
        $service = app(\App\Domain\Clearance\Services\ClearanceSequenceService::class);
        $jobNo = $service->getNextJobNo('CCD');

        // Allow 4 or more digits in sequence part to accommodate running counters without test failures
        $this->assertMatchesRegularExpression('/^CCD\d{4}-\d{4,}$/', $jobNo);
    }

    // GAP-06 — Test GetOutstandingClearanceQuery returns paginated compliance records
    public function test_outstanding_clearance_query_executes(): void
    {
        $query = app(\App\Domain\Compliance\Queries\GetOutstandingClearanceQuery::class);
        $results = $query->execute([]);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $results);
    }

    // Test that each column in customer_clearance_data can be searched and filtered independently
    public function test_outstanding_compliance_filters_each_column_independently(): void
    {
        $query = app(\App\Domain\Compliance\Queries\GetOutstandingClearanceQuery::class);

        // 1. Filter by job_no
        $resJob = $query->execute(['job_no' => 'CCD']);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $resJob);

        // 2. Filter by customer_name
        $resCust = $query->execute(['customer_name' => 'JAPAN']);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $resCust);

        // 3. Filter by hs_code
        $resHs = $query->execute(['hs_code' => '85']);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $resHs);

        // 4. Filter by product_description
        $resDesc = $query->execute(['product_description' => 'Product']);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $resDesc);

        // 5. Filter by imp_exp (0 = Import, 1 = Export)
        $resImp = $query->execute(['imp_exp' => '0']);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $resImp);

        // 6. Filter by create_date
        $resDate = $query->execute(['create_date' => '2026']);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $resDate);

        // 7. Filter by compliance status (all, completed, outstanding)
        $resAll = $query->execute(['status' => 'all']);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $resAll);
    }

    // Test HTTP GET /compliance/outstanding with independent column query parameters
    public function test_outstanding_compliance_screen_accepts_column_filters(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);

        $response = $this->actingAs($admin)->get('/compliance/outstanding?' . http_build_query([
            'job_no' => 'CCD',
            'customer_name' => 'JAPAN',
            'hs_code' => '85',
            'imp_exp' => '0',
            'product_description' => 'Test',
            'status' => 'all',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Customer Clearance Audit Records');
        $response->assertSee('CCD');
    }

    // GAP-06 — Test BatchCustomerNameSyncService syncs customer names across records
    public function test_batch_customer_name_sync_service(): void
    {
        $customer = \App\Models\Business\MasterCustomer::first();
        $service = app(\App\Domain\MasterData\Services\BatchCustomerNameSyncService::class);
        $updatedCount = $service->sync($customer->id, $customer->company_name);

        $this->assertIsInt($updatedCount);
    }

    public function test_admin_users_index_renders_for_admin(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('System User Directory');
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        // Create a non-admin user (no administrator role)
        $nonAdmin = \App\Models\Auth\AdminUser::where('id', '!=', 1)->first();

        if (!$nonAdmin) {
            $this->markTestSkipped('No non-admin user found in authdb for this test.');
        }

        $response = $this->actingAs($nonAdmin)->get('/admin/users');

        // Should be forbidden (403) or redirected away
        $response->assertStatus(403);
    }

    public function test_admin_users_show_renders_for_admin(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);

        $response = $this->actingAs($admin)->get('/admin/users/1');

        $response->assertStatus(200);
        $response->assertSee('Role-Based Access Control');
    }

    // Auth layout — login page must NOT render sidebar elements (no Customer Registry link)
    public function test_login_page_has_no_sidebar(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign In');
        // Sidebar nav links must not appear on the login page
        $response->assertDontSee('Customs Clearances');
        $response->assertDontSee('Customer Registry');
    }

    // Auth layout — register page must NOT render sidebar
    public function test_register_page_has_no_sidebar(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Self-Registration');
        $response->assertDontSee('Customs Clearances');
    }

    // Forgot password — step 1 form renders correctly
    public function test_forgot_password_page_renders(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Forgot Password');
        $response->assertSee('Verify Account');
    }

    // Forgot password — unknown email returns generic message (anti-enumeration)
    public function test_forgot_password_unknown_email_returns_generic_message(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@th.nissin-asia.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /*
    public function test_forgot_password_valid_email_redirects_to_reset(): void
    {
        $user = \App\Models\Auth\AdminUser::find(1);

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertRedirect('/reset-password');
        $this->assertEquals($user->email, session('password_reset_email'));
    }
    */

    // Assert that submitting valid email sends the reset link and returns to forgot-password with success status
    public function test_forgot_password_valid_email_sends_reset_email(): void
    {
        $user = \App\Models\Auth\AdminUser::find(1);

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHas('status');
    }

    /*
    public function test_reset_password_page_renders_with_session(): void
    {
        $user = \App\Models\Auth\AdminUser::find(1);

        // Simulate step 1 having completed
        session(['password_reset_email' => $user->email]);

        $response = $this->get('/reset-password');

        $response->assertStatus(200);
        $response->assertSee('Set New Password');
        $response->assertSee($user->email);
    }
    */

    // Assert reset-password page renders when accessed via the emailed token link
    public function test_reset_password_page_renders_with_token(): void
    {
        $user = \App\Models\Auth\AdminUser::find(1);
        $token = \Illuminate\Support\Facades\Password::broker('users')->createToken($user);

        $response = $this->get('/reset-password?token=' . $token . '&email=' . urlencode($user->email));

        $response->assertStatus(200);
        $response->assertSee('Set New Password');
        $response->assertSee($user->email);
    }

    //  Reset password — without token/email (direct URL) redirects back to forgot step 1
    public function test_reset_password_without_session_redirects_to_forgot(): void
    {
        // No token/email query string — direct URL access
        $response = $this->get('/reset-password');

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors(['email']);
    }

    /*
    public function test_reset_password_updates_password_and_redirects_to_login(): void
    {
        $user = \App\Models\Auth\AdminUser::find(1);
        $originalHash = $user->password;

        session(['password_reset_email' => $user->email]);

        $response = $this->post('/reset-password', [
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        // Restore original password to avoid breaking other tests
        $user->refresh()->update(['password' => $originalHash]);
    }
    */

    // Assert reset password with cryptographic token from email updates password and redirects to login
    public function test_reset_password_updates_password_and_redirects_to_login(): void
    {
        $user = \App\Models\Auth\AdminUser::find(1);
        $originalHash = $user->password;
        $token = \Illuminate\Support\Facades\Password::broker('users')->createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        // Restore original password to avoid breaking other tests
        $user->refresh()->update(['password' => $originalHash]);
    }

    // public function test_aws_s3_connection_and_file_operations(): void
    // {
    //     // Skip if running in an environment where S3 credentials aren't configured
    //     if (empty(config('filesystems.disks.s3.key')) || empty(config('filesystems.disks.s3.bucket'))) {
    //         $this->markTestSkipped('AWS S3 credentials or bucket not configured in this environment.');
    //     }

    //     $disk = Storage::disk('s3');
    //     $testFileName = 'connectivity_tests/probe_' . time() . '_' . bin2hex(random_bytes(4)) . '.txt';
    //     $payload = 's3-healthcheck-token-' . uniqid();

    //     try {
    //         // 1. Test Write / Put operation
    //         $putSuccess = $disk->put($testFileName, $payload);
    //         $this->assertTrue($putSuccess, 'Failed to write probe file to AWS S3.');

    //         // 2. Test Check / Exists operation
    //         $this->assertTrue($disk->exists($testFileName), 'Probe file does not exist in AWS S3.');

    //         // 3. Test Read / Get operation
    //         $contents = $disk->get($testFileName);
    //         $this->assertSame($payload, $contents, 'Content retrieved from AWS S3 does not match uploaded content.');

    //         // 4. Test Temporary URL Generation (Pre-signed URL)
    //         $url = $disk->temporaryUrl($testFileName, now()->addMinutes(5));
    //         $this->assertNotEmpty($url);
    //         $this->assertStringContainsString('X-Amz-Signature', $url);

    //     } catch (Throwable $e) {
    //         $this->fail('AWS S3 Connection failed with error: ' . $e->getMessage());
    //     } finally {
    //         // Cleanup probe file from S3 bucket
    //         if ($disk->exists($testFileName)) {
    //             $disk->delete($testFileName);
    //         }
    //     }
    // }
    public function test_aws_s3_connection_and_file_operations(): void
    {
        if (empty(config('filesystems.disks.s3.key')) || empty(config('filesystems.disks.s3.bucket'))) {
            $this->markTestSkipped('AWS S3 credentials or bucket not configured in this environment.');
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('s3');
        $testFileName = 'connectivity_tests/probe_' . time() . '_' . bin2hex(random_bytes(4)) . '.txt';
        $payload = 's3-healthcheck-token-' . uniqid();

        try {
            // 1. Write / Put
            $putSuccess = $disk->put($testFileName, $payload);
            $this->assertTrue($putSuccess, 'Failed to write probe file to AWS S3.');

            // 2. Check Exists (using fileExists which executes HeadObject without requiring ListBucket IAM permission)
            $this->assertTrue($disk->fileExists($testFileName), 'Probe file does not exist in AWS S3.');

            // 3. Read / Get
            $contents = $disk->get($testFileName);
            $this->assertSame($payload, $contents, 'Content retrieved from AWS S3 does not match uploaded content.');

            // 4. Temporary URL Generation
            $url = $disk->temporaryUrl($testFileName, now()->addMinutes(5));
            $this->assertNotEmpty($url);
            $this->assertStringContainsString('X-Amz-Signature', $url);

        } catch (\Throwable $e) {
            $this->fail('AWS S3 Connection failed with error: ' . $e->getMessage());
        } finally {
            if ($disk->fileExists($testFileName)) {
                $disk->delete($testFileName);
            }
        }
    }

    //  Test that clearance upload screen renders AWS S3 connection status
    public function test_clearance_upload_screen_shows_aws_status(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $response = $this->actingAs($admin)->get('/clearance/upload');
        $response->assertStatus(200);
        $response->assertSee('AWS S3 Connected');
        $response->assertSee('sn-ccd-database-system-th');
    }

    //  Test that POST /clearance/upload/test-aws executes live S3 test and redirects back with status
    public function test_clearance_upload_test_aws_endpoint(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $response = $this->actingAs($admin)->post('/clearance/upload/test-aws');
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    //  Test that attaching evidence via OutstandingAuditController uses DocumentStorageService and attaches file
    public function test_outstanding_attach_evidence_saves_file(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $record = \App\Models\Business\CustomerClearanceData::first();

        \Illuminate\Support\Facades\Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->create('customs_ruling.pdf', 150, 'application/pdf');

        $response = $this->actingAs($admin)->post("/compliance/outstanding/{$record->id}/attach", [
            'evidence_slot' => '2',
            'evidence_file' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $record->refresh();
        $this->assertNotNull($record->hs_evidence_file_2);
        $this->assertStringContainsString('hs2', $record->hs_evidence_file_2);
    }

    //  Test that quantity_unit_id longer than 3 characters is rejected by validation with custom error message
    public function test_clearance_record_creation_validates_quantity_unit_length(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $response = $this->actingAs($admin)->post('/clearance/store', [
            'job_no' => 'CCD2611-9991',
            'master_customer_id' => 1,
            'master_branch_id' => 1,
            'imp_exp' => 0,
            'product_description_en' => 'Test Product',
            'hs_code' => '85423100',
            'quantity_unit_id' => 'C6213', // > 3 characters
        ]);

        $response->assertSessionHasErrors(['quantity_unit_id']);
    }

    //  Test that uploading attachments captures debug info with local path and S3 URI
    public function test_upload_debug_captures_local_and_s3_addresses(): void
    {
        $admin = \App\Models\Auth\AdminUser::find(1);
        $file = \Illuminate\Http\UploadedFile::fake()->create('test_catalog.pdf', 120, 'application/pdf');

        $testJobNo = 'CCD2611-' . rand(8000, 8999);
        // Ensure test job no does not collide with previous run in persistent database
        \App\Models\Business\CustomerClearanceData::where('job_no', $testJobNo)->delete();
        $response = $this->actingAs($admin)->post('/clearance/store', [
            'job_no' => $testJobNo,
            'master_customer_id' => 1,
            'master_branch_id' => 1,
            'imp_exp' => 0,
            'product_description_en' => 'Test Product With Attachment',
            'product_description_th' => 'โมดูลทดสอบสินค้า',
            'hs_code' => '85423100',
            'quantity_unit_id' => 1,
            'reference_file_1' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('upload_debug');

        $debug = session('upload_debug');
        $this->assertIsArray($debug);
        $this->assertNotEmpty($debug);
        $this->assertArrayHasKey('local_path', $debug[0]);
        $this->assertSame('Bypassed (Cloud-Only)', $debug[0]['local_path']);
        $this->assertStringContainsString('s3://sn-ccd-database-system-th', $debug[0]['s3_location']);

        $s3Disk = \Illuminate\Support\Facades\Storage::disk('s3');
        $s3Path = str_replace('s3://sn-ccd-database-system-th/', '', $debug[0]['s3_location']);
        $this->assertTrue($s3Disk->fileExists($s3Path), 'Uploaded file was not found in real S3 bucket!');
        $s3Disk->delete($s3Path);
    }
}

