<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business\CustomerClearanceData;
use App\Models\Business\MasterBranch;
use App\Models\Business\MasterLicense;
use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;
use App\Support\Database\MasterDataUniqueFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    // ==========================================
    // Branches
    // ==========================================

    public function branches(Request $request): View
    {
        $query = MasterBranch::query();

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('branch_code', 'LIKE', $search)
                    ->orWhere('branch_name', 'LIKE', $search);
            });
        }

        $branches = $query->orderBy('branch_name')->paginate(20)->withQueryString();

        return view('admin.master.branches', compact('branches'));
    }

    public function storeBranch(Request $request): RedirectResponse
    {
        // Ignore the current record so an unchanged branch_code can be saved.
        $data = $request->validate([
            // 'branch_code' => 'required|string|max:5|unique:mysql.master_branches,branch_code',
            'branch_code' => ['required', 'string', 'max:5', MasterDataUniqueFields::rule('branch_code')],
            'branch_name' => 'required|string|max:100',
        ], MasterDataUniqueFields::messages());

        // MasterBranch::create([
        //     'branch_code' => strtoupper(trim($data['branch_code'])),
        //     'branch_name' => trim($data['branch_name']),
        // ]);
        // Calculate next id value defensively before creating master branch
        $nextId = ((int) MasterBranch::max('id')) + 1;
        MasterBranch::create([
            'id' => $nextId,
            'branch_code' => strtoupper(trim($data['branch_code'])),
            'branch_name' => trim($data['branch_name']),
        ]);

        return redirect()->route('admin.master.branches')
            ->with('success', "Branch '{$data['branch_name']}' created successfully.");
    }

    public function updateBranch(Request $request, int $id): RedirectResponse
    {
        $branch = MasterBranch::findOrFail($id);

        // Ignore the current record so an unchanged branch_code can be saved.
        $data = $request->validate([
            // 'branch_code' => 'required|string|max:5|unique:mysql.master_branches,branch_code,' . $branch->id,
            'branch_code' => ['required', 'string', 'max:5', MasterDataUniqueFields::rule('branch_code', $branch->id)],
            'branch_name' => 'required|string|max:100',
        ], MasterDataUniqueFields::messages());

        $branch->update([
            'branch_code' => strtoupper(trim($data['branch_code'])),
            'branch_name' => trim($data['branch_name']),
        ]);

        return redirect()->route('admin.master.branches')
            ->with('success', "Branch '{$branch->branch_name}' updated successfully.");
    }

    public function destroyBranch(int $id): RedirectResponse
    {
        $branch = MasterBranch::findOrFail($id);
        $linked = CustomerClearanceData::where('master_branch_id', $branch->id)->count();

        if ($linked > 0) {
            return redirect()->route('admin.master.branches')
                ->with('error', "Cannot delete branch '{$branch->branch_name}' ({$branch->branch_code}) — {$linked} clearance record(s) are linked to it.");
        }

        $branchName = $branch->branch_name;
        $branch->delete();

        return redirect()->route('admin.master.branches')
            ->with('success', "Branch '{$branchName}' deleted successfully.");
    }

    // ==========================================
    // Units
    // ==========================================

    public function units(Request $request): View
    {
        $query = MasterUnit::query();

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->input('search')) . '%';
            $query->where('unit', 'LIKE', $search);
        }

        $units = $query->orderBy('unit')->paginate(20)->withQueryString();

        return view('admin.master.units', compact('units'));
    }

    public function storeUnit(Request $request): RedirectResponse
    {
        // Ignore the current record so an unchanged unit can be saved.
        $data = $request->validate([
            // 'unit' => 'required|string|max:3|unique:mysql.master_units,unit',
            'unit' => ['required', 'string', 'max:3', MasterDataUniqueFields::rule('unit')],
        ], MasterDataUniqueFields::messages());

        $unitCode = strtoupper(trim($data['unit']));
        MasterUnit::create([
            'unit' => $unitCode,
        ]);

        return redirect()->route('admin.master.units')
            ->with('success', "Unit '{$unitCode}' created successfully.");
    }

    public function updateUnit(Request $request, int $id): RedirectResponse
    {
        $unit = MasterUnit::findOrFail($id);

        // Ignore the current record so an unchanged unit can be saved.
        $data = $request->validate([
            // 'unit' => 'required|string|max:3|unique:mysql.master_units,unit,' . $unit->id,
            'unit' => ['required', 'string', 'max:3', MasterDataUniqueFields::rule('unit', $unit->id)],
        ], MasterDataUniqueFields::messages());

        $unitCode = strtoupper(trim($data['unit']));
        $unit->update([
            'unit' => $unitCode,
        ]);

        return redirect()->route('admin.master.units')
            ->with('success', "Unit '{$unitCode}' updated successfully.");
    }

    public function destroyUnit(int $id): RedirectResponse
    {
        $unit = MasterUnit::findOrFail($id);
        $linked = CustomerClearanceData::where('quantity_unit_id', $unit->unit)
            ->orWhere('quantity_unit_id', (string) $unit->id)
            ->count();

        if ($linked > 0) {
            return redirect()->route('admin.master.units')
                ->with('error', "Cannot delete unit '{$unit->unit}' — {$linked} clearance record(s) are linked to it.");
        }

        $unitCode = $unit->unit;
        $unit->delete();

        return redirect()->route('admin.master.units')
            ->with('success', "Unit '{$unitCode}' deleted successfully.");
    }

    // ==========================================
    // Statistics
    // ==========================================

    public function statistics(Request $request): View
    {
        $query = MasterStatistic::query();

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->input('search')) . '%';
            $query->where('statistics', 'LIKE', $search);
        }

        $statistics = $query->orderBy('statistics')->paginate(20)->withQueryString();

        return view('admin.master.statistics', compact('statistics'));
    }

    public function storeStatistic(Request $request): RedirectResponse
    {
        // Ignore the current record so an unchanged statistic code can be saved.
        $data = $request->validate([
            // 'statistics' => 'required|string|max:3|unique:mysql.master_statistics,statistics',
            'statistics' => ['required', 'string', 'max:3', MasterDataUniqueFields::rule('statistics')],
        ], MasterDataUniqueFields::messages());

        $statCode = trim($data['statistics']);
        MasterStatistic::create([
            'statistics' => $statCode,
        ]);

        return redirect()->route('admin.master.statistics')
            ->with('success', "Statistical code '{$statCode}' created successfully.");
    }

    public function updateStatistic(Request $request, int $id): RedirectResponse
    {
        $stat = MasterStatistic::findOrFail($id);

        // Ignore the current record so an unchanged statistic code can be saved.
        $data = $request->validate([
            // 'statistics' => 'required|string|max:3|unique:mysql.master_statistics,statistics,' . $stat->id,
            'statistics' => ['required', 'string', 'max:3', MasterDataUniqueFields::rule('statistics', $stat->id)],
        ], MasterDataUniqueFields::messages());

        $statCode = trim($data['statistics']);
        $stat->update([
            'statistics' => $statCode,
        ]);

        return redirect()->route('admin.master.statistics')
            ->with('success', "Statistical code '{$statCode}' updated successfully.");
    }

    public function destroyStatistic(int $id): RedirectResponse
    {
        $stat = MasterStatistic::findOrFail($id);
        $linked = CustomerClearanceData::where('statistic_code_id', $stat->statistics)
            ->orWhere('statistic_code_id', (string) $stat->id)
            ->count();

        if ($linked > 0) {
            return redirect()->route('admin.master.statistics')
                ->with('error', "Cannot delete statistical code '{$stat->statistics}' — {$linked} clearance record(s) are linked to it.");
        }

        $statCode = $stat->statistics;
        $stat->delete();

        return redirect()->route('admin.master.statistics')
            ->with('success', "Statistical code '{$statCode}' deleted successfully.");
    }

    // ==========================================
    // Licenses
    // ==========================================

    public function licenses(Request $request): View
    {
        $query = MasterLicense::query();

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('license_en', 'LIKE', $search)
                    ->orWhere('license_th', 'LIKE', $search);
            });
        }

        $licenses = $query->orderBy('license_en')->paginate(20)->withQueryString();

        return view('admin.master.licenses', compact('licenses'));
    }

    public function storeLicense(Request $request): RedirectResponse
    {
        // Added unique validation for license_en and license_th (both are UNIQUE in DB).
        // Previously missing unique rules caused a 500 on duplicate insert.
        // Both license_en and license_th are UNIQUE in the DB; shared rules/messages.
        $data = $request->validate([
            // 'license_en' => 'required|string|max:191|unique:mysql.master_licenses,license_en',
            // 'license_th' => 'nullable|string|max:191|unique:mysql.master_licenses,license_th',
            'license_en' => ['required', 'string', 'max:191', MasterDataUniqueFields::rule('license_en')],
            'license_th' => ['nullable', 'string', 'max:191', MasterDataUniqueFields::rule('license_th')],
        ], MasterDataUniqueFields::messages());

        MasterLicense::create([
            'license_en' => trim($data['license_en']),
            'license_th' => !empty($data['license_th']) ? trim($data['license_th']) : null,
        ]);

        return redirect()->route('admin.master.licenses')
            ->with('success', "License '{$data['license_en']}' created successfully.");
    }

    public function updateLicense(Request $request, int $id): RedirectResponse
    {
        $license = MasterLicense::findOrFail($id);

        // Added unique validation with ignore-self for license_en and license_th.
        // Excludes the current record so updating without changing values does not fail.
        $data = $request->validate([
            // 'license_en' => 'required|string|max:191',
            // 'license_th' => 'nullable|string|max:191',
            'license_en' => ['required', 'string', 'max:191', MasterDataUniqueFields::rule('license_en', $license->id)],
            'license_th' => ['nullable', 'string', 'max:191', MasterDataUniqueFields::rule('license_th', $license->id)],
        ], MasterDataUniqueFields::messages());

        $license->update([
            'license_en' => trim($data['license_en']),
            'license_th' => !empty($data['license_th']) ? trim($data['license_th']) : null,
        ]);

        return redirect()->route('admin.master.licenses')
            ->with('success', "License '{$license->license_en}' updated successfully.");
    }

    public function destroyLicense(int $id): RedirectResponse
    {
        $license = MasterLicense::findOrFail($id);
        $linked = CustomerClearanceData::where('license1_name_id', $license->id)
            ->orWhere('license2_name_id', $license->id)
            ->orWhere('license3_name_id', $license->id)
            ->orWhere('license4_name_id', $license->id)
            ->count();

        if ($linked > 0) {
            return redirect()->route('admin.master.licenses')
                ->with('error', "Cannot delete license '{$license->license_en}' — {$linked} clearance record(s) are linked to it.");
        }

        $licenseName = $license->license_en;
        $license->delete();

        return redirect()->route('admin.master.licenses')
            ->with('success', "License '{$licenseName}' deleted successfully.");
    }
}
