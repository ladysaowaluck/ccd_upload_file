@extends('layouts.app')

@section('title', 'Edit Clearance Record - ' . $record->job_no)
@section('header_title', 'Edit Customs Clearance Record (' . $record->job_no . ')')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8 max-w-5xl mx-auto">
    <form method="POST" action="{{ route('clearance.update', $record->id) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-800">Job Identification & Client Details</h3>
                <p class="text-xs text-slate-500">Update clearance record specifications and classification metadata.</p>
            </div>
            <span class="font-mono text-xs font-bold text-brand-700 bg-brand-50 px-3 py-1.5 rounded-lg border border-brand-200">
                {{ $record->job_no }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Job Number <span class="text-rose-500">*</span></label>
                <input type="text" name="job_no" value="{{ old('job_no', $record->job_no) }}" required class="w-full text-xs font-mono font-bold rounded-lg border border-slate-300 px-3 py-2 bg-slate-50 text-brand-800 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                @error('job_no') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Customer <span class="text-rose-500">*</span></label>
                <select name="master_customer_id" required class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ old('master_customer_id', $record->master_customer_id) == $c->id ? 'selected' : '' }}>
                            {{ $c->company_name }}
                        </option>
                    @endforeach
                </select>
                @error('master_customer_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Branch Office <span class="text-rose-500">*</span></label>
                <select name="master_branch_id" required class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('master_branch_id', $record->master_branch_id) == $b->id ? 'selected' : '' }}>
                            {{ $b->branch_name }} ({{ $b->branch_code }})
                        </option>
                    @endforeach
                </select>
                @error('master_branch_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Declaration Type <span class="text-rose-500">*</span></label>
                <select name="imp_exp" required class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="0" {{ old('imp_exp', $record->imp_exp) == '0' ? 'selected' : '' }}>Import (นำเข้า)</option>
                    <option value="1" {{ old('imp_exp', $record->imp_exp) == '1' ? 'selected' : '' }}>Export (ส่งออก)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Product Code</label>
                <input type="text" name="product_code_1" value="{{ old('product_code_1', $record->product_code_1) }}" placeholder="Part or SKU Code..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Brand Name</label>
                <input type="text" name="brand_name" value="{{ old('brand_name', $record->brand_name) }}" placeholder="Brand / Maker..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">HS Code (8 digits) <span class="text-rose-500">*</span></label>
                <input type="text" name="hs_code" value="{{ old('hs_code', $record->hs_code) }}" required class="w-full text-xs font-mono font-bold rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                @error('hs_code') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Sequence Code</label>
                <input type="text" name="sequence_code" value="{{ old('sequence_code', $record->sequence_code) }}" class="w-full text-xs font-mono rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Country of Origin</label>
                <input type="text" name="country_of_origin" value="{{ old('country_of_origin', $record->country_of_origin) }}" placeholder="e.g. JAPAN, THAILAND" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
        </div>

        <div class="border-b border-slate-200 pb-4 pt-2">
            <h3 class="text-sm font-bold text-slate-800">Product Technical Description & Composition</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Product Description (English) <span class="text-rose-500">*</span></label>
                <textarea name="product_description_en" required rows="3" class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('product_description_en', $record->product_description_en) }}</textarea>
                @error('product_description_en') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Product Description (Thai)</label>
                <textarea name="product_description_th" rows="3" class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('product_description_th', $record->product_description_th) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Materials / Composition</label>
                <input type="text" name="materials" value="{{ old('materials', $record->materials) }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Usage / Application</label>
                <input type="text" name="usage" value="{{ old('usage', $record->usage) }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Function Description</label>
                <textarea name="function" rows="2" class="w-full text-xs rounded-lg border border-slate-300 p-2.5">{{ old('function', $record->function) }}</textarea>
            </div>
        </div>

        <div class="border-b border-slate-200 pb-4 pt-2">
            <h3 class="text-sm font-bold text-slate-800">Tariff Rates & Customs Quantities</h3>
        </div>

        {{--
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Statistical Code</label>
                <input type="text" name="statistic_code" value="{{ old('statistic_code', $record->statistic_code) }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity Unit</label>
                <input type="text" name="quantity_unit" value="{{ old('quantity_unit', $record->quantity_unit) }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tariff Rate Ad Valorem (%)</label>
                <input type="number" step="0.01" name="tariff_ad_valorem" value="{{ old('tariff_ad_valorem', $record->tariff_ad_valorem) }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Privilege Code</label>
                <input type="text" name="privilege_code" value="{{ old('privilege_code', $record->privilege_code) }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>
        --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Statistical Code</label>
                <input type="text" name="statistic_code" value="{{ old('statistic_code', $record->statistic_code) }}" maxlength="3" class="w-full text-xs uppercase font-mono rounded-lg border border-slate-300 px-3 py-2">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Max 3 chars (e.g. 000, 001)</span>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity Unit</label>
                <input type="text" name="quantity_unit" value="{{ old('quantity_unit', $record->quantity_unit) }}" maxlength="3" class="w-full text-xs uppercase font-mono rounded-lg border border-slate-300 px-3 py-2">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Max 3 chars (e.g. C62, KGM, LTR)</span>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tariff Rate Ad Valorem (%)</label>
                <input type="number" step="0.01" name="tariff_ad_valorem" value="{{ old('tariff_ad_valorem', $record->tariff_ad_valorem) }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Privilege Code</label>
                <input type="text" name="privilege_code" value="{{ old('privilege_code', $record->privilege_code) }}" maxlength="3" class="w-full text-xs uppercase font-mono rounded-lg border border-slate-300 px-3 py-2">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Max 3 chars (e.g. 000)</span>
            </div>
        </div>

        <!-- Document Attachments Update -->
        <div class="border-b border-slate-200 pb-4 pt-2">
            <h3 class="text-sm font-bold text-slate-800">Attached Documents</h3>
            <p class="text-xs text-slate-500">Upload replacement files or attach missing evidence documents.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                <div class="flex justify-between items-center mb-1">
                    <label class="text-xs font-semibold text-slate-700">Reference File 1</label>
                    @if($record->reference_file_1)
                        <span class="text-[10px] text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Current File Exists</span>
                    @endif
                </div>
                <input type="file" name="reference_file_1" class="w-full text-xs">
            </div>

            <div class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                <div class="flex justify-between items-center mb-1">
                    <label class="text-xs font-semibold text-slate-700">Reference File 2</label>
                    @if($record->reference_file_2)
                        <span class="text-[10px] text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Current File Exists</span>
                    @endif
                </div>
                <input type="file" name="reference_file_2" class="w-full text-xs">
            </div>

            <div class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                <div class="flex justify-between items-center mb-1">
                    <label class="text-xs font-semibold text-slate-700">HS Evidence File 1</label>
                    @if($record->hs_evidence_file_1)
                        <span class="text-[10px] text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Current File Exists</span>
                    @else
                        <span class="text-[10px] text-rose-600 font-semibold bg-rose-50 px-2 py-0.5 rounded border border-rose-200">Missing Evidence</span>
                    @endif
                </div>
                <input type="file" name="hs_evidence_file_1" class="w-full text-xs">
            </div>

            <div class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                <div class="flex justify-between items-center mb-1">
                    <label class="text-xs font-semibold text-slate-700">HS Evidence File 2</label>
                    @if($record->hs_evidence_file_2)
                        <span class="text-[10px] text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Current File Exists</span>
                    @endif
                </div>
                <input type="file" name="hs_evidence_file_2" class="w-full text-xs">
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-4 border-t border-slate-200">
            <a href="{{ route('clearance.show', $record->id) }}" class="px-5 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Cancel</a>
            <button type="submit" class="px-6 py-2 text-xs font-semibold text-white bg-brand-700 hover:bg-brand-800 rounded-lg shadow-sm transition">
                Update Clearance Record
            </button>
        </div>
    </form>
</div>
@endsection

