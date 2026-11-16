@extends('layouts.app')

@section('title', 'Register Clearance')
@section('header_title', 'Register New Customs Clearance Record')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
    <form method="POST" action="{{ route('clearance.store') }}" enctype="multipart/form-data" class="space-y-8">
        @csrf

        {{-- ─── Section: Job Reference ─── --}}
        <div>
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Job Reference</h3>
            <div class="space-y-3">

                <div class="flex items-start gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Job Number <span class="text-rose-500">*</span></label>
                    <div class="flex-1">
                        <input type="text" name="job_no" value="{{ old('job_no', $suggestedJobNo) }}" required
                            class="w-full text-xs font-mono font-bold rounded-lg border border-slate-300 px-3 py-2 bg-slate-50 text-brand-800 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        @error('job_no') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Customer Name <span class="text-rose-500">*</span></label>
                    <div class="flex-1">
                        <select name="master_customer_id" required class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <option value="">Select Customer...</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('master_customer_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        @error('master_customer_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Branch <span class="text-rose-500">*</span></label>
                    <div class="flex-1">
                        <select name="master_branch_id" required class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <option value="">Select Branch...</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ old('master_branch_id') == $b->id ? 'selected' : '' }}>{{ $b->branch_name }} ({{ $b->branch_code }})</option>
                            @endforeach
                        </select>
                        @error('master_branch_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Imp / Exp <span class="text-rose-500">*</span></label>
                    <div class="flex-1">
                        <select name="imp_exp" required class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <option value="0" {{ old('imp_exp', '0') == '0' ? 'selected' : '' }}>Import (นำเข้า)</option>
                            <option value="1" {{ old('imp_exp') == '1' ? 'selected' : '' }}>Export (ส่งออก)</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>

        {{-- ─── Section: Product Details ─── --}}
        <div>
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Product Details</h3>
            <div class="space-y-3">

                <div class="flex items-start gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">English Description <span class="text-rose-500">*</span></label>
                    <div class="flex-1">
                        <textarea name="product_description_en" required rows="3"
                            class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('product_description_en') }}</textarea>
                        @error('product_description_en') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Thai Description</label>
                    <div class="flex-1">
                        <textarea name="product_description_th" rows="3"
                            class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('product_description_th') }}</textarea>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Product Code 1</label>
                    <div class="flex-1">
                        <input type="text" name="product_code_1" value="{{ old('product_code_1') }}" placeholder="Part / SKU code"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Product Code 2</label>
                    <div class="flex-1">
                        <input type="text" name="product_code_2" value="{{ old('product_code_2') }}" placeholder="Secondary code"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Product Year</label>
                    <div class="flex-1">
                        <input type="text" name="product_year" value="{{ old('product_year') }}" placeholder="e.g. 2024"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Brand Name</label>
                    <div class="flex-1">
                        <input type="text" name="brand_name" value="{{ old('brand_name') }}" placeholder="Brand / Maker"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Model</label>
                    <div class="flex-1">
                        <input type="text" name="model_name" value="{{ old('model_name') }}" placeholder="Model name / number"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Materials</label>
                    <div class="flex-1">
                        <input type="text" name="materials" value="{{ old('materials') }}" placeholder="Composition / materials"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Usage</label>
                    <div class="flex-1">
                        <input type="text" name="usage" value="{{ old('usage') }}" placeholder="Usage / application"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Function</label>
                    <div class="flex-1">
                        <input type="text" name="function" value="{{ old('function') }}" placeholder="Function description"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Position</label>
                    <div class="flex-1">
                        <input type="text" name="position" value="{{ old('position') }}" placeholder="Mounting / installation position"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Country of Origin</label>
                    <div class="flex-1">
                        <input type="text" name="country_of_origin" value="{{ old('country_of_origin') }}" placeholder="e.g. JAPAN, THAILAND"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700 pt-2">Note</label>
                    <div class="flex-1">
                        <textarea name="note" rows="2" placeholder="Additional notes..."
                            class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('note') }}</textarea>
                    </div>
                </div>

            </div>
        </div>

        {{-- ─── Section: Tariff Details ─── --}}
        <div>
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Tariff Details</h3>
            <div class="space-y-3">

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">HS Code (8 digits) <span class="text-rose-500">*</span></label>
                    <div class="flex-1">
                        <input type="text" name="hs_code" value="{{ old('hs_code') }}" required maxlength="8" placeholder="8-digit HS Code"
                            class="w-full text-xs font-mono font-bold rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        @error('hs_code') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Sequence Code (5 digits)</label>
                    <div class="flex-1">
                        <input type="text" name="sequence_code" value="{{ old('sequence_code') }}" maxlength="5" placeholder="e.g. 00000"
                            class="w-full text-xs font-mono rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Statistic Code (3 digits) <span class="text-rose-500">*</span></label>
                    <div class="flex-1">
                        <input type="text" name="statistic_code" value="{{ old('statistic_code') }}" maxlength="3" placeholder="000"
                            class="w-full text-xs font-mono uppercase rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Quantity Unit (3 digits)</label>
                    <div class="flex-1">
                        <input type="text" name="quantity_unit" value="{{ old('quantity_unit') }}" maxlength="3" placeholder="C62"
                            class="w-full text-xs font-mono uppercase rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Privilege Code (3 digits)</label>
                    <div class="flex-1">
                        <input type="text" name="privilege_code" value="{{ old('privilege_code') }}" maxlength="3" placeholder="000"
                            class="w-full text-xs font-mono uppercase rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</label>
                    <div class="flex-1">
                        <input type="number" step="0.01" name="tariff_ad_valorem" value="{{ old('tariff_ad_valorem') }}" placeholder="0.00"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Unit</label>
                    <div class="flex-1">
                        <input type="text" name="tariff_specific_rate_unit" value="{{ old('tariff_specific_rate_unit') }}" placeholder="e.g. KGM"
                            class="w-full text-xs font-mono rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Baht</label>
                    <div class="flex-1">
                        <input type="number" step="0.01" name="tariff_specific_rate_baht" value="{{ old('tariff_specific_rate_baht') }}" placeholder="0.00"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

            </div>
        </div>

        {{-- ─── Section: Anti-Dumping ─── --}}
        <div>
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Anti-Dumping</h3>
            <div class="space-y-3">

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</label>
                    <div class="flex-1">
                        <input type="number" step="0.01" name="anti_dumping_ad_valorem" value="{{ old('anti_dumping_ad_valorem') }}" placeholder="0.00"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

            </div>
        </div>

        {{-- ─── Section: Excise Tax ─── --}}
        <div>
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Excise Tax</h3>
            <div class="space-y-3">

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</label>
                    <div class="flex-1">
                        <input type="number" step="0.01" name="excise_tax_ad_valorem" value="{{ old('excise_tax_ad_valorem') }}" placeholder="0.00"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Unit</label>
                    <div class="flex-1">
                        <input type="text" name="excise_tax_rate_unit_id" value="{{ old('excise_tax_rate_unit_id') }}" placeholder="e.g. LTR"
                            class="w-full text-xs font-mono rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Baht</label>
                    <div class="flex-1">
                        <input type="number" step="0.01" name="excise_tax_rate_baht" value="{{ old('excise_tax_rate_baht') }}" placeholder="0.00"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

            </div>
        </div>

        {{-- ─── Section: Other Tax 1 & 2 ─── --}}
        @foreach([1, 2] as $tn)
        <div>
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Other Tax {{ $tn }} (if any)</h3>
            <div class="space-y-3">

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Name of Tax</label>
                    <div class="flex-1">
                        <input type="text" name="other_tax{{ $tn }}_name_of_tax" value="{{ old('other_tax'.$tn.'_name_of_tax') }}" placeholder="Tax name"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Ad Valorem %)</label>
                    <div class="flex-1">
                        <input type="number" step="0.01" name="other_tax{{ $tn }}_ad_valorem" value="{{ old('other_tax'.$tn.'_ad_valorem') }}" placeholder="0.00"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Unit</label>
                    <div class="flex-1">
                        <input type="text" name="other_tax{{ $tn }}_rate_unit_id" value="{{ old('other_tax'.$tn.'_rate_unit_id') }}" placeholder="e.g. KGM"
                            class="w-full text-xs font-mono rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Tariff Rate (Specific) Baht</label>
                    <div class="flex-1">
                        <input type="number" step="0.01" name="other_tax{{ $tn }}_rate_baht" value="{{ old('other_tax'.$tn.'_rate_baht') }}" placeholder="0.00"
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

            </div>
        </div>
        @endforeach

        {{-- ─── Section: License 1–4 ─── --}}
        @foreach([1, 2, 3, 4] as $ln)
        <div>
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">License {{ $ln }}</h3>
            <div class="space-y-3">

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Name of License {{ $ln }}</label>
                    <div class="flex-1">
                        <select name="license{{ $ln }}_name_id" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <option value="">— None —</option>
                            @foreach($licenses as $lic)
                                <option value="{{ $lic->id }}" {{ old('license'.$ln.'_name_id') == $lic->id ? 'selected' : '' }}>
                                    {{ $lic->license_en ?? $lic->license_th }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Note</label>
                    <div class="flex-1">
                        <input type="text" name="license{{ $ln }}_note" value="{{ old('license'.$ln.'_note') }}" placeholder="License note..."
                            class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>

            </div>
        </div>
        @endforeach

        {{-- ─── Section: Reference Files ─── --}}
        <div>
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">Reference Files</h3>
            <div class="space-y-3">

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Catalog, SDS etc. 1</label>
                    <div class="flex-1">
                        <input type="file" name="reference_file_1" class="w-full text-xs">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Catalog, SDS etc. 2</label>
                    <div class="flex-1">
                        <input type="file" name="reference_file_2" class="w-full text-xs">
                    </div>
                </div>

            </div>
        </div>

        {{-- ─── Section: HS Evidence ─── --}}
        <div>
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200 pb-2 mb-4">HS Evidence</h3>
            <div class="space-y-3">

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Signed Requisition Form</label>
                    <div class="flex-1">
                        <input type="file" name="hs_evidence_file_1" class="w-full text-xs">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Email Evidence</label>
                    <div class="flex-1">
                        <input type="file" name="hs_evidence_file_2" class="w-full text-xs">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Advance Ruling</label>
                    <div class="flex-1">
                        <input type="file" name="hs_evidence_file_3" class="w-full text-xs">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-700">Customs Reference</label>
                    <div class="flex-1">
                        <input type="file" name="hs_evidence_file_4" class="w-full text-xs">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-500">Create Date</label>
                    <div class="flex-1 text-xs text-slate-400 italic">Auto-set to today</div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-52 shrink-0 text-xs font-semibold text-slate-500">Update Date</label>
                    <div class="flex-1 text-xs text-slate-400 italic">Auto-set on save</div>
                </div>

            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end space-x-3 pt-4 border-t border-slate-200">
            <a href="{{ route('clearance.index') }}" class="px-5 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Cancel</a>
            <button type="submit" class="px-6 py-2 text-xs font-semibold text-white bg-brand-700 hover:bg-brand-800 rounded-lg shadow-sm transition">
                Create Clearance Record
            </button>
        </div>

    </form>
</div>
@endsection
