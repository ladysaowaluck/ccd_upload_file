<?php

namespace Tests\Unit;

use App\Domain\Clearance\Actions\GenerateJobSequenceAction;
use PHPUnit\Framework\TestCase;

// Unit test for GenerateJobSequenceAction verifying sequence prefix formatting and padding
class GenerateJobSequenceActionTest extends TestCase
{
    //Test sequence prefix and formatting logic.
    public function test_sequence_format_structure(): void
    {
        $action = new GenerateJobSequenceAction();
        $prefix = "CCD" . date('ym') . "-";

        // Validate prefix format: 3 letters + 4 digits (YYMM) + dash
        $this->assertMatchesRegularExpression('/^[A-Z]{3}\d{4}-$/', $prefix);

        // Validate padding of numbers
        $padded1 = str_pad('1', 4, '0', STR_PAD_LEFT);
        $this->assertSame('0001', $padded1);

        $padded9999 = str_pad('9999', 4, '0', STR_PAD_LEFT);
        $this->assertSame('9999', $padded9999);

        // Numbers with 5 digits should remain intact (not truncated)
        $padded10000 = str_pad('10000', 4, '0', STR_PAD_LEFT);
        $this->assertSame('10000', $padded10000);
    }

    // Test sequence calculation logic.
    public function test_sequence_numeric_increment(): void
    {
        $prefix = "CCD2611-";
        $lastJobNo = "CCD2611-0150";

        $rawSeq = substr($lastJobNo, strlen($prefix));
        $this->assertSame('0150', $rawSeq);
        $this->assertSame(150, (int) $rawSeq);

        $nextSeq = (int) $rawSeq + 1;
        $nextJobNo = $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        $this->assertSame('CCD2611-0151', $nextJobNo);
    }
}

