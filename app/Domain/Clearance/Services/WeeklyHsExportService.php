<?php

namespace App\Domain\Clearance\Services;

use App\Models\Business\CustomerClearanceData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class WeeklyHsExportService
{
    /**
     * Build the export object for the specified Job No range.
     *
     * @param string $fromJobNo
     * @param string $toJobNo
     * @return WeeklyHsMultiSheetExport
     */
    public function buildExport(string $fromJobNo, string $toJobNo): WeeklyHsMultiSheetExport
    {
        $records = CustomerClearanceData::with(['branch', 'customer'])
            ->where('job_no', '>=', $fromJobNo)
            ->where('job_no', '<=', $toJobNo)
            ->orderBy('job_no', 'asc')
            ->get();

        $ranking = DB::connection('mysql')
            ->table('customer_clearance_data')
            ->where('job_no', '>=', $fromJobNo)
            ->where('job_no', '<=', $toJobNo)
            ->select('customer_name', DB::raw('COUNT(job_no) as job_no_count'))
            ->groupBy('customer_name')
            ->orderByDesc('job_no_count')
            ->get();

        return new WeeklyHsMultiSheetExport($records, $ranking);
    }

    //  Count records matching the specified range.
    public function countRecords(string $fromJobNo, string $toJobNo): int
    {
        return CustomerClearanceData::where('job_no', '>=', $fromJobNo)
            ->where('job_no', '<=', $toJobNo)
            ->count();
    }
}

class WeeklyHsMultiSheetExport implements WithMultipleSheets
{
    public function __construct(
        protected Collection $records,
        protected Collection $ranking
    ) {
    }

    public function sheets(): array
    {
        return [
            new WeeklyHsRankingSheet($this->ranking),
            new WeeklyHsRecordSheet($this->records),
        ];
    }
}

class WeeklyHsRankingSheet implements FromCollection, WithTitle, WithHeadings
{
    public function __construct(protected Collection $ranking)
    {
    }

    public function title(): string
    {
        return 'Ranking';
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Customer Name',
            'Total Job Count',
        ];
    }

    public function collection(): Collection
    {
        $rank = 1;
        return $this->ranking->map(function ($item) use (&$rank) {
            return [
                'rank' => $rank++,
                'customer_name' => $item->customer_name,
                'total_job_count' => $item->job_no_count,
            ];
        });
    }
}

class WeeklyHsRecordSheet implements FromCollection, WithTitle, WithHeadings, WithMapping
{
    public function __construct(protected Collection $records)
    {
    }

    public function title(): string
    {
        return 'HS Record';
    }

    public function headings(): array
    {
        return [
            'Job Number',
            'Customer Name',
            'Branch Code',
            'Operation Type',
            'Product Description (EN)',
            'Product Description (TH)',
            'Product Code',
            'Brand Name',
            'Materials',
            'Usage',
            'Function',
            'Country of Origin',
            'HS Code',
            'Sequence Code',
            'Statistic Code',
            'Quantity Unit',
            'Privilege Code',
            'Tariff Rate (%)',
            'Reference File 1',
            'Reference File 2',
            'Evidence File 1',
            'Evidence File 2',
            'Created Date',
        ];
    }

    public function collection(): Collection
    {
        return $this->records;
    }

    /**
     * @param CustomerClearanceData $row
     */
    public function map($row): array
    {
        return [
            $row->job_no,
            $row->customer_name,
            $row->branch ? $row->branch->branch_code : '',
            $row->imp_exp === 1 ? 'Import' : 'Export',
            $row->product_description_en,
            $row->product_description_th,
            $row->product_code_1,
            $row->brand_name,
            $row->materials,
            $row->usage,
            $row->function,
            $row->country_of_origin,
            $row->hs_code,
            $row->sequence_code,
            $row->statistic_code_id,
            $row->quantity_unit_id,
            $row->privilege_code,
            $row->tariff_ad_valorem ? $row->tariff_ad_valorem . '%' : '0%',
            $row->reference_file_1 ? 'Attached' : '-',
            $row->reference_file_2 ? 'Attached' : '-',
            $row->hs_evidence_file_1 ? 'Attached' : '-',
            $row->hs_evidence_file_2 ? 'Attached' : '-',
            $row->create_date,
        ];
    }
}

