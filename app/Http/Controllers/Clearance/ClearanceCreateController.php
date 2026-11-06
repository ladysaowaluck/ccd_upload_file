<?php

namespace App\Http\Controllers\Clearance;

// Gemini : ClearanceCreateController for single manual CCD registration and edit
use App\Domain\Clearance\Actions\GenerateJobSequenceAction;
use App\Http\Controllers\Controller;
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterBranch;
use App\Models\Business\MasterCustomer;
use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Domain\Clearance\Services\ClearanceSequenceService;
use Illuminate\View\View;

class ClearanceCreateController extends Controller
{
    // Lady : Direct controller coupling to GenerateJobSequenceAction bypasses domain service abstraction.
    /*
    // [Previous Version - Direct Action Injection]
    protected GenerateJobSequenceAction $jobGenerator;

    public function __construct(GenerateJobSequenceAction $jobGenerator)
    {
        $this->jobGenerator = $jobGenerator;
    }
    */
    // Gemini : GAP-06.2 & GAP-11 — Injected ClearanceSequenceService, CreateClearanceRecordAction, and DocumentStorageService
    public function __construct(
        protected ClearanceSequenceService $sequenceService,
        protected \App\Domain\Clearance\Actions\CreateClearanceRecordAction $createAction,
        protected \App\Services\Storage\DocumentStorageService $storageService
    ) {}

    public function create(): View
    {
        $branches = MasterBranch::orderBy('branch_name')->get();
        $customers = MasterCustomer::where('is_enable', 0)->orderBy('company_name')->get();
        $units = MasterUnit::orderBy('unit')->get();
        $statistics = MasterStatistic::orderBy('statistics')->get();
        $suggestedJobNo = $this->sequenceService->getNextJobNo('CCD');

        return view('clearance.create', compact('branches', 'customers', 'units', 'statistics', 'suggestedJobNo'));
    }

    // Lady : Inline validation and manual file storage directly in the controller violates the Single Responsibility Principle (SRP) and bypasses the CreateClearanceRecordAction domain action.
    /*
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'job_no' => 'required|string|unique:mysql.customer_clearance_data,job_no',
            'master_customer_id' => 'required|exists:mysql.master_customers,id',
            'master_branch_id' => 'required|exists:mysql.master_branches,id',
            'imp_exp' => 'required|in:0,1',
            'product_description_en' => 'required|string|max:500',
            'product_description_th' => 'nullable|string|max:500',
            'product_code_1' => 'nullable|string|max:100',
            'hs_code' => 'required|string|max:20',
            'sequence_code' => 'nullable|string|max:20',
            'statistic_code' => 'nullable|string|max:10',
            'quantity_unit' => 'nullable|string|max:10',
            'privilege_code' => 'nullable|string|max:20',
            'tariff_ad_valorem' => 'nullable|numeric',
            'tariff_specific_rate_baht' => 'nullable|numeric',
            'reference_file_1' => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
            'hs_evidence_file_1' => 'nullable|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png',
        ]);

        $customer = MasterCustomer::find($validated['master_customer_id']);
        $validated['customer_name'] = $customer ? $customer->company_name : '';
        $validated['create_date'] = date('Y-m-d');
        $validated['update_date'] = date('Y-m-d');
        $validated['edit_user_id'] = auth()->id() ?: 1;

        if ($request->hasFile('reference_file_1')) {
            $rf1 = $request->file('reference_file_1');
            $rfName = "{$validated['job_no']}_rf1_" . time() . '.' . $rf1->getClientOriginalExtension();
            $validated['reference_file_1'] = $rf1->storeAs('reference', $rfName, 'public');
        }

        if ($request->hasFile('hs_evidence_file_1')) {
            $hs1 = $request->file('hs_evidence_file_1');
            $hsName = "{$validated['job_no']}_hs1_" . time() . '.' . $hs1->getClientOriginalExtension();
            $validated['hs_evidence_file_1'] = $hs1->storeAs('hs_evidence', $hsName, 'public');
        }

        CustomerClearanceData::create($validated);

        return redirect()->route('clearance.index')->with('success', "Clearance record {$validated['job_no']} created successfully.");
    }
    */
    // Gemini : GAP-10 & GAP-11 — Use StoreClearanceRecordRequest form request and delegate creation to CreateClearanceRecordAction
    public function store(\App\Http\Requests\StoreClearanceRecordRequest $request): RedirectResponse
    {
        $payload = array_merge($request->validated(), $request->allFiles());
        $record = $this->createAction->execute($payload);

        // Gemini : Flash upload debug data containing destination address and S3 status
        $uploadHistory = $this->storageService->getUploadHistory();
        $redirect = redirect()->route('clearance.index')->with('success', "Clearance record {$record->job_no} created successfully.");
        if (!empty($uploadHistory)) {
            $redirect->with('upload_debug', $uploadHistory);
        }

        return $redirect;
    }
}

