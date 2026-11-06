{{-- Lady : Previously extended layouts.app which rendered the full sidebar + topbar on the register page. --}}
{{-- @extends('layouts.app') --}}
{{-- Gemini : Switch to sidebar-free auth layout for registration page --}}
@extends('layouts.auth')

@section('title', 'Register Account')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
    <div class="text-center mb-6">
        <h2 class="text-xl font-bold text-slate-900">Employee Self-Registration</h2>
        <p class="text-xs text-slate-500 mt-1">Registration is strictly restricted to company email addresses</p>
    </div>

    {{-- Domain whitelist notice --}}
    <div class="mb-5 p-3 rounded-lg bg-blue-50 border border-blue-200 text-blue-900 text-xs">
        <strong>Domain Whitelist:</strong> Only
        <code class="font-mono bg-blue-100 px-1 py-0.5 rounded">@th.nissin-asia.com</code>
        addresses are permitted.
    </div>

    <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Username</label>
            <input type="text" name="username" value="{{ old('username') }}" required
                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Corporate Email (@th.nissin-asia.com)</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                placeholder="username@th.nissin-asia.com"
                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
            <input type="password" name="password" required
                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" required
                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>

        <button type="submit" class="w-full py-2.5 px-4 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition">
            Create Account
        </button>
    </form>

    <div class="mt-5 pt-4 border-t border-slate-100 text-center text-xs">
        <span class="text-slate-500">Already registered?</span>
        <a href="{{ route('login') }}" class="text-brand-600 hover:underline font-semibold ml-1">Sign in</a>
    </div>
</div>
@endsection
