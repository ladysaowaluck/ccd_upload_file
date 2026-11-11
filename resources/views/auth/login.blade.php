@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-slate-900">Sign In</h2>
            <p class="text-xs text-slate-500 mt-1">Access customs clearance database and compliance audits</p>
        </div>

        @if(session('verified'))
            <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-xs">
                {{ session('verified') }}
            </div>
        @endif

         <!-- Display emerald status pop-up banner after registration or password reset, matching forgot-password styling -->
        <!-- @if(session('status'))
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-700 font-medium">
                <p>{{ session('status') }}</p>
                @if(session('verify_url') && !app()->isProduction())
                    <div class="mt-2.5 pt-2 border-t border-emerald-200">
                        <span class="text-slate-600">Dev/Local mail mode: </span>
                        <a href="{{ session('verify_url') }}" class="font-bold text-brand-700 underline hover:text-brand-900 ml-1">
                             Click here to verify and activate account now
                        </a>
                    </div>
                @endif
            </div>
        @endif -->

        {{-- Show verification errors (expired/invalid token) --}}
        @if($errors->has('verification'))
            <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-xs">
                {{ $errors->first('verification') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf

            {{--
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus
                    class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Corporate Email</label>
                <input type="text" name="username" value="{{ old('username') }}"
                    placeholder="username or employee@th.nissin-asia.com" required autofocus
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
                {{-- Forgot Password link to self-service reset page --}}
                <a href="{{ route('password.forgot') }}" class="text-slate-500 hover:text-brand-600 hover:underline">
                    Forgot password?
                </a>
            </div>

            <button type="submit"
                class="w-full py-2.5 px-4 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded-lg shadow-sm transition">
                Sign In
            </button>
        </form>

        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
            <div>
                <span class="text-slate-500">New employee?</span>
                <a href="{{ route('register') }}" class="text-brand-600 hover:underline font-semibold ml-1">Create
                    account</a>
            </div>
            <div>
                <a href="{{ route('verification.resend') }}" class="text-slate-500 hover:text-brand-600 hover:underline">
                    Resend verification email
                </a>
            </div>
        </div>
    </div>
@endsection