@extends('layouts.auth')

@section('title', 'Resend Verification Email')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
    <div class="text-center mb-6">
        <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900">Resend Verification Email</h2>
        <p class="text-xs text-slate-500 mt-1">
            Please input your registered corporate email. The system will send a new activation link.
        </p>
    </div>

    <!-- @if(session('status'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-xs">
            <p class="font-medium">{{ session('status') }}</p>
            @if(session('verify_url'))
                <div class="mt-2.5 pt-2 border-t border-green-200">
                    <span class="text-slate-600">Dev/Local mail mode: </span>
                    <a href="{{ session('verify_url') }}" class="font-bold text-brand-700 underline hover:text-brand-900 ml-1">
                        Click here to verify and activate account now
                    </a>
                </div>
            @endif
        </div>
    @endif -->

    <!-- @if($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-xs">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif -->

    <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Corporate Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                placeholder="username@th.nissin-asia.com"
                required autofocus
                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>

        <button type="submit" class="w-full py-2.5 px-4 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition">
            Resend Verification Email
        </button>
    </form>

    <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
        <a href="{{ route('login') }}" class="hover:text-brand-600 hover:underline">&larr; Back to Sign In</a>
        <a href="{{ route('register') }}" class="hover:text-brand-600 hover:underline">Register Account &rarr;</a>
    </div>
</div>
@endsection

