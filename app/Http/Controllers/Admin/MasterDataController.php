<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business\MasterBranch;
use App\Models\Business\MasterLicense;
use App\Models\Business\MasterStatistic;
use App\Models\Business\MasterUnit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterDataController extends Controller
{
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

    public function licenses(Request $request): View
    {
        $query = MasterLicense::query();

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                // $q->where('license_name', 'LIKE', $search);
                $q->where('license_en', 'LIKE', $search)
                  ->orWhere('license_th', 'LIKE', $search);
            });
        }

        // $licenses = $query->orderBy('license_name')->paginate(20)->withQueryString();
        $licenses = $query->orderBy('license_en')->paginate(20)->withQueryString();

        return view('admin.master.licenses', compact('licenses'));
    }
}

