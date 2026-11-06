@extends('layouts.app')

@section('title', 'Duplicate CCD Record')
@section('header_title', 'Duplicate Clearance Record')

@section('content')
<div class="max-w-xl mx-auto space-y-6">

    {{-- Source record summary --}}
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 text-sm text-slate-700">
        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Duplicating From</div>
        <div class="grid grid-cols-2 gap-y-1.5 text-xs">
            <span class="text-slate-500">Job Number</span>
            <span class="font-mono font-bold text-brand-700">{{ $source->job_no }}</span>

            <span class="text-slate-500">Customer</span>
            <span class="font-medium">{{ $source->customer_name }}</span>

            <span class="text-slate-500">HS Code</span>
            <span class="font-mono">{{ $source->hs_code }}</span>

            <span class="text-slate-500">Product (EN)</span>
            <span class="truncate">{{ Str::limit($source->product_description_en, 60) }}</span>
        </div>
        <div class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            All tariff classification fields will be copied. File attachments (reference &amp; evidence) will be blank on the new record.
        </div>
    </div>

    {{-- Replication form --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="text-sm font-semibold text-slate-800 mb-5">New Record Details</div>

        <form method="POST" action="{{ route('clearance.replicate.execute', $source->id) }}" class="space-y-5">
            @csrf

            {{-- New job number --}}
            <div>
                <label for="new_job_no" class="block text-xs font-semibold text-slate-700 mb-1">
                    New Job Number <span class="text-rose-500">*</span>
                </label>
                <input type="text"
                       id="new_job_no"
                       name="new_job_no"
                       value="{{ old('new_job_no', $suggestedNo) }}"
                       required
                       maxlength="12"
                       placeholder="e.g. CCD2611-0001"
                       class="w-full font-mono text-sm rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none @error('new_job_no') border-rose-400 @enderror">
                @error('new_job_no')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-400">Format: CCD/CST/NTB + YYMM + - + 4 digits (12 chars total)</p>
            </div>

            {{-- New product code 1 (optional override) --}}
            <div>
                <label for="new_product_code_1" class="block text-xs font-semibold text-slate-700 mb-1">
                    New Product Code 1
                    <span class="font-normal text-slate-400">(optional — leave blank to clear)</span>
                </label>
                <input type="text"
                       id="new_product_code_1"
                       name="new_product_code_1"
                       value="{{ old('new_product_code_1') }}"
                       maxlength="100"
                       class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                @error('new_product_code_1')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- New product code 2 (optional override) --}}
            <div>
                <label for="new_product_code_2" class="block text-xs font-semibold text-slate-700 mb-1">
                    New Product Code 2
                    <span class="font-normal text-slate-400">(optional)</span>
                </label>
                <input type="text"
                       id="new_product_code_2"
                       name="new_product_code_2"
                       value="{{ old('new_product_code_2') }}"
                       maxlength="100"
                       class="w-full text-sm rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            {{-- Action buttons --}}
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <a href="{{ route('clearance.show', $source->id) }}"
                   class="text-xs text-slate-600 hover:text-slate-800 font-medium px-3 py-2 rounded-lg hover:bg-slate-100 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="bg-brand-700 hover:bg-brand-800 text-white text-xs font-semibold px-5 py-2 rounded-lg transition">
                    Duplicate Record
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

