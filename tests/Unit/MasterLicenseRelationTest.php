<?php

namespace Tests\Unit;

use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterLicense;
use Tests\TestCase;

/**
 * Unit test verifying MasterLicense model and relations according to ccd_system_rds structure.
 *
 * Verifies:
 *   1. MasterLicense table has license_th and license_en (no license_code column in DB).
 *   2. MasterLicense::license_name accessor provides fallback to license_en/license_th.
 *   3. MasterLicense::license_code accessor returns null gracefully (preventing exceptions).
 *   4. CustomerClearanceData defines BelongsTo relationships: license1, license2, license3, license4 with MasterLicense.
 *   5. CustomerClearanceData license1_code fallback returns null gracefully.
 */
class MasterLicenseRelationTest extends TestCase
{
    // Test that MasterLicense fillable attributes match ccd_system_rds_structure.sql (license_th, license_en, no license_code).
    public function test_master_license_fillable_has_no_license_code(): void
    {
        $license = new MasterLicense();
        $fillable = $license->getFillable();

        $this->assertContains('license_th', $fillable, 'master_licenses must have license_th');
        $this->assertContains('license_en', $fillable, 'master_licenses must have license_en');
        $this->assertNotContains('license_code', $fillable, 'master_licenses must NOT have license_code field in ccd_system_rds');
    }

    // Test license_name accessor falls back to license_en or license_th.
    public function test_master_license_name_accessor(): void
    {
        $license = new MasterLicense([
            'license_th' => 'กรมศุลกากร',
            'license_en' => 'The Customs Department',
        ]);

        $this->assertSame('The Customs Department', $license->license_name);
        $this->assertSame('The Customs Department', $license->license_en);
        $this->assertSame('กรมศุลกากร', $license->license_th);
    }

    
    //  Test that accessing license_code returns null safely without throwing exceptions.
    public function test_master_license_code_returns_null_safely(): void
    {
        $license = new MasterLicense([
            'license_th' => 'กรมศุลกากร',
            'license_en' => 'The Customs Department',
        ]);

        $this->assertNull($license->license_code, 'license_code must safely return null since column does not exist');
    }

    // Test that CustomerClearanceData defines relations license1..4 pointing to MasterLicense.
    public function test_customer_clearance_data_has_license_relations(): void
    {
        $record = new CustomerClearanceData();

        $rel1 = $record->license1();
        $this->assertSame('license1_name_id', $rel1->getForeignKeyName());
        $this->assertInstanceOf(MasterLicense::class, $rel1->getRelated());

        $rel2 = $record->license2();
        $this->assertSame('license2_name_id', $rel2->getForeignKeyName());
        $this->assertInstanceOf(MasterLicense::class, $rel2->getRelated());

        $rel3 = $record->license3();
        $this->assertSame('license3_name_id', $rel3->getForeignKeyName());
        $this->assertInstanceOf(MasterLicense::class, $rel3->getRelated());

        $rel4 = $record->license4();
        $this->assertSame('license4_name_id', $rel4->getForeignKeyName());
        $this->assertInstanceOf(MasterLicense::class, $rel4->getRelated());
    }

    // Test CustomerClearanceData license code fallback returns null safely.
    public function test_clearance_record_license_code_fallbacks_return_null(): void
    {
        $record = new CustomerClearanceData();

        $this->assertNull($record->license1_code);
        $this->assertNull($record->license2_code);
        $this->assertNull($record->license3_code);
        $this->assertNull($record->license4_code);
    }
}
