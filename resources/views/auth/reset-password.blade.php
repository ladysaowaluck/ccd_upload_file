@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-slate-900">Set New Password</h2>
            <p class="text-xs text-slate-500 mt-1">
                Reset link verified for <strong>{{ $email }}</strong>
            </p>
        </div>

        <!-- @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
                {{ $errors->first() }}
            </div>
        @endif -->

        <form method="POST" action="{{ route('password.reset.submit') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">New Password</label>
                <input type="password" name="password" required minlength="8"
                    class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                    placeholder="Minimum 8 characters">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" required
                    class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <button type="submit"
                class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                Save New Password
            </button>
        </form>

        <div class="mt-5 pt-4 border-t border-slate-100 text-center text-xs">
            <a href="{{ route('login') }}" class="text-slate-500 hover:text-brand-600 hover:underline">&larr; Back to Sign In</a>
        </div>
    </div>
@endsection