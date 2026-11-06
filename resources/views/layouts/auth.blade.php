<!DOCTYPE html>
{{-- Gemini : Auth layout — sidebar-free shell for login, register, and forgot-password pages --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SNC CCD System') }} — @yield('title', 'Authentication')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef8ff',
                            100: '#d8eeff',
                            200: '#b9e0ff',
                            300: '#89ceff',
                            400: '#52b2ff',
                            500: '#2b91ff',
                            600: '#1471f5',
                            700: '#0d59e1',
                            800: '#1147b6',
                            900: '#133e8f',
                            950: '#0b2456',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full antialiased font-sans text-slate-800 bg-slate-100 flex flex-col min-h-screen">

    {{-- Top brand bar --}}
    <!-- <header class="bg-slate-900 border-b border-slate-800 h-14 flex items-center px-6">
        <div class="flex items-center space-x-2">
            <span class="inline-block w-3 h-3 bg-blue-500 rounded-sm"></span>
            <span class="text-white text-sm font-semibold tracking-wider">SNC CCD SYSTEM</span>
        </div>
        <span class="ml-3 text-slate-500 text-xs">Customs Clearance Database</span>
    </header> -->

    {{-- Page content centered --}}
    <main class="flex-1 flex items-center justify-center py-10 px-4">
        <div class="w-full max-w-md">

            {{-- Error flash --}}
            @if ($errors->any())
                <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm">
                    <div class="font-semibold mb-1">Please correct the following errors:</div>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Success flash --}}
            @if (session('success'))
                <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center shadow-sm">
                    <svg class="w-5 h-5 mr-3 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    {{-- Footer --}}
    <footer class="py-4 text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} SNC-LAVALIN / Nissin Asia &bull; Internal Use Only
    </footer>

</body>
</html>

