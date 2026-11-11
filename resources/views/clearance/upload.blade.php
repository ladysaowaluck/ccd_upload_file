@extends('layouts.app')

@section('title', 'Excel Requisition Ingestion')
@section('header_title', 'Excel Requisition Ingestion')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6" x-data="{ tab: 'single' }">

        <!-- <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-start space-x-3.5">
                            <div class="p-2.5 rounded-lg {{ ($awsConfigured ?? false) ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-amber-50 text-amber-600 border border-amber-200' }}">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-bold text-slate-800">Storage Backend:</span>
                                    @if($awsConfigured ?? false)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            <span class="w-1.5 h-1.5 mr-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                            AWS S3 Connected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            Local Disk Only (AWS S3 Not Configured)
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    @if($awsConfigured ?? false)
                                        Primary: <code class="bg-slate-100 px-1 py-0.5 rounded text-slate-700">storage/app/public/</code> &bull; Cloud Mirror: <code class="bg-slate-100 px-1 py-0.5 rounded text-brand-700 font-semibold">{{ $awsBucket ?? 'sn-ccd-database-system-th' }}</code> ({{ $awsRegion ?? 'ap-southeast-7' }})
                                    @else
                                        Files will be stored locally in <code class="bg-slate-100 px-1 py-0.5 rounded text-slate-700">storage/app/public/</code>.
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($awsConfigured ?? false)
                        <div class="flex-shrink-0">
                            <form method="POST" action="{{ route('clearance.upload.test-aws') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3.5 py-1.5 text-xs font-medium rounded-lg text-slate-700 bg-slate-100 hover:bg-brand-50 hover:text-brand-700 border border-slate-300 hover:border-brand-300 transition">
                                    <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    Test AWS S3 Connection
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div> -->

        <!-- Tab Selector -->
        <div class="flex border-b border-slate-200 bg-white rounded-t-xl px-6 pt-3 shadow-sm">
            <button type="button" @click="tab = 'single'"
                :class="tab === 'single' ? 'border-brand-600 text-brand-700 border-b-2 font-semibold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                class="py-3 px-5 text-sm transition focus:outline-none flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <span>Single Requisition Ingestion (Standard Form)</span>
            </button>
            <button type="button" @click="tab = 'multi'"
                :class="tab === 'multi' ? 'border-brand-600 text-brand-700 border-b-2 font-semibold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                class="py-3 px-5 text-sm transition focus:outline-none flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
                <span>Batch Multi-Record Ingestion (Tabular)</span>
            </button>
        </div>

        <!-- ================= SINGLE IMPORT TAB ================= -->
        <div x-show="tab === 'single'"
            class="bg-white rounded-b-xl shadow-sm border border-t-0 border-slate-200 p-6 md:p-8 space-y-6">
            <div class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Upload Single Requisition Sheet</h3>
                    <p class="text-xs text-slate-500 mt-1">Parses standard pre-formatted customer clearance Excel form
                        (mapping cells N1, D4, O2, G10, G12, G8, F25, G14, G16, G18, F24, G37, etc.).</p>
                </div>
                <a href="{{ route('clearance.create') }}"
                    class="inline-flex items-center text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-lg px-3 py-1.5 transition flex-shrink-0">
                    <svg class="w-3.5 h-3.5 mr-1 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                    Manual Form Entry &rarr;
                </a>
            </div>

            <form method="POST" action="{{ route('clearance.import.single') }}" enctype="multipart/form-data"
                class="space-y-6">
                @csrf

                <!-- Primary Excel File -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Requisition Excel
                        File (.xlsx, .xls) <span class="text-rose-500">*</span></label>
                    <input type="file" name="single_record" accept=".xlsx,.xls" required
                        class="block w-full text-xs text-slate-700 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 cursor-pointer">
                    @error('single_record')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Import / Export Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Clearance Operation
                        Type</label>
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center text-xs text-slate-700 cursor-pointer">
                            <input type="radio" name="imp_exp" value="Import" checked
                                class="text-brand-600 focus:ring-brand-500 mr-2">
                            <span class="font-medium">Import (นำเข้า)</span>
                        </label>
                        <label class="flex items-center text-xs text-slate-700 cursor-pointer">
                            <input type="radio" name="imp_exp" value="Export"
                                class="text-brand-600 focus:ring-brand-500 mr-2">
                            <span class="font-medium">Export (ส่งออก)</span>
                        </label>
                    </div>
                </div>

                <!-- Reference Files Accordion -->
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Optional Reference
                        Attachments (Specification / Catalog)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Reference File 1</label>
                            <input type="file" name="reference_file_1"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Reference File 2</label>
                            <input type="file" name="reference_file_2"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                    </div>
                </div>

                <!-- HS Code Evidence Files Accordion -->
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Customs Tariff & HS Code
                        Evidence Files (Compliance Requirement)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">HS Evidence File 1</label>
                            <input type="file" name="hs_evidence_file_1"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">HS Evidence File 2</label>
                            <input type="file" name="hs_evidence_file_2"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">HS Evidence File 3</label>
                            <input type="file" name="hs_evidence_file_3"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">HS Evidence File 4</label>
                            <input type="file" name="hs_evidence_file_4"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="py-2.5 px-6 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                        Upload & Ingest Single Record
                    </button>
                </div>
            </form>
        </div>

        <!-- ================= BATCH IMPORT FORM (PRIMARY) ================= -->
        {{-- Added x-show="tab === 'multi'" and styled container to hide/show according to active tab --}}
        <div x-show="tab === 'multi'"
            class="bg-white rounded-b-xl shadow-sm border border-t-0 border-slate-200 p-6 md:p-8 space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-base font-bold text-slate-900">Upload Multi-Record Batch Excel</h3>
                <p class="text-xs text-slate-500 mt-1">Parses mass customs clearance records from tabular Excel files (e.g.
                    <span class="font-mono text-slate-700">Excel Load HS CODE CCD DATABASE.xlsx</span>) with fixed column
                    structure. Attached reference or evidence files will be automatically associated with all imported
                    records.
                </p>
            </div>

            <form method="POST" action="{{ route('clearance.import.multi') }}" enctype="multipart/form-data"
                class="space-y-6">
                @csrf

                <!-- Primary Batch File -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">Batch Excel File
                        (.xlsx, .xls) <span class="text-rose-500">*</span></label>
                    <input type="file" name="multi_record" accept=".xlsx,.xls" required
                        class="block w-full text-xs text-slate-700 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 cursor-pointer">
                    @error('multi_record')
                        <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Reference Files Accordion -->
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Optional Shared Reference
                        Attachments</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Reference File 1</label>
                            <input type="file" name="reference_file_1"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Reference File 2</label>
                            <input type="file" name="reference_file_2"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                    </div>
                </div>

                <!-- HS Code Evidence Files Accordion -->
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Shared Customs Tariff & HS
                        Code Evidence Files</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">HS Evidence File 1</label>
                            <input type="file" name="hs_evidence_file_1"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">HS Evidence File 2</label>
                            <input type="file" name="hs_evidence_file_2"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">HS Evidence File 3</label>
                            <input type="file" name="hs_evidence_file_3"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">HS Evidence File 4</label>
                            <input type="file" name="hs_evidence_file_4"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-white file:border file:border-slate-300">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="py-2.5 px-6 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                        Upload & Ingest Batch Records
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection