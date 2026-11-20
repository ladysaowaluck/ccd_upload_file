<?php

namespace Tests\Feature\Clearance;

use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterBranch;
use App\Models\Business\MasterCustomer;
use App\Models\Business\MasterLicense;
use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;
use App\Domain\Clearance\Actions\CreateClearanceRecordAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Gemini: End-to-end test of "Register New Customs Clearance Record" (/clearance/create)
 * submitting EVERY field of the form, exactly as the browser posts it.
 */
class ClearanceCreateFullRecordTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mysql'];

    private function staff(): AdminUser
    {
        $user = new AdminUser(['name' => 'Clearance Tester', 'email' => 'clearance_test@th.nissin-asia.com']);
        $user->id = 987654322;
        $user->setRelation('roles', collect([new AdminRole(['slug' => 'administrator'])]));

        return $user;
    }

    /** Make sure the master rows the form needs exist (inside the test transaction). */
    private function masters(): array
    {
        $branch = MasterBranch::first() ?? MasterBranch::create(['id' => 1, 'branch_code' => 'TST', 'branch_name' => 'Test Branch']);
        $customer = MasterCustomer::create([
            // Long company name (> 60 chars, the size of customer_clearance_data.customer_name).
            'company_name' => 'Siam Long Name Import Export and International Logistics Company Limited ' . Str::random(6),
            'tax_id' => 'T' . Str::random(12),
            'is_enable' => 0,
        ]);
        $stat = MasterStatistic::firstOrCreate(['statistics' => '001']);
        $unit = MasterUnit::firstOrCreate(['unit' => 'C62']);
        $kgm = MasterUnit::firstOrCreate(['unit' => 'KGM']);
        $ltr = MasterUnit::firstOrCreate(['unit' => 'LTR']);
        $licenses = collect(range(1, 4))->map(fn ($i) => MasterLicense::create([
            'license_en' => "Full Test License {$i} " . Str::random(6),
            'license_th' => "ใบอนุญาตทดสอบ {$i} " . Str::random(6),
        ]));

        return compact('branch', 'customer', 'stat', 'unit', 'kgm', 'ltr', 'licenses');
    }

    private function fullPayload(array $m, string $jobNo): array
    {
        $payload = [
            // Header
            'job_no' => $jobNo,
            'master_customer_id' => $m['customer']->id,
            'master_branch_id' => $m['branch']->id,
            'imp_exp' => '0',
            // Product details
            'product_description_en' => 'Industrial servo motor for packaging machine, 3-phase 400V',
            'product_description_th' => 'มอเตอร์เซอร์โวอุตสาหกรรมสำหรับเครื่องบรรจุภัณฑ์ 3 เฟส 400 โวลต์',
            'product_code_1' => 'SRV-400-3P',
            'product_code_2' => 'ALT-PN-99812',
            'product_year' => '2025',
            'brand_name' => 'ACME Motion',
            'model_name' => str_repeat('M', 50),           // column is varchar(50)
            'materials' => 'Steel housing, copper winding',
            'usage' => 'Drives conveyor in packaging line',
            'function' => 'Converts electrical energy to precise rotary motion',
            'position' => 'Mounted on conveyor frame',
            'country_of_origin' => 'Japan',
            'note' => str_repeat('N', 500),               // column is varchar(500)
            // Tariff details
            'hs_code' => '85015229',
            'sequence_code' => '00001',
            // Master data selectors post the IDs
            'statistic_code_id' => (string) $m['stat']->id,
            'quantity_unit_id' => (string) $m['unit']->id,
            'privilege_code' => '000',
            'tariff_ad_valorem' => '10.00',
            'tariff_specific_rate_unit_id' => (string) $m['kgm']->id, // master data selector posts the ID
            'tariff_specific_rate_baht' => '12.50',
            // Anti-dumping / excise
            'anti_dumping_ad_valorem' => '5.25',
            'excise_tax_ad_valorem' => '3.00',
            'excise_tax_rate_unit_id' => (string) $m['ltr']->id,
            'excise_tax_rate_baht' => '1.75',
            // Other taxes
            'other_tax1_name_of_tax' => 'Interior tax',
            'other_tax1_ad_valorem' => '1.00',
            'other_tax1_rate_unit_id' => (string) $m['kgm']->id,
            'other_tax1_rate_baht' => '0.50',
            'other_tax2_name_of_tax' => 'Local tax',
            'other_tax2_ad_valorem' => '2.00',
            'other_tax2_rate_unit_id' => (string) $m['unit']->id,
            'other_tax2_rate_baht' => '0.25',
            // Files
            'reference_file_1' => UploadedFile::fake()->create('catalog.pdf', 120, 'application/pdf'),
            'reference_file_2' => UploadedFile::fake()->create('spec sheet.xlsx', 80),
            'hs_evidence_file_1' => UploadedFile::fake()->create('ruling.pdf', 60, 'application/pdf'),
            'hs_evidence_file_2' => UploadedFile::fake()->image('photo.jpg'),
            'hs_evidence_file_3' => UploadedFile::fake()->create('proof.docx', 40),
            'hs_evidence_file_4' => UploadedFile::fake()->create('extra.png', 30, 'image/png'),
        ];

        foreach ($m['licenses'] as $i => $license) {
            $n = $i + 1;
            $payload["license{$n}_name_id"] = (string) $license->id;
            $payload["license{$n}_note"] = "License {$n} note";
        }

        return $payload;
    }

    private function nextJobNo(): string
    {
        do {
            $jobNo = 'CCD' . date('ym') . '-' . random_int(1000, 9999);
        } while (CustomerClearanceData::where('job_no', $jobNo)->exists());

        return $jobNo;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('s3');
        config(['filesystems.disks.s3.key' => 'test-key', 'filesystems.disks.s3.bucket' => 'test-bucket']);
    }

    public function test_create_form_renders(): void
    {
        $this->masters();

        $this->actingAs($this->staff())->get('/clearance/create')
            ->assertOk()
            ->assertSee('Create Clearance Record');
    }

    public function test_full_record_is_created_and_success_notification_is_shown(): void
    {
        $m = $this->masters();
        $jobNo = $this->nextJobNo();

        $response = $this->actingAs($this->staff())
            ->from('/clearance/create')
            ->post('/clearance/store', $this->fullPayload($m, $jobNo));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('clearance.index'));
        $response->assertSessionHas('success', "Clearance record {$jobNo} created successfully.");

        // The success notification is visible on the page the user lands on.
        $this->actingAs($this->staff())->get(route('clearance.index'))
            ->assertOk()
            ->assertSee("Clearance record {$jobNo} created successfully.");

        $record = CustomerClearanceData::where('job_no', $jobNo)->firstOrFail();

        // Header
        $this->assertSame($m['customer']->id, (int) $record->master_customer_id);
        $this->assertSame(mb_substr($m['customer']->company_name, 0, 60), $record->customer_name);
        $this->assertSame($m['branch']->id, (int) $record->master_branch_id);
        $this->assertSame(0, (int) $record->imp_exp);

        // Product
        $this->assertSame('SRV-400-3P', $record->product_code_1);
        $this->assertSame('ALT-PN-99812', $record->product_code_2);
        $this->assertSame(2025, (int) $record->product_year);
        $this->assertSame(str_repeat('M', 50), $record->model_name);
        $this->assertSame('Drives conveyor in packaging line', $record->usages);
        $this->assertSame('Converts electrical energy to precise rotary motion', $record->functions);
        $this->assertSame(str_repeat('N', 500), $record->note);

        // Tariff: codes typed in the form are stored as master IDs (same as the Excel import)
        $this->assertSame('85015229', $record->hs_code);
        $this->assertSame((int) $m['stat']->id, (int) $record->statistic_code_id);
        $this->assertSame((int) $m['unit']->id, (int) $record->quantity_unit_id);
        $this->assertSame('001', $record->statistic_code);
        $this->assertSame('C62', $record->quantity_unit);
        // Column tariff_specific_rate_unit_id stores the master_units ID; the code is shown via the helper.
        $this->assertSame((int) $m['kgm']->id, (int) $record->tariff_specific_rate_unit_id);
        $this->assertSame('KGM', $record->tariff_specific_rate_unit);
        $this->assertEquals(12.50, (float) $record->tariff_specific_rate_baht);
        $this->assertEquals(5.25, (float) $record->anti_dumping_ad_valorem);
        $this->assertSame('Local tax', $record->other_tax2_name_of_tax);
        // Excise / other tax units: selector IDs stored, codes displayed
        $this->assertSame((string) $m['ltr']->id, (string) $record->excise_tax_rate_unit_id);
        $this->assertSame('LTR', $record->unitCodeFor('excise_tax_rate_unit_id'));
        $this->assertSame('KGM', $record->unitCodeFor('other_tax1_rate_unit_id'));
        $this->assertSame('C62', $record->unitCodeFor('other_tax2_rate_unit_id'));

        // Licenses
        foreach ($m['licenses'] as $i => $license) {
            $n = $i + 1;
            $this->assertSame($license->id, (int) $record->{"license{$n}_name_id"});
            $this->assertSame("License {$n} note", $record->{"license{$n}_note"});
        }

        // Files stored and linked
        foreach (['reference_file_1', 'reference_file_2', 'hs_evidence_file_1', 'hs_evidence_file_2', 'hs_evidence_file_3', 'hs_evidence_file_4'] as $field) {
            $this->assertNotEmpty($record->{$field}, "{$field} should be stored");
            Storage::disk('s3')->assertExists($record->{$field});
        }
    }

    public function test_minimal_record_with_only_required_fields_is_created(): void
    {
        $m = $this->masters();
        $jobNo = $this->nextJobNo();

        $this->actingAs($this->staff())->post('/clearance/store', [
            'job_no' => $jobNo,
            'master_customer_id' => $m['customer']->id,
            'master_branch_id' => $m['branch']->id,
            'imp_exp' => '1',
            'product_description_en' => 'Spare part',
            'hs_code' => '84713020',
        ])->assertSessionHasNoErrors()->assertRedirect(route('clearance.index'));

        $this->assertTrue(CustomerClearanceData::where('job_no', $jobNo)->exists());
    }

    public function test_empty_selectors_and_unknown_unit_code_do_not_block_create(): void
    {
        $m = $this->masters();
        $jobNo = $this->nextJobNo();
        $payload = array_merge($this->fullPayload($m, $jobNo), [
            'statistic_code_id' => '',
            'quantity_unit_id' => '',
            'tariff_specific_rate_unit_id' => 'XYZ',
        ]);

        $this->actingAs($this->staff())->post('/clearance/store', $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('clearance.index'));

        $record = CustomerClearanceData::where('job_no', $jobNo)->firstOrFail();
        $this->assertNull($record->quantity_unit_id);
        $this->assertNull($record->tariff_specific_rate_unit_id);
        // Empty statistic keeps the existing default: master statistic "000" (when it exists).
        $this->assertSame(MasterStatistic::where('statistics', '000')->value('id'), $record->statistic_code_id === null ? null : (int) $record->statistic_code_id);
    }

    public function test_values_longer_than_the_db_columns_show_field_errors(): void
    {
        $m = $this->masters();
        $payload = array_merge($this->fullPayload($m, $this->nextJobNo()), [
            'model_name' => str_repeat('M', 51),
            'note' => str_repeat('N', 501),
            'tariff_specific_rate_baht' => '100000000',
        ]);

        $this->actingAs($this->staff())->from('/clearance/create')->post('/clearance/store', $payload)
            ->assertRedirect('/clearance/create')
            ->assertSessionHasErrors(['model_name', 'note', 'tariff_specific_rate_baht']);
    }

    public function test_unexpected_save_failure_shows_a_visible_error_without_sql_details(): void
    {
        $m = $this->masters();
        $this->mock(CreateClearanceRecordAction::class)
            ->shouldReceive('execute')->andThrow(new RuntimeException('SQLSTATE[HY000]: secret internal detail'));

        $response = $this->actingAs($this->staff())
            ->from('/clearance/create')
            ->followingRedirects()
            ->post('/clearance/store', $this->fullPayload($m, $this->nextJobNo()));

        $response->assertOk();
        $response->assertSee('Failed to create the clearance record');
        $response->assertDontSee('SQLSTATE');
        $response->assertDontSee('secret internal detail');
        // Typed values are kept
        $response->assertSee('SRV-400-3P');
    }

    public function test_created_record_can_be_opened_and_saved_from_edit_without_changing_codes(): void
    {
        $m = $this->masters();
        $jobNo = $this->nextJobNo();
        $this->actingAs($this->staff())->post('/clearance/store', $this->fullPayload($m, $jobNo))->assertSessionHasNoErrors();
        $record = CustomerClearanceData::where('job_no', $jobNo)->firstOrFail();

        // Show and edit pages display the codes, not the IDs.
        $this->actingAs($this->staff())->get("/clearance/{$record->id}")->assertOk()->assertSee('001')->assertSee('C62');
        $this->actingAs($this->staff())->get("/clearance/{$record->id}/edit")->assertOk()
            ->assertSee('<option value="' . $m['stat']->id . '" selected>001</option>', false)
            ->assertSee('<option value="' . $m['unit']->id . '" selected>C62</option>', false)
            ->assertSee('<option value="' . $m['kgm']->id . '" selected>KGM</option>', false);

        // Saving the edit form with the displayed codes keeps the same master IDs.
        $this->actingAs($this->staff())->put("/clearance/{$record->id}", [
            'job_no' => $jobNo,
            'master_customer_id' => $m['customer']->id,
            'master_branch_id' => $m['branch']->id,
            'imp_exp' => '0',
            'product_description_en' => 'Updated description',
            'hs_code' => '85015229',
            'statistic_code_id' => (string) $m['stat']->id,
            'quantity_unit_id' => (string) $m['unit']->id,
            'tariff_specific_rate_unit_id' => (string) $m['kgm']->id,
        ])->assertSessionHasNoErrors()->assertRedirect(route('clearance.show', $record->id));

        $record->refresh();
        $this->assertSame((int) $m['stat']->id, (int) $record->statistic_code_id);
        $this->assertSame((int) $m['unit']->id, (int) $record->quantity_unit_id);
        $this->assertSame((int) $m['kgm']->id, (int) $record->tariff_specific_rate_unit_id);
        $this->assertSame('Updated description', $record->product_description_en);
    }

    public function test_create_and_edit_forms_show_master_data_selectors(): void
    {
        $m = $this->masters();

        $this->actingAs($this->staff())->get('/clearance/create')->assertOk()
            ->assertSee('<select name="statistic_code_id"', false)
            ->assertSee('<select name="quantity_unit_id"', false)
            ->assertSee('<select name="tariff_specific_rate_unit_id"', false)
            ->assertSee('<option value="' . $m['kgm']->id . '" >KGM</option>', false)
            ->assertSee('<option value="' . $m['stat']->id . '" >001</option>', false)
            ->assertSee('<option value="' . $m['unit']->id . '" >C62</option>', false)
            ->assertDontSee('name="statistic_code"', false)
            ->assertDontSee('name="quantity_unit"', false);
    }

    public function test_large_master_ids_are_not_truncated(): void
    {
        $m = $this->masters();
        $jobNo = $this->nextJobNo();
        $bigStat = MasterStatistic::forceCreate(['id' => 123456, 'statistics' => 'Z' . random_int(10, 99)]);
        $bigUnit = MasterUnit::forceCreate(['id' => 654321, 'unit' => 'Y' . random_int(10, 99)]);

        $this->actingAs($this->staff())->post('/clearance/store', array_merge($this->fullPayload($m, $jobNo), [
            'statistic_code_id' => (string) $bigStat->id,
            'quantity_unit_id' => (string) $bigUnit->id,
        ]))->assertSessionHasNoErrors();

        $record = CustomerClearanceData::where('job_no', $jobNo)->firstOrFail();
        $this->assertSame(123456, (int) $record->statistic_code_id);
        $this->assertSame(654321, (int) $record->quantity_unit_id);
        $this->assertSame($bigStat->statistics, $record->statistic_code);
        $this->assertSame($bigUnit->unit, $record->quantity_unit);
    }

    public function test_all_rate_unit_fields_use_master_data_selector_and_show_codes(): void
    {
        $m = $this->masters();

        $create = $this->actingAs($this->staff())->get('/clearance/create')->assertOk();
        foreach (['tariff_specific_rate_unit_id', 'excise_tax_rate_unit_id', 'other_tax1_rate_unit_id', 'other_tax2_rate_unit_id'] as $field) {
            $create->assertSee('<select name="' . $field . '"', false);
            $create->assertDontSee('<input type="text" name="' . $field . '"', false);
        }

        $jobNo = $this->nextJobNo();
        $this->actingAs($this->staff())->post('/clearance/store', $this->fullPayload($m, $jobNo))->assertSessionHasNoErrors();
        $record = CustomerClearanceData::where('job_no', $jobNo)->firstOrFail();

        // Legacy row that still holds a typed code: edit pre-selects the matching unit, show displays it.
        $record->forceFill(['other_tax1_rate_unit_id' => 'kgm'])->save();

        $edit = $this->actingAs($this->staff())->get("/clearance/{$record->id}/edit")->assertOk();
        $edit->assertSee('<option value="' . $m['ltr']->id . '" selected>LTR</option>', false);
        $edit->assertSee('<option value="' . $m['kgm']->id . '" selected>KGM</option>', false);

        $this->actingAs($this->staff())->get("/clearance/{$record->id}")->assertOk()
            ->assertSee('LTR')->assertSee('kgm');
    }

    public function test_master_data_selects_use_select_customer_style_and_unique_names(): void
    {
        $m = $this->masters();
        $jobNo = $this->nextJobNo();
        $this->actingAs($this->staff())->post('/clearance/store', $this->fullPayload($m, $jobNo))->assertSessionHasNoErrors();
        $record = CustomerClearanceData::where('job_no', $jobNo)->firstOrFail();

        $customerClass = 'class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none"';
        $fields = ['statistic_code_id', 'quantity_unit_id', 'tariff_specific_rate_unit_id', 'excise_tax_rate_unit_id', 'other_tax1_rate_unit_id', 'other_tax2_rate_unit_id'];

        foreach (['/clearance/create', "/clearance/{$record->id}/edit"] as $url) {
            $html = $this->actingAs($this->staff())->get($url)->assertOk()->getContent();
            foreach ($fields as $field) {
                $this->assertSame(1, substr_count($html, '<select name="' . $field . '"'), "{$url}: {$field} must appear once");
                $this->assertMatchesRegularExpression('/<select name="' . $field . '" data-searchable\s+' . preg_quote($customerClass, '/') . '/', $html, "{$url}: {$field} style + type-to-search");
            }
            $this->assertStringContainsString('Select Unit...', $html);
            // Type-to-search assets are loaded from the app itself (no CDN)
            $this->assertStringContainsString('vendor/tom-select/tom-select.complete.min.js', $html);
            $this->assertStringContainsString('js/searchable-select.js', $html);
        }

        // Saving the edit form keeps each unit in its own column.
        $this->actingAs($this->staff())->put("/clearance/{$record->id}", [
            'job_no' => $jobNo,
            'master_customer_id' => $m['customer']->id,
            'master_branch_id' => $m['branch']->id,
            'imp_exp' => '0',
            'product_description_en' => 'Edit units',
            'hs_code' => '85015229',
            'tariff_specific_rate_unit_id' => (string) $m['kgm']->id,
            'excise_tax_rate_unit_id' => (string) $m['ltr']->id,
            'other_tax1_rate_unit_id' => (string) $m['unit']->id,
            'other_tax2_rate_unit_id' => (string) $m['kgm']->id,
        ])->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertSame('KGM', $record->tariff_specific_rate_unit);
        $this->assertSame('LTR', $record->unitCodeFor('excise_tax_rate_unit_id'));
        $this->assertSame('C62', $record->unitCodeFor('other_tax1_rate_unit_id'));
        $this->assertSame('KGM', $record->unitCodeFor('other_tax2_rate_unit_id'));
    }
}
