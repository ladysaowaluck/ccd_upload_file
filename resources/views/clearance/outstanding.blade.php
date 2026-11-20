{{-- Outstanding Auditing replicated from CustomerClearanceOutStandingController with full columns, filters, and safe
missing-field presentation --}}
@extends('layouts.app')

@section('title', 'CCD Database OutStanding')
@section('header_title', 'Outstanding Customs Evidence Compliance')

@section('content')
    {{--
    @if(session('success'))
    <div x-data="{ showSuccessModal: true }" x-show="showSuccessModal" @keydown.escape.window="showSuccessModal = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-in fade-in duration-150"
        style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden transform transition-all animate-in zoom-in-95 duration-150"
            @click.outside="showSuccessModal = false">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-bold text-slate-800">File Attached Successfully</h3>
                        <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                            {{ session('success') }}
                        </p>
                        @if(session('upload_debug') && is_array(session('upload_debug')))
                        <div
                            class="mt-3 p-3 rounded-xl bg-slate-50 border border-slate-200 text-[11px] font-mono space-y-1.5 text-slate-600">
                            @foreach(session('upload_debug') as $dbg)
                            <div
                                class="flex items-center justify-between text-slate-800 font-semibold border-b border-slate-200/60 pb-1">
                                <span class="truncate max-w-[240px]">{{ $dbg['file_name'] ?? 'Uploaded File' }}</span>
                                <span class="text-emerald-700">{{ number_format(($dbg['size_bytes'] ?? 0) / 1024, 1) }}
                                    KB</span>
                            </div>
                            @if(!empty($dbg['s3_synced']))
                            <div class="text-[10px] text-emerald-700 flex items-center gap-1">
                                <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Backed up to AWS S3: <strong class="font-bold">{{ $dbg['s3_bucket'] ?? ''
                                        }}</strong></span>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-3 border-t border-slate-100 flex justify-end">
                <button type="button" @click="showSuccessModal = false"
                    class="px-5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition">
                    OK
                </button>
            </div>
        </div>
    </div>
    @endif
    --}}

    <!-- Render in-page notification banner with padding for attached file success -->
    @if(session('success'))
        <div
            class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs shadow-sm flex items-start justify-between gap-3 animate-in fade-in duration-150">
            <div class="flex items-start gap-3">
                <div
                    class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-emerald-900 text-xs">File Attached Successfully</div>
                    <div class="text-xs text-emerald-800 mt-0.5 leading-relaxed">{{ session('success') }}</div>
                    @if(session('upload_debug') && is_array(session('upload_debug')))
                        <!-- <div class="mt-2 p-2.5 rounded-lg bg-white/80 border border-emerald-200/80 text-[11px] font-mono text-emerald-900 space-y-1">
                                                            @foreach(session('upload_debug') as $dbg)
                                                                <div class="flex items-center gap-2">
                                                                    <span class="font-semibold">{{ $dbg['file_name'] ?? 'Uploaded File' }}</span>
                                                                    <span class="text-emerald-700">({{ number_format(($dbg['size_bytes'] ?? 0) / 1024, 1) }} KB)</span>
                                                                    @if(!empty($dbg['s3_synced']))
                                                                        <span class="text-[10px] text-emerald-600">• Backed up to S3: {{ $dbg['s3_bucket'] ?? '' }}</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div> -->
                    @endif
                </div>
            </div>
            <button type="button" onclick="this.parentElement.remove()"
                class="text-emerald-600 hover:text-emerald-800 p-1 rounded transition" title="Dismiss">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div
            class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-sm flex items-start justify-between gap-3 animate-in fade-in duration-150">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center text-rose-700 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-rose-900 text-xs">Attachment Error</div>
                    <div class="text-xs text-rose-800 mt-0.5 leading-relaxed">{{ session('error') }}</div>
                </div>
            </div>
            <button type="button" onclick="this.parentElement.remove()"
                class="text-rose-600 hover:text-rose-800 p-1 rounded transition" title="Dismiss">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <div class="space-y-4"
        x-data="{ filterOpen: {{ count(array_filter(request()->except(['page', 'sort_by', 'sort_dir']))) > 0 ? 'true' : 'true' }} }">

        <!-- Top Summary & Control Bar -->
        <div
            class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                    <!-- Keep header text on single line for exact string match in test_outstanding_compliance_screen_accepts_column_filters -->
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wide">Customer Clearance Audit
                        Records</span>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-100 text-brand-800 border border-brand-200">
                        {{ $records->total() }} Records Found
                    </span>
                    <span class="text-xs text-slate-400 font-mono">| Title: CCD Database OutStanding</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Displaying clearance records from <code
                        class="bg-slate-100 px-1 py-0.5 rounded font-mono text-slate-700">customer_clearance_data</code>,
                    even if certain fields or evidence documents do not exist.
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <button @click="filterOpen = !filterOpen"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition shadow-sm">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                        </path>
                    </svg>
                    <span x-text="filterOpen ? 'Hide Filters' : 'Show Filters'"></span>
                </button>

                <!-- Render Create button only if the user has write privileges. -->
                @if(auth()->user()?->canWrite())
                    <a href="{{ route('clearance.create') }}"
                        class="inline-flex items-center px-3 py-1.5 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create
                    </a>
                @endif
            </div>
        </div>

        <!-- 3-Column Filter Panel (Modeled on CustomerClearanceOutStandingController:208-390) -->
        <div x-show="filterOpen" x-collapse class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <form method="GET" action="{{ route('compliance.outstanding') }}" id="searchFilterForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">

                    <!-- Filter Column 1: Core Identifiers -->
                    <div class="space-y-3 border-r-0 md:border-r md:border-slate-200 md:pr-6">
                        <h4 class="font-bold text-slate-700 uppercase tracking-wider text-[11px] border-b pb-1">Identifiers
                            & Master</h4>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Job ref.</label>
                            <input type="text" name="job_no" value="{{ request('job_no') }}" placeholder="Filter Job Ref..."
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none font-mono">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Customer</label>
                            <input type="text" name="customer_name" value="{{ request('customer_name') }}"
                                placeholder="Filter Customer..."
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Branch</label>
                            <select name="master_branch_id"
                                class="w-full text-xs rounded border border-slate-300 px-2 py-1.5 bg-white focus:ring-1 focus:ring-brand-500 focus:outline-none">
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
                            <input type="text" name="hs_code" value="{{ request('hs_code') }}"
                                placeholder="Filter HS Code..."
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none font-mono">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Stat</label>
                            <select name="statistic_code_id"
                                class="w-full text-xs rounded border border-slate-300 px-2 py-1.5 bg-white focus:ring-1 focus:ring-brand-500 focus:outline-none">
                                <option value="">All Statistics</option>
                                @foreach($statistics ?? [] as $st)
                                    <option value="{{ $st->id }}" {{ request('statistic_code_id') == $st->id ? 'selected' : '' }}>
                                        {{ $st->statistics }} - {{ $st->description ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Added brand_name, model_name, country_of_origin, product_code_2 filter inputs -->
                    <!-- Filter Column 2: Descriptions & Free Keyword -->
                    <div class="space-y-3 border-r-0 md:border-r md:border-slate-200 md:pr-6">
                        <h4 class="font-bold text-slate-700 uppercase tracking-wider text-[11px] border-b pb-1">Product &
                            Free Keyword</h4>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Product desc En</label>
                            <input type="text" name="product_description_en" value="{{ request('product_description_en') }}"
                                placeholder="Filter English desc..."
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Product desc Th</label>
                            <input type="text" name="product_description_th" value="{{ request('product_description_th') }}"
                                placeholder="Filter Thai desc..."
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Product code 1</label>
                            <input type="text" name="product_code_1" value="{{ request('product_code_1') }}"
                                placeholder="Filter Product code..."
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Product code 2</label>
                            <input type="text" name="product_code_2" value="{{ request('product_code_2') }}"
                                placeholder="Filter Product code 2..."
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Brand Name</label>
                            <input type="text" name="brand_name" value="{{ request('brand_name') }}"
                                placeholder="Filter Brand..."
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Model Name</label>
                            <input type="text" name="model_name" value="{{ request('model_name') }}"
                                placeholder="Filter Model..."
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Country of Origin</label>
                            <input type="text" name="country_of_origin" value="{{ request('country_of_origin') }}"
                                placeholder="Filter Country..."
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Create Date (Insert)</label>
                            <input type="text" name="create_date" value="{{ request('create_date') }}"
                                placeholder="YYYY-MM-DD"
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none font-mono">
                        </div>

                        <div class="pt-1">
                            <label class="block font-semibold text-slate-600 mb-1">Keyword Search</label>
                            <input type="text" name="keyword" value="{{ request('keyword') }}"
                                placeholder="You can search free key word"
                                class="w-full text-xs rounded border border-slate-300 px-2.5 py-1.5 focus:ring-1 focus:ring-brand-500 focus:outline-none bg-amber-50/50">
                            <p class="text-[10px] text-slate-400 mt-0.5">Searches across product codes, materials,
                                functions, notes, etc.</p>
                        </div>
                    </div>

                    <!-- Filter Column 3: Evidence Files Checkboxes & Status -->
                    <div class="space-y-3">
                        <h4 class="font-bold text-slate-700 uppercase tracking-wider text-[11px] border-b pb-1">Compliance &
                            File Existence</h4>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Document &amp; Evidence Status</label>
                            <select name="status"
                                class="w-full text-xs rounded border border-slate-300 px-2 py-1.5 bg-white focus:ring-1 focus:ring-brand-500 focus:outline-none">
                                <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All Records
                                    (Default)</option>
                                <option value="missing_any" {{ request('status') === 'missing_any' ? 'selected' : '' }}>
                                    Missing Any (Reference or Evidence)</option>
                                <option value="missing_ref" {{ request('status') === 'missing_ref' ? 'selected' : '' }}>
                                    Missing Reference File (Catalog/SDS)</option>
                                <option value="outstanding" {{ request('status') === 'outstanding' || request('status') === 'missing_evidence' ? 'selected' : '' }}>Pending Evidence
                                    (Sign/Email/AR null)</option>
                                <option value="completed" {{ request('status') === 'completed' || request('status') === 'fully_documented' ? 'selected' : '' }}>With Evidence / Fully
                                    Documented</option>
                            </select>
                        </div>

                        <div class="space-y-2 pt-2">
                            <label class="block font-semibold text-slate-600">Attached File Checkboxes:</label>

                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                <label class="inline-flex items-center text-slate-700">
                                    <input type="checkbox" name="exist_reference_file_1" value="1" {{ request('exist_reference_file_1') ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-brand-600 mr-1.5">
                                    Catalog, SDS 1
                                </label>
                                <label class="inline-flex items-center text-slate-700">
                                    <input type="checkbox" name="exist_reference_file_2" value="1" {{ request('exist_reference_file_2') ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-brand-600 mr-1.5">
                                    Catalog, SDS 2
                                </label>
                                <label class="inline-flex items-center text-slate-700">
                                    <input type="checkbox" name="exist_hs_evidence_file_1" value="1" {{ request('exist_hs_evidence_file_1') ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-rose-600 mr-1.5">
                                    Signed req form
                                </label>
                                <label class="inline-flex items-center text-slate-700">
                                    <input type="checkbox" name="exist_hs_evidence_file_2" value="1" {{ request('exist_hs_evidence_file_2') ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-rose-600 mr-1.5">
                                    Email evidence
                                </label>
                                <label class="inline-flex items-center text-slate-700">
                                    <input type="checkbox" name="exist_hs_evidence_file_3" value="1" {{ request('exist_hs_evidence_file_3') ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-rose-600 mr-1.5">
                                    Advance ruling
                                </label>
                                <label class="inline-flex items-center text-slate-700">
                                    <input type="checkbox" name="exist_hs_evidence_file_4" value="1" {{ request('exist_hs_evidence_file_4') ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-brand-600 mr-1.5">
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
                <a href="{{ route('compliance.outstanding') }}"
                    class="text-rose-600 hover:text-rose-800 font-semibold underline ml-1">
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
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'job_no', 'sort_dir' => request('sort_by') === 'job_no' && request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-brand-700 flex items-center">
                                    Job ref.
                                    @if(request('sort_by', 'job_no') === 'job_no')
                                        <span
                                            class="ml-1 text-[10px]">{{ request('sort_dir', 'asc') === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </a>
                            </th>
                            <!-- 2. License -->
                            <th class="px-2 py-3 text-center whitespace-nowrap" title="License Attached">
                                License
                            </th>
                            <!-- 3. Customer -->
                            <th class="px-3 py-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'customer_name', 'sort_dir' => request('sort_by') === 'customer_name' && request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="hover:text-brand-700 flex items-center">
                                    Customer
                                    @if(request('sort_by') === 'customer_name')
                                        <span class="ml-1 text-[10px]">{{ request('sort_dir') === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </a>
                            </th>
                            <!-- 4. Branch -->
                            <th class="px-2 py-3 text-center whitespace-nowrap">Branch</th>
                            <!-- 5. Imp/ Exp -->
                            <th class="px-2 py-3 text-center whitespace-nowrap">Imp/ Exp</th>
                            <!-- 6. HS code -->
                            <th class="px-2.5 py-3 whitespace-nowrap">HS code</th>
                            <!-- 7. Duty rate(%) -->
                            <th class="px-2 py-3 text-center whitespace-nowrap">Duty rate(%)</th>
                            <!-- 8. Stat -->
                            <th class="px-2 py-3 text-center whitespace-nowrap">Stat</th>
                            <!-- 9. Qty Unit -->
                            <th class="px-2 py-3 text-center whitespace-nowrap">Qty Unit</th>
                            <!-- 10. Product code 1 -->
                            <th class="px-2.5 py-3 whitespace-nowrap">Product code 1</th>
                            <!-- 11. Product code 2 -->
                            <th class="px-2.5 py-3 whitespace-nowrap">Product code 2</th>
                            <!-- 12. En desc -->
                            <th class="px-3 py-3 whitespace-nowrap min-w-[150px]">En desc</th>
                            <!-- 13. Th desc -->
                            <th class="px-3 py-3 whitespace-nowrap min-w-[150px]">Th desc</th>
                            <!-- 14. Brand -->
                            <th class="px-2.5 py-3 text-center whitespace-nowrap">Brand</th>
                            <!-- 15. Model -->
                            <th class="px-2.5 py-3 whitespace-nowrap">Model</th>
                            <!-- 16. Materials -->
                            <th class="px-3 py-3 whitespace-nowrap min-w-[120px]">Materials</th>
                            <!-- 17. Usages -->
                            <th class="px-3 py-3 whitespace-nowrap min-w-[120px]">Usages</th>
                            <!-- 18. Functions -->
                            <th class="px-3 py-3 whitespace-nowrap min-w-[120px]">Functions</th>
                            <!-- 19. Country -->
                            <th class="px-2.5 py-3 whitespace-nowrap">Country</th>
                            <!-- 20. Note -->
                            <th class="px-3 py-3 whitespace-nowrap min-w-[100px]">Note</th>
                            <!-- 21. File 1 (Reference File 1: Sky background) -->
                            <th class="px-2 py-3 text-center whitespace-nowrap text-sky-900 border-l border-sky-200"
                                style="background-color: #e0f2fe;" title="Reference File 1 (Catalog/SDS)">File 1</th>
                            <!-- 22. File 2 (Reference File 2: Sky background) -->
                            <th class="px-2 py-3 text-center whitespace-nowrap text-sky-900 border-r border-sky-200"
                                style="background-color: #e0f2fe;" title="Reference File 2 (Catalog/SDS)">File 2</th>
                            <!-- 23. Sign (Pink background #ffdbdb as in backup controller) -->
                            <th class="px-2 py-3 text-center whitespace-nowrap text-rose-900 border-x border-rose-200"
                                style="background-color: #ffdbdb;" title="Signed requisition form (hs_evidence_file_1)">
                                Sign
                            </th>
                            <!-- 24. Email (Pink background #ffdbdb) -->
                            <th class="px-2 py-3 text-center whitespace-nowrap text-rose-900 border-r border-rose-200"
                                style="background-color: #ffdbdb;" title="Email evidence (hs_evidence_file_2)">
                                Email
                            </th>
                            <!-- 25. AR (Pink background #ffdbdb) -->
                            <th class="px-2 py-3 text-center whitespace-nowrap text-rose-900 border-r border-rose-200"
                                style="background-color: #ffdbdb;" title="Advance ruling (hs_evidence_file_3)">
                                AR
                            </th>
                            <!-- 26. Customs -->
                            <th class="px-2 py-3 text-center whitespace-nowrap"
                                title="Customs reference (hs_evidence_file_4)">Customs</th>
                            <!-- 27. Insert -->
                            <th class="px-2 py-3 text-center whitespace-nowrap">Insert</th>
                            <!-- 28. Update -->
                            <th class="px-2 py-3 text-center whitespace-nowrap">Update</th>
                            <!-- 29. Actions -->
                            <th class="px-3 py-3 text-center whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($records as $item)
                            @php
                                $hasLicense = !empty($item->license1_name_id) || !empty($item->license2_name_id) || !empty($item->license3_name_id) || !empty($item->license4_name_id);

                                // Format HS Code like CustomerClearanceOutStandingController: xxxx.xx.xx
                                $rawHs = (string) ($item->hs_code ?? '');
                                if (strlen($rawHs) >= 8) {
                                    $formattedHs = substr($rawHs, 0, 4) . '.' . substr($rawHs, 4, 2) . '.' . substr($rawHs, 6, 2);
                                } else {
                                    $formattedHs = $rawHs ?: '-';
                                }

                                // Format Dates as d-m-Y
                                $insertDate = !empty($item->create_date) ? \Carbon\Carbon::parse($item->create_date)->format('d-m-Y') : '-';
                                $updateDate = !empty($item->update_date) ? \Carbon\Carbon::parse($item->update_date)->format('d-m-Y') : '-';
                            @endphp

                            {{-- Added copied state to Alpine x-data for instant visual feedback on copy action --}}
                            <tr class="hover:bg-slate-50/80 transition" x-data="{ uploadModal: false, copied: false }">
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
                                     <!-- $item->quantityUnit->unit ?? ($item->quantity_unit_id ?: '-') 
                                    {"$item->quantityUnit" hits the quantity_unit accessor (a string), so the unit code never showed.
                                         The accessor returns the master_units code (e.g. C62) via the relation.  -->
                                    {{ $item->quantity_unit ?: '-' }}
                                </td>

                                <!-- 10. Product code 1 -->
                                <td class="px-2.5 py-2 font-mono text-slate-800 whitespace-nowrap"
                                    title="{{ $item->product_code_1 }}">
                                    {{ $item->product_code_1 ?: '-' }}
                                </td>

                                <!-- Added cells for all remaining customer_clearance_data columns -->
                                <!-- 11. Product code 2 -->
                                <td class="px-2.5 py-2 font-mono text-slate-700 whitespace-nowrap"
                                    title="{{ $item->product_code_2 }}">
                                    {{ $item->product_code_2 ?: '-' }}
                                </td>

                                <!-- 12. En desc -->
                                <td class="px-3 py-2 max-w-[200px] truncate text-slate-800"
                                    title="{{ $item->product_description_en }}">
                                    {{ $item->product_description_en ?: '-' }}
                                </td>

                                <!-- 13. Th desc -->
                                <td class="px-3 py-2 max-w-[200px] truncate text-slate-600"
                                    title="{{ $item->product_description_th }}">
                                    {{ $item->product_description_th ?: '-' }}
                                </td>

                                <!-- 14. Brand -->
                                <td class="px-2.5 py-2 text-slate-700 whitespace-nowrap" title="{{ $item->brand_name }}">
                                    {{ $item->brand_name ?: '-' }}
                                </td>

                                <!-- 15. Model -->
                                <td class="px-2.5 py-2 text-slate-700 whitespace-nowrap" title="{{ $item->model_name }}">
                                    {{ $item->model_name ?: '-' }}
                                </td>

                                <!-- 16. Materials -->
                                <td class="px-3 py-2 max-w-[160px] truncate text-slate-600" title="{{ $item->materials }}">
                                    {{ $item->materials ?: '-' }}
                                </td>

                                <!-- 17. Usages -->
                                <td class="px-3 py-2 max-w-[160px] truncate text-slate-600" title="{{ $item->usages }}">
                                    {{ $item->usages ?: '-' }}
                                </td>

                                <!-- 18. Functions -->
                                <td class="px-3 py-2 max-w-[160px] truncate text-slate-600" title="{{ $item->functions }}">
                                    {{ $item->functions ?: '-' }}
                                </td>

                                <!-- 19. Country of Origin -->
                                <td class="px-2.5 py-2 text-slate-700 whitespace-nowrap" title="{{ $item->country_of_origin }}">
                                    {{ $item->country_of_origin ?: '-' }}
                                </td>

                                <!-- 20. Note -->
                                <td class="px-3 py-2 max-w-[120px] truncate text-slate-500" title="{{ $item->note }}">
                                    {{ $item->note ?: '-' }}
                                </td>

                                <!-- 13. File 1 (Reference 1) -->
                                <td class="px-2 py-2 text-center whitespace-nowrap">
                                    @if(!empty($item->reference_file_1))
                                        <!-- Replaced download/view conditional with view-only (pre-signed S3 URL).
                                                                Download routed through the server which is blocked by S3 IAM policy in production. -->
                                        <!-- @if(auth()->user()?->canDownload()) download link @else view link @endif  -->
                                        <a href="{{ route('clearance.view', ['id' => $item->id, 'field' => 'reference_file_1']) }}"
                                            target="_blank" title="View Reference File 1: {{ basename($item->reference_file_1) }}"
                                            class="inline-flex items-center text-sky-600 hover:text-sky-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                        </a>
                                    @endif
                                </td>

                                <!-- 14. File 2 (Reference 2) -->
                                <td class="px-2 py-2 text-center whitespace-nowrap">
                                    @if(!empty($item->reference_file_2))
                                        <!-- view-only via pre-signed S3 URL  -->
                                        <!-- @if(auth()->user()?->canDownload()) download link @else view link @endif  -->
                                        <a href="{{ route('clearance.view', ['id' => $item->id, 'field' => 'reference_file_2']) }}"
                                            target="_blank" title="View Reference File 2: {{ basename($item->reference_file_2) }}"
                                            class="inline-flex items-center text-sky-600 hover:text-sky-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                        </a>
                                    @endif
                                </td>

                                <!-- 15. Sign (hs_evidence_file_1 with #ffdbdb background) -->
                                <td class="px-2 py-2 text-center whitespace-nowrap border-x border-rose-200"
                                    style="background-color: #ffdbdb;">
                                    @if(!empty($item->hs_evidence_file_1))
                                        <!-- view-only via pre-signed S3 URL  -->
                                        <!-- @if(auth()->user()?->canDownload()) download link @else view link @endif -->
                                        <a href="{{ route('clearance.view', ['id' => $item->id, 'field' => 'hs_evidence_file_1']) }}"
                                            target="_blank" title="View Signed Req Form: {{ basename($item->hs_evidence_file_1) }}"
                                            class="inline-flex items-center text-emerald-800 hover:text-emerald-950 font-bold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                        </a>
                                    @endif
                                </td>

                                <!-- 16. Email (hs_evidence_file_2 with #ffdbdb background) -->
                                <td class="px-2 py-2 text-center whitespace-nowrap border-r border-rose-200"
                                    style="background-color: #ffdbdb;">
                                    @if(!empty($item->hs_evidence_file_2))
                                        <!-- view-only via pre-signed S3 URL -->
                                        <!-- @if(auth()->user()?->canDownload()) download link @else view link @endif  -->
                                        <a href="{{ route('clearance.view', ['id' => $item->id, 'field' => 'hs_evidence_file_2']) }}"
                                            target="_blank" title="View Email Evidence: {{ basename($item->hs_evidence_file_2) }}"
                                            class="inline-flex items-center text-emerald-800 hover:text-emerald-950 font-bold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                        </a>
                                    @endif
                                </td>

                                <!-- 17. AR (hs_evidence_file_3 with #ffdbdb background) -->
                                <td class="px-2 py-2 text-center whitespace-nowrap border-r border-rose-200"
                                    style="background-color: #ffdbdb;">
                                    @if(!empty($item->hs_evidence_file_3))
                                        <!-- view-only via pre-signed S3 URL  -->
                                        <!-- @if(auth()->user()?->canDownload()) download link @else view link @endif -->
                                        <a href="{{ route('clearance.view', ['id' => $item->id, 'field' => 'hs_evidence_file_3']) }}"
                                            target="_blank" title="View Advance Ruling: {{ basename($item->hs_evidence_file_3) }}"
                                            class="inline-flex items-center text-emerald-800 hover:text-emerald-950 font-bold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                        </a>
                                    @endif
                                </td>

                                <!-- 18. Customs (hs_evidence_file_4) -->
                                <td class="px-2 py-2 text-center whitespace-nowrap">
                                    @if(!empty($item->hs_evidence_file_4))
                                        <!-- view-only via pre-signed S3 URL  -->
                                        <!-- @if(auth()->user()?->canDownload()) download link @else view link @endif  -->
                                        <a href="{{ route('clearance.view', ['id' => $item->id, 'field' => 'hs_evidence_file_4']) }}"
                                            target="_blank" title="View Customs Ref: {{ basename($item->hs_evidence_file_4) }}"
                                            class="inline-flex items-center text-sky-600 hover:text-sky-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
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

                                <!-- 21. Actions (Copy Job No / Show / Edit / Attach File) -->
                                <td class="px-3 py-2 text-center whitespace-nowrap font-medium space-x-1.5">
                                    <!-- Button to copy Job Number to clipboard with visual feedback  -->
                                    <button type="button"
                                        @click="copyJobNoToClipboard('{{ $item->job_no ?: ('CCD-' . $item->id) }}'); copied = true; setTimeout(() => copied = false, 1800);"
                                        class="text-slate-400 hover:text-brand-700 inline-flex items-center transition p-0.5 rounded hover:bg-slate-100 align-middle"
                                        :class="{ 'text-emerald-600 bg-emerald-50': copied }"
                                        :title="copied ? 'Copied {{ $item->job_no ?: ('CCD-' . $item->id) }}!' : 'Copy Job No: {{ $item->job_no ?: ('CCD-' . $item->id) }}'">
                                        <span x-show="!copied" class="inline-flex items-center">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </span>
                                        <span x-show="copied" style="display: none;"
                                            class="inline-flex items-center text-emerald-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    </button>
                                    <a href="{{ route('clearance.show', $item->id) }}"
                                        class="text-brand-600 hover:text-brand-900 inline-flex items-center translate-y-[4px]"
                                        title="Show Link">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                    </a>
                                    <!-- Only render Edit link when the user has explicit canEdit() privileges. -->
                                    @if(auth()->user()?->canEdit())
                                        <a href="{{ route('clearance.edit', $item->id) }}"
                                            class="text-amber-600 hover:text-amber-800 inline-flex items-center translate-y-[4px]"
                                            title="Edit Link">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                        </a>
                                    @endif
                                    <!-- Standard users and elevated users with write access can attach evidence files. -->
                                    @if(auth()->user()?->canWrite())
                                        <button @click="uploadModal = true"
                                            class="text-rose-600 hover:text-rose-800 font-semibold inline-flex items-center border border-rose-200 px-1.5 py-0.5 rounded text-[10px] bg-rose-50 hover:bg-rose-100"
                                            title="Attach Customs Evidence File">
                                            + Attach
                                        </button>

                                        <!-- Universal Document & Evidence Attachment Modal -->
                                        <div x-show="uploadModal"
                                            class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4"
                                            style="display: none;">
                                            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left"
                                                @click.outside="uploadModal = false">
                                                <!-- Keep exact string 'Upload Customs Evidence' so unassigned role tests continue to pass assertion checks -->
                                                <div class="font-bold text-sm text-slate-900 mb-0.5">Upload Customs Evidence &amp;
                                                    Reference</div>
                                                <div class="text-[11px] text-slate-500 mb-2">Upload technical reference
                                                    (Catalog/SDS) or regulatory HS evidence</div>
                                                <div class="text-xs text-brand-700 font-mono mb-3">Target Job: {{ $item->job_no }}
                                                    ({{ $item->customer_name }})</div>

                                                <form method="POST" action="{{ route('compliance.outstanding.attach', $item->id) }}"
                                                    enctype="multipart/form-data" class="space-y-4">
                                                    @csrf
                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target
                                                            Document / Evidence Slot</label>
                                                        <select name="evidence_slot"
                                                            class="w-full text-xs rounded-lg border border-slate-300 p-2 bg-white">
                                                            <optgroup label="Technical Reference (Catalog / Specification / SDS)">
                                                                <option value="ref1">Reference File 1 (Catalog / Specification / SDS
                                                                    1)</option>
                                                                <option value="ref2">Reference File 2 (Catalog / Specification / SDS
                                                                    2)</option>
                                                            </optgroup>
                                                            <optgroup label="Customs HS Evidence">
                                                                <option value="1">Sign - HS Evidence 1 (Signed Requisition Form)
                                                                </option>
                                                                <option value="2">Email - HS Evidence 2 (Email Evidence /
                                                                    Correspondence)</option>
                                                                <option value="3">AR - HS Evidence 3 (Advance Ruling Letter)
                                                                </option>
                                                                <option value="4">Customs - HS Evidence 4 (Customs Reference File)
                                                                </option>
                                                            </optgroup>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-700 mb-1">File (PDF,
                                                            Image, Excel, Word - max 20MB)</label>
                                                        <!-- Add accept attribute for allowed file extensions  -->
                                                        <input type="file" name="evidence_file"
                                                            accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.jpeg,.png" required
                                                            class="w-full text-xs border border-slate-300 rounded-lg p-2 bg-slate-50">
                                                    </div>
                                                    <div class="flex justify-end space-x-2 pt-2">
                                                        <button type="button" @click="uploadModal = false"
                                                            class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
                                                        <button type="submit"
                                                            class="px-4 py-1.5 text-xs bg-brand-700 hover:bg-brand-800 text-white font-semibold rounded-lg shadow-sm">Upload
                                                            Document</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <!-- Updated colspan to 29 to match new total column count -->
                                <td colspan="29" class="px-4 py-12 text-center text-slate-400">
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

    <!-- Lightweight clipboard helper with fallback for non-secure contexts  -->
    <script>
        function copyJobNoToClipboard(jobNo) {
            if (!jobNo) return;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(jobNo).catch(function () {
                    fallbackCopy(jobNo);
                });
            } else {
                fallbackCopy(jobNo);
            }
        }

        function fallbackCopy(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-999999px';
            ta.style.top = '-999999px';
            ta.setAttribute('readonly', '');
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try {
                document.execCommand('copy');
            } catch (e) {
                console.error('Fallback clipboard copy failed', e);
            }
            document.body.removeChild(ta);
        }
    </script>
@endsection