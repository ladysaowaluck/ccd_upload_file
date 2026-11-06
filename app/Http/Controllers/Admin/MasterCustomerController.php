<?php

namespace App\Http\Controllers\Admin;

// Gemini : MasterCustomerController for corporate client registry and batch customer name synchronization
use App\Domain\MasterData\Services\BatchCustomerNameSyncService;
use App\Http\Controllers\Controller;
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterCustomer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterCustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = MasterCustomer::query();

        if ($request->filled('company_name')) {
            $query->where('company_name', 'LIKE', '%' . trim((string)$request->input('company_name')) . '%');
        }

        if ($request->filled('tax_id')) {
            $query->where('tax_id', 'LIKE', '%' . trim((string)$request->input('tax_id')) . '%');
        }

        if ($request->filled('is_enable') && in_array($request->input('is_enable'), ['0', '1'], true)) {
            $query->where('is_enable', (int)$request->input('is_enable'));
        }

        $customers = $query->orderBy('company_name')->paginate(25)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    // Lady : Inline batch update logic in controller couples presentation to database mutation details.
    /*
    // [Previous Version - Inline Update in Controller]
    public function syncNames(Request $request): RedirectResponse
    {
        $request->validate([
            'master_customer_id' => 'required|exists:master_customers,id',
        ]);

        $customer = MasterCustomer::findOrFail($request->input('master_customer_id'));

        $updatedCount = CustomerClearanceData::where('master_customer_id', $customer->id)
            ->update(['customer_name' => $customer->company_name]);

        return back()->with('success', "Harmonized customer name '{$customer->company_name}' across {$updatedCount} clearance records.");
    }
    */
    // Lady : Inline Request validation in controller action couples request parsing with business flow.
    /*
    public function syncNames(Request $request, BatchCustomerNameSyncService $syncService): RedirectResponse
    {
        $request->validate([
            'master_customer_id' => 'required|exists:master_customers,id',
        ]);

        $customerId = (int) $request->input('master_customer_id');
        $customer = MasterCustomer::findOrFail($customerId);

        $updatedCount = $syncService->sync($customerId);

        return back()->with('success', "Harmonized customer name '{$customer->company_name}' across {$updatedCount} clearance records.");
    }
    */
    // Gemini : GAP-10 — Use MasterCustomerRequest for centralized authorization and input validation
    public function syncNames(\App\Http\Requests\MasterCustomerRequest $request, BatchCustomerNameSyncService $syncService): RedirectResponse
    {
        $customerId = (int) $request->validated('master_customer_id', $request->input('master_customer_id'));
        $customer = MasterCustomer::findOrFail($customerId);

        $updatedCount = $syncService->sync($customerId);

        return back()->with('success', "Harmonized customer name '{$customer->company_name}' across {$updatedCount} clearance records.");
    }
}

