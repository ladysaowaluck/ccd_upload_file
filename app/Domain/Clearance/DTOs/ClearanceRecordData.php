<?php

namespace App\Domain\Clearance\DTOs;

readonly class ClearanceRecordData
{
    public function __construct(
        // --- Identity ---
        public string  $jobNo,
        public int     $masterCustomerId,
        public string  $customerName,
        public int     $masterBranchId,
        public int     $impExp,

        // --- Product description (required) ---
        public string  $productDescriptionEn,

        // --- Product detail (nullable) ---
        public ?string $productDescriptionTh        = null,
        public ?string $productCode1                = null,
        public ?string $productCode2                = null,
        public ?int    $productYear                 = null,
        public ?string $brandName                   = null,
        public ?string $modelName                   = null,
        public ?string $materials                   = null,
        public ?string $usages                      = null,
        public ?string $functions                   = null,
        public ?string $position                    = null,
        public ?string $countryOfOrigin             = null,
        public ?string $note                        = null,

        // --- HS / Tariff (nullable strings + FK IDs) ---
        public ?string $hsCode                      = null,
        public ?string $sequenceCode                = null,
        public ?int    $statisticCodeId             = null,
        public ?int    $quantityUnitId              = null,
        public ?string $privilegeCode               = null,
        public ?float  $tariffAdValorem             = null,
        public ?int    $tariffSpecificRateUnitId     = null,
        public ?float  $tariffSpecificRateBaht       = null,

        // --- Anti-dumping ---
        public ?float  $antiDumpingAdValorem         = null,

        // --- Excise tax ---
        public ?float  $exciseTaxAdValorem           = null,
        public ?string $exciseTaxRateUnitId          = null,
        public ?float  $exciseTaxRateBaht            = null,

        // --- Other tax 1 ---
        public ?string $otherTax1NameOfTax           = null,
        public ?float  $otherTax1AdValorem           = null,
        public ?string $otherTax1RateUnitId          = null,
        public ?float  $otherTax1RateBaht            = null,

        // --- Other tax 2 ---
        public ?string $otherTax2NameOfTax           = null,
        public ?float  $otherTax2AdValorem           = null,
        public ?string $otherTax2RateUnitId          = null,
        public ?float  $otherTax2RateBaht            = null,

        // --- Licenses ---
        public ?int    $license1NameId               = null,
        public ?string $license1Note                 = null,
        public ?int    $license2NameId               = null,
        public ?string $license2Note                 = null,
        public ?int    $license3NameId               = null,
        public ?string $license3Note                 = null,
        public ?int    $license4NameId               = null,
        public ?string $license4Note                 = null,

        // --- Attached files ---
        public ?string $referenceFile1               = null,
        public ?string $referenceFile2               = null,
        public ?string $hsEvidenceFile1              = null,
        public ?string $hsEvidenceFile2              = null,
        public ?string $hsEvidenceFile3              = null,
        public ?string $hsEvidenceFile4              = null,
    ) {}

    /**
     * Instantiate DTO from request or associative array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            // Identity
            jobNo:                     (string) ($data['job_no'] ?? ''),
            masterCustomerId:          (int)    ($data['master_customer_id'] ?? 0),
            customerName:              (string) ($data['customer_name'] ?? ''),
            masterBranchId:            (int)    ($data['master_branch_id'] ?? 0),
            impExp:                    (int)    ($data['imp_exp'] ?? 0),

            // Product description
            productDescriptionEn:      (string) ($data['product_description_en'] ?? ''),

            // Product detail
            productDescriptionTh:      self::str($data, 'product_description_th'),
            productCode1:              self::str($data, 'product_code_1'),
            productCode2:              self::str($data, 'product_code_2'),
            productYear:               self::int($data, 'product_year'),
            brandName:                 self::str($data, 'brand_name'),
            modelName:                 self::str($data, 'model_name'),
            materials:                 self::str($data, 'materials'),
            usages:                    self::str($data, 'usages') ?? self::str($data, 'usage'),
            functions:                 self::str($data, 'functions') ?? self::str($data, 'function'),
            position:                  self::str($data, 'position'),
            countryOfOrigin:           self::str($data, 'country_of_origin'),
            note:                      self::str($data, 'note'),

            // HS / Tariff
            hsCode:                    self::str($data, 'hs_code'),
            sequenceCode:              self::str($data, 'sequence_code'),
            statisticCodeId:           self::int($data, 'statistic_code_id'),
            quantityUnitId:            self::int($data, 'quantity_unit_id'),
            privilegeCode:             self::str($data, 'privilege_code'),
            tariffAdValorem:           self::float($data, 'tariff_ad_valorem'),
            tariffSpecificRateUnitId:  self::int($data, 'tariff_specific_rate_unit_id'),
            tariffSpecificRateBaht:    self::float($data, 'tariff_specific_rate_baht'),

            // Anti-dumping
            antiDumpingAdValorem:      self::float($data, 'anti_dumping_ad_valorem'),

            // Excise tax
            exciseTaxAdValorem:        self::float($data, 'excise_tax_ad_valorem'),
            exciseTaxRateUnitId:       self::str($data, 'excise_tax_rate_unit_id'),
            exciseTaxRateBaht:         self::float($data, 'excise_tax_rate_baht'),

            // Other tax 1
            otherTax1NameOfTax:        self::str($data, 'other_tax1_name_of_tax'),
            otherTax1AdValorem:        self::float($data, 'other_tax1_ad_valorem'),
            otherTax1RateUnitId:       self::str($data, 'other_tax1_rate_unit_id'),
            otherTax1RateBaht:         self::float($data, 'other_tax1_rate_baht'),

            // Other tax 2
            otherTax2NameOfTax:        self::str($data, 'other_tax2_name_of_tax'),
            otherTax2AdValorem:        self::float($data, 'other_tax2_ad_valorem'),
            otherTax2RateUnitId:       self::str($data, 'other_tax2_rate_unit_id'),
            otherTax2RateBaht:         self::float($data, 'other_tax2_rate_baht'),

            // Licenses
            license1NameId:            self::int($data, 'license1_name_id'),
            license1Note:              self::str($data, 'license1_note'),
            license2NameId:            self::int($data, 'license2_name_id'),
            license2Note:              self::str($data, 'license2_note'),
            license3NameId:            self::int($data, 'license3_name_id'),
            license3Note:              self::str($data, 'license3_note'),
            license4NameId:            self::int($data, 'license4_name_id'),
            license4Note:              self::str($data, 'license4_note'),

            // Files
            referenceFile1:            self::str($data, 'reference_file_1'),
            referenceFile2:            self::str($data, 'reference_file_2'),
            hsEvidenceFile1:           self::str($data, 'hs_evidence_file_1'),
            hsEvidenceFile2:           self::str($data, 'hs_evidence_file_2'),
            hsEvidenceFile3:           self::str($data, 'hs_evidence_file_3'),
            hsEvidenceFile4:           self::str($data, 'hs_evidence_file_4'),
        );
    }

    /**
     * Convert DTO to array for model persistence.
     * Null values are excluded so Eloquent only updates provided fields.
     */
    public function toArray(): array
    {
        return array_filter([
            // Identity
            'job_no'                       => $this->jobNo,
            'master_customer_id'           => $this->masterCustomerId,
            'customer_name'                => $this->customerName,
            'master_branch_id'             => $this->masterBranchId,
            'imp_exp'                      => $this->impExp,

            // Product
            'product_description_en'       => $this->productDescriptionEn,
            'product_description_th'       => $this->productDescriptionTh,
            'product_code_1'               => $this->productCode1,
            'product_code_2'               => $this->productCode2,
            'product_year'                 => $this->productYear,
            'brand_name'                   => $this->brandName,
            'model_name'                   => $this->modelName,
            'materials'                    => $this->materials,
            'usages'                       => $this->usages,
            'functions'                    => $this->functions,
            'position'                     => $this->position,
            'country_of_origin'            => $this->countryOfOrigin,
            'note'                         => $this->note,

            // HS / Tariff
            'hs_code'                      => $this->hsCode,
            'sequence_code'                => $this->sequenceCode,
            'statistic_code_id'            => $this->statisticCodeId,
            'quantity_unit_id'             => $this->quantityUnitId,
            'privilege_code'               => $this->privilegeCode,
            'tariff_ad_valorem'            => $this->tariffAdValorem,
            'tariff_specific_rate_unit_id' => $this->tariffSpecificRateUnitId,
            'tariff_specific_rate_baht'    => $this->tariffSpecificRateBaht,

            // Anti-dumping
            'anti_dumping_ad_valorem'      => $this->antiDumpingAdValorem,

            // Excise tax
            'excise_tax_ad_valorem'        => $this->exciseTaxAdValorem,
            'excise_tax_rate_unit_id'      => $this->exciseTaxRateUnitId,
            'excise_tax_rate_baht'         => $this->exciseTaxRateBaht,

            // Other tax 1
            'other_tax1_name_of_tax'       => $this->otherTax1NameOfTax,
            'other_tax1_ad_valorem'        => $this->otherTax1AdValorem,
            'other_tax1_rate_unit_id'      => $this->otherTax1RateUnitId,
            'other_tax1_rate_baht'         => $this->otherTax1RateBaht,

            // Other tax 2
            'other_tax2_name_of_tax'       => $this->otherTax2NameOfTax,
            'other_tax2_ad_valorem'        => $this->otherTax2AdValorem,
            'other_tax2_rate_unit_id'      => $this->otherTax2RateUnitId,
            'other_tax2_rate_baht'         => $this->otherTax2RateBaht,

            // Licenses
            'license1_name_id'             => $this->license1NameId,
            'license1_note'                => $this->license1Note,
            'license2_name_id'             => $this->license2NameId,
            'license2_note'                => $this->license2Note,
            'license3_name_id'             => $this->license3NameId,
            'license3_note'                => $this->license3Note,
            'license4_name_id'             => $this->license4NameId,
            'license4_note'                => $this->license4Note,

            // Files
            'reference_file_1'             => $this->referenceFile1,
            'reference_file_2'             => $this->referenceFile2,
            'hs_evidence_file_1'           => $this->hsEvidenceFile1,
            'hs_evidence_file_2'           => $this->hsEvidenceFile2,
            'hs_evidence_file_3'           => $this->hsEvidenceFile3,
            'hs_evidence_file_4'           => $this->hsEvidenceFile4,
        ], fn($val) => $val !== null);
    }

    // -------------------------------------------------------------------------
    // Private casting helpers — keep fromArray() clean and readable
    // -------------------------------------------------------------------------

    private static function str(array $data, string $key): ?string
    {
        return !empty($data[$key]) ? (string) $data[$key] : null;
    }

    private static function int(array $data, string $key): ?int
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (int) $data[$key] : null;
    }

    private static function float(array $data, string $key): ?float
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (float) $data[$key] : null;
    }
}
