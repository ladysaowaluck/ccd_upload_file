<?php

namespace App\Support\Database;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Single source of truth for UNIQUE fields on the master tables.
 * Used by validation (Rule::unique + messages) and by DuplicateEntryExceptionMapper
 * (DB index name -> form field + message), so both layers show the same text.
 *
 * Index names come from proj_doc/ccd_system_rds_structure.sql.
 */
final class MasterDataUniqueFields
{
    public const CONNECTION = 'mysql';

    public const GENERIC_FIELD = 'duplicate';
    public const GENERIC_MESSAGE = 'This record conflicts with an existing record. Please check your input and try again.';

    /**
     * field => [table, db unique index name (null = application-level rule only), message]
     */
    private const FIELDS = [
        'tax_id' => [
            'table' => 'master_customers',
            'index' => 'master_customers_tax_id_unique',
            'message' => 'This customer Tax ID has already been used. Please enter a different value.',
        ],
        // company_name only has a non-unique index in the DB; uniqueness is an application rule.
        'company_name' => [
            'table' => 'master_customers',
            'index' => null,
            'message' => 'This customer name has already been used. Please enter a different value.',
        ],
        'license_en' => [
            'table' => 'master_licenses',
            'index' => 'master_licenses_license_en_unique',
            'message' => 'This license name (EN) already exists. Please enter a different value.',
        ],
        'license_th' => [
            'table' => 'master_licenses',
            'index' => 'master_licenses_license_th_unique',
            'message' => 'This license name (TH) already exists. Please enter a different value.',
        ],
        'statistics' => [
            'table' => 'master_statistics',
            'index' => 'master_statistics_statistics_unique',
            'message' => 'This statistic code is already registered. Please enter a different value.',
        ],
        // The schema dump shows no UNIQUE index on master_units.unit; the name below follows the
        // Laravel convention so the mapper still works if the index exists in a live database.
        'unit' => [
            'table' => 'master_units',
            'index' => 'master_units_unit_unique',
            'message' => 'This unit code has already been used. Please enter a different value.',
        ],
        'branch_code' => [
            'table' => 'master_branches',
            'index' => 'master_branches_branch_code_unique',
            'message' => 'This branch code already exists. Please enter a different value.',
        ],
    ];

    /** Rule::unique for a field; pass the current record id on update to ignore itself. */
    public static function rule(string $field, int|string|null $ignoreId = null): Unique
    {
        $table = self::FIELDS[$field]['table'];

        return Rule::unique(self::CONNECTION . '.' . $table, $field)->ignore($ignoreId);
    }

    public static function message(string $field): string
    {
        return self::FIELDS[$field]['message'] ?? self::GENERIC_MESSAGE;
    }

    /** Validation messages in the "field.unique" format expected by Laravel's validator. */
    public static function messages(): array
    {
        $messages = [];
        foreach (self::FIELDS as $field => $definition) {
            $messages[$field . '.unique'] = $definition['message'];
        }

        return $messages;
    }

    /** Resolve a form field from a DB unique index name, e.g. "master_branches_branch_code_unique". */
    public static function fieldForIndex(string $index): ?string
    {
        foreach (self::FIELDS as $field => $definition) {
            if ($definition['index'] !== null && strcasecmp($definition['index'], $index) === 0) {
                return $field;
            }
        }

        return null;
    }

    /** Resolve a form field from a table + column pair (used for SQLite-style messages). */
    public static function fieldForColumn(string $table, string $column): ?string
    {
        $definition = self::FIELDS[$column] ?? null;

        return ($definition !== null && $definition['index'] !== null && $definition['table'] === $table)
            ? $column
            : null;
    }
}
