<?php

namespace Tests\Unit;

use App\Support\Database\DuplicateEntryExceptionMapper;
use App\Support\Database\MasterDataUniqueFields;
use Illuminate\Database\UniqueConstraintViolationException;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pure unit tests (no database) for translating duplicate-key DB errors
 * into field-level validation errors. Uses the Laravel TestCase only for the Validator facade.
 */
class DuplicateEntryExceptionMapperTest extends TestCase
{
    private function duplicate(string $driverMessage): UniqueConstraintViolationException
    {
        return new UniqueConstraintViolationException(
            'mysql',
            'insert into `x` (`y`) values (?)',
            ['secret-value'],
            new PDOException($driverMessage)
        );
    }

    private function errorsFor(string $driverMessage): array
    {
        return (new DuplicateEntryExceptionMapper())($this->duplicate($driverMessage))->errors();
    }

    public static function uniqueIndexProvider(): array
    {
        // Every UNIQUE index found in proj_doc/ccd_system_rds_structure.sql for the five master tables.
        return [
            'customers.tax_id'       => ['master_customers', 'master_customers_tax_id_unique', 'tax_id'],
            'licenses.license_en'    => ['master_licenses', 'master_licenses_license_en_unique', 'license_en'],
            'licenses.license_th'    => ['master_licenses', 'master_licenses_license_th_unique', 'license_th'],
            'statistics.statistics'  => ['master_statistics', 'master_statistics_statistics_unique', 'statistics'],
            'branches.branch_code'   => ['master_branches', 'master_branches_branch_code_unique', 'branch_code'],
            'units.unit (if present)' => ['master_units', 'master_units_unit_unique', 'unit'],
        ];
    }

    #[DataProvider('uniqueIndexProvider')]
    public function test_mysql8_key_format_maps_to_field(string $table, string $index, string $field): void
    {
        $errors = $this->errorsFor("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'X' for key '{$table}.{$index}'");

        $this->assertSame([$field => [MasterDataUniqueFields::message($field)]], $errors);
    }

    #[DataProvider('uniqueIndexProvider')]
    public function test_mariadb_key_format_maps_to_field(string $table, string $index, string $field): void
    {
        $errors = $this->errorsFor("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'X' for key '{$index}'");

        $this->assertSame([$field => [MasterDataUniqueFields::message($field)]], $errors);
    }

    public function test_sqlite_format_maps_to_field(): void
    {
        $errors = $this->errorsFor('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: master_branches.branch_code');

        $this->assertArrayHasKey('branch_code', $errors);
    }

    public function test_primary_key_or_unknown_index_uses_generic_message(): void
    {
        $primary = $this->errorsFor("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '9' for key 'master_branches.PRIMARY'");
        $unknown = $this->errorsFor("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'x' for key 'users.users_email_unique'");

        $expected = [MasterDataUniqueFields::GENERIC_FIELD => [MasterDataUniqueFields::GENERIC_MESSAGE]];
        $this->assertSame($expected, $primary);
        $this->assertSame($expected, $unknown);
    }

    public function test_company_name_is_not_mapped_from_db_because_it_has_no_unique_index(): void
    {
        $errors = $this->errorsFor("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'x' for key 'master_customers.master_customers_company_name_index'");

        $this->assertArrayHasKey(MasterDataUniqueFields::GENERIC_FIELD, $errors);
    }

    public function test_messages_never_contain_sql_or_bindings(): void
    {
        $errors = $this->errorsFor("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'secret-value' for key 'master_units_unit_unique'");
        $flat = json_encode($errors);

        $this->assertStringNotContainsString('SQLSTATE', $flat);
        $this->assertStringNotContainsString('insert into', $flat);
        $this->assertStringNotContainsString('secret-value', $flat);
    }

    public function test_validation_messages_cover_every_unique_field(): void
    {
        $messages = MasterDataUniqueFields::messages();

        foreach (['tax_id', 'company_name', 'license_en', 'license_th', 'statistics', 'unit', 'branch_code'] as $field) {
            $this->assertArrayHasKey("{$field}.unique", $messages);
        }
    }
}
