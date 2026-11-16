@extends('layouts.app')

@section('title', 'Master Customs Licenses')
@section('header_title', 'Master Customs Licenses')

@section('content')
<!-- Master Customs Licenses with unified navigation tabs, filter, and full CRUD modals -->
<div class="space-y-6">
    <!-- Header & Subnav -->
    <div>
        <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
            <a href="{{ route('clearance.index') }}" class="hover:text-brand-600">Home</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 font-medium">Administration</span>
            <span class="mx-2">/</span>
            <span class="text-slate-800 font-medium">Master Data</span>
            <span class="mx-2">/</span>
            <span class="text-slate-800 font-medium">Customs Licenses</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Master Customs Licenses</h1>
        <p class="text-sm text-slate-500 mt-1">Registered import/export customs license codes and descriptions.</p>
    </div>

    <!-- Master Data Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6 text-sm font-medium">
            <a href="{{ route('admin.master.customers.index') }}" class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Customers
            </a>
            <a href="{{ route('admin.master.branches') }}" class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Branches
            </a>
            <a href="{{ route('admin.master.units') }}" class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Units
            </a>
            <a href="{{ route('admin.master.statistics') }}" class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Statistical Codes
            </a>
            <a href="{{ route('admin.master.licenses') }}" class="py-2.5 px-1 border-b-2 border-brand-600 text-brand-600 font-semibold">
                Customs Licenses
            </a>
        </nav>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="px-4 py-3 rounded-lg bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="px-4 py-3 rounded-lg bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <form method="GET" action="{{ route('admin.master.licenses') }}" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by English or Thai license description..."
                    class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="bg-brand-700 hover:bg-brand-800 text-white font-semibold py-2 px-4 rounded-lg text-xs transition">
                    Search
                </button>
                <a href="{{ route('admin.master.licenses') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 py-2 px-3 rounded-lg text-xs transition">
                    Reset
                </a>
                <button type="button" onclick="document.getElementById('createLicenseModal').classList.remove('hidden')"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg text-xs transition whitespace-nowrap">
                    + New License
                </button>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">ID</th>
                        <th class="px-6 py-3.5">License (EN)</th>
                        <th class="px-6 py-3.5">License (TH)</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($licenses as $license)
                    <tr class="hover:bg-slate-50/90 transition">
                        <td class="px-6 py-3.5 font-mono text-slate-500">#{{ $license->id }}</td>
                        <td class="px-6 py-3.5 text-slate-900 font-semibold">{{ $license->license_en }}</td>
                        <td class="px-6 py-3.5 text-slate-600">{{ $license->license_th ?: '—' }}</td>
                        <td class="px-6 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button"
                                    onclick="document.getElementById('editLicenseModal{{ $license->id }}').classList.remove('hidden')"
                                    class="p-1.5 text-slate-500 hover:text-brand-700 hover:bg-slate-100 rounded-lg transition inline-flex items-center justify-center"
                                    title="Edit License">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('admin.master.licenses.destroy', $license->id) }}" class="inline-block"
                                    onsubmit="return confirm('Delete license {{ addslashes($license->license_en) }}? This will fail if clearance records are linked.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition inline-flex items-center justify-center"
                                        title="Delete License">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                            No licenses found matching your search.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($licenses->hasPages())
        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $licenses->links() }}
        </div>
        @endif
    </div>
</div>

 <!-- Create License Modal -->
<div id="createLicenseModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md mx-4 p-6">
        <h3 class="text-sm font-bold text-slate-800 mb-4">New Customs License</h3>
        <form method="POST" action="{{ route('admin.master.licenses.store') }}" class="space-y-4">
            @csrf

            <div class="flex items-center gap-4">
                <label class="w-28 shrink-0 text-xs font-semibold text-slate-700">License (EN) <span class="text-rose-500">*</span></label>
                <div class="flex-1">
                    <input type="text" name="license_en" required maxlength="191" placeholder="e.g. Hazardous Substance License"
                        class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="w-28 shrink-0 text-xs font-semibold text-slate-700">License (TH)</label>
                <div class="flex-1">
                    <input type="text" name="license_th" maxlength="191" placeholder="e.g. ใบอนุญาตนำเข้าวัตถุอันตราย"
                        class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-slate-200">
                <button type="button" onclick="document.getElementById('createLicenseModal').classList.add('hidden')"
                    class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Cancel</button>
                <button type="submit"
                    class="px-5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit License Modals -->
@foreach ($licenses as $license)
<div id="editLicenseModal{{ $license->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md mx-4 p-6">
        <h3 class="text-sm font-bold text-slate-800 mb-4">Edit License #{{ $license->id }}</h3>
        <form method="POST" action="{{ route('admin.master.licenses.update', $license->id) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-4">
                <label class="w-28 shrink-0 text-xs font-semibold text-slate-700">License (EN) <span class="text-rose-500">*</span></label>
                <div class="flex-1">
                    <input type="text" name="license_en" value="{{ old('license_en', $license->license_en) }}" required maxlength="191"
                        class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="w-28 shrink-0 text-xs font-semibold text-slate-700">License (TH)</label>
                <div class="flex-1">
                    <input type="text" name="license_th" value="{{ old('license_th', $license->license_th) }}" maxlength="191"
                        class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-slate-200">
                <button type="button" onclick="document.getElementById('editLicenseModal{{ $license->id }}').classList.add('hidden')"
                    class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Cancel</button>
                <button type="submit"
                    class="px-5 py-2 text-xs font-semibold text-white bg-brand-700 hover:bg-brand-800 rounded-lg transition">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endforeach

@endsection
