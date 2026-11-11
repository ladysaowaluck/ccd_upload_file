@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-brand-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-slate-900">Forgot Password</h2>
            <p class="text-xs text-slate-500 mt-1">
                Enter your corporate email. If an account exists, you can set a new password.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-700 font-medium">
                {{ session('status') }}
            </div>
        @endif

        <!-- @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
                {{ $errors->first() }}
            </div>
        @endif -->

        <form method="POST" action="{{ route('password.forgot.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Corporate Email</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="username@th.nissin-asia.com"
                    required autofocus
                    class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <button type="submit"
                class="w-full py-2.5 px-4 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                Verify Account &amp; Send Reset Link
            </button>
        </form>

        <div class="mt-5 pt-4 border-t border-slate-100 text-center text-xs">
            <a href="{{ route('login') }}" class="text-slate-500 hover:text-brand-600 hover:underline">&larr; Back to Sign
                In</a>
        </div>
    </div>
@endsection