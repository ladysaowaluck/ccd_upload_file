@extends('layouts.app')

@section('title', 'Register Clearance')
@section('header_title', 'Register New Customs Clearance Record')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
    <form method="POST" action="{{ route('clearance.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="border-b border-slate-200 pb-4">
            <h3 class="text-sm font-bold text-slate-800">Job Identification & Client Details</h3>
            <p class="text-xs text-slate-500">Auto-generated job sequence and master branch assignment.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Job Number <span class="text-rose-500">*</span></label>
                <input type="text" name="job_no" value="{{ old('job_no', $suggestedJobNo) }}" required class="w-full text-xs font-mono font-bold rounded-lg border border-slate-300 px-3 py-2 bg-slate-50 text-brand-800 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Customer <span class="text-rose-500">*</span></label>
                <select name="master_customer_id" required class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Select Corporate Client...</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ old('master_customer_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Branch Office <span class="text-rose-500">*</span></label>
                <select name="master_branch_id" required class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">Select Branch...</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('master_branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->branch_name }} ({{ $b->branch_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Declaration Type <span class="text-rose-500">*</span></label>
                <select name="imp_exp" required class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="0" {{ old('imp_exp', '0') == '0' ? 'selected' : '' }}>Import (นำเข้า)</option>
                    <option value="1" {{ old('imp_exp') == '1' ? 'selected' : '' }}>Export (ส่งออก)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Product Code</label>
                <input type="text" name="product_code_1" value="{{ old('product_code_1') }}" placeholder="Part or SKU Code..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">HS Code <span class="text-rose-500">*</span></label>
                <input type="text" name="hs_code" value="{{ old('hs_code') }}" required placeholder="8-digit HS Code..." class="w-full text-xs font-mono font-bold rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
        </div>

        <div class="border-b border-slate-200 pb-4 pt-2">
            <h3 class="text-sm font-bold text-slate-800">Product Technical Description</h3>
            <p class="text-xs text-slate-500">Provide official English and Thai descriptions for customs declaration.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Product Description (English) <span class="text-rose-500">*</span></label>
                <textarea name="product_description_en" required rows="3" class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('product_description_en') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Product Description (Thai)</label>
                <textarea name="product_description_th" rows="3" class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('product_description_th') }}</textarea>
            </div>
        </div>

        <div class="border-b border-slate-200 pb-4 pt-2">
            <h3 class="text-sm font-bold text-slate-800">Tariff & Quantity Units</h3>
        </div>

        {{-- Lady : Unconstrained text inputs allowed entering strings longer than the database varchar(3) limits (e.g. 'C6213'), triggering SQLSTATE[22001] string truncation errors. --}}
        {{--
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Statistical Code</label>
                <input type="text" name="statistic_code" value="{{ old('statistic_code', '000') }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity Unit</label>
                <input type="text" name="quantity_unit" value="{{ old('quantity_unit', 'C62') }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Ad Valorem Rate (%)</label>
                <input type="number" step="0.01" name="tariff_ad_valorem" value="{{ old('tariff_ad_valorem') }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Privilege Code</label>
                <input type="text" name="privilege_code" value="{{ old('privilege_code', '000') }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>
        --}}
        {{-- Gemini : Add strict maxlength='3' and uppercase styling to conform to database column limits --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Statistical Code</label>
                <input type="text" name="statistic_code" value="{{ old('statistic_code', '000') }}" maxlength="3" placeholder="000" class="w-full text-xs uppercase font-mono rounded-lg border border-slate-300 px-3 py-2">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Max 3 chars (e.g. 000, 001)</span>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity Unit</label>
                <input type="text" name="quantity_unit" value="{{ old('quantity_unit', 'C62') }}" maxlength="3" placeholder="C62" class="w-full text-xs uppercase font-mono rounded-lg border border-slate-300 px-3 py-2">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Max 3 chars (e.g. C62, KGM, LTR)</span>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Ad Valorem Rate (%)</label>
                <input type="number" step="0.01" name="tariff_ad_valorem" value="{{ old('tariff_ad_valorem') }}" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Privilege Code</label>
                <input type="text" name="privilege_code" value="{{ old('privilege_code', '000') }}" maxlength="3" placeholder="000" class="w-full text-xs uppercase font-mono rounded-lg border border-slate-300 px-3 py-2">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Max 3 chars (e.g. 000)</span>
            </div>
        </div>

        <div class="border-b border-slate-200 pb-4 pt-2">
            <h3 class="text-sm font-bold text-slate-800">Initial Document Attachments</h3>
            <p class="text-xs text-slate-500">Optional during creation; upload signed requisition or catalog if available.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Reference File 1 (Catalog / SDS)</label>
                <input type="file" name="reference_file_1" class="w-full text-xs">
            </div>
            <div class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                <label class="block text-xs font-semibold text-slate-700 mb-1">HS Evidence File 1 (Signed Requisition)</label>
                <input type="file" name="hs_evidence_file_1" class="w-full text-xs">
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-4">
            <a href="{{ route('clearance.index') }}" class="px-5 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Cancel</a>
            <button type="submit" class="px-6 py-2 text-xs font-semibold text-white bg-brand-700 hover:bg-brand-800 rounded-lg shadow-sm transition">
                Create Clearance Record
            </button>
        </div>
    </form>
</div>
@endsection

