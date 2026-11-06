@extends('layouts.app')

@section('title', 'Corporate Customer Registry')
@section('header_title', 'Master Corporate Customer Registry')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <!-- Filter Bar -->
    <div class="p-5 border-b border-slate-200 bg-slate-50/70">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Company Name</label>
                <input type="text" name="company_name" value="{{ request('company_name') }}" placeholder="Search company..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tax ID</label>
                <input type="text" name="tax_id" value="{{ request('tax_id') }}" placeholder="13-digit Tax ID..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                <select name="is_enable" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white">
                    <option value="">All Statuses</option>
                    <option value="0" {{ request('is_enable') === '0' ? 'selected' : '' }}>Enabled (Active)</option>
                    <option value="1" {{ request('is_enable') === '1' ? 'selected' : '' }}>Disabled</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-brand-700 hover:bg-brand-800 text-white font-semibold py-2 px-4 rounded-lg text-xs transition">Search</button>
                <a href="{{ route('admin.customers.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 py-2 px-3 rounded-lg text-xs transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
            <thead class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3.5">ID</th>
                    <th class="px-4 py-3.5">Company Name</th>
                    <th class="px-4 py-3.5">Tax ID</th>
                    <th class="px-4 py-3.5 text-center">Status</th>
                    <th class="px-4 py-3.5 text-right">Name Synchronization</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($customers as $item)
                <tr class="hover:bg-slate-50/90 transition">
                    <td class="px-4 py-3 font-mono text-slate-500">{{ $item->id }}</td>
                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $item->company_name }}</td>
                    <td class="px-4 py-3 font-mono text-slate-600">{{ $item->tax_id ?: '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($item->is_enable == 0)
                            <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20">Enabled</span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-400/20">Disabled</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('admin.customers.sync') }}" class="inline-block" onsubmit="return confirm('Harmonize customer name across all historical clearance records?')">
                            @csrf
                            <input type="hidden" name="master_customer_id" value="{{ $item->id }}">
                            <button type="submit" class="text-brand-600 hover:text-brand-900 text-xs font-semibold">
                                Sync to Records →
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-slate-400">No master customers found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
    <div class="p-4 border-t border-slate-200 bg-slate-50">
        {{ $customers->links() }}
    </div>
    @endif
</div>
@endsection

