@extends('layouts.app')

@section('title', 'Clearance Repository')
@section('header_title', 'Customer Clearance Data Repository')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <!-- Filter Section -->
    <div class="p-5 border-b border-slate-200 bg-slate-50/70">
        <form method="GET" action="{{ route('clearance.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Job Number</label>
                <input type="text" name="job_no" value="{{ request('job_no') }}" placeholder="CCD2304-..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">HS Code</label>
                <input type="text" name="hs_code" value="{{ request('hs_code') }}" placeholder="8-digit code..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Customer Name</label>
                <input type="text" name="customer_name" value="{{ request('customer_name') }}" placeholder="Search client..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Branch</label>
                <select name="branch_id" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->branch_name }} ({{ $b->branch_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-brand-700 hover:bg-brand-800 text-white font-semibold py-2 px-4 rounded-lg text-xs transition shadow-sm">
                    Search
                </button>
                <a href="{{ route('clearance.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium py-2 px-3 rounded-lg text-xs transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
            <thead class="bg-slate-100/75 text-slate-700 font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3.5">Job Ref</th>
                    <th class="px-4 py-3.5">Customer Name</th>
                    <th class="px-4 py-3.5">HS Code</th>
                    <th class="px-4 py-3.5">Product Description (EN / TH)</th>
                    <th class="px-4 py-3.5">Duty Rate</th>
                    <th class="px-4 py-3.5 text-center">Compliance</th>
                    <th class="px-4 py-3.5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($records as $item)
                <tr class="hover:bg-slate-50/90 transition">
                    <td class="px-4 py-3 font-mono font-semibold text-brand-700 whitespace-nowrap">
                        {{ $item->job_no }}
                    </td>
                    <td class="px-4 py-3 max-w-[200px] truncate text-slate-800 font-medium">
                        {{ $item->customer_name }}
                    </td>
                    <td class="px-4 py-3 font-mono text-slate-900 font-semibold whitespace-nowrap">
                        {{ $item->hs_code }}
                    </td>
                    <td class="px-4 py-3 max-w-sm">
                        <div class="text-slate-900 font-medium truncate">{{ $item->product_description_en }}</div>
                        <div class="text-[11px] text-slate-500 truncate mt-0.5">{{ $item->product_description_th }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                        {{ $item->tariff_ad_valorem ? $item->tariff_ad_valorem . '%' : '-' }}
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        @if($item->isMissingEvidence())
                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                Missing Evidence
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                Verified
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                        <a href="{{ route('clearance.show', $item->id) }}" class="inline-block bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-2.5 py-1 rounded text-[11px] font-medium transition">
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                        No clearance records found matching the filter criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($records->hasPages())
    <div class="p-4 border-t border-slate-200 bg-slate-50">
        {{ $records->links() }}
    </div>
    @endif
</div>
@endsection

