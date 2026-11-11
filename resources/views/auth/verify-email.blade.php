@extends('layouts.auth')

@section('title', 'Verify Your Email')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
    <div class="text-center mb-6">
        <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900">Verify Your Email Address</h2>
        <p class="text-xs text-slate-500 mt-2 leading-relaxed">
            Thanks for signing up! Before getting started, please verify your email address by clicking on the link we just sent to your corporate email.
        </p>
    </div>

    <!-- @if (session('status'))
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

    <div class="space-y-3 pt-2">
        <a href="{{ route('verification.resend') }}" class="block w-full text-center py-2.5 px-4 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition">
            Resend Verification Email
        </a>

        <div class="text-center pt-2">
            <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-brand-600 hover:underline">
                &larr; Back to Sign In
            </a>
        </div>
    </div>
</div>
@endsection

