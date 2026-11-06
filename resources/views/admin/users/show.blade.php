{{-- Gemini : GAP-08 — Admin User Profile and Role Management View --}}
@extends('layouts.app')

@section('header_title', 'User Details — ' . ($user->name ?: $user->username))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center justify-between">
        <nav class="flex text-xs text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('clearance.index') }}" class="hover:text-brand-600">Home</a>
            <span class="mx-2">/</span>
            <a href="{{ route('admin.users.index') }}" class="hover:text-brand-600">Users</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 font-medium">{{ $user->username }}</span>
        </nav>
        <a href="{{ route('admin.users.index') }}" class="text-xs text-brand-600 hover:underline font-medium">
            &larr; Back to User Directory
        </a>
    </div>

    <!-- User Profile Overview Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-xl shadow-sm">
                    {{ strtoupper(substr($user->name ?: $user->username, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">{{ $user->name ?: $user->username }}</h2>
                    <p class="text-xs text-slate-500 font-mono">User ID: #{{ $user->id }} &bull; Username: {{ $user->username }}</p>
                </div>
            </div>
            <div>
                @if($user->isAdmin())
                    <span class="inline-flex items-center rounded-full bg-purple-50 px-3 py-1 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-700/20">
                        Administrator
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                        Standard User
                    </span>
                @endif
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <dt class="text-xs font-medium text-slate-500">Corporate Email</dt>
                <dd class="mt-1 font-mono text-slate-900">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Office Location</dt>
                <dd class="mt-1 text-slate-900">{{ $user->office?->name ?? 'Not Assigned' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Department</dt>
                <dd class="mt-1 text-slate-900">{{ $user->department?->name ?? 'Not Assigned' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Division</dt>
                <dd class="mt-1 text-slate-900">{{ $user->division?->name ?? 'Not Assigned' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Account Created</dt>
                <dd class="mt-1 text-slate-900">{{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-slate-500">Last Modified</dt>
                <dd class="mt-1 text-slate-900">{{ $user->updated_at ? $user->updated_at->format('Y-m-d H:i') : '-' }}</dd>
            </div>
        </div>
    </div>

    <!-- Role Assignment Form Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50">
            <h3 class="text-base font-semibold text-slate-900">Role-Based Access Control (RBAC) Assignment</h3>
            <p class="text-xs text-slate-500 mt-0.5">Toggle system permissions and roles assigned to this account.</p>
        </div>

        <form method="POST" action="{{ route('admin.users.roles.update', $user->id) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="space-y-3">
                @foreach ($allRoles as $role)
                    @php
                        $isAssigned = $user->roles->contains('id', $role->id);
                    @endphp
                    <label class="flex items-start p-3 rounded-lg border border-slate-200 hover:bg-slate-50 transition cursor-pointer">
                        <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" {{ $isAssigned ? 'checked' : '' }}
                            class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <div class="ml-3">
                            <span class="text-sm font-semibold text-slate-800">{{ $role->name }}</span>
                            <span class="ml-2 font-mono text-xs text-slate-400">({{ $role->slug }})</span>
                            @if($role->permissions && $role->permissions->count())
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach($role->permissions->take(5) as $perm)
                                        <span class="inline-block text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono">
                                            {{ $perm->slug }}
                                        </span>
                                    @endforeach
                                    @if($role->permissions->count() > 5)
                                        <span class="text-[10px] text-slate-400 self-center">+{{ $role->permissions->count() - 5 }} more</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-semibold transition shadow-sm">
                    Save Role Assignments
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

