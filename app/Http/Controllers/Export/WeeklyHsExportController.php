<?php

namespace App\Http\Controllers\Export;

// Gemini : GAP-04 — WeeklyHsExportController managing weekly HS Code report filters and Excel downloads
use App\Domain\Clearance\Services\WeeklyHsExportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WeeklyHsExportController extends Controller
{
    public function __construct(
        protected WeeklyHsExportService $exportService
    ) {}

    /**
     * Display the weekly HS export filtering interface.
     */
    public function index(): View
    {
        $defaultMonth = date('Y-m');
        return view('export.weekly', compact('defaultMonth'));
    }

    /**
     * Generate and download the filtered weekly HS Code Excel report.
     */
    public function export(Request $request): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'category'   => 'required|string|in:CCD,CST,NTB',
            'year_month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'date_from'  => 'required|integer|min:1',
            'date_to'    => 'required|integer|min:1|gte:date_from',
        ], [
            'date_to.gte' => 'The end sequence must be greater than or equal to the start sequence.',
        ]);

        $category = strtoupper($validated['category']);
        $yearMonth = $validated['year_month']; // e.g. "2026-11"
        $yymm = substr($yearMonth, 2, 2) . substr($yearMonth, 5, 2); // e.g. "2611"
        $mm = substr($yearMonth, 5, 2);

        $fromSeq = sprintf('%04d', (int)$validated['date_from']);
        $toSeq = sprintf('%04d', (int)$validated['date_to']);

        $fromJobNo = "{$category}{$yymm}-{$fromSeq}";
        $toJobNo = "{$category}{$yymm}-{$toSeq}";

        $recordCount = $this->exportService->countRecords($fromJobNo, $toJobNo);

        if ($recordCount === 0) {
            return back()
                ->withInput()
                ->with('warning', "No records found matching sequence range {$fromJobNo} to {$toJobNo}.");
        }

        $filename = "HS_Code{$mm}-{$fromSeq}_{$mm}-{$toSeq}.xlsx";

        return Excel::download(
            $this->exportService->buildExport($fromJobNo, $toJobNo),
            $filename
        );
    }
}

