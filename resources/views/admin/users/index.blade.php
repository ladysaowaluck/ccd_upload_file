{{-- Gemini : GAP-08 — Admin User Management Directory Index View --}}
@extends('layouts.app')

@section('header_title', 'User & Role Administration')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header Title -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
                <a href="{{ route('clearance.index') }}" class="hover:text-brand-600">Home</a>
                <span class="mx-2">/</span>
                <span class="text-slate-800 font-medium">Administration</span>
                <span class="mx-2">/</span>
                <span class="text-slate-800 font-medium">Users</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">System User Directory</h1>
            <p class="text-sm text-slate-500 mt-1">Manage user access rights, corporate email authorizations, and RBAC roles.</p>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Search User</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Username, name, or corporate email..."
                    class="w-full text-sm rounded-lg border-slate-300 focus:ring-brand-500 focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Filter by Role</label>
                <select name="role" class="w-full text-sm rounded-lg border-slate-300 focus:ring-brand-500 focus:border-brand-500">
                    <option value="">All Roles</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->slug }}" {{ request('role') == $r->slug ? 'selected' : '' }}>
                            {{ $r->name }} ({{ $r->slug }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition shadow-sm">
                    Filter Directory
                </button>
                <a href="{{ route('admin.users.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 py-2 px-3 rounded-lg text-sm transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider text-xs">
                    <tr>
                        <th class="px-6 py-3.5">User</th>
                        <th class="px-6 py-3.5">Email / Domain</th>
                        <th class="px-6 py-3.5">Organization / Dept</th>
                        <th class="px-6 py-3.5">Assigned Roles</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($users as $user)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-sm">
                                    {{ strtoupper(substr($user->name ?: $user->username, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $user->name ?: $user->username }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ $user->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-slate-800 font-mono text-xs">{{ $user->email }}</div>
                            <span class="inline-flex items-center text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-medium mt-0.5">
                                Verified Domain
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-600">
                            <div>{{ $user->department?->name ?? '-' }}</div>
                            <div class="text-slate-400 text-[11px]">{{ $user->office?->name ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->roles as $role)
                                    @if($role->slug === 'administrator')
                                        <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-700/20">
                                            {{ $role->name }}
                                        </span>
                                    @elseif($role->slug === 'manager')
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/20">
                                            {{ $role->name }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                            {{ $role->name }}
                                        </span>
                                    @endif
                                @empty
                                    <span class="text-xs text-slate-400 italic">No roles assigned</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.users.show', $user->id) }}" class="inline-flex items-center text-xs font-medium text-brand-600 hover:text-brand-900 bg-brand-50 hover:bg-brand-100 px-2.5 py-1.5 rounded-lg transition">
                                Manage Roles &rarr;
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                            No users matched your search criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

