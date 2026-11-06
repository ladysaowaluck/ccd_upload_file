@extends('layouts.app')

@section('title', 'Weekly HS Code Export')
@section('header_title', 'Weekly Customs Tariff & HS Code Reports')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-200 p-8 space-y-6">
    <div class="border-b border-slate-100 pb-4">
        <h3 class="text-base font-bold text-slate-900">Generate Weekly HS Code Export (.xlsx)</h3>
        <p class="text-xs text-slate-500 mt-1">Export customs clearance records filtered by Job Number sequence range into an organized Excel report containing a Customer Ranking sheet and an HS Record detail sheet.</p>
    </div>

    @if(session('warning'))
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center space-x-3">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('export.weekly.post') }}" class="space-y-5">
        @csrf

        <!-- Category -->
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Job Prefix Category</label>
            <div class="grid grid-cols-3 gap-3">
                <label class="flex items-center justify-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 text-xs font-semibold text-slate-700 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700 transition">
                    <input type="radio" name="category" value="CCD" {{ old('category', 'CCD') === 'CCD' ? 'checked' : '' }} class="hidden">
                    <span>CCD (Standard)</span>
                </label>
                <label class="flex items-center justify-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 text-xs font-semibold text-slate-700 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700 transition">
                    <input type="radio" name="category" value="CST" {{ old('category') === 'CST' ? 'checked' : '' }} class="hidden">
                    <span>CST</span>
                </label>
                <label class="flex items-center justify-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 text-xs font-semibold text-slate-700 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700 transition">
                    <input type="radio" name="category" value="NTB" {{ old('category') === 'NTB' ? 'checked' : '' }} class="hidden">
                    <span>NTB</span>
                </label>
            </div>
            @error('category')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Target Month -->
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Target Month (YYYY-MM)</label>
            <input type="month" name="year_month" value="{{ old('year_month', $defaultMonth) }}" required class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            @error('year_month')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Sequence Range -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Sequence From</label>
                <div class="relative">
                    <input type="number" name="date_from" value="{{ old('date_from', 1) }}" min="1" max="9999" required placeholder="1" class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-mono">e.g. 1 (0001)</span>
                </div>
                @error('date_from')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Sequence To</label>
                <div class="relative">
                    <input type="number" name="date_to" value="{{ old('date_to', 50) }}" min="1" max="9999" required placeholder="50" class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-mono">e.g. 50 (0050)</span>
                </div>
                @error('date_to')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Report Specification Info -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1">
            <div class="font-bold text-slate-700">Workbook Structure:</div>
            <div>• <strong>Sheet 1 (Ranking):</strong> Aggregated job volume ranking grouped by Customer Name.</div>
            <div>• <strong>Sheet 2 (HS Record):</strong> Complete customs clearance fields and evidence status.</div>
            <div>• <strong>Output Format:</strong> <code class="font-mono bg-slate-200 px-1 py-0.5 rounded text-slate-800">HS_Code{MM}-{From}_{MM}-{To}.xlsx</code></div>
        </div>

        <div class="flex justify-end pt-3">
            <button type="submit" class="py-2.5 px-6 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Download Weekly Report</span>
            </button>
        </div>
    </form>
</div>
@endsection

