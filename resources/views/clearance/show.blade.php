@extends('layouts.app')

@section('title', 'Clearance Record ' . $record->job_no)
@section('header_title', 'Customs Clearance Details - ' . $record->job_no)

@section('content')
<div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('clearance.index') }}" class="inline-flex items-center text-xs font-medium text-slate-600 hover:text-slate-900">
            ← Back to Clearance List
        </a>
        {{--
        <div class="flex items-center space-x-2">
            @if($record->isMissingEvidence())
                <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                    Compliance: Missing Evidence
                </span>
            @else
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                    Compliance: Verified
                </span>
            @endif
        </div>
        --}}
        <div class="flex items-center space-x-3">
            @if($record->isMissingEvidence())
                <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                    Compliance: Missing Evidence
                </span>
            @else
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                    Compliance: Verified
                </span>
            @endif
            <!-- <a href="{{ route('clearance.replicate.show', $record->id) }}"
               class="inline-flex items-center px-3 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-300 rounded-lg text-xs font-semibold transition">
                Duplicate as New CCD
            </a> -->
            <a href="{{ route('clearance.edit', $record->id) }}" class="inline-flex items-center px-3 py-1 bg-brand-700 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Edit Record
            </a>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden p-6 md:p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-6 border-b border-slate-200">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Job Reference</div>
                <div class="text-base font-mono font-bold text-brand-700 mt-1">{{ $record->job_no }}</div>
            </div>
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Customer</div>
                <div class="text-sm font-semibold text-slate-800 mt-1">{{ $record->customer_name }}</div>
            </div>
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Branch Office</div>
                <div class="text-sm font-semibold text-slate-800 mt-1">
                    {{ $record->branch ? $record->branch->branch_name : 'N/A' }} ({{ $record->branch ? $record->branch->branch_code : '' }})
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pb-6 border-b border-slate-200 text-xs">
            <div>
                <div class="text-slate-400 font-bold uppercase">Declaration</div>
                <div class="font-semibold text-slate-800 mt-1">{{ $record->imp_exp == 0 ? 'Import' : 'Export' }}</div>
            </div>
            <div>
                <div class="text-slate-400 font-bold uppercase">HS Code</div>
                <div class="font-mono font-bold text-slate-900 mt-1">{{ $record->hs_code }}</div>
            </div>
            <div>
                <div class="text-slate-400 font-bold uppercase">Tariff Duty Rate</div>
                <div class="font-semibold text-slate-800 mt-1">{{ $record->tariff_ad_valorem ? $record->tariff_ad_valorem . '%' : '-' }}</div>
            </div>
            <div>
                <div class="text-slate-400 font-bold uppercase">Quantity Unit</div>
                <div class="font-semibold text-slate-800 mt-1">{{ $record->quantity_unit ?: '-' }}</div>
            </div>
        </div>

        <div class="space-y-4 pb-6 border-b border-slate-200">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Product Description (English)</div>
                <div class="text-sm font-medium text-slate-900 mt-1 bg-slate-50 p-3 rounded-lg border border-slate-200">{{ $record->product_description_en }}</div>
            </div>
            @if($record->product_description_th)
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Product Description (Thai)</div>
                <div class="text-sm font-medium text-slate-900 mt-1 bg-slate-50 p-3 rounded-lg border border-slate-200">{{ $record->product_description_th }}</div>
            </div>
            @endif
        </div>

        <!-- Document Attachments Section -->
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">Attached Customs Files & Technical Evidence</h4>
            {{--
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70">
                    <div class="text-xs font-bold text-slate-700">Reference File 1 (Catalog/SDS)</div>
                    <div class="text-xs text-slate-500 mt-1 truncate">
                        {{ $record->reference_file_1 ?: 'No catalog attached' }}
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70">
                    <div class="text-xs font-bold text-slate-700">HS Evidence File 1 (Signed Requisition)</div>
                    <div class="text-xs text-slate-500 mt-1 truncate">
                        {{ $record->hs_evidence_file_1 ?: 'No requisition form attached' }}
                    </div>
                </div>
            </div>
            --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @php
                    $attachmentSlots = [
                        ['field' => 'reference_file_1', 'label' => 'Reference File 1 (Catalog/SDS)'],
                        ['field' => 'reference_file_2', 'label' => 'Reference File 2 (Specification)'],
                        ['field' => 'hs_evidence_file_1', 'label' => 'HS Evidence 1 (Tariff Requisition)'],
                        ['field' => 'hs_evidence_file_2', 'label' => 'HS Evidence 2 (Ruling / Certificate)'],
                        ['field' => 'hs_evidence_file_3', 'label' => 'HS Evidence 3 (Customs Evidence)'],
                        ['field' => 'hs_evidence_file_4', 'label' => 'HS Evidence 4 (Additional Document)'],
                    ];
                @endphp

                @foreach($attachmentSlots as $slot)
                    @php $filePath = $record->{$slot['field']}; @endphp
                    <div class="p-4 rounded-xl border border-slate-200 {{ $filePath ? 'bg-white shadow-sm' : 'bg-slate-50/60' }}">
                        <div class="text-xs font-bold text-slate-700">{{ $slot['label'] }}</div>
                        @if($filePath)
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-xs text-slate-700 font-mono font-medium truncate max-w-[170px]" title="{{ basename($filePath) }}">
                                    {{ basename($filePath) }}
                                </span>
                                {{--
                                <a href="{{ asset('storage/' . ltrim($filePath, '/')) }}" target="_blank" download class="inline-flex items-center text-xs text-brand-600 hover:text-brand-800 font-semibold transition">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Download
                                </a>
                                --}}
                                <a href="{{ route('clearance.download', ['id' => $record->id, 'field' => $slot['field']]) }}" class="inline-flex items-center text-xs text-brand-600 hover:text-brand-800 font-semibold transition">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Download
                                </a>
                            </div>

                            <div class="mt-2.5 pt-2 border-t border-slate-100 text-[10px] text-slate-500 font-mono space-y-1">
                                <div class="truncate text-slate-600" title="storage/app/public/{{ ltrim($filePath, '/') }}">
                                    <span class="text-slate-400 font-semibold">Local:</span> storage/app/public/{{ ltrim($filePath, '/') }}
                                </div>
                                @if(config('filesystems.disks.s3.bucket'))
                                <div class="truncate text-emerald-700 font-medium" title="s3://{{ config('filesystems.disks.s3.bucket') }}/{{ ltrim($filePath, '/') }}">
                                    <span class="text-slate-400 font-semibold">AWS:</span> s3://{{ config('filesystems.disks.s3.bucket') }}/{{ ltrim($filePath, '/') }}
                                </div>
                                @endif
                            </div>
                        @else
                            <div class="text-xs text-slate-400 mt-2 italic">Not attached</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

