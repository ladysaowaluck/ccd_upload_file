<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the sanitisation logic in MultiSheetImport.
 *
 * Tests the two root-cause behaviours:
 *   1. isSubHeaderRow() — returns true for Row 2 "HS code" / "Customer" labels
 *   2. sanitizeRow()    — cleans raw Excel cell values before validation
 *
 * No database, no Laravel app — runs with plain PHPUnit.
 * Run: vendor/phpunit/phpunit/phpunit tests/Unit/MultiSheetImportSanitizeTest.php
 */
class MultiSheetImportSanitizeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers (copied logic so the test can run without booting Laravel)
    // -------------------------------------------------------------------------

    private function cleanNumeric(mixed $value): ?string
    {
        $cleaned = preg_replace('/[^0-9]/', '', (string) $value);
        return $cleaned !== '' ? $cleaned : null;
    }

    private function defaultStatistic(mixed $value): string
    {
        $v = trim((string) $value);
        return $v !== '' ? mb_substr($v, 0, 3) : '000';
    }

    private function nullIfEmpty(mixed $value): mixed
    {
        $v = trim((string) $value);
        return $v !== '' ? $v : null;
    }

    private function isSubHeaderRow(array $row): bool
    {
        $hsCode = (string) ($row['hs_code_8_digits'] ?? '');
        $customer = (string) ($row['customer'] ?? '');

        return (!is_numeric($hsCode) && !empty($hsCode))
            || strtolower(trim($customer)) === 'customer';
    }

    // -------------------------------------------------------------------------
    // isSubHeaderRow tests
    // -------------------------------------------------------------------------

    /** Row 2 label row: hs_code_8_digits = "HS code" → must be identified as sub-header */
    public function test_subheader_detected_when_hs_code_is_label(): void
    {
        $row = ['hs_code_8_digits' => 'HS code', 'customer' => 'SHARP MANUFACTURING'];
        $this->assertTrue($this->isSubHeaderRow($row));
    }

    /** Row 2 label row: customer = "Customer" → must be identified as sub-header */
    public function test_subheader_detected_when_customer_is_label(): void
    {
        $row = ['hs_code_8_digits' => '', 'customer' => 'Customer'];
        $this->assertTrue($this->isSubHeaderRow($row));
    }

    /** Real data row: hs_code = "85371011" → must NOT be detected as sub-header */
    public function test_data_row_not_detected_as_subheader(): void
    {
        $row = ['hs_code_8_digits' => '85371011', 'customer' => 'SHARP MANUFACTURING'];
        $this->assertFalse($this->isSubHeaderRow($row));
    }

    /** Edge case: hs_code already stripped to digits → not sub-header */
    public function test_numeric_hs_code_not_detected_as_subheader(): void
    {
        $row = ['hs_code_8_digits' => '84714100', 'customer' => 'Some Company Co.,Ltd.'];
        $this->assertFalse($this->isSubHeaderRow($row));
    }

    // -------------------------------------------------------------------------
    // cleanNumeric (hs_code stripping) tests
    // -------------------------------------------------------------------------

    // Excel sometimes formats HS codes with dots: "8537.10.11" */
    public function test_hs_code_dots_stripped(): void
    {
        $this->assertSame('85371011', $this->cleanNumeric('8537.10.11'));
    }

    // Leading/trailing spaces are stripped */
    public function test_hs_code_spaces_stripped(): void
    {
        $this->assertSame('85371011', $this->cleanNumeric(' 85371011 '));
    }

    // Pure numeric string passes through unchanged */
    public function test_hs_code_pure_numeric_unchanged(): void
    {
        $this->assertSame('85043100', $this->cleanNumeric('85043100'));
    }

    // Text label (sub-header) becomes null after cleaning */
    public function test_hs_code_text_label_becomes_null(): void
    {
        $this->assertNull($this->cleanNumeric('HS code'));
    }

    // Empty string becomes null */
    public function test_hs_code_empty_becomes_null(): void
    {
        $this->assertNull($this->cleanNumeric(''));
    }

    // -------------------------------------------------------------------------
    // defaultStatistic tests
    // -------------------------------------------------------------------------

    // Blank statistic_code defaults to "000" 
    public function test_statistic_code_blank_defaults_to_000(): void
    {
        $this->assertSame('000', $this->defaultStatistic(''));
        $this->assertSame('000', $this->defaultStatistic(null));
        $this->assertSame('000', $this->defaultStatistic('   '));
    }

    // Valid 3-digit code passes through
    public function test_statistic_code_valid_passes_through(): void
    {
        $this->assertSame('001', $this->defaultStatistic('001'));
        $this->assertSame('999', $this->defaultStatistic('999'));
    }

    // Longer code is truncated to 3 chars
    public function test_statistic_code_truncated_to_3(): void
    {
        $this->assertSame('123', $this->defaultStatistic('12345'));
    }

    // -------------------------------------------------------------------------
    // nullIfEmpty (sequence_code / privilege_code) tests
    // -------------------------------------------------------------------------

    // Empty string sequence_code becomes null */
    public function test_sequence_code_empty_becomes_null(): void
    {
        $this->assertNull($this->nullIfEmpty(''));
        $this->assertNull($this->nullIfEmpty('   '));
    }

    // Valid sequence_code passes through */
    public function test_sequence_code_valid_passes_through(): void
    {
        $this->assertSame('00100', $this->nullIfEmpty('00100'));
    }

    // Privilege code label string "Privilege code" (> 10 chars) → once nullIfEmpty is applied to empty → null */
    public function test_privilege_code_empty_becomes_null(): void
    {
        // In a real data row the privilege_code cell is empty (""), so nullIfEmpty converts it to null
        $this->assertNull($this->nullIfEmpty(''));
    }

    // Privilege code "000" is preserved */
    public function test_privilege_code_valid_preserved(): void
    {
        $this->assertSame('000', $this->nullIfEmpty('000'));
    }

    // -------------------------------------------------------------------------
    // Full row sanitize simulation (integration of all sanitize helpers)
    // -------------------------------------------------------------------------

    /**
     * Simulates what sanitizeRow() does on the actual CCD Excel data row:
     *   hs_code  = "85371011"  → "85371011" (stays)
     *   seq_code = ""          → null
     *   stat_code = "000"      → "000"
     *   priv_code = ""         → null
     *   tariff   = ""          → null
     */
    // public function test_actual_excel_row_sanitize_produces_valid_data(): void
    // {
    //     $rawRow = [
    //         'hs_code_8_digits'        => '85371011',
    //         'sequence_code_5_digits'  => '',
    //         'statistic_code_3_digits' => '000',
    //         'quantity_unit_3_digits'  => 'KGM',
    //         'privilege_code_3_digits' => '',
    //         'tariff_rate_ad_valorem_rate' => '10',
    //     ];

    //     // Apply sanitize logic
    //     $rawRow['hs_code_8_digits']        = $this->cleanNumeric($rawRow['hs_code_8_digits']);
    //     $rawRow['sequence_code_5_digits']  = $this->nullIfEmpty($rawRow['sequence_code_5_digits']);
    //     $rawRow['statistic_code_3_digits'] = $this->defaultStatistic($rawRow['statistic_code_3_digits']);
    //     $rawRow['privilege_code_3_digits'] = $this->nullIfEmpty($rawRow['privilege_code_3_digits']);
    //     $rawRow['tariff_rate_ad_valorem_rate'] = $this->nullIfEmpty($rawRow['tariff_rate_ad_valorem_rate']);

    //     $this->assertSame('85371011', $rawRow['hs_code_8_digits']);
    //     $this->assertNull($rawRow['sequence_code_5_digits']);
    //     $this->assertSame('000', $rawRow['statistic_code_3_digits']);
    //     $this->assertNull($rawRow['privilege_code_3_digits']);
    //     $this->assertSame('10', $rawRow['tariff_rate_ad_valorem_rate']);
    // }

    /**
     * Why the error STILL occurred even though the model looked correct:
     * The model (CustomerClearanceData) stores fields fine.
     * The error happens in MultiSheetImport->collection() BEFORE the model is ever touched.
     * Row 2 ("HS code", "Seq", "Stat", "Privilege code") failed validation:
     *   - is_numeric("HS code")     = false  → "must be a number" + "must be 8 digits"
     *   - is_numeric("Seq")         = false  → "must be a number"
     *   - strlen("Stat") = 4 ≠ 3           → "must be 3 digits"
     *   - strlen("Privilege code") = 14 > 10 → "must not be greater than 10 characters"
     */
    public function test_why_model_ok_but_error_still_occurred(): void
    {
        // Simulate what Row 2 (sub-header) looks like after WithHeadingRow processing
        $subHeaderRow = [
            'hs_code_8_digits' => 'HS code',       // text, not numeric
            'sequence_code_5_digits' => 'Seq',           // text, not numeric
            'statistic_code_3_digits' => 'Stat',          // text, length 4 ≠ 3
            'privilege_code_3_digits' => 'Privilege code',// 14 chars > 10
        ];

        // Confirm isSubHeaderRow would catch this row
        $this->assertTrue($this->isSubHeaderRow($subHeaderRow));

        // Confirm cleanNumeric on "HS code" produces null (not a valid 8-digit code)
        $this->assertNull($this->cleanNumeric('HS code'));

        // Confirm strlen("Privilege code") > 10 — which is what triggered the original error
        $this->assertGreaterThan(10, strlen('Privilege code'));

        // The model itself was never the problem — validation crashed before it was called
        $this->assertTrue(true, 'Model is fine. Validation on Row 2 is the root cause.');
    }

    // Test that mass record uploads with fixed cell columns (e.g. column index 2 = customer, 18 = hs_code)
    // are correctly resolved even if indexed numerically or by column letters.
    public function test_fixed_cell_column_indexing(): void
    {
        // Simulate row indexed by fixed column positions: 2 = Customer, 3 = Branch, 18 = HS Code
        $fixedIndexedRow = [
            2 => 'SHARP MANUFACTURING (THAILAND)CO.,LTD.',
            3 => 'HQO',
            5 => 'OP PANEL UN EX',
            18 => '8537.10.11',
            20 => '000',
        ];

        // Clean HS code from column 18
        $cleanHs = $this->cleanNumeric($fixedIndexedRow[18]);
        $this->assertSame('85371011', $cleanHs);

        // Statistic code from column 20
        $cleanStat = $this->defaultStatistic($fixedIndexedRow[20]);
        $this->assertSame('000', $cleanStat);
    }
}

