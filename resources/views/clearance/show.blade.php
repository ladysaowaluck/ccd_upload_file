@extends('layouts.app')

@section('title', 'Clearance Record ' . $record->job_no)
@section('header_title', 'Customs Clearance Details - ' . $record->job_no)

@section('content')
    <div class="space-y-6">

        <!-- In-page notification banner with padding showing up-to-date status after record update -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs shadow-sm flex items-start justify-between gap-3 animate-in fade-in duration-150">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-emerald-900 text-xs">Record Updated: Up to Date</div>
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
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 p-1 rounded transition" title="Dismiss">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-sm flex items-start justify-between gap-3 animate-in fade-in duration-150">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center text-rose-700 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-rose-900 text-xs">Error</div>
                        <div class="text-xs text-rose-800 mt-0.5 leading-relaxed">{{ session('error') }}</div>
                    </div>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800 p-1 rounded transition" title="Dismiss">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        <!-- Top Action Bar  -->
        <div class="flex items-center justify-between">
            <a href="{{ route('clearance.index') }}"
                class="inline-flex items-center text-xs font-medium text-slate-600 hover:text-slate-900">
                ← Back to Clearance List
            </a>
            <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                @if($record->isFullyDocumented())
                    <span
                        class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                        Compliance: Fully Documented
                    </span>
                @elseif($record->isMissingAllDocuments())
                    <span
                        class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                        Compliance: Missing Evidence &amp; Reference
                    </span>
                @elseif($record->isMissingEvidence())
                    <span
                        class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20">
                        Compliance: Missing Evidence
                    </span>
                @else
                    <span
                        class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-800 ring-1 ring-inset ring-sky-600/20">
                        Compliance: Missing Reference
                    </span>
                @endif

                <!-- Granular chips for Reference Files and HS Evidence Files  -->
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {{ $record->hasReferenceFile() ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                    Ref (Catalog/SDS): {{ $record->hasReferenceFile() ? 'Attached' : 'Missing' }}
                </span>
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {{ $record->hasHsEvidenceFile() ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                    Evidence (Sign/AR): {{ $record->hasHsEvidenceFile() ? 'Attached' : 'Missing' }}
                </span>
                @if(auth()->user()?->canEdit())
                    <a href="{{ route('clearance.edit', $record->id) }}"
                        class="inline-flex items-center px-3 py-1 bg-brand-700 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Edit Record
                    </a>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8 space-y-8">

            <!-- ─── Section: Job Reference ─── -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Job Reference</h3>
                <div class="space-y-3">

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Job Number</div>
                        <div
                            class="flex-1 text-xs font-mono font-bold text-brand-700 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->job_no }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Customer Name</div>
                        <div
                            class="flex-1 text-xs font-semibold text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->customer_name ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Branch</div>
                        <div
                            class="flex-1 text-xs font-semibold text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->branch ? $record->branch->branch_name . ' (' . $record->branch->branch_code . ')' : '—' }}
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Imp / Exp</div>
                        <div
                            class="flex-1 text-xs font-semibold text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->imp_exp == 0 ? 'Import (นำเข้า)' : 'Export (ส่งออก)' }}
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Create Date</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->create_date ?? '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Update Date</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->update_date ?? '—' }}</div>
                    </div>

                </div>
            </div>

            <!-- ─── Section: Product Details ─── -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Product Details</h3>
                <div class="space-y-3">

                    <div class="flex items-start gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">English Description</div>
                        <div
                            class="flex-1 text-xs font-medium text-slate-900 rounded-lg border border-slate-200 bg-slate-50 p-2.5 whitespace-pre-line">
                            {{ $record->product_description_en ?: '—' }}</div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Thai Description</div>
                        <div
                            class="flex-1 text-xs font-medium text-slate-900 rounded-lg border border-slate-200 bg-slate-50 p-2.5 whitespace-pre-line">
                            {{ $record->product_description_th ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Product Code 1</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->product_code_1 ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Product Code 2</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->product_code_2 ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Product Year</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->product_year ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Brand Name</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->brand_name ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Model</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->model_name ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Materials</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->materials ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Usage</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->usage ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Function</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->function ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Position</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->position ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Country of Origin</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->country_of_origin ?: '—' }}</div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Note</div>
                        <div
                            class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 p-2.5 whitespace-pre-line">
                            {{ $record->note ?: '—' }}</div>
                    </div>

                </div>
            </div>

            <!-- ─── Section: Tariff Details ─── -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Tariff Details</h3>
                <div class="space-y-3">

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">HS Code (8 digits)</div>
                        <div
                            class="flex-1 text-xs font-mono font-bold text-slate-900 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->hs_code ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Sequence Code (5 digits)</div>
                        <div
                            class="flex-1 text-xs font-mono text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->sequence_code ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Statistic Code (3 digits)</div>
                        <div
                            class="flex-1 text-xs font-mono uppercase text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->statistic_code ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Quantity Unit (3 digits)</div>
                        <div
                            class="flex-1 text-xs font-mono uppercase text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->quantity_unit ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Privilege Code (3 digits)</div>
                        <div
                            class="flex-1 text-xs font-mono uppercase text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->privilege_code ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->tariff_ad_valorem !== null ? $record->tariff_ad_valorem . '%' : '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Unit</div>
                        <div
                            class="flex-1 text-xs font-mono text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                             <!-- {{ $record->tariff_specific_rate_unit_id ?: '—' }}  -->
                            {{ $record->tariff_specific_rate_unit ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Baht</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->tariff_specific_rate_baht !== null ? number_format($record->tariff_specific_rate_baht, 2) : '—' }}
                        </div>
                    </div>

                </div>
            </div>

            <!-- ─── Section: Anti-Dumping ─── -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Anti-Dumping</h3>
                <div class="space-y-3">

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->anti_dumping_ad_valorem !== null ? $record->anti_dumping_ad_valorem . '%' : '—' }}
                        </div>
                    </div>

                </div>
            </div>

            <!-- ─── Section: Excise Tax ─── -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Excise Tax</h3>
                <div class="space-y-3">

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->excise_tax_ad_valorem !== null ? $record->excise_tax_ad_valorem . '%' : '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Unit</div>
                        <div
                            class="flex-1 text-xs font-mono text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <!--  {{ $record->excise_tax_rate_unit_id ?: '—' }} 
                             : Show the unit code from master_units (e.g. LTR) instead of the ID.  -->
                            {{ $record->unitCodeFor('excise_tax_rate_unit_id') ?: '—' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Baht</div>
                        <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            {{ $record->excise_tax_rate_baht !== null ? number_format($record->excise_tax_rate_baht, 2) : '—' }}
                        </div>
                    </div>

                </div>
            </div>

            <!-- ─── Section: Other Tax 1 & 2 ─── -->
            @foreach([1, 2] as $tn)
                <div>
                    <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Other Tax {{ $tn }}</h3>
                    <div class="space-y-3">

                        <div class="flex items-center gap-4">
                            <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Name of Tax</div>
                            <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                {{ $record->{'other_tax' . $tn . '_name_of_tax'} ?: '—' }}</div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</div>
                            <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                {{ $record->{'other_tax' . $tn . '_ad_valorem'} !== null ? $record->{'other_tax' . $tn . '_ad_valorem'} . '%' : '—' }}
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Unit</div>
                            <div
                                class="flex-1 text-xs font-mono text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <!--  {{ $record->{'other_tax' . $tn . '_rate_unit_id'} ?: '—' }} 
                                 : Show the unit code from master_units instead of the ID.  -->
                                {{ $record->unitCodeFor('other_tax' . $tn . '_rate_unit_id') ?: '—' }}</div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Baht</div>
                            <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                {{ $record->{'other_tax' . $tn . '_rate_baht'} !== null ? number_format($record->{'other_tax' . $tn . '_rate_baht'}, 2) : '—' }}
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach

            <!-- ─── Section: License 1–4 ─── -->
            @foreach([1, 2, 3, 4] as $ln)
                @php $lic = $record->{'license' . $ln}; @endphp
                <div>
                    <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">License {{ $ln }}</h3>
                    <div class="space-y-3">

                        <div class="flex items-center gap-4">
                            <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Name of License {{ $ln }}</div>
                            <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <!-- {{ $lic ? ($lic->license_en ?? $lic->license_th) : '—' }}</div> -->
                                {{ $lic ? "{$lic->license_th} - {$lic->license_en}" : '—' }}
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">Note</div>
                            <div class="flex-1 text-xs text-slate-800 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                {{ $record->{'license' . $ln . '_note'} ?: '—' }}</div>
                        </div>

                    </div>
                </div>
            @endforeach

            <!-- ─── Section: Reference Files ─── -->
            @php
                $referenceSlots = [
                    ['field' => 'reference_file_1', 'label' => 'Catalog, SDS etc. 1'],
                    ['field' => 'reference_file_2', 'label' => 'Catalog, SDS etc. 2'],
                ];
            @endphp
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Reference Files</h3>
                <div class="space-y-3">

                    @foreach($referenceSlots as $slot)
                        @php $filePath = $record->{$slot['field']}; @endphp
                        <div class="flex items-center gap-4">
                            <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">{{ $slot['label'] }}</div>
                            <div class="flex-1 text-xs rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                @if($filePath)
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="text-slate-700 font-mono truncate max-w-md" title="{{ basename($filePath) }}">
                                            {{ basename($filePath) }}
                                        </span>
                                        <a href="{{ route('clearance.view', ['id' => $record->id, 'field' => $slot['field']]) }}"
                                            target="_blank"
                                            class="inline-flex items-center text-xs text-sky-600 hover:text-sky-800 font-semibold transition">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                            View
                                        </a>
                                        <!-- Download removed — View (pre-signed S3 URL) works in production;
                                             download routes through server which is blocked by S3 IAM policy.  -->
                                        <!-- @if(auth()->user()?->canDownload())
                                            <a href="{{ route('clearance.download', ['id' => $record->id, 'field' => $slot['field']]) }}"
                                                class="inline-flex items-center text-xs text-brand-600 hover:text-brand-800 font-semibold transition">
                                                Download
                                            </a>
                                        @endif  -->
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">Not attached</span>
                                @endif
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

            <!-- ─── Section: HS Evidence ─── -->
            @php
                $evidenceSlots = [
                    ['field' => 'hs_evidence_file_1', 'label' => 'Signed Requisition Form'],
                    ['field' => 'hs_evidence_file_2', 'label' => 'Email Evidence'],
                    ['field' => 'hs_evidence_file_3', 'label' => 'Advance Ruling'],
                    ['field' => 'hs_evidence_file_4', 'label' => 'Customs Reference'],
                ];
            @endphp
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">HS Evidence</h3>
                <div class="space-y-3">

                    @foreach($evidenceSlots as $slot)
                        @php $filePath = $record->{$slot['field']}; @endphp
                        <div class="flex items-center gap-4">
                            <div class="w-52 shrink-0 text-xs font-semibold text-slate-700">{{ $slot['label'] }}</div>
                            <div class="flex-1 text-xs rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                @if($filePath)
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="text-slate-700 font-mono truncate max-w-md" title="{{ basename($filePath) }}">
                                            {{ basename($filePath) }}
                                        </span>
                                        <a href="{{ route('clearance.view', ['id' => $record->id, 'field' => $slot['field']]) }}"
                                            target="_blank"
                                            class="inline-flex items-center text-xs text-sky-600 hover:text-sky-800 font-semibold transition">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                            View
                                        </a>
                                        <!-- Download removed — use View (pre-signed S3 URL) instead. -->
                                        <!-- @if(auth()->user()?->canDownload())
                                            <a href="{{ route('clearance.download', ['id' => $record->id, 'field' => $slot['field']]) }}"
                                                class="inline-flex items-center text-xs text-brand-600 hover:text-brand-800 font-semibold transition">
                                                Download
                                            </a>
                                        @endif  -->
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">Not attached</span>
                                @endif
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

        </div>
    </div>
@endsection