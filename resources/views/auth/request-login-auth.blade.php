@extends('layouts.auth')

@section('title', 'Request System Login Privileges')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
    <div class="text-center mb-6">
        <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900">Request System Login Privileges</h2>
        <p class="text-xs text-slate-500 mt-2 leading-relaxed">
            Please input your corporate email (SN Web ID). The system will send a system confirmation email to grant access.
        </p>
    </div>

    <!-- @if (session('status'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-xs">
            <p class="font-medium">{{ session('status') }}</p>
            @if(session('verify_url'))
                <div class="mt-2.5 pt-2 border-t border-green-200">
                    <span class="text-slate-600">Dev/Local mail mode: </span>
                    <a href="{{ session('verify_url') }}" class="font-bold text-brand-700 underline hover:text-brand-900 ml-1">
                        Click here to grant system privileges now
                    </a>
                </div>
            @endif
        </div>
    @endif -->

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-xs">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('req.system_auth.post') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Corporate Email (SN Web ID)</label>
            <input type="email" name="email" value="{{ old('email') }}"
                placeholder="username@th.nissin-asia.com"
                required autofocus
                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>

        <button type="submit" class="w-full py-2.5 px-4 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition">
            Request System Login Privileges
        </button>
    </form>

    <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
        <a href="{{ route('login') }}" class="hover:text-brand-600 hover:underline">&larr; Back to Sign In</a>
        <a href="{{ route('register') }}" class="hover:text-brand-600 hover:underline">New user? Register &rarr;</a>
    </div>
</div>
@endsection

