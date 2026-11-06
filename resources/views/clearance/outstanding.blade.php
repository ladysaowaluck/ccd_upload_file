@extends('layouts.app')

@section('title', 'Outstanding Auditing')
@section('header_title', 'Outstanding Customs Evidence Compliance')

@section('content')
<div class="space-y-6">
    <!-- Notice Card -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start space-x-3 text-amber-900 text-xs">
        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <div>
            <div class="font-bold">Compliance Rule Active:</div>
            <div>The records below have <strong>no attached HS evidence files</strong> (slots 1, 2, and 3 are empty). To prevent compliance fines and customs audit issues, upload official signed requisition documents, SDS, or customs advance rulings.</div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/70 flex justify-between items-center">
            <div class="text-xs font-semibold text-slate-700">
                Outstanding Records ({{ $records->total() }} pending verification)
            </div>
            <form method="GET" action="{{ route('compliance.outstanding') }}" class="flex space-x-2">
                <input type="text" name="job_no" value="{{ request('job_no') }}" placeholder="Filter Job No..." class="text-xs rounded-lg border border-slate-300 px-3 py-1.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white text-xs px-3 py-1.5 rounded-lg font-medium transition">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">Job Ref</th>
                        <th class="px-4 py-3.5">Customer Name</th>
                        <th class="px-4 py-3.5">HS Code</th>
                        <th class="px-4 py-3.5">Product Description (EN)</th>
                        <th class="px-4 py-3.5">Create Date</th>
                        <th class="px-4 py-3.5 text-right">Upload Evidence</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($records as $item)
                    <tr class="hover:bg-slate-50/90 transition" x-data="{ uploadModal: false }">
                        <td class="px-4 py-3 font-mono font-semibold text-rose-700 whitespace-nowrap">
                            {{ $item->job_no }}
                        </td>
                        <td class="px-4 py-3 max-w-[200px] truncate text-slate-800 font-medium">
                            {{ $item->customer_name }}
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-900 font-semibold whitespace-nowrap">
                            {{ $item->hs_code }}
                        </td>
                        <td class="px-4 py-3 max-w-sm truncate text-slate-700">
                            {{ $item->product_description_en }}
                        </td>
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                            {{ $item->create_date ?: '-' }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button @click="uploadModal = true" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 px-2.5 py-1 rounded text-[11px] font-semibold transition">
                                + Attach File
                            </button>

                            <!-- Modal -->
                            <div x-show="uploadModal" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4" style="display: none;">
                                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left" @click.outside="uploadModal = false">
                                    <div class="font-bold text-sm text-slate-900 mb-2">Upload Evidence for {{ $item->job_no }}</div>
                                    <form method="POST" action="{{ route('compliance.outstanding.attach', $item->id) }}" enctype="multipart/form-data" class="space-y-4">
                                        @csrf
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 mb-1">Target Evidence Slot</label>
                                            <select name="evidence_slot" class="w-full text-xs rounded-lg border border-slate-300 p-2 bg-white">
                                                <option value="1">HS Evidence File 1 (Signed Requisition)</option>
                                                <option value="2">HS Evidence File 2 (Advance Ruling / Ruling Letter)</option>
                                                <option value="3">HS Evidence File 3 (Customs Reference)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 mb-1">File (PDF, Image, Excel)</label>
                                            <input type="file" name="evidence_file" required class="w-full text-xs border border-slate-300 rounded-lg p-2 bg-slate-50">
                                        </div>
                                        <div class="flex justify-end space-x-2 pt-2">
                                            <button type="button" @click="uploadModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
                                            <button type="submit" class="px-4 py-1.5 text-xs bg-brand-700 hover:bg-brand-800 text-white font-semibold rounded-lg">Upload</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                            Great! No outstanding clearance records found without evidence.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $records->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

