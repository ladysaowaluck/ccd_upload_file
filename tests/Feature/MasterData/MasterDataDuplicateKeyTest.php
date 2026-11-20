<?php

namespace Tests\Feature\MasterData;

use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use App\Models\Business\MasterBranch;
use App\Models\Business\MasterCustomer;
use App\Models\Business\MasterLicense;
use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;
use App\Support\Database\MasterDataUniqueFields;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class MasterDataDuplicateKeyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mysql'];

    private const MASTER_TABLES = ['master_customers', 'master_licenses', 'master_statistics', 'master_units', 'master_branches'];

    private function admin(): AdminUser
    {
        // In-memory admin (no auth DB writes); roles relation drives the role middleware.
        $user = new AdminUser(['name' => 'Duplicate Test Admin', 'email' => 'dup_test@th.nissin-asia.com']);
        $user->id = 987654321;
        $user->setRelation('roles', collect([new AdminRole(['slug' => 'administrator'])]));

        return $user;
    }

    /** A value of the given length that does not exist yet in table.column. */
    private function freshValue(string $table, string $column, int $length): string
    {
        do {
            $value = strtoupper(Str::random($length));
        } while (DB::connection('mysql')->table($table)->where($column, $value)->exists());

        return $value;
    }

    private function noSqlLeak(string $content): void
    {
        $this->assertStringNotContainsString('SQLSTATE', $content);
        $this->assertStringNotContainsStringIgnoringCase('insert into', $content);
        $this->assertStringNotContainsStringIgnoringCase('update `', $content);
    }

    // ---------------------------------------------------------------
    // 1. Successful creation with a unique value
    // ---------------------------------------------------------------

    public function test_create_with_unique_values_succeeds_for_all_tables(): void
    {
        $admin = $this->admin();
        $branch = $this->freshValue('master_branches', 'branch_code', 5);
        $unit = $this->freshValue('master_units', 'unit', 3);
        $stat = $this->freshValue('master_statistics', 'statistics', 3);
        $licEn = 'LIC-EN-' . Str::random(10);
        $taxId = 'T' . Str::random(12);

        $this->actingAs($admin)->post('/admin/master/branches', ['branch_code' => $branch, 'branch_name' => 'Dup Test Branch'])
            ->assertRedirect(route('admin.master.branches'))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/master/units', ['unit' => $unit])
            ->assertRedirect(route('admin.master.units'))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/master/statistics', ['statistics' => $stat])
            ->assertRedirect(route('admin.master.statistics'))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/master/licenses', ['license_en' => $licEn])
            ->assertRedirect(route('admin.master.licenses'))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/master/customers', ['company_name' => 'Dup Test Co ' . Str::random(8), 'tax_id' => $taxId, 'is_enable' => '0'])
            ->assertRedirect(route('admin.master.customers.index'))->assertSessionHasNoErrors();

        $this->assertTrue(MasterBranch::where('branch_code', $branch)->exists());
        $this->assertTrue(MasterUnit::where('unit', $unit)->exists());
        $this->assertTrue(MasterStatistic::where('statistics', $stat)->exists());
        $this->assertTrue(MasterLicense::where('license_en', $licEn)->exists());
        $this->assertTrue(MasterCustomer::where('tax_id', $taxId)->exists());
    }

    // ---------------------------------------------------------------
    // 2. Duplicate value during creation -> 422 (JSON) with field error
    // ---------------------------------------------------------------

    public function test_duplicate_on_create_returns_422_with_field_message_for_all_tables(): void
    {
        $admin = $this->admin();
        $branch = MasterBranch::create(['id' => ((int) MasterBranch::max('id')) + 1, 'branch_code' => $this->freshValue('master_branches', 'branch_code', 5), 'branch_name' => 'B']);
        $unit = MasterUnit::create(['unit' => $this->freshValue('master_units', 'unit', 3)]);
        $stat = MasterStatistic::create(['statistics' => $this->freshValue('master_statistics', 'statistics', 3)]);
        $license = MasterLicense::create(['license_en' => 'LIC-EN-' . Str::random(10), 'license_th' => 'LIC-TH-' . Str::random(10)]);
        $customer = MasterCustomer::create(['company_name' => 'Dup Co ' . Str::random(8), 'tax_id' => 'T' . Str::random(12), 'is_enable' => 0]);

        $cases = [
            ['/admin/master/branches', ['branch_code' => $branch->branch_code, 'branch_name' => 'Other'], 'branch_code'],
            ['/admin/master/units', ['unit' => $unit->unit], 'unit'],
            ['/admin/master/statistics', ['statistics' => $stat->statistics], 'statistics'],
            ['/admin/master/licenses', ['license_en' => $license->license_en], 'license_en'],
            ['/admin/master/customers', ['company_name' => 'Another ' . Str::random(8), 'tax_id' => $customer->tax_id, 'is_enable' => '0'], 'tax_id'],
        ];

        foreach ($cases as [$url, $payload, $field]) {
            $response = $this->actingAs($admin)->postJson($url, $payload);

            $response->assertStatus(422)
                ->assertJsonValidationErrors([$field])
                ->assertJsonPath("errors.{$field}.0", MasterDataUniqueFields::message($field));
            $this->noSqlLeak($response->getContent());
        }
    }

    public function test_duplicate_is_detected_case_insensitively_like_the_db_collation(): void
    {
        $unit = MasterUnit::create(['unit' => $this->freshValue('master_units', 'unit', 3)]);

        $this->actingAs($this->admin())->postJson('/admin/master/units', ['unit' => strtolower($unit->unit)])
            ->assertStatus(422)->assertJsonValidationErrors(['unit']);
    }

    // ---------------------------------------------------------------
    // 3. Successful update with an unchanged unique value
    // ---------------------------------------------------------------

    public function test_update_with_unchanged_unique_value_succeeds_for_all_tables(): void
    {
        $admin = $this->admin();
        $branch = MasterBranch::create(['id' => ((int) MasterBranch::max('id')) + 1, 'branch_code' => $this->freshValue('master_branches', 'branch_code', 5), 'branch_name' => 'B']);
        $unit = MasterUnit::create(['unit' => $this->freshValue('master_units', 'unit', 3)]);
        $stat = MasterStatistic::create(['statistics' => $this->freshValue('master_statistics', 'statistics', 3)]);
        $license = MasterLicense::create(['license_en' => 'LIC-EN-' . Str::random(10), 'license_th' => 'LIC-TH-' . Str::random(10)]);
        $customer = MasterCustomer::create(['company_name' => 'Dup Co ' . Str::random(8), 'tax_id' => 'T' . Str::random(12), 'is_enable' => 0]);

        $this->actingAs($admin)->put("/admin/master/branches/{$branch->id}", ['branch_code' => $branch->branch_code, 'branch_name' => 'Renamed'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put("/admin/master/units/{$unit->id}", ['unit' => $unit->unit])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put("/admin/master/statistics/{$stat->id}", ['statistics' => $stat->statistics])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put("/admin/master/licenses/{$license->id}", ['license_en' => $license->license_en, 'license_th' => $license->license_th])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put("/admin/master/customers/{$customer->id}", ['company_name' => $customer->company_name, 'tax_id' => $customer->tax_id, 'is_enable' => '1'])->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $branch->fresh()->branch_name);
        $this->assertSame(1, (int) $customer->fresh()->is_enable);
    }

    // ---------------------------------------------------------------
    // 4. Duplicate value during update
    // ---------------------------------------------------------------

    public function test_duplicate_on_update_returns_422_for_all_tables(): void
    {
        $admin = $this->admin();
        $nextBranchId = ((int) MasterBranch::max('id')) + 1;
        $b1 = MasterBranch::create(['id' => $nextBranchId, 'branch_code' => $this->freshValue('master_branches', 'branch_code', 5), 'branch_name' => 'B1']);
        $b2 = MasterBranch::create(['id' => $nextBranchId + 1, 'branch_code' => $this->freshValue('master_branches', 'branch_code', 5), 'branch_name' => 'B2']);
        $u1 = MasterUnit::create(['unit' => $this->freshValue('master_units', 'unit', 3)]);
        $u2 = MasterUnit::create(['unit' => $this->freshValue('master_units', 'unit', 3)]);
        $s1 = MasterStatistic::create(['statistics' => $this->freshValue('master_statistics', 'statistics', 3)]);
        $s2 = MasterStatistic::create(['statistics' => $this->freshValue('master_statistics', 'statistics', 3)]);
        $l1 = MasterLicense::create(['license_en' => 'LIC-EN-' . Str::random(10), 'license_th' => 'LIC-TH-' . Str::random(10)]);
        $l2 = MasterLicense::create(['license_en' => 'LIC-EN-' . Str::random(10)]);
        $c1 = MasterCustomer::create(['company_name' => 'Dup Co ' . Str::random(8), 'tax_id' => 'T' . Str::random(12), 'is_enable' => 0]);
        $c2 = MasterCustomer::create(['company_name' => 'Dup Co ' . Str::random(8), 'tax_id' => 'T' . Str::random(12), 'is_enable' => 0]);

        $cases = [
            ["/admin/master/branches/{$b2->id}", ['branch_code' => $b1->branch_code, 'branch_name' => 'B2'], 'branch_code'],
            ["/admin/master/units/{$u2->id}", ['unit' => $u1->unit], 'unit'],
            ["/admin/master/statistics/{$s2->id}", ['statistics' => $s1->statistics], 'statistics'],
            ["/admin/master/licenses/{$l2->id}", ['license_en' => $l1->license_en], 'license_en'],
            ["/admin/master/customers/{$c2->id}", ['company_name' => $c2->company_name, 'tax_id' => $c1->tax_id, 'is_enable' => '0'], 'tax_id'],
        ];

        foreach ($cases as [$url, $payload, $field]) {
            $this->actingAs($admin)->putJson($url, $payload)
                ->assertStatus(422)
                ->assertJsonPath("errors.{$field}.0", MasterDataUniqueFields::message($field));
        }

        // Records are unchanged
        $this->assertNotSame($b1->branch_code, $b2->fresh()->branch_code);
        $this->assertNotSame($l1->license_en, $l2->fresh()->license_en);
    }

    // ---------------------------------------------------------------
    // 5. Composite unique constraints (schema guard)
    // ---------------------------------------------------------------

    public function test_live_schema_unique_indexes_are_single_column_and_all_handled(): void
    {
        if (DB::connection('mysql')->getDriverName() !== 'mysql' && DB::connection('mysql')->getDriverName() !== 'mariadb') {
            $this->markTestSkipped('information_schema check requires MySQL/MariaDB.');
        }

        $rows = DB::connection('mysql')->select(
            'SELECT TABLE_NAME AS tbl, INDEX_NAME AS idx, COUNT(*) AS cols
               FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND NON_UNIQUE = 0 AND INDEX_NAME <> \'PRIMARY\'
                AND TABLE_NAME IN (' . implode(',', array_fill(0, count(self::MASTER_TABLES), '?')) . ')
              GROUP BY TABLE_NAME, INDEX_NAME',
            self::MASTER_TABLES
        );

        foreach ($rows as $row) {
            // No composite UNIQUE index exists today; if one is added it must be mapped explicitly.
            $this->assertSame(1, (int) $row->cols, "Composite UNIQUE index {$row->tbl}.{$row->idx} needs explicit handling.");
            $this->assertNotNull(
                MasterDataUniqueFields::fieldForIndex($row->idx),
                "UNIQUE index {$row->tbl}.{$row->idx} is not registered in MasterDataUniqueFields."
            );
        }
    }

    // ---------------------------------------------------------------
    // 6. Multiple duplicate fields in one request
    // ---------------------------------------------------------------

    public function test_multiple_duplicate_fields_are_all_reported(): void
    {
        $license = MasterLicense::create(['license_en' => 'LIC-EN-' . Str::random(10), 'license_th' => 'LIC-TH-' . Str::random(10)]);
        $customer = MasterCustomer::create(['company_name' => 'Dup Co ' . Str::random(8), 'tax_id' => 'T' . Str::random(12), 'is_enable' => 0]);

        $this->actingAs($this->admin())
            ->postJson('/admin/master/licenses', ['license_en' => $license->license_en, 'license_th' => $license->license_th])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['license_en', 'license_th']);

        $this->actingAs($this->admin())
            ->postJson('/admin/master/customers', ['company_name' => $customer->company_name, 'tax_id' => $customer->tax_id, 'is_enable' => '0'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['company_name', 'tax_id']);
    }

    // ---------------------------------------------------------------
    // 7. Database-level duplicate (race condition: validation passed, DB rejects)
    // ---------------------------------------------------------------

    public function test_database_level_duplicate_is_converted_to_422_json(): void
    {
        $stat = MasterStatistic::create(['statistics' => $this->freshValue('master_statistics', 'statistics', 3)]);
        $license = MasterLicense::create(['license_en' => 'LIC-EN-' . Str::random(10)]);

        // Simulates the race: insert without validation so MySQL raises error 1062.
        Route::middleware('web')->post('/_test/raw-duplicate/{type}', function (string $type) use ($stat, $license) {
            if ($type === 'stat') {
                MasterStatistic::create(['statistics' => $stat->statistics]);
            } else {
                MasterLicense::create(['license_en' => $license->license_en]);
            }

            return response('created');
        });

        $response = $this->postJson('/_test/raw-duplicate/stat');
        $response->assertStatus(422)->assertJsonPath('errors.statistics.0', MasterDataUniqueFields::message('statistics'));
        $this->noSqlLeak($response->getContent());

        $this->postJson('/_test/raw-duplicate/license')
            ->assertStatus(422)->assertJsonPath('errors.license_en.0', MasterDataUniqueFields::message('license_en'));
    }

    public function test_database_level_duplicate_on_web_form_redirects_back_with_field_error_and_input(): void
    {
        $stat = MasterStatistic::create(['statistics' => $this->freshValue('master_statistics', 'statistics', 3)]);

        Route::middleware('web')->post('/_test/raw-duplicate-web', function () use ($stat) {
            MasterStatistic::create(['statistics' => $stat->statistics]);

            return response('created');
        });

        $this->from('/admin/master/statistics')
            ->post('/_test/raw-duplicate-web', ['statistics' => $stat->statistics, '_form' => 'createStatModal'])
            ->assertRedirect('/admin/master/statistics')
            ->assertSessionHasErrors(['statistics' => MasterDataUniqueFields::message('statistics')])
            ->assertSessionHasInput('statistics', $stat->statistics);
    }

    // ---------------------------------------------------------------
    // 8. Foreign key violations must not be treated as duplicates
    // ---------------------------------------------------------------

    public function test_foreign_key_violation_is_not_converted_to_duplicate_error(): void
    {
        Route::middleware('web')->post('/_test/fk-violation', function () {
            $pdo = new PDOException('SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails');
            $pdo->errorInfo = ['23000', 1452, 'Cannot add or update a child row: a foreign key constraint fails'];

            throw new QueryException('mysql', 'insert into `child` (`parent_id`) values (?)', [99], $pdo);
        });

        $response = $this->postJson('/_test/fk-violation');

        $response->assertStatus(500);
        $this->assertArrayNotHasKey('errors', (array) $response->json());
        $this->noSqlLeak($response->getContent());
    }

    // ---------------------------------------------------------------
    // 9. Frontend: message shown under the input, modal re-opened, value kept
    // ---------------------------------------------------------------

    public function test_frontend_shows_duplicate_message_under_field_and_keeps_input(): void
    {
        $admin = $this->admin();
        $unit = MasterUnit::create(['unit' => $this->freshValue('master_units', 'unit', 3)]);

        $response = $this->actingAs($admin)
            ->from('/admin/master/units')
            ->followingRedirects()
            ->post('/admin/master/units', ['unit' => $unit->unit, '_form' => 'createUnitModal']);

        $response->assertOk();
        $response->assertSee(MasterDataUniqueFields::message('unit'));
        $response->assertSee('role="alert"', false);
        $response->assertSee('document.getElementById("createUnitModal")', false);
        $response->assertSee('value="' . $unit->unit . '"', false);
    }

    public function test_frontend_edit_modal_error_is_scoped_to_the_submitted_record(): void
    {
        $admin = $this->admin();
        $l1 = MasterLicense::create(['license_en' => 'AAA-DUP-' . Str::random(8)]);
        $l2 = MasterLicense::create(['license_en' => 'AAA-DUP-' . Str::random(8)]);

        $response = $this->actingAs($admin)
            ->from('/admin/master/licenses?search=AAA-DUP-')
            ->followingRedirects()
            ->put("/admin/master/licenses/{$l2->id}", ['license_en' => $l1->license_en, '_form' => "editLicenseModal{$l2->id}"]);

        $response->assertOk();
        $response->assertSee('document.getElementById("editLicenseModal' . $l2->id . '")', false);
        $this->assertSame(1, substr_count($response->getContent(), MasterDataUniqueFields::message('license_en') . '</span>'));
    }

    // ---------------------------------------------------------------
    // 10. Unrelated server errors must still return HTTP 500
    // ---------------------------------------------------------------

    public function test_unrelated_server_error_still_returns_500(): void
    {
        Route::middleware('web')->post('/_test/server-error', function () {
            throw new RuntimeException('Unexpected failure');
        });

        $this->postJson('/_test/server-error')->assertStatus(500);
        $this->post('/_test/server-error')->assertStatus(500);
    }

    // ---------------------------------------------------------------
    // Regression: delete and search are unaffected
    // ---------------------------------------------------------------

    public function test_delete_and_search_still_work(): void
    {
        $admin = $this->admin();
        $unit = MasterUnit::create(['unit' => $this->freshValue('master_units', 'unit', 3)]);
        $stat = MasterStatistic::create(['statistics' => $this->freshValue('master_statistics', 'statistics', 3)]);

        $this->actingAs($admin)->get('/admin/master/units?search=' . $unit->unit)->assertOk()->assertSee($unit->unit);
        $this->actingAs($admin)->get('/admin/master/branches?search=zz')->assertOk();
        $this->actingAs($admin)->get('/admin/master/licenses?search=zz')->assertOk();
        $this->actingAs($admin)->get('/admin/master/statistics?search=' . $stat->statistics)->assertOk()->assertSee($stat->statistics);
        $this->actingAs($admin)->get('/admin/master/customers?company_name=zz')->assertOk();

        $this->actingAs($admin)->delete("/admin/master/units/{$unit->id}")->assertRedirect(route('admin.master.units'));
        $this->assertNull(MasterUnit::find($unit->id));
    }
}
