<?php

namespace App\Domain\Clearance\DTOs;

// Gemini : GAP-06.1 — Typed Data Transfer Object (DTO) for clearance record transfer between layers
readonly class ClearanceRecordData
{
    public function __construct(
        public string $jobNo,
        public int $masterCustomerId,
        public string $customerName,
        public int $masterBranchId,
        public int $impExp,
        public string $productDescriptionEn,
        public ?string $productDescriptionTh = null,
        public ?string $productCode1 = null,
        public ?string $brandName = null,
        public ?string $materials = null,
        public ?string $usage = null,
        public ?string $function = null,
        public ?string $countryOfOrigin = null,
        public ?string $hsCode = null,
        public ?string $sequenceCode = null,
        public ?string $statisticCode = null,
        public ?string $quantityUnit = null,
        public ?string $privilegeCode = null,
        public ?float $tariffAdValorem = null,
        public ?float $tariffSpecificRateBaht = null,
    ) {}

    /**
     * Instantiate DTO from request or associative array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            jobNo: (string)($data['job_no'] ?? ''),
            masterCustomerId: (int)($data['master_customer_id'] ?? 0),
            customerName: (string)($data['customer_name'] ?? ''),
            masterBranchId: (int)($data['master_branch_id'] ?? 0),
            impExp: (int)($data['imp_exp'] ?? 0),
            productDescriptionEn: (string)($data['product_description_en'] ?? ''),
            productDescriptionTh: !empty($data['product_description_th']) ? (string)$data['product_description_th'] : null,
            productCode1: !empty($data['product_code_1']) ? (string)$data['product_code_1'] : null,
            brandName: !empty($data['brand_name']) ? (string)$data['brand_name'] : null,
            materials: !empty($data['materials']) ? (string)$data['materials'] : null,
            usage: !empty($data['usage']) ? (string)$data['usage'] : null,
            function: !empty($data['function']) ? (string)$data['function'] : null,
            countryOfOrigin: !empty($data['country_of_origin']) ? (string)$data['country_of_origin'] : null,
            hsCode: !empty($data['hs_code']) ? (string)$data['hs_code'] : null,
            sequenceCode: !empty($data['sequence_code']) ? (string)$data['sequence_code'] : null,
            statisticCode: !empty($data['statistic_code']) ? (string)$data['statistic_code'] : null,
            quantityUnit: !empty($data['quantity_unit']) ? (string)$data['quantity_unit'] : null,
            privilegeCode: !empty($data['privilege_code']) ? (string)$data['privilege_code'] : null,
            tariffAdValorem: isset($data['tariff_ad_valorem']) && is_numeric($data['tariff_ad_valorem']) ? (float)$data['tariff_ad_valorem'] : null,
            tariffSpecificRateBaht: isset($data['tariff_specific_rate_baht']) && is_numeric($data['tariff_specific_rate_baht']) ? (float)$data['tariff_specific_rate_baht'] : null,
        );
    }

    /**
     * Convert DTO to array for model persistence.
     */
    public function toArray(): array
    {
        return array_filter([
            'job_no'                    => $this->jobNo,
            'master_customer_id'        => $this->masterCustomerId,
            'customer_name'             => $this->customerName,
            'master_branch_id'          => $this->masterBranchId,
            'imp_exp'                   => $this->impExp,
            'product_description_en'    => $this->productDescriptionEn,
            'product_description_th'    => $this->productDescriptionTh,
            'product_code_1'            => $this->productCode1,
            'brand_name'                => $this->brandName,
            'materials'                 => $this->materials,
            'usage'                     => $this->usage,
            'function'                  => $this->function,
            'country_of_origin'         => $this->countryOfOrigin,
            'hs_code'                   => $this->hsCode,
            'sequence_code'             => $this->sequenceCode,
            'statistic_code'            => $this->statisticCode,
            'quantity_unit'             => $this->quantityUnit,
            'privilege_code'            => $this->privilegeCode,
            'tariff_ad_valorem'         => $this->tariffAdValorem,
            'tariff_specific_rate_baht' => $this->tariffSpecificRateBaht,
        ], fn($val) => $val !== null);
    }
}

