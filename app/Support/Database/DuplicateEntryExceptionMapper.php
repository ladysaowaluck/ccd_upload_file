<?php

namespace App\Support\Database;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Converts a DB duplicate-key error into a ValidationException.
 *
 * Laravel already raises UniqueConstraintViolationException ONLY for duplicate keys
 * (MySQL/MariaDB error 1062, SQLite "UNIQUE constraint failed", PostgreSQL 23505).
 * Foreign-key violations (MySQL 1451/1452) stay a plain QueryException and are not touched.
 *
 * Laravel then renders the ValidationException normally:
 *   - JSON / AJAX request  -> HTTP 422 { message, errors: { field: [msg] } }
 *   - Browser form request -> redirect back with input + field errors
 * No SQL, bindings or connection details are placed in the response.
 */
final class DuplicateEntryExceptionMapper
{
    public function __invoke(UniqueConstraintViolationException $exception): ValidationException
    {
        $field = $this->resolveField($exception->getMessage()) ?? MasterDataUniqueFields::GENERIC_FIELD;

        return ValidationException::withMessages([
            $field => MasterDataUniqueFields::message($field),
        ]);
    }

    private function resolveField(string $message): ?string
    {
        // MySQL 8: "for key 'table.index_name'", MySQL 5.7 / MariaDB: "for key 'index_name'"
        if (preg_match("/for key '(?:[^'.]+\\.)?([^']+)'/", $message, $matches) === 1) {
            return MasterDataUniqueFields::fieldForIndex($matches[1]);
        }

        // SQLite: "UNIQUE constraint failed: table.column"
        if (preg_match('/UNIQUE constraint failed: (\w+)\.(\w+)/i', $message, $matches) === 1) {
            return MasterDataUniqueFields::fieldForColumn($matches[1], $matches[2]);
        }

        return null;
    }
}
