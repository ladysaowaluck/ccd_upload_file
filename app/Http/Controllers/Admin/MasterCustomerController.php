<?php

namespace App\Http\Controllers\Admin;

use App\Domain\MasterData\Services\BatchCustomerNameSyncService;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterCustomerRequest;
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
            $query->where('company_name', 'LIKE', '%' . trim((string) $request->input('company_name')) . '%');
        }

        if ($request->filled('tax_id')) {
            $query->where('tax_id', 'LIKE', '%' . trim((string) $request->input('tax_id')) . '%');
        }

        if ($request->filled('is_enable') && in_array($request->input('is_enable'), ['0', '1'], true)) {
            $query->where('is_enable', (int) $request->input('is_enable'));
        }

        $customers = $query->orderBy('company_name')->paginate(25)->withQueryString();

        return view('admin.master.customers.index', compact('customers'));
    }

    public function store(MasterCustomerRequest $request): RedirectResponse
    {
        MasterCustomer::create([
            'company_name' => $request->input('company_name'),
            'tax_id'       => $request->input('tax_id'),
            'is_enable'    => $request->input('is_enable', 0),
        ]);

        return redirect()->route('admin.master.customers.index')
            ->with('success', "Customer '{$request->input('company_name')}' created successfully.");
    }

    public function edit(int $id): View
    {
        $customer = MasterCustomer::findOrFail($id);
        return view('admin.master.customers.edit', compact('customer'));
    }

    public function update(MasterCustomerRequest $request, int $id, BatchCustomerNameSyncService $syncService): RedirectResponse
    {
        $customer    = MasterCustomer::findOrFail($id);
        $nameChanged = $customer->company_name !== $request->input('company_name');

        $customer->update([
            'company_name' => $request->input('company_name'),
            'tax_id'       => $request->input('tax_id'),
            'is_enable'    => $request->input('is_enable', $customer->is_enable),
        ]);

        $synced = 0;
        if ($nameChanged) {
            $synced = $syncService->sync($customer->id);
        }

        $message = "Customer '{$customer->company_name}' updated.";
        if ($synced > 0) {
            $message .= " Name synced to {$synced} clearance record(s).";
        }

        return redirect()->route('admin.master.customers.index')->with('success', $message);
    }

    public function destroy(int $id): RedirectResponse
    {
        $customer = MasterCustomer::findOrFail($id);

        $linkedCount = CustomerClearanceData::where('master_customer_id', $id)->count();
        if ($linkedCount > 0) {
            return redirect()->route('admin.master.customers.index')
                ->with('error', "Cannot delete '{$customer->company_name}' — {$linkedCount} clearance record(s) are still linked.");
        }

        $name = $customer->company_name;
        $customer->delete();

        return redirect()->route('admin.master.customers.index')
            ->with('success', "Customer '{$name}' deleted.");
    }

    public function syncNames(MasterCustomerRequest $request, BatchCustomerNameSyncService $syncService): RedirectResponse
    {
        $customerId = (int) $request->validated('master_customer_id', $request->input('master_customer_id'));
        $customer   = MasterCustomer::findOrFail($customerId);

        $updatedCount = $syncService->sync($customerId);

        return back()->with('success', "Harmonized customer name '{$customer->company_name}' across {$updatedCount} clearance records.");
    }
}
