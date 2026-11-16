@extends('layouts.app')

@section('title', 'Clearance Operations Hub')
@section('header_title', 'Customs Clearance Operations Hub')

@section('content')
    <!-- Redesigned Home Page with Executive KPIs, Quick Shortcuts, Admin Announcement Board, and Clearance Data
    Repository -->
    <div class="space-y-6">

        <!-- Flash Notifications -->
        @if(session('success'))
            <div
                class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div
                class="px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z">
                        </path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- ══════════════════════════════════════════════════════════
        SECTION 1: DASHBOARD KPI SUMMARY CARDS
        ══════════════════════════════════════════════ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- KPI 1: Total Records -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Declarations</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">
                            {{ number_format($dashboardStats['total'] ?? 0) }}
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-brand-50 border border-brand-100 flex items-center justify-center text-brand-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center text-xs text-slate-500">
                    <span class="text-brand-700 font-semibold">Active Repository</span>
                    <span class="mx-1.5">•</span>
                    <span>Customs Clearance</span>
                </div>
            </div>

            <!-- KPI 2: Verified Compliance -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Compliance Verified</p>
                        <h3 class="text-2xl font-extrabold text-emerald-700 mt-1 font-mono">
                            {{ number_format($dashboardStats['verified'] ?? 0) }}
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center text-xs">
                    @php
                        $total = $dashboardStats['total'] ?? 0;
                        $verified = $dashboardStats['verified'] ?? 0;
                        $rate = $total > 0 ? round(($verified / $total) * 100) : 0;
                    @endphp
                    <span class="font-bold text-emerald-700">{{ $rate }}%</span>
                    <span class="text-slate-500 ml-1">of total declarations compliant</span>
                </div>
            </div>

            <!-- KPI 3: Missing Evidence (Link to Audit) -->
            <a href="{{ route('compliance.outstanding') }}"
                class="block bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-rose-200 transition group">
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-rose-600 transition">
                            Missing Evidence</p>
                        <h3 class="text-2xl font-extrabold text-rose-600 mt-1 font-mono">
                            {{ number_format($dashboardStats['missing'] ?? 0) }}
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 group-hover:scale-105 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center text-xs text-rose-600 font-semibold">
                    <span>Review & attach files →</span>
                </div>
            </a>

            <!-- KPI 4: Active Clients -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Corporate Clients</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">
                            {{ number_format($dashboardStats['customers'] ?? 0) }}
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center text-xs text-slate-500">
                    <span class="text-indigo-700 font-semibold">Active Master Customers</span>
                </div>
            </div>

        </div>

        <!-- ══════════════════════════════════════════════════════════════
        SECTION 2: ADMIN ANNOUNCEMENT BOARD
        ════════════════════════════════════════════════════ -->
        @php
            $typeStyles = [
                'info' => ['border' => 'border-sky-200', 'bg' => 'bg-sky-50/70', 'badge' => 'bg-sky-100 text-sky-800 border-sky-300', 'icon' => 'text-sky-600'],
                'warning' => ['border' => 'border-amber-200', 'bg' => 'bg-amber-50/70', 'badge' => 'bg-amber-100 text-amber-800 border-amber-300', 'icon' => 'text-amber-600'],
                'danger' => ['border' => 'border-rose-200', 'bg' => 'bg-rose-50/70', 'badge' => 'bg-rose-100 text-rose-800 border-rose-300', 'icon' => 'text-rose-600'],
                'success' => ['border' => 'border-emerald-200', 'bg' => 'bg-emerald-50/70', 'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300', 'icon' => 'text-emerald-600'],
            ];
            $currentStyle = $typeStyles[$announcement['type'] ?? 'info'] ?? $typeStyles['info'];
        @endphp

        <div class="rounded-xl border {{ $currentStyle['border'] }} {{ $currentStyle['bg'] }} p-5 shadow-sm transition">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/60 pb-3 mb-3">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center {{ $currentStyle['icon'] }} shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border {{ $currentStyle['badge'] }}">
                                Official Notice / ประกาศ
                            </span>
                            <h4 class="text-sm font-bold text-slate-800">{{ $announcement['title'] ?? 'Notice' }}</h4>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span>By <strong class="text-slate-700">{{ $announcement['updated_by'] ?? 'Admin' }}</strong></span>
                    <span>•</span>
                    <span>{{ $announcement['updated_at'] ?? 'Recently' }}</span>

                    @if(auth()->user() && auth()->user()->isAdmin())
                        <button type="button"
                            onclick="document.getElementById('editAnnouncementModal').classList.remove('hidden')"
                            class="ml-2 inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 hover:text-slate-900 shadow-xs transition">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            Edit Announcement
                        </button>
                    @endif
                </div>
            </div>

            <div class="text-xs text-slate-700 leading-relaxed max-w-4xl">
                {!! nl2br(e($announcement['message'] ?? '')) !!}
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════ -->
        <!-- SECTION 3: QUICK ACTION SHORTCUT BUTTONS -->
        <!-- ══════════════════════════════════════════════════════════════ -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Quick Shortcuts & Workflows</h3>
                <span class="text-xs text-slate-400">Frequently used operations</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">

                {{-- Render Register CCD and Upload Requisition only for users who have write authorization. --}}
                @if(auth()->user()?->canWrite())
                    {{-- Shortcut 1: Register CCD --}}
                    <a href="{{ route('clearance.create') }}"
                        class="flex flex-col p-4 bg-white rounded-xl border border-slate-200 hover:border-brand-500 hover:shadow-md transition group">
                        <div
                            class="w-10 h-10 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center mb-2.5 group-hover:bg-brand-700 group-hover:text-white transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div class="font-bold text-xs text-slate-800 group-hover:text-brand-700 transition">Register CCD</div>
                        <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">New clearance entry</div>
                    </a>

                    <!-- Shortcut 2: Ingest Excel -->
                    <a href="{{ route('clearance.upload') }}"
                        class="flex flex-col p-4 bg-white rounded-xl border border-slate-200 hover:border-emerald-500 hover:shadow-md transition group">
                        <div
                            class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center mb-2.5 group-hover:bg-emerald-700 group-hover:text-white transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                        </div>
                        <div class="font-bold text-xs text-slate-800 group-hover:text-emerald-700 transition">Upload Requisition
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">Excel & HS evidence</div>
                    </a>
                @endif

                <!-- Shortcut 3: Missing Evidence Audit (View permitted for all) -->
                <a href="{{ route('compliance.outstanding') }}"
                    class="flex flex-col p-4 bg-white rounded-xl border border-slate-200 hover:border-rose-500 hover:shadow-md transition group">
                    <div
                        class="w-10 h-10 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center mb-2.5 group-hover:bg-rose-700 group-hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="font-bold text-xs text-slate-800 group-hover:text-rose-700 transition">Evidence Audit</div>
                    <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">Review customs compliance</div>
                </a>

                <!--Render Weekly HS Export report shortcut only if user has download authorization. -->
                @if(auth()->user()?->canDownload())
                    <!-- Shortcut 4: Weekly HS Export -->
                    <a href="{{ route('export.weekly') }}"
                        class="flex flex-col p-4 bg-white rounded-xl border border-slate-200 hover:border-sky-500 hover:shadow-md transition group">
                        <div
                            class="w-10 h-10 rounded-lg bg-sky-50 text-sky-700 flex items-center justify-center mb-2.5 group-hover:bg-sky-700 group-hover:text-white transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="font-bold text-xs text-slate-800 group-hover:text-sky-700 transition">Weekly Export</div>
                        <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">HS report spreadsheet</div>
                    </a>
                @endif

                <!-- Shortcut 5: Master Customer Data (or All Master Data) -->
                @if(auth()->user() && auth()->user()->isAdmin())
                    <a href="{{ route('admin.master.customers.index') }}"
                        class="flex flex-col p-4 bg-white rounded-xl border border-slate-200 hover:border-indigo-500 hover:shadow-md transition group">
                        <div
                            class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center mb-2.5 group-hover:bg-indigo-700 group-hover:text-white transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7zM9 9h6m-6 4h6" />
                            </svg>
                        </div>
                        <div class="font-bold text-xs text-slate-800 group-hover:text-indigo-700 transition">Master Data</div>
                        <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">Clients, units, branches</div>
                    </a>
                @elseif(auth()->user()?->isReadOnly())
                    <div class="flex flex-col p-4 bg-amber-50/60 rounded-xl border border-amber-200">
                        <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center mb-2.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div class="font-bold text-xs text-amber-800">Read-Only Mode</div>
                        <div class="text-[11px] text-amber-600 mt-0.5 line-clamp-1">Viewing declarations permitted</div>
                    </div>
                @else
                    <div class="flex flex-col p-4 bg-slate-50/60 rounded-xl border border-slate-200 opacity-75">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center mb-2.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="font-bold text-xs text-slate-600">Standard Access</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Operator mode active</div>
                    </div>
                @endif

            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════
        SECTION 4: CLEARANCE REPOSITORY (FILTER & TABLE)
        ══════════════════════════════════════════ -->
        <!-- <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">

            <div class="p-5 border-b border-slate-200 bg-slate-50/70">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Customs Clearance Repository</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Search and inspect all customer clearance declarations.</p>
                    </div>
                    <span
                        class="text-xs font-mono font-semibold text-slate-500 bg-white px-2.5 py-1 rounded-lg border border-slate-200">
                        {{ $records->total() }} records found
                    </span>
                </div>

                <form method="GET" action="{{ route('clearance.index') }}"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Job Number</label>
                        <input type="text" name="job_no" value="{{ request('job_no') }}" placeholder="CCD2304-..."
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">HS Code</label>
                        <input type="text" name="hs_code" value="{{ request('hs_code') }}" placeholder="8-digit code..."
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Customer Name</label>
                        <input type="text" name="customer_name" value="{{ request('customer_name') }}"
                            placeholder="Search client..."
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Branch</label>
                        <select name="branch_id"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->branch_name }} ({{ $b->branch_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit"
                            class="flex-1 bg-brand-700 hover:bg-brand-800 text-white font-semibold py-2 px-4 rounded-lg text-xs transition shadow-sm">
                            Search
                        </button>
                        <a href="{{ route('clearance.index') }}"
                            class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium py-2 px-3 rounded-lg text-xs transition">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                    <thead class="bg-slate-100/75 text-slate-700 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3.5">Job Ref</th>
                            <th class="px-4 py-3.5">Customer Name</th>
                            <th class="px-4 py-3.5">HS Code</th>
                            <th class="px-4 py-3.5">Product Description (EN / TH)</th>
                            <th class="px-4 py-3.5">Duty Rate</th>
                            <th class="px-4 py-3.5 text-center">Compliance</th>
                            <th class="px-4 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($records as $item)
                            <tr class="hover:bg-slate-50/90 transition">
                                <td class="px-4 py-3 font-mono font-semibold text-brand-700 whitespace-nowrap">
                                    {{ $item->job_no }}
                                </td>
                                <td class="px-4 py-3 max-w-[200px] truncate text-slate-800 font-medium">
                                    {{ $item->customer_name }}
                                </td>
                                <td class="px-4 py-3 font-mono text-slate-900 font-semibold whitespace-nowrap">
                                    {{ $item->hs_code }}
                                </td>
                                <td class="px-4 py-3 max-w-sm">
                                    <div class="text-slate-900 font-medium truncate">{{ $item->product_description_en }}</div>
                                    <div class="text-[11px] text-slate-500 truncate mt-0.5">{{ $item->product_description_th }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                    {{ $item->tariff_ad_valorem !== null ? $item->tariff_ad_valorem . '%' : '-' }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($item->isMissingEvidence())
                                        <span
                                            class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                            Missing Evidence
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            Verified
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                                    <a href="{{ route('clearance.show', $item->id) }}"
                                        class="inline-block bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-2.5 py-1 rounded text-[11px] font-medium transition">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                    No clearance records found matching the filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($records->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50">
                    {{ $records->links() }}
                </div>
            @endif
        </div> -->

    </div>

    <!-- ══════════════════════════════════════════════════════════
    MODAL: EDIT ANNOUNCEMENT (ADMIN ONLY)
    ══════════════════════════════════════════════════════ -->
    @if(auth()->user() && auth()->user()->isAdmin())
        <div id="editAnnouncementModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-lg mx-4 p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">Update Home Announcement</h3>
                    </div>
                    <button type="button" onclick="document.getElementById('editAnnouncementModal').classList.add('hidden')"
                        class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
                </div>

                <form method="POST" action="{{ route('clearance.announcement.update') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Announcement Title <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $announcement['title'] ?? '') }}" required
                            maxlength="150" placeholder="e.g. Customs Tariff Policy Update - November 2026"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Notice Message Text <span
                                class="text-rose-500">*</span></label>
                        <textarea name="message" rows="4" required
                            placeholder="Type important instructions, system notices, or customs regulation reminders for users..."
                            class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('message', $announcement['message'] ?? '') }}</textarea>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Line breaks are preserved automatically.</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Notice Style / Priority</label>
                            <select name="type"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                <option value="info" {{ ($announcement['type'] ?? 'info') === 'info' ? 'selected' : '' }}>Standard
                                    Information (Blue)</option>
                                <option value="warning" {{ ($announcement['type'] ?? '') === 'warning' ? 'selected' : '' }}>
                                    Important Notice (Amber)</option>
                                <option value="danger" {{ ($announcement['type'] ?? '') === 'danger' ? 'selected' : '' }}>Urgent
                                    Alert (Rose)</option>
                                <option value="success" {{ ($announcement['type'] ?? '') === 'success' ? 'selected' : '' }}>System
                                    Update / Operational (Green)</option>
                            </select>
                        </div>

                        <div class="flex items-center pt-5">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" {{ !empty($announcement['is_active']) ? 'checked' : '' }} class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span class="text-xs font-semibold text-slate-700">Display notice on Home Dashboard</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-200">
                        <button type="button" onclick="document.getElementById('editAnnouncementModal').classList.add('hidden')"
                            class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Cancel</button>
                        <button type="submit"
                            class="px-5 py-2 text-xs font-semibold text-white bg-brand-700 hover:bg-brand-800 rounded-lg shadow-sm transition">Publish
                            Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endsection