{{-- Lady : Previously extended layouts.app which rendered the full sidebar + topbar on the login page. --}}
{{-- @extends('layouts.app') --}}
{{-- Gemini : Switch to sidebar-free auth layout for login page --}}
@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
    <div class="text-center mb-6">
        <h2 class="text-xl font-bold text-slate-900">Sign In</h2>
        <p class="text-xs text-slate-500 mt-1">Access customs clearance database and compliance audits</p>
    </div>

    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
        @csrf

        {{-- Lady : Label previously only indicated "Username", confusing users who attempted to sign in with corporate email. --}}
        {{--
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Username</label>
            <input type="text" name="username" value="{{ old('username') }}" required autofocus class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>
        --}}
        {{-- Gemini : Support username or corporate email with clear label and placeholder --}}
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Username or Corporate Email</label>
            <input type="text" name="username" value="{{ old('username') }}"
                placeholder="username or employee@th.nissin-asia.com"
                required autofocus
                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
            <input type="password" name="password" required
                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>

        <div class="flex items-center justify-between text-xs pt-1">
            <label class="flex items-center text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 mr-2">
                Remember me
            </label>
            {{-- Gemini : Forgot Password link to self-service reset page --}}
            <a href="{{ route('password.forgot') }}" class="text-slate-500 hover:text-brand-600 hover:underline">
                Forgot password?
            </a>
        </div>

        <button type="submit" class="w-full py-2.5 px-4 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition">
            Sign In
        </button>
    </form>

    <div class="mt-5 pt-4 border-t border-slate-100 text-center text-xs">
        <span class="text-slate-500">New employee?</span>
        <a href="{{ route('register') }}" class="text-brand-600 hover:underline font-semibold ml-1">Create account</a>
    </div>
</div>
@endsection
