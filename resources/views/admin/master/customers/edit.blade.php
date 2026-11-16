@extends('layouts.app')

@section('title', 'Edit Customer - ' . $customer->company_name)
@section('header_title', 'Edit Master Customer')

@section('content')
 <!-- Edit form for master customer — auto-syncs name on save if changed -->
<div class="space-y-6 max-w-2xl">
    <div>
        <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
            <a href="{{ route('clearance.index') }}" class="hover:text-brand-600">Home</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 font-medium">Administration</span>
            <span class="mx-2">/</span>
            <a href="{{ route('admin.master.customers.index') }}" class="hover:text-brand-600">Master Customers</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 font-medium">Edit #{{ $customer->id }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Customer</h1>
        <p class="text-sm text-slate-500 mt-1">Update customer details or toggle active status.</p>
    </div>

    @if($errors->any())
        <div class="px-4 py-3 rounded-lg bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
        <form method="POST" action="{{ route('admin.master.customers.update', $customer->id) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-4">
                <label class="w-32 shrink-0 text-xs font-semibold text-slate-700">Company Name <span class="text-rose-500">*</span></label>
                <div class="flex-1">
                    <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}" required
                        class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('company_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="w-32 shrink-0 text-xs font-semibold text-slate-700">Tax ID</label>
                <div class="flex-1">
                    <input type="text" name="tax_id" value="{{ old('tax_id', $customer->tax_id) }}" placeholder="13-digit Tax ID"
                        class="w-full text-xs font-mono rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('tax_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="w-32 shrink-0 text-xs font-semibold text-slate-700">Status <span class="text-rose-500">*</span></label>
                <div class="flex-1">
                    <select name="is_enable" required class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="0" {{ old('is_enable', $customer->is_enable) == '0' ? 'selected' : '' }}>Enabled (Active)</option>
                        <option value="1" {{ old('is_enable', $customer->is_enable) == '1' ? 'selected' : '' }}>Disabled</option>
                    </select>
                </div>
            </div>

            <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                If you change the company name, it will automatically sync to all linked customs clearance records.
            </p>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="{{ route('admin.master.customers.index') }}"
                    class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Cancel</a>
                <button type="submit"
                    class="px-5 py-2 text-xs font-semibold text-white bg-brand-700 hover:bg-brand-800 rounded-lg transition">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
