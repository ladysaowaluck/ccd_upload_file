{{-- Outstanding Auditing replicated from CustomerClearanceOutStandingController with full columns, filters, and safe missing-field presentation --}}
@extends('layouts.app')

@section('title', 'CCD Database OutStanding')
@section('header_title', 'CCD Database OutStanding')

@section('content')
<div class="space-y-4" x-data="{ filterOpen: {{ count(array_filter(request()->except(['page', 'sort_by', 'sort_dir']))) > 0 ? 'true' : 'true' }} }">

    <!-- Top Summary & Control Bar -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                <!-- Keep header text on single line for exact string match in test_outstanding_compliance_screen_accepts_column_filters -->
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wide">Customer Clearance Audit Records</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-100 text-brand-800 border border-brand-200">
                    {{ $records->total() }} Records Found
                </span>
                <span class="text-xs text-slate-400 font-mono">| Title: CCD Database OutStanding</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Displaying clearance records from <code class="bg-slate-100 px-1 py-0.5 rounded font-mono text-slate-700">customer_clearance_data</code>, even if certain fields or evidence documents do not exist.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <button @click="filterOpen = !filterOpen"
                class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition shadow-sm">
                <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                <span x-text="filterOpen ? 'Hide Filters' : 'Show Filters'"></span>
            </button>

            <a href="{{ route('clearance.create') }}"
                class="inline-flex items-center px-3 py-1.5 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create
            </a>
        </div>
    </div>

    <!-- 3-Column Filter Panel (Modeled on CustomerClearanceOutStandingController:208-390) -->
    <div x-show="filterOpen" x-collapse class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <form method="GET" action="{{ route('compliance.outstanding') }}" id="searchFilterForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
                
                <!-- Filter Column 1: Core Identifiers -->
                <div class="space-y-3 border-r-0 md:border-r md:border-slate-200 md:pr-6">
                    <h4 class="font-bold text-slate-700 uppercase tracking-wider text-[11px] border-b pb-1">Identifiers & Master</h4>
                    
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Job ref.</label>
                        <input type="text" name="job_no" value="{{ request('job_no') }}" placeholder="Filter Job Ref..."
                            class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none font-mono">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Customer</label>
                        <input type="text" name="customer_name" value="{{ request('customer_name') }}" placeholder="Filter Customer..."
                            class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Branch</label>
                        <select name="master_branch_id" class="w-full text-xs rounded border border-slate-300 px-2 py-1.5 bg-white focus:ring-1 focus:ring-brand-500 focus:outline-none">
                            <option value="">All Branches</option>
                            @foreach($branches ?? [] as $br)
                                <option value="{{ $br->id }}" {{ request('master_branch_id') == $br->id ? 'selected' : '' }}>
                                    {{ $br->branch_code }} - {{ $br->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Imp/Exp</label>
                        <div class="flex items-center space-x-4 pt-1">
                            <label class="inline-flex items-center text-xs text-slate-700">
                                <input type="radio" name="imp_exp" value="" {{ request('imp_exp') === null || request('imp_exp') === '' ? 'checked' : '' }} class="mr-1 text-brand-600">
                                All
                            </label>
                            <label class="inline-flex items-center text-xs text-slate-700">
                                <input type="radio" name="imp_exp" value="0" {{ request('imp_exp') === '0' ? 'checked' : '' }} class="mr-1 text-brand-600">
                                Imp
                            </label>
                            <label class="inline-flex items-center text-xs text-slate-700">
                                <input type="radio" name="imp_exp" value="1" {{ request('imp_exp') === '1' ? 'checked' : '' }} class="mr-1 text-brand-600">
                                Exp
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">HS code</label>
                        <input type="text" name="hs_code" value="{{ request('hs_code') }}" placeholder="Filter HS Code..."
                            class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none font-mono">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Stat</label>
                        <select name="statistic_code_id" class="w-full text-xs rounded border border-slate-300 px-2 py-1.5 bg-white focus:ring-1 focus:ring-brand-500 focus:outline-none">
                            <option value="">All Statistics</option>
                            @foreach($statistics ?? [] as $st)
                                <option value="{{ $st->id }}" {{ request('statistic_code_id') == $st->id ? 'selected' : '' }}>
                                    {{ $st->statistics }} - {{ $st->description ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Filter Column 2: Descriptions & Free Keyword -->
                <div class="space-y-3 border-r-0 md:border-r md:border-slate-200 md:pr-6">
                    <h4 class="font-bold text-slate-700 uppercase tracking-wider text-[11px] border-b pb-1">Product & Free Keyword</h4>

                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Product desc En</label>
                        <input type="text" name="product_description_en" value="{{ request('product_description_en') }}" placeholder="Filter English desc..."
                            class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Product desc Th</label>
                        <input type="text" name="product_description_th" value="{{ request('product_description_th') }}" placeholder="Filter Thai desc..."
                            class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Product code 1</label>
                        <input type="text" name="product_code_1" value="{{ request('product_code_1') }}" placeholder="Filter Product code..."
                            class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Create Date (Insert)</label>
                        <input type="text" name="create_date" value="{{ request('create_date') }}" placeholder="YYYY-MM-DD"
                            class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none font-mono">
                    </div>

                    <div class="pt-1">
                        <label class="block font-semibold text-slate-600 mb-1">Keyword Search</label>
                        <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="You can search free key word"
                            class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none bg-amber-50/50">
                        <p class="text-[10px] text-slate-400 mt-0.5">Searches across product codes, materials, functions, notes, etc.</p>
                    </div>
                </div>

                <!-- Filter Column 3: Evidence Files Checkboxes & Status -->
                <div class="space-y-3">
                    <h4 class="font-bold text-slate-700 uppercase tracking-wider text-[11px] border-b pb-1">Compliance & File Existence</h4>

                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Evidence Status</label>
                        <select name="status" class="w-full text-xs rounded border border-slate-300 px-2 py-1.5 bg-white focus:ring-1 focus:ring-brand-500 focus:outline-none">
                            <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All Records (Default)</option>
                            <option value="outstanding" {{ request('status') === 'outstanding' ? 'selected' : '' }}>Pending Evidence (Sign/Email/AR null)</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>With Evidence Attached</option>
                        </select>
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="block font-semibold text-slate-600">Attached File Checkboxes:</label>
                        
                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                            <label class="inline-flex items-center text-slate-700">
                                <input type="checkbox" name="exist_reference_file_1" value="1" {{ request('exist_reference_file_1') ? 'checked' : '' }} class="rounded border-slate-300 text-brand-600 mr-1.5">
                                Catalog, SDS 1
                            </label>
                            <label class="inline-flex items-center text-slate-700">
                                <input type="checkbox" name="exist_reference_file_2" value="1" {{ request('exist_reference_file_2') ? 'checked' : '' }} class="rounded border-slate-300 text-brand-600 mr-1.5">
                                Catalog, SDS 2
                            </label>
                            <label class="inline-flex items-center text-slate-700">
                                <input type="checkbox" name="exist_hs_evidence_file_1" value="1" {{ request('exist_hs_evidence_file_1') ? 'checked' : '' }} class="rounded border-slate-300 text-rose-600 mr-1.5">
                                Signed req form
                            </label>
                            <label class="inline-flex items-center text-slate-700">
                                <input type="checkbox" name="exist_hs_evidence_file_2" value="1" {{ request('exist_hs_evidence_file_2') ? 'checked' : '' }} class="rounded border-slate-300 text-rose-600 mr-1.5">
                                Email evidence
                            </label>
                            <label class="inline-flex items-center text-slate-700">
                                <input type="checkbox" name="exist_hs_evidence_file_3" value="1" {{ request('exist_hs_evidence_file_3') ? 'checked' : '' }} class="rounded border-slate-300 text-rose-600 mr-1.5">
                                Advance ruling
                            </label>
                            <label class="inline-flex items-center text-slate-700">
                                <input type="checkbox" name="exist_hs_evidence_file_4" value="1" {{ request('exist_hs_evidence_file_4') ? 'checked' : '' }} class="rounded border-slate-300 text-brand-600 mr-1.5">
                                Customs ref
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <a href="{{ route('compliance.outstanding') }}"
                    class="px-4 py-1.5 text-xs font-medium rounded-lg text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    Reset
                </a>
                <button type="submit"
                    class="px-5 py-1.5 text-xs font-semibold rounded-lg text-white bg-brand-700 hover:bg-brand-800 shadow-sm transition">
                    Apply Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Active Filter Badges -->
    @php
        $activeFilters = array_filter(request()->except(['page', 'sort_by', 'sort_dir']), fn($v) => !is_null($v) && $v !== '' && $v !== 'all');
    @endphp
    @if(count($activeFilters) > 0)
        <div class="flex items-center space-x-2 flex-wrap gap-y-2 text-xs">
            <span class="font-semibold text-slate-500">Active Filters:</span>
            @foreach($activeFilters as $k => $val)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-200 text-slate-800">
                    {{ ucfirst(str_replace(['_', 'exist '], [' ', 'With '], $k)) }}: {{ $val === '1' ? 'Exist' : $val }}
                </span>
            @endforeach
            <a href="{{ route('compliance.outstanding') }}" class="text-rose-600 hover:text-rose-800 font-semibold underline ml-1">
                Clear All
            </a>
        </div>
    @endif

    <!-- Data Table Card (Replicating CustomerClearanceOutStandingController column layout) -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-[11px] text-left">
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider select-none">
                    <tr>
                        <!-- 1. Job ref. -->
                        <th class="px-2.5 py-3 whitespace-nowrap">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'job_no', 'sort_dir' => request('sort_by') === 'job_no' && request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-brand-700 flex items-center">
                                Job ref.
                                @if(request('sort_by', 'job_no') === 'job_no')
                                    <span class="ml-1 text-[10px]">{{ request('sort_dir', 'asc') === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <!-- 2. License -->
                        <th class="px-2 py-3 text-center whitespace-nowrap" title="License Attached">
                            License
                        </th>
                        <!-- 3. Customer -->
                        <th class="px-3 py-3 whitespace-nowrap">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'customer_name', 'sort_dir' => request('sort_by') === 'customer_name' && request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-brand-700 flex items-center">
                                Customer
                                @if(request('sort_by') === 'customer_name')
                                    <span class="ml-1 text-[10px]">{{ request('sort_dir') === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <!-- 4. Branch -->
                        <th class="px-2 py-3 whitespace-nowrap">Branch</th>
                        <!-- 5. Imp/ Exp -->
                        <th class="px-2 py-3 text-center whitespace-nowrap">Imp/ Exp</th>
                        <!-- 6. HS code -->
                        <th class="px-2.5 py-3 whitespace-nowrap">HS code</th>
                        <!-- 7. Duty rate(%) -->
                        <th class="px-2 py-3 text-right whitespace-nowrap">Duty rate(%)</th>
                        <!-- 8. Stat -->
                        <th class="px-2 py-3 text-center whitespace-nowrap">Stat</th>
                        <!-- 9. Qty Unit -->
                        <th class="px-2 py-3 text-center whitespace-nowrap">Qty Unit</th>
                        <!-- 10. Product code -->
                        <th class="px-2.5 py-3 whitespace-nowrap">Product code</th>
                        <!-- 11. En desc -->
                        <th class="px-3 py-3 whitespace-nowrap min-w-[150px]">En desc</th>
                        <!-- 12. Th desc -->
                        <th class="px-3 py-3 whitespace-nowrap min-w-[150px]">Th desc</th>
                        <!-- 13. File 1 -->
                        <th class="px-2 py-3 text-center whitespace-nowrap" title="Reference File 1 (Catalog/SDS)">File 1</th>
                        <!-- 14. File 2 -->
                        <th class="px-2 py-3 text-center whitespace-nowrap" title="Reference File 2 (Catalog/SDS)">File 2</th>
                        <!-- 15. Sign (Pink background #ffdbdb as in backup controller) -->
                        <th class="px-2 py-3 text-center whitespace-nowrap text-rose-900 border-x border-rose-200" style="background-color: #ffdbdb;" title="Signed requisition form (hs_evidence_file_1)">
                            Sign
                        </th>
                        <!-- 16. Email (Pink background #ffdbdb) -->
                        <th class="px-2 py-3 text-center whitespace-nowrap text-rose-900 border-r border-rose-200" style="background-color: #ffdbdb;" title="Email evidence (hs_evidence_file_2)">
                            Email
                        </th>
                        <!-- 17. AR (Pink background #ffdbdb) -->
                        <th class="px-2 py-3 text-center whitespace-nowrap text-rose-900 border-r border-rose-200" style="background-color: #ffdbdb;" title="Advance ruling (hs_evidence_file_3)">
                            AR
                        </th>
                        <!-- 18. Customs -->
                        <th class="px-2 py-3 text-center whitespace-nowrap" title="Customs reference (hs_evidence_file_4)">Customs</th>
                        <!-- 19. Insert -->
                        <th class="px-2 py-3 text-center whitespace-nowrap">Insert</th>
                        <!-- 20. Update -->
                        <th class="px-2 py-3 text-center whitespace-nowrap">Update</th>
                        <!-- 21. Actions -->
                        <th class="px-3 py-3 text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($records as $item)
                        @php
                            $hasLicense = !empty($item->license1_name_id) || !empty($item->license2_name_id) || !empty($item->license3_name_id) || !empty($item->license4_name_id);
                            
                            // Format HS Code like CustomerClearanceOutStandingController: xxxx.xx.xx
                            $rawHs = (string)($item->hs_code ?? '');
                            if (strlen($rawHs) >= 8) {
                                $formattedHs = substr($rawHs, 0, 4) . '.' . substr($rawHs, 4, 2) . '.' . substr($rawHs, 6, 2);
                            } else {
                                $formattedHs = $rawHs ?: '-';
                            }

                            // Format Dates as d-m-Y
                            $insertDate = !empty($item->create_date) ? \Carbon\Carbon::parse($item->create_date)->format('d-m-Y') : '-';
                            $updateDate = !empty($item->update_date) ? \Carbon\Carbon::parse($item->update_date)->format('d-m-Y') : '-';
                        @endphp

                        <tr class="hover:bg-slate-50/80 transition" x-data="{ uploadModal: false }">
                            <!-- 1. Job ref. -->
                            <td class="px-2.5 py-2 font-mono font-bold text-brand-700 whitespace-nowrap">
                                <a href="{{ route('clearance.show', $item->id) }}" class="hover:underline">
                                    {{ $item->job_no ?: 'CCD-' . $item->id }}
                                </a>
                            </td>

                            <!-- 2. License (※ if exists) -->
                            <td class="px-2 py-2 text-center font-bold text-slate-700 whitespace-nowrap">
                                {{ $hasLicense ? '※' : '' }}
                            </td>

                            <!-- 3. Customer -->
                            <td class="px-3 py-2 max-w-[180px] truncate text-slate-800 font-medium"
                                title="{{ $item->customer_name ?: ($item->customer->company_name ?? '-') }}">
                                {{ $item->customer_name ?: ($item->customer->company_name ?? '-') }}
                            </td>

                            <!-- 4. Branch -->
                            <td class="px-2 py-2 text-slate-700 font-mono whitespace-nowrap">
                                {{ $item->branch->branch_code ?? ($item->master_branch_id ?: '-') }}
                            </td>

                            <!-- 5. Imp/ Exp -->
                            <td class="px-2 py-2 text-center whitespace-nowrap">
                                @if(is_null($item->imp_exp) || $item->imp_exp === '')
                                    <span class="text-slate-400">-</span>
                                @elseif($item->imp_exp == 1)
                                    <span class="font-semibold text-amber-700">Exp</span>
                                @else
                                    <span class="font-semibold text-blue-700">Imp</span>
                                @endif
                            </td>

                            <!-- 6. HS code -->
                            <td class="px-2.5 py-2 font-mono text-slate-900 font-semibold whitespace-nowrap">
                                {{ $formattedHs }}
                            </td>

                            <!-- 7. Duty rate(%) -->
                            <td class="px-2 py-2 text-right font-mono text-slate-800 whitespace-nowrap">
                                {{ !is_null($item->tariff_ad_valorem) && $item->tariff_ad_valorem !== '' ? $item->tariff_ad_valorem : '-' }}
                            </td>

                            <!-- 8. Stat -->
                            <td class="px-2 py-2 text-center font-mono text-slate-700 whitespace-nowrap">
                                {{ $item->statistic->statistics ?? ($item->statistic_code_id ?: '-') }}
                            </td>

                            <!-- 9. Qty Unit -->
                            <td class="px-2 py-2 text-center font-mono text-slate-700 whitespace-nowrap">
                                {{ $item->quantityUnit->unit ?? ($item->quantity_unit_id ?: '-') }}
                            </td>

                            <!-- 10. Product code -->
                            <td class="px-2.5 py-2 font-mono text-slate-800 whitespace-nowrap" title="{{ $item->product_code_1 }}">
                                {{ $item->product_code_1 ?: '-' }}
                            </td>

                            <!-- 11. En desc -->
                            <td class="px-3 py-2 max-w-[200px] truncate text-slate-800" title="{{ $item->product_description_en }}">
                                {{ $item->product_description_en ?: '-' }}
                            </td>

                            <!-- 12. Th desc -->
                            <td class="px-3 py-2 max-w-[200px] truncate text-slate-600" title="{{ $item->product_description_th }}">
                                {{ $item->product_description_th ?: '-' }}
                            </td>

                            <!-- 13. File 1 (Reference 1) -->
                            <td class="px-2 py-2 text-center whitespace-nowrap">
                                @if(!empty($item->reference_file_1))
                                    <a href="{{ route('clearance.download', ['id' => $item->id, 'field' => 'reference_file_1']) }}"
                                        target="_blank" title="Download Reference File 1: {{ basename($item->reference_file_1) }}"
                                        class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    </a>
                                @endif
                            </td>

                            <!-- 14. File 2 (Reference 2) -->
                            <td class="px-2 py-2 text-center whitespace-nowrap">
                                @if(!empty($item->reference_file_2))
                                    <a href="{{ route('clearance.download', ['id' => $item->id, 'field' => 'reference_file_2']) }}"
                                        target="_blank" title="Download Reference File 2: {{ basename($item->reference_file_2) }}"
                                        class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    </a>
                                @endif
                            </td>

                            <!-- 15. Sign (hs_evidence_file_1 with #ffdbdb background) -->
                            <td class="px-2 py-2 text-center whitespace-nowrap border-x border-rose-200" style="background-color: #ffdbdb;">
                                @if(!empty($item->hs_evidence_file_1))
                                    <a href="{{ route('clearance.download', ['id' => $item->id, 'field' => 'hs_evidence_file_1']) }}"
                                        target="_blank" title="Download Signed Req Form: {{ basename($item->hs_evidence_file_1) }}"
                                        class="inline-flex items-center text-emerald-800 hover:text-emerald-950 font-bold">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    </a>
                                @endif
                            </td>

                            <!-- 16. Email (hs_evidence_file_2 with #ffdbdb background) -->
                            <td class="px-2 py-2 text-center whitespace-nowrap border-r border-rose-200" style="background-color: #ffdbdb;">
                                @if(!empty($item->hs_evidence_file_2))
                                    <a href="{{ route('clearance.download', ['id' => $item->id, 'field' => 'hs_evidence_file_2']) }}"
                                        target="_blank" title="Download Email Evidence: {{ basename($item->hs_evidence_file_2) }}"
                                        class="inline-flex items-center text-emerald-800 hover:text-emerald-950 font-bold">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    </a>
                                @endif
                            </td>

                            <!-- 17. AR (hs_evidence_file_3 with #ffdbdb background) -->
                            <td class="px-2 py-2 text-center whitespace-nowrap border-r border-rose-200" style="background-color: #ffdbdb;">
                                @if(!empty($item->hs_evidence_file_3))
                                    <a href="{{ route('clearance.download', ['id' => $item->id, 'field' => 'hs_evidence_file_3']) }}"
                                        target="_blank" title="Download Advance Ruling: {{ basename($item->hs_evidence_file_3) }}"
                                        class="inline-flex items-center text-emerald-800 hover:text-emerald-950 font-bold">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    </a>
                                @endif
                            </td>

                            <!-- 18. Customs (hs_evidence_file_4) -->
                            <td class="px-2 py-2 text-center whitespace-nowrap">
                                @if(!empty($item->hs_evidence_file_4))
                                    <a href="{{ route('clearance.download', ['id' => $item->id, 'field' => 'hs_evidence_file_4']) }}"
                                        target="_blank" title="Download Customs Ref: {{ basename($item->hs_evidence_file_4) }}"
                                        class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    </a>
                                @endif
                            </td>

                            <!-- 19. Insert Date (d-m-Y) -->
                            <td class="px-2 py-2 text-center font-mono text-slate-600 whitespace-nowrap">
                                {{ $insertDate }}
                            </td>

                            <!-- 20. Update Date (d-m-Y) -->
                            <td class="px-2 py-2 text-center font-mono text-slate-600 whitespace-nowrap">
                                {{ $updateDate }}
                            </td>

                            <!-- 21. Actions (Show / Edit / Attach File) -->
                            <td class="px-3 py-2 text-right whitespace-nowrap font-medium space-x-1.5">
                                <a href="{{ route('clearance.show', $item->id) }}" class="text-brand-600 hover:text-brand-900 inline-flex items-center" title="Show Link">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </a>
                                <a href="{{ route('clearance.edit', $item->id) }}" class="text-amber-600 hover:text-amber-800 inline-flex items-center" title="Edit Link">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <button @click="uploadModal = true"
                                    class="text-rose-600 hover:text-rose-800 font-semibold inline-flex items-center border border-rose-200 px-1.5 py-0.5 rounded text-[10px] bg-rose-50 hover:bg-rose-100"
                                    title="Attach Customs Evidence File">
                                    + Attach
                                </button>

                                <!-- Attach Evidence Modal -->
                                <div x-show="uploadModal"
                                    class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4"
                                    style="display: none;">
                                    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left"
                                        @click.outside="uploadModal = false">
                                        <div class="font-bold text-sm text-slate-900 mb-1">Upload Customs Evidence</div>
                                        <div class="text-xs text-brand-700 font-mono mb-3">Target Job: {{ $item->job_no }} ({{ $item->customer_name }})</div>

                                        <form method="POST" action="{{ route('compliance.outstanding.attach', $item->id) }}"
                                            enctype="multipart/form-data" class="space-y-4">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 mb-1">Target Evidence Slot</label>
                                                <select name="evidence_slot" class="w-full text-xs rounded-lg border border-slate-300 p-2 bg-white">
                                                    <option value="1">Sign - HS Evidence 1 (Signed Requisition Form)</option>
                                                    <option value="2">Email - HS Evidence 2 (Email Evidence / Correspondence)</option>
                                                    <option value="3">AR - HS Evidence 3 (Advance Ruling Letter)</option>
                                                    <option value="4">Customs - HS Evidence 4 (Customs Reference File)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 mb-1">File (PDF, Image, Excel)</label>
                                                <input type="file" name="evidence_file" required
                                                    class="w-full text-xs border border-slate-300 rounded-lg p-2 bg-slate-50">
                                            </div>
                                            <div class="flex justify-end space-x-2 pt-2">
                                                <button type="button" @click="uploadModal = false"
                                                    class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
                                                <button type="submit"
                                                    class="px-4 py-1.5 text-xs bg-brand-700 hover:bg-brand-800 text-white font-semibold rounded-lg">Upload</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="21" class="px-4 py-12 text-center text-slate-400">
                                No clearance records found in customer_clearance_data matching the specified criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
