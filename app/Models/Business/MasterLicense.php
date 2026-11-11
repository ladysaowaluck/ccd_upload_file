<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterLicense extends Model
{
    protected $connection = 'mysql';
    protected $table = 'master_licenses';

    // Fix master_licenses fillable attributes to match ccd_system_rds structure.
    // Table master_licenses has license_th and license_en (no license_code or license_name).
    protected $fillable = [
        'license_th',
        'license_en',
    ];

    // Inverse relationships to customer_clearance_data for license slots 1 to 4
    public function clearanceData1(): HasMany
    {
        return $this->hasMany(CustomerClearanceData::class, 'license1_name_id');
    }

    public function clearanceData2(): HasMany
    {
        return $this->hasMany(CustomerClearanceData::class, 'license2_name_id');
    }

    public function clearanceData3(): HasMany
    {
        return $this->hasMany(CustomerClearanceData::class, 'license3_name_id');
    }

    public function clearanceData4(): HasMany
    {
        return $this->hasMany(CustomerClearanceData::class, 'license4_name_id');
    }

    // Accessor for license_name providing seamless compatibility returning English or Thai license title
    public function getLicenseNameAttribute(): string
    {
        return $this->license_en ?? $this->license_th ?? '';
    }

    // Safe null fallback accessor to prevent exceptions if code accesses license_code
    public function getLicenseCodeAttribute(): ?string
    {
        // Table master_licenses has no license_code field in ccd_system_rds
        return null;
    }
}


