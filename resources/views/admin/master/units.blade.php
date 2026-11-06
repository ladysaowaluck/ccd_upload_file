{{-- Gemini : GAP-09 — Master Quantity Units Registry View --}}
@extends('layouts.app')

@section('header_title', 'Master Quantity Units Registry')

@section('content')
<div class="space-y-6">
    <!-- Header & Subnav -->
    <div>
        <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
            <a href="{{ route('clearance.index') }}" class="hover:text-brand-600">Home</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 font-medium">Administration</span>
            <span class="mx-2">/</span>
            <span class="text-slate-800 font-medium">Master Data</span>
            <span class="mx-2">/</span>
            <span class="text-slate-800 font-medium">Units</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Master Quantity Units</h1>
        <p class="text-sm text-slate-500 mt-1">Standardized customs units of measurement.</p>
    </div>

    <!-- Master Data Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6 text-sm font-medium">
            <a href="{{ route('admin.customers.index') }}" class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Customers
            </a>
            <a href="{{ route('admin.master.branches') }}" class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Branches
            </a>
            <a href="{{ route('admin.master.units') }}" class="py-2.5 px-1 border-b-2 border-brand-600 text-brand-600 font-semibold">
                Units
            </a>
            <a href="{{ route('admin.master.statistics') }}" class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Statistical Codes
            </a>
            <a href="{{ route('admin.master.licenses') }}" class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Customs Licenses
            </a>
        </nav>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <form method="GET" action="{{ route('admin.master.units') }}" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by unit name or symbol..."
                    class="w-full text-sm rounded-lg border-slate-300 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2 px-5 rounded-lg text-sm transition">
                    Search
                </button>
                <a href="{{ route('admin.master.units') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 py-2 px-4 rounded-lg text-sm transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider text-xs">
                    <tr>
                        <th class="px-6 py-3.5">ID</th>
                        <th class="px-6 py-3.5">Unit Symbol / Code</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($units as $unit)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-6 py-3.5 text-xs text-slate-400 font-mono">#{{ $unit->id }}</td>
                        <td class="px-6 py-3.5 font-mono font-semibold text-slate-900">{{ $unit->unit }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="px-6 py-12 text-center text-slate-400">
                            No measurement units found matching your search.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($units->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $units->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

