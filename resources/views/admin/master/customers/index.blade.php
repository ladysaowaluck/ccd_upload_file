@extends('layouts.app')

@section('title', 'Corporate Customer Registry')
@section('header_title', 'Master Corporate Customer Registry')

@section('content')
<!-- Master Customers Registry (moved to /admin/master/customers) with unified subnav tabs and full CRUD -->
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
            <span class="text-slate-800 font-medium">Customers</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Master Customers</h1>
        <p class="text-sm text-slate-500 mt-1">Corporate clients and customs clearance account holders.</p>
    </div>

    <!-- Master Data Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6 text-sm font-medium">
            <a href="{{ route('admin.master.customers.index') }}"
                class="py-2.5 px-1 border-b-2 border-brand-600 text-brand-600 font-semibold">
                Customers
            </a>
            <a href="{{ route('admin.master.branches') }}"
                class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Branches
            </a>
            <a href="{{ route('admin.master.units') }}"
                class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Units
            </a>
            <a href="{{ route('admin.master.statistics') }}"
                class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Statistical Codes
            </a>
            <a href="{{ route('admin.master.licenses') }}"
                class="py-2.5 px-1 border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                Customs Licenses
            </a>
        </nav>
    </div>

    <!-- Flash Messages -->
    <!-- @if(session('success'))
        <div class="px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="px-4 py-3 rounded-lg bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="px-4 py-3 rounded-lg bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif -->

    <!-- Filter Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <form method="GET" action="{{ route('admin.master.customers.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Company Name</label>
                <input type="text" name="company_name" value="{{ request('company_name') }}" placeholder="Search company..."
                    class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tax ID</label>
                <input type="text" name="tax_id" value="{{ request('tax_id') }}" placeholder="13-digit Tax ID..."
                    class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                <select name="is_enable" class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="0" {{ request('is_enable') === '0' ? 'selected' : '' }}>Enabled (Active)</option>
                    <option value="1" {{ request('is_enable') === '1' ? 'selected' : '' }}>Disabled</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-brand-700 hover:bg-brand-800 text-white font-semibold py-2 px-4 rounded-lg text-xs transition">Search</button>
                <a href="{{ route('admin.master.customers.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 py-2 px-3 rounded-lg text-xs transition">Reset</a>
                <button type="button" onclick="document.getElementById('createModal').classList.remove('hidden')"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg text-xs transition whitespace-nowrap">
                    + New Customer
                </button>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">ID</th>
                        <th class="px-4 py-3.5">Company Name</th>
                        <th class="px-4 py-3.5">Tax ID</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($customers as $item)
                    <tr class="hover:bg-slate-50/90 transition">
                        <td class="px-4 py-3 font-mono text-slate-500">#{{ $item->id }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $item->company_name }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $item->tax_id ?: '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($item->is_enable == 0)
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20">Enabled</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-400/20">Disabled</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                 <!-- Sync -->
                                <form method="POST" action="{{ route('admin.master.customers.sync') }}" class="inline-block mr-1"
                                    onsubmit="return confirm('Sync name to all linked clearance records?')">
                                    @csrf
                                    <input type="hidden" name="master_customer_id" value="{{ $item->id }}">
                                    <button type="submit" class="text-brand-600 hover:text-brand-900 text-xs font-semibold px-1 py-1 rounded hover:bg-brand-50 transition" title="Harmonize customer name across historical clearance records">
                                        Sync →
                                    </button>
                                </form>
                                <!-- Edit -->
                                <a href="{{ route('admin.master.customers.edit', $item->id) }}"
                                    class="p-1.5 text-slate-500 hover:text-brand-700 hover:bg-slate-100 rounded-lg transition inline-flex items-center justify-center"
                                    title="Edit Customer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </a>
                                <!-- Delete -->
                                <form method="POST" action="{{ route('admin.master.customers.destroy', $item->id) }}" class="inline-block"
                                    onsubmit="return confirm('Delete {{ addslashes($item->company_name) }}? This will fail if clearance records are still linked.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition inline-flex items-center justify-center"
                                        title="Delete Customer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-slate-400">No master customers found matching your search.</td>
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
</div>

<!-- Create Customer Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md mx-4 p-6">
        <h3 class="text-sm font-bold text-slate-800 mb-4">New Customer</h3>
        <form method="POST" action="{{ route('admin.master.customers.store') }}" class="space-y-4">
            @csrf
            <!-- "_form" identifies the submitted modal so errors/old values are shown only there. -->
            <input type="hidden" name="_form" value="createModal">

            <div class="flex items-center gap-4">
                <label class="w-28 shrink-0 text-xs font-semibold text-slate-700">Company Name <span class="text-rose-500">*</span></label>
                <div class="flex-1">
                    <input type="text" name="company_name" required placeholder="Full company name"
                        value="{{ old('_form') === 'createModal' ? old('company_name') : '' }}"
                        class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <x-form-error field="company_name" form="createModal" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="w-28 shrink-0 text-xs font-semibold text-slate-700">Tax ID</label>
                <div class="flex-1">
                    <input type="text" name="tax_id" placeholder="13-digit Tax ID"
                        value="{{ old('_form') === 'createModal' ? old('tax_id') : '' }}"
                        class="w-full text-xs font-mono rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <x-form-error field="tax_id" form="createModal" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="w-28 shrink-0 text-xs font-semibold text-slate-700">Status <span class="text-rose-500">*</span></label>
                <div class="flex-1">
                    <select name="is_enable" required class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        {{-- <option value="0">Enabled (Active)</option> --}}
                        {{-- <option value="1">Disabled</option> --}}
                        <option value="0">Enabled (Active)</option>
                        <option value="1" {{ old('_form') === 'createModal' && old('is_enable') === '1' ? 'selected' : '' }}>Disabled</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-slate-200">
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                    class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Cancel</button>
                <button type="submit"
                    class="px-5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition">Create</button>
            </div>
        </form>
    </div>
</div>

<x-reopen-modal-on-error />
@endsection
