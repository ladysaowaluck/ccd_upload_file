<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerClearanceData extends Model
{
    protected $connection = 'mysql';
    protected $table = 'customer_clearance_data';

    public $timestamps = true;

    protected $fillable = [
        'job_no',
        'master_customer_id',
        'customer_name',
        'master_branch_id',
        'imp_exp',
        'product_description_en',
        'product_description_th',
        'product_code_1',
        'product_code_2',
        'product_year',
        'brand_name',
        'model_name',
        'materials',
        'usages',
        'functions',
        'position',
        'country_of_origin',
        'note',
        'hs_code',
        'sequence_code',
        'statistic_code_id',
        'quantity_unit_id',
        'privilege_code',
        'tariff_ad_valorem',
        'tariff_specific_rate_unit_id',
        'tariff_specific_rate_baht',
        'anti_dumping_ad_valorem',
        'excise_tax_ad_valorem',
        'excise_tax_rate_unit_id',
        'excise_tax_rate_baht',
        'other_tax1_name_of_tax',
        'other_tax1_ad_valorem',
        'other_tax1_rate_unit_id',
        'other_tax1_rate_baht',
        'other_tax2_name_of_tax',
        'other_tax2_ad_valorem',
        'other_tax2_rate_unit_id',
        'other_tax2_rate_baht',
        'license1_name_id',
        'license1_note',
        'license2_name_id',
        'license2_note',
        'license3_name_id',
        'license3_note',
        'license4_name_id',
        'license4_note',
        'reference_file_1',
        'reference_file_2',
        'hs_evidence_file_1',
        'hs_evidence_file_2',
        'hs_evidence_file_3',
        'hs_evidence_file_4',
        'create_date',
        'update_date',
        'edit_user_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(MasterCustomer::class, 'master_customer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(MasterBranch::class, 'master_branch_id');
    }

    public function statistic(): BelongsTo
    {
        return $this->belongsTo(MasterStatistic::class, 'statistic_code_id');
    }

    public function quantityUnit(): BelongsTo
    {
        return $this->belongsTo(MasterUnit::class, 'quantity_unit_id');
    }

    public function tariffSpecificRateUnit(): BelongsTo
    {
        return $this->belongsTo(MasterUnit::class, 'tariff_specific_rate_unit_id');
    }

    public function license1(): BelongsTo
    {
        return $this->belongsTo(MasterLicense::class, 'license1_name_id');
    }

    public function license2(): BelongsTo
    {
        return $this->belongsTo(MasterLicense::class, 'license2_name_id');
    }

    public function license3(): BelongsTo
    {
        return $this->belongsTo(MasterLicense::class, 'license3_name_id');
    }

    public function license4(): BelongsTo
    {
        return $this->belongsTo(MasterLicense::class, 'license4_name_id');
    }

    // License name accessors resolving to license_en or license_th since master_licenses has no license_code
    public function getLicense1NameAttribute(): ?string
    {
        return $this->license1?->license_en ?? $this->license1?->license_th;
    }

    public function getLicense2NameAttribute(): ?string
    {
        return $this->license2?->license_en ?? $this->license2?->license_th;
    }

    public function getLicense3NameAttribute(): ?string
    {
        return $this->license3?->license_en ?? $this->license3?->license_th;
    }

    public function getLicense4NameAttribute(): ?string
    {
        return $this->license4?->license_en ?? $this->license4?->license_th;
    }

    // Safe null accessors if license code is accessed on clearance records
    public function getLicense1CodeAttribute(): ?string
    {
        // Table master_licenses has no license_code field
        return null;
    }

    public function getLicense2CodeAttribute(): ?string
    {
        // Table master_licenses has no license_code field
        return null;
    }

    public function getLicense3CodeAttribute(): ?string
    {
        // Table master_licenses has no license_code field
        return null;
    }

    public function getLicense4CodeAttribute(): ?string
    {
        // Table master_licenses has no license_code field
        return null;
    }

    // Accessors / Mutators for seamless dual-compatibility between structure columns (usages, functions) and legacy view references
    public function getUsageAttribute(): ?string
    {
        return $this->attributes['usages'] ?? $this->attributes['usage'] ?? null;
    }

    public function setUsageAttribute(?string $value): void
    {
        $this->attributes['usages'] = $value;
        $this->attributes['usage'] = $value;
    }

    public function getFunctionAttribute(): ?string
    {
        return $this->attributes['functions'] ?? $this->attributes['function'] ?? null;
    }

    public function setFunctionAttribute(?string $value): void
    {
        $this->attributes['functions'] = $value;
        $this->attributes['function'] = $value;
    }

    public function getStatisticCodeAttribute(): ?string
    {
        // return $this->attributes['statistic_code_id'] ?? $this->statistic?->statistics ?? null;
        // Lady: statistic_code_id holds the master_statistics ID, so returning it first showed e.g. "93"
        // instead of "001". Show the master code; fall back to the raw value for unmatched legacy rows.
        $raw = $this->attributes['statistic_code_id'] ?? null;

        return $this->statistic?->statistics ?? ($raw !== null ? (string) $raw : null);
    }

    public function getQuantityUnitAttribute(): ?string
    {
        // return $this->attributes['quantity_unit'] ?? $this->quantityUnit?->unit ?? null;
        // Lady: "$this->quantityUnit" resolves to THIS accessor (quantity_unit), not the relation, so it was
        // always null. Read the relation explicitly; fall back to the raw value for unmatched legacy rows.
        $raw = $this->attributes['quantity_unit_id'] ?? null;

        return $this->attributes['quantity_unit']
            ?? $this->getRelationValue('quantityUnit')?->unit
            ?? ($raw !== null ? (string) $raw : null);
    }

    // Provide backward-compatibility accessor/mutator for tariff_specific_rate_unit_id pointing to actual column tariff_specific_rate_unit
    // public function getTariffSpecificRateUnitIdAttribute(): ?string
    // {
    //     return $this->attributes['tariff_specific_rate_unit'] ?? null;
    // }
    //
    // public function setTariffSpecificRateUnitIdAttribute(?string $value): void
    // {
    //     $this->attributes['tariff_specific_rate_unit'] = $value !== null ? substr(trim((string) $value), 0, 10) : null;
    // }
    // Lady: The real table has tariff_specific_rate_unit_id (int, master_units.id) and NO column
    // tariff_specific_rate_unit. The mutator above redirected every save to that missing column
    // (SQLSTATE 42S22 / 1054 "Unknown column"), so every create failed.

    // Display helper — unit code (e.g. "KGM") of tariff_specific_rate_unit_id; raw value if not matched.
    public function getTariffSpecificRateUnitAttribute(): ?string
    {
        $raw = $this->attributes['tariff_specific_rate_unit_id'] ?? null;

        return $this->getRelationValue('tariffSpecificRateUnit')?->unit ?? ($raw !== null ? (string) $raw : null);
    }

    // Unit code for the *_rate_unit_id columns (excise / other tax) that store master_units.id from the
    // master data selector. Legacy rows that hold a typed code (e.g. "LTR") are shown as stored.
    public function unitCodeFor(string $column): ?string
    {
        $value = $this->attributes[$column] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $unit = MasterUnit::find((int) $value);
            if ($unit) {
                return $unit->unit;
            }
        }

        return (string) $value;
    }

    // Added unified reference and evidence file compliance inspection methods
    // so both Reference Files (Catalog, SDS) and HS Evidence Files are explicitly checked.
    public function hasReferenceFile(): bool
    {
        return !empty($this->reference_file_1) || !empty($this->reference_file_2);
    }

    public function hasHsEvidenceFile(): bool
    {
        return !empty($this->hs_evidence_file_1)
            || !empty($this->hs_evidence_file_2)
            || !empty($this->hs_evidence_file_3)
            || !empty($this->hs_evidence_file_4);
    }

    public function isMissingReference(): bool
    {
        return !$this->hasReferenceFile();
    }

    public function isFullyDocumented(): bool
    {
        return $this->hasReferenceFile() && $this->hasHsEvidenceFile();
    }

    public function isPartiallyDocumented(): bool
    {
        return ($this->hasReferenceFile() && !$this->hasHsEvidenceFile())
            || (!$this->hasReferenceFile() && $this->hasHsEvidenceFile());
    }

    public function isMissingAllDocuments(): bool
    {
        return !$this->hasReferenceFile() && !$this->hasHsEvidenceFile();
    }

    // Check if clearance record has zero uploaded evidence files
    public function isMissingEvidence(): bool
    {
        return empty($this->hs_evidence_file_1)
            && empty($this->hs_evidence_file_2)
            && empty($this->hs_evidence_file_3);
    }
}

