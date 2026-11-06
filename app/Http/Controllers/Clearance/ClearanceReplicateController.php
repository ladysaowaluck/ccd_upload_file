<?php

namespace App\Http\Controllers\Clearance;

// Gemini : MISS-01 — ClearanceReplicateController handles the duplicate-CCD workflow.
// Shows form with auto-suggested next job no, then delegates duplication to the action class.
use App\Domain\Clearance\Actions\GenerateJobSequenceAction;
use App\Domain\Clearance\Actions\ReplicateClearanceRecordAction;
use App\Http\Controllers\Controller;
use App\Models\Business\CustomerClearanceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClearanceReplicateController extends Controller
{
    /**
     * Show the replication form pre-filled with the next suggested job number.
     */
    public function show(int $id, GenerateJobSequenceAction $sequence): View
    {
        $source = CustomerClearanceData::findOrFail($id);

        // Suggest the next CCD seq for this month as default
        try {
            $suggestedNo = $sequence->execute('CCD');
        } catch (\OverflowException) {
            // If sequence is full, let user type manually
            $suggestedNo = '';
        }

        return view('clearance.replicate', compact('source', 'suggestedNo'));
    }

    /**
     * Execute record duplication and redirect to edit page of the new record.
     */
    public function execute(
        Request $request,
        int $id,
        ReplicateClearanceRecordAction $action
    ): RedirectResponse {
        $source = CustomerClearanceData::findOrFail($id);

        $request->validate([
            'new_job_no' => [
                'required',
                'regex:/^(CCD|CST|NTB)\d{4}-\d{4}$/',
                'unique:customer_clearance_data,job_no',
            ],
            'new_product_code_1' => ['nullable', 'string', 'max:100'],
            'new_product_code_2' => ['nullable', 'string', 'max:100'],
        ]);

        $newRecord = $action->execute(
            $source,
            $request->input('new_job_no'),
            $request->input('new_product_code_1'),
            $request->input('new_product_code_2'),
        );

        return redirect()
            ->route('clearance.edit', $newRecord->id)
            ->with('success', "Record replicated as {$newRecord->job_no}. Please verify details and add evidence files.");
    }
}

