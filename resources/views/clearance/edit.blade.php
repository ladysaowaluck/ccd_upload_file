@extends('layouts.app')

@section('title', 'Edit Clearance - ' . $record->job_no)
@section('header_title', 'Edit Customs Clearance Record (' . $record->job_no . ')')

@section('content')

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
        <form method="POST" action="{{ route('clearance.update', $record->id) }}" enctype="multipart/form-data"
            class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Section: Job Reference -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Job Reference</h3>
                <div class="space-y-3">

                    <div class="flex items-start gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Job Number <span
                                class="text-rose-500">*</span></label>
                        <div class="flex-1">
                            <input type="text" name="job_no" value="{{ old('job_no', $record->job_no) }}" required
                                class="w-full text-xs font-mono font-bold rounded-lg border border-slate-300 px-3 py-2 bg-slate-50 text-brand-800 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            @error('job_no')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Customer Name <span
                                class="text-rose-500">*</span></label>
                        <div class="flex-1">
                            <select name="master_customer_id" required
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                <option value="">Select Customer...</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ old('master_customer_id', $record->master_customer_id) == $c->id ? 'selected' : '' }}>{{ $c->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('master_customer_id')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Branch <span
                                class="text-rose-500">*</span></label>
                        <div class="flex-1">
                            <select name="master_branch_id" required
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                <option value="">Select Branch...</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('master_branch_id', $record->master_branch_id) == $b->id ? 'selected' : '' }}>{{ $b->branch_name }} ({{ $b->branch_code }})</option>
                                @endforeach
                            </select>
                            @error('master_branch_id')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Imp / Exp <span
                                class="text-rose-500">*</span></label>
                        <div class="flex-1">
                            <select name="imp_exp" required
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                <option value="0" {{ old('imp_exp', $record->imp_exp) == '0' ? 'selected' : '' }}>Import
                                    (นำเข้า)</option>
                                <option value="1" {{ old('imp_exp', $record->imp_exp) == '1' ? 'selected' : '' }}>Export
                                    (ส่งออก)</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Section: Product Details -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Product Details</h3>
                <div class="space-y-3">

                    <div class="flex items-start gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">English Description <span
                                class="text-rose-500">*</span></label>
                        <div class="flex-1">
                            <textarea name="product_description_en" required rows="3"
                                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('product_description_en', $record->product_description_en) }}</textarea>
                            @error('product_description_en')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Thai Description</label>
                        <div class="flex-1">
                            <textarea name="product_description_th" rows="3"
                                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('product_description_th', $record->product_description_th) }}</textarea>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Product Code 1</label>
                        <div class="flex-1">
                            <input type="text" name="product_code_1"
                                value="{{ old('product_code_1', $record->product_code_1) }}" placeholder="Part / SKU code"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Product Code 2</label>
                        <div class="flex-1">
                            <input type="text" name="product_code_2"
                                value="{{ old('product_code_2', $record->product_code_2) }}" placeholder="Secondary code"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Product Year</label>
                        <div class="flex-1">
                            <input type="text" name="product_year" value="{{ old('product_year', $record->product_year) }}"
                                placeholder="e.g. 2024"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Brand Name</label>
                        <div class="flex-1">
                            <input type="text" name="brand_name" value="{{ old('brand_name', $record->brand_name) }}"
                                placeholder="Brand / Maker"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Model</label>
                        <div class="flex-1">
                            <input type="text" name="model_name" value="{{ old('model_name', $record->model_name) }}"
                                placeholder="Model name / number"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Materials</label>
                        <div class="flex-1">
                            <input type="text" name="materials" value="{{ old('materials', $record->materials) }}"
                                placeholder="Composition / materials"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Usage</label>
                        <div class="flex-1">
                            <input type="text" name="usage" value="{{ old('usage', $record->usage) }}"
                                placeholder="Usage / application"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Function</label>
                        <div class="flex-1">
                            <input type="text" name="function" value="{{ old('function', $record->function) }}"
                                placeholder="Function description"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Position</label>
                        <div class="flex-1">
                            <input type="text" name="position" value="{{ old('position', $record->position) }}"
                                placeholder="Mounting / installation position"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Country of Origin</label>
                        <div class="flex-1">
                            <input type="text" name="country_of_origin"
                                value="{{ old('country_of_origin', $record->country_of_origin) }}"
                                placeholder="e.g. JAPAN, THAILAND"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Note</label>
                        <div class="flex-1">
                            <textarea name="note" rows="2" placeholder="Additional notes..."
                                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('note', $record->note) }}</textarea>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Section: Tariff Details -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Tariff Details</h3>
                <div class="space-y-3">

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">HS Code (8 digits) <span
                                class="text-rose-500">*</span></label>
                        <div class="flex-1">
                            <input type="text" name="hs_code" value="{{ old('hs_code', $record->hs_code) }}" required
                                maxlength="8" placeholder="8-digit HS Code"
                                class="w-full text-xs font-mono font-bold rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            @error('hs_code')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Sequence Code (5 digits)</label>
                        <div class="flex-1">
                            <input type="text" name="sequence_code"
                                value="{{ old('sequence_code', $record->sequence_code) }}" maxlength="5"
                                placeholder="e.g. 00000"
                                class="w-full text-xs font-mono rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Statistic Code (3 digits) <span
                                class="text-rose-500">*</span></label>
                        <div class="flex-1">
                            <input type="text" name="statistic_code"
                                value="{{ old('statistic_code', $record->statistic_code) }}" maxlength="3" placeholder="000"
                                class="w-full text-xs font-mono uppercase rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Quantity Unit (3 digits)</label>
                        <div class="flex-1">
                            <input type="text" name="quantity_unit"
                                value="{{ old('quantity_unit', $record->quantity_unit) }}" maxlength="3" placeholder="C62"
                                class="w-full text-xs font-mono uppercase rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Privilege Code (3 digits)</label>
                        <div class="flex-1">
                            <input type="text" name="privilege_code"
                                value="{{ old('privilege_code', $record->privilege_code) }}" maxlength="3" placeholder="000"
                                class="w-full text-xs font-mono uppercase rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</label>
                        <div class="flex-1">
                            <input type="number" step="0.01" name="tariff_ad_valorem"
                                value="{{ old('tariff_ad_valorem', $record->tariff_ad_valorem) }}" placeholder="0.00"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific)
                            Unit</label>
                        <div class="flex-1">
                            <input type="text" name="tariff_specific_rate_unit"
                                value="{{ old('tariff_specific_rate_unit', $record->tariff_specific_rate_unit) }}"
                                placeholder="e.g. KGM"
                                class="w-full text-xs font-mono rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific)
                            Baht</label>
                        <div class="flex-1">
                            <input type="number" step="0.01" name="tariff_specific_rate_baht"
                                value="{{ old('tariff_specific_rate_baht', $record->tariff_specific_rate_baht) }}"
                                placeholder="0.00"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                </div>
            </div>

            <!-- Section: Anti-Dumping -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Anti-Dumping</h3>
                <div class="space-y-3">

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</label>
                        <div class="flex-1">
                            <input type="number" step="0.01" name="anti_dumping_ad_valorem"
                                value="{{ old('anti_dumping_ad_valorem', $record->anti_dumping_ad_valorem) }}"
                                placeholder="0.00"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                </div>
            </div>

            <!-- Section: Excise Tax -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Excise Tax</h3>
                <div class="space-y-3">

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</label>
                        <div class="flex-1">
                            <input type="number" step="0.01" name="excise_tax_ad_valorem"
                                value="{{ old('excise_tax_ad_valorem', $record->excise_tax_ad_valorem) }}"
                                placeholder="0.00"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific)
                            Unit</label>
                        <div class="flex-1">
                            <input type="text" name="excise_tax_rate_unit_id"
                                value="{{ old('excise_tax_rate_unit_id', $record->excise_tax_rate_unit_id) }}"
                                placeholder="e.g. LTR"
                                class="w-full text-xs font-mono rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific)
                            Baht</label>
                        <div class="flex-1">
                            <input type="number" step="0.01" name="excise_tax_rate_baht"
                                value="{{ old('excise_tax_rate_baht', $record->excise_tax_rate_baht) }}" placeholder="0.00"
                                class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                </div>
            </div>

            <!-- Section: Other Tax 1 & 2 -->
            @foreach([1, 2] as $tn)
                <div>
                    <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Other Tax {{ $tn }} (if
                        any)</h3>
                    <div class="space-y-3">

                        <div class="flex items-center gap-4">
                            <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Name of Tax</label>
                            <div class="flex-1">
                                <input type="text" name="other_tax{{ $tn }}_name_of_tax"
                                    value="{{ old('other_tax' . $tn . '_name_of_tax', $record->{'other_tax' . $tn . '_name_of_tax'}) }}"
                                    placeholder="Tax name"
                                    class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</label>
                            <div class="flex-1">
                                <input type="number" step="0.01" name="other_tax{{ $tn }}_ad_valorem"
                                    value="{{ old('other_tax' . $tn . '_ad_valorem', $record->{'other_tax' . $tn . '_ad_valorem'}) }}"
                                    placeholder="0.00"
                                    class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific)
                                Unit</label>
                            <div class="flex-1">
                                <input type="text" name="other_tax{{ $tn }}_rate_unit_id"
                                    value="{{ old('other_tax' . $tn . '_rate_unit_id', $record->{'other_tax' . $tn . '_rate_unit_id'}) }}"
                                    placeholder="e.g. KGM"
                                    class="w-full text-xs font-mono rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific)
                                Baht</label>
                            <div class="flex-1">
                                <input type="number" step="0.01" name="other_tax{{ $tn }}_rate_baht"
                                    value="{{ old('other_tax' . $tn . '_rate_baht', $record->{'other_tax' . $tn . '_rate_baht'}) }}"
                                    placeholder="0.00"
                                    class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach

            <!-- Section: License 1–4 -->
            @foreach([1, 2, 3, 4] as $ln)
                <div>
                    <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">License {{ $ln }}</h3>
                    <div class="space-y-3">

                        <div class="flex items-center gap-4">
                            <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Name of License {{ $ln }}</label>
                            <div class="flex-1">
                                <select name="license{{ $ln }}_name_id"
                                    class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                    <option value="">— None —</option>
                                    @foreach($licenses as $lic)
                                        <option value="{{ $lic->id }}" {{ old('license' . $ln . '_name_id', $record->{'license' . $ln . '_name_id'}) == $lic->id ? 'selected' : '' }}>
                                            {{ $lic->license_en ?? $lic->license_th }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Note</label>
                            <div class="flex-1">
                                <input type="text" name="license{{ $ln }}_note"
                                    value="{{ old('license' . $ln . '_note', $record->{'license' . $ln . '_note'}) }}"
                                    placeholder="License note..."
                                    class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach

            <!-- Section: Reference Files -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Reference Files</h3>
                <div class="space-y-3">

                    @foreach([1, 2] as $rn)
                        <div class="flex items-center gap-4">
                            <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">
                                Catalog, SDS etc. {{ $rn }}
                                @if($record->{'reference_file_' . $rn})
                                    <span
                                        class="ml-1 text-[10px] text-emerald-700 font-semibold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Exists</span>
                                @endif
                            </label>
                            <div class="flex-1">
                                <input type="file" name="reference_file_{{ $rn }}" class="w-full text-xs">
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

            <!-- Section: HS Evidence -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">HS Evidence</h3>
                <div class="space-y-3">

                    @php
                        $evidenceLabels = [
                            1 => 'Signed Requisition Form',
                            2 => 'Email Evidence',
                            3 => 'Advance Ruling',
                            4 => 'Customs Reference',
                        ];
                    @endphp

                    @foreach([1, 2, 3, 4] as $en)
                        <div class="flex items-center gap-4">
                            <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">
                                {{ $evidenceLabels[$en] }}
                                @if($record->{'hs_evidence_file_' . $en})
                                    <span
                                        class="ml-1 text-[10px] text-emerald-700 font-semibold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Exists</span>
                                @elseif($en === 1)
                                    <span
                                        class="ml-1 text-[10px] text-rose-600 font-semibold bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">Missing</span>
                                @endif
                            </label>
                            <div class="flex-1">
                                <input type="file" name="hs_evidence_file_{{ $en }}" class="w-full text-xs">
                            </div>
                        </div>
                    @endforeach

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-500">Create Date</label>
                        <div class="flex-1 text-xs text-slate-400 italic">{{ $record->create_date ?? 'Auto-set' }}</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="w-52 shrink-0 text-xs font-semibold text-slate-500">Update Date</label>
                        <div class="flex-1 text-xs text-slate-400 italic">Auto-set on save</div>
                    </div>

                </div>
            </div>

             <!-- Actions -->
                    <!-- Delete button on the left, Cancel + Save on the right -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                         <!-- Only display Delete Record button if user has explicit canDelete() privileges. -->
                        @if(auth()->user()?->canDelete())
                            <button type="button" onclick="document.getElementById('deleteModal').classList.remove('hidden')"
                                class="px-4 py-2 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition">
                                Delete Record
                            </button>
                        @else
                            <div></div>
                        @endif
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('clearance.show', $record->id) }}"
                                class="px-5 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Cancel</a>
                            <button type="submit"
                                class="px-6 py-2 text-xs font-semibold text-white bg-brand-700 hover:bg-brand-800 rounded-lg shadow-sm transition">
                                Update Clearance Record
                            </button>
                        </div>
                    </div>

        </form>
    </div>

    <!-- Delete confirmation modal — separate form with DELETE method spoofing -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-sm mx-4 p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Delete Clearance Record</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Are you sure you want to delete <span
                            class="font-semibold font-mono text-slate-700">{{ $record->job_no }}</span>?
                        This action cannot be undone.
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden')"
                    class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Cancel
                </button>
                <form method="POST" action="{{ route('clearance.destroy', $record->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition">
                        Yes, Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection