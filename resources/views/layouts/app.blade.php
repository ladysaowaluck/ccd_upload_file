<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SNC CCD System') }} - @yield('title', 'Customs Clearance Repository')</title>

    <link rel="icon" type="image/png" href="{{ asset('images/letter-n.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/letter-n.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS CDN Fallback for Zero-Build Immediate High-Performance Rendering -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                        brand: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        /* Unified enterprise theme anchored to user-requested corporate palette:
                                  - #0070C0 : Corporate SNC Blue (brand-600)
                                  - #FA7800 : Vibrant Operations / Cargo Orange (cargo-500)
                                  - #F59E0B : Warm Amber / Attention Warning (amber-500)
                                  - #8B0019 : Nissin Deep Crimson Burgundy (nissin-700 / rose-700)
                                  - #B90019 : Nissin Vivid Crimson Red (nissin-600 / rose-600)
                        */
                        brand: {
                            50: '#edf6fd',
                            100: '#d5ebfa',
                            200: '#b0d9f6',
                            300: '#77c0ef',
                            400: '#38a3e6',
                            500: '#0f87d4',
                            600: '#0070C0', // SNC Corporate Blue (Primary)
                            700: '#005a9c', // Primary button & active nav state
                            800: '#004c82', // Darker interactive hover state
                            900: '#053f6b',
                            950: '#032642',
                        },
                        nissin: {
                            50: '#fdf2f3',
                            100: '#fbe6e8',
                            200: '#f7cfd3',
                            300: '#f0abb2',
                            400: '#e57a85',
                            500: '#d74756',
                            600: '#B90019', // Vivid Crimson
                            700: '#8B0019', // Deep Burgundy / Nissin Signature Red
                            800: '#730416',
                            900: '#5c0615',
                            950: '#350109',
                        },
                        cargo: {
                            50: '#fff7ed',
                            100: '#ffeed6',
                            200: '#fed7aa',
                            300: '#fdbb72',
                            400: '#fb9732',
                            500: '#FA7800', // Vibrant Operations Orange
                            600: '#df6400',
                            700: '#b94c00',
                            800: '#943d05',
                            900: '#78330a',
                            950: '#431702',
                        },
                        rose: {
                            50: '#fdf2f3',
                            100: '#fbe6e8',
                            200: '#f7cfd3',
                            300: '#f0abb2',
                            400: '#e57a85',
                            500: '#d74756',
                            600: '#B90019', // Vivid Crimson for missing evidence & error alerts
                            700: '#8B0019', // Deep Burgundy Crimson
                            800: '#730416',
                            900: '#5c0615',
                            950: '#350109',
                        },
                        amber: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#F59E0B', // Warm Attention Amber
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                            950: '#451a03',
                        },
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>

<body class="h-full antialiased font-sans text-slate-800 bg-slate-100/60" x-data="{ sidebarOpen: false }">
    <div class="min-h-full flex flex-col lg:flex-row">

        <!-- Sidebar Navigation -->
        <!-- <aside class="w-full lg:w-64 bg-slate-900 border-r border-slate-800 text-slate-300 flex-shrink-0 flex flex-col"> -->
        <aside
            class="w-full lg:w-64 lg:sticky lg:top-0 lg:h-screen lg:self-start bg-slate-900 border-r border-slate-800 text-slate-300 flex-shrink-0 flex flex-col z-30">
            <div
                class="h-16 flex items-center justify-between px-5 bg-slate-950 text-white border-b border-slate-800/80">

                <a href="{{ route('clearance.index') }}" class="flex items-center gap-3 group transition">
                    <img src="{{ asset('images/Logo Nissin-1.png') }}" alt="SNC Logo"
                        class="h-8 w-auto object-contain flex-shrink-0 group-hover:brightness-110 transition">

                    {{--
                    <span class="text-white tracking-wider text-sm font-semibold">CCD SYSTEM</span>
                    --}}

                    <div class="flex flex-col min-w-0">
                        <div class="flex items-center gap-1.5 leading-none">
                            <span
                                class="text-[15px] font-extrabold tracking-tight text-white font-brand group-hover:text-brand-300 transition-colors">
                                CCD
                            </span>
                            <span
                                class="text-[10px] font-extrabold tracking-widest uppercase px-1.5 py-0.5 rounded bg-cargo-950/90 text-cargo-400 border border-cargo-600/50 shadow-sm leading-none">
                                SYSTEM
                            </span>
                        </div>
                        <!-- <span class="text-[9px] font-semibold uppercase tracking-wider text-slate-400 mt-1 leading-none flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-nissin-600 inline-block shadow-xs"></span>
                            Customs Clearance
                        </span> -->
                    </div>
                </a>
            </div>

            <!-- <div> -->
            <nav class="p-4 space-y-1.5 text-sm font-medium flex-1 overflow-y-auto">
                <a href="{{ route('clearance.index') }}"
                    class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('clearance.index') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <!-- <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg> -->
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    Home
                </a>
                {{-- Conditionally show Manual New CCD and Upload (Excel) only if the user has write privileges. --}}
                @if(auth()->user()?->canWrite())
                    <a href="{{ route('clearance.create') }}"
                        class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('clearance.create') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                        <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        Manual New CCD
                    </a>
                    <a href="{{ route('clearance.upload') }}"
                        class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('clearance.upload*') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                        <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                        Upload ( Excel )
                    </a>
                @endif
                <a href="{{ route('compliance.outstanding') }}"
                    class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('compliance.outstanding') ? 'bg-rose-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80 text-rose-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                    Database
                </a>
                <!-- Only render Weekly HS Exports report link if user has download permissions -->
                @if(auth()->user()?->canDownload())
                    <a href="{{ route('export.weekly') }}"
                        class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('export.weekly*') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                        <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Weekly HS Exports
                    </a>
                @endif

                @if(auth()->user() && auth()->user()->isAdmin())
                    <div class="pt-5 pb-1 text-xs uppercase tracking-wider text-slate-500 font-bold px-3">
                        Master Data
                    </div>
                    <a href="{{ route('admin.master.customers.index') }}"
                        class="flex items-center px-3 py-2.5 rounded-lg {{ (request()->routeIs('admin.master.customers.*') || request()->routeIs('admin.customers.*')) ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                        <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                        Customer
                    </a>
                    <!-- User Management link, admin-only, links to admin.users.index -->
                    <a href="{{ route('admin.users.index') }}"
                        class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                        <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                            </path>
                        </svg>
                        User Management
                    </a>

                    <!-- Master data navigation links (admin-only section) -->
                    <div class="pt-3 pb-1 text-xs uppercase tracking-wider text-slate-500 font-bold px-3">
                        Reference Data
                    </div>
                    <a href="{{ route('admin.master.branches') }}"
                        class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.master.branches') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                        <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Branches
                    </a>
                    <a href="{{ route('admin.master.units') }}"
                        class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.master.units') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                        <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3">
                            </path>
                        </svg>
                        Units
                    </a>
                    <a href="{{ route('admin.master.statistics') }}"
                        class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.master.statistics') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                        <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        Statistical Codes
                    </a>
                    <a href="{{ route('admin.master.licenses') }}"
                        class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.master.licenses') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                        <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Customs Licenses
                    </a>
                @endif



            </nav>

            <!-- <div class="p-4 border-t border-slate-800 bg-slate-950 text-xs text-slate-400">
                    <div>RDS Connected</div>
                    <div class="text-[11px] text-emerald-400 font-mono mt-0.5">Version 2.0.0</div>
                </div> -->
            <div
                class="p-4 border-t border-slate-800 bg-slate-950 text-xs text-slate-400 mt-auto flex-shrink-0 sticky bottom-0 z-20">
                <div>RDS Connected</div>
                <div class="text-[11px] text-emerald-400 font-mono mt-0.5">Version 2.0.0</div>
            </div>

            <!-- </div> -->

        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header
                class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shadow-sm sticky top-0 z-30">
                <!-- <header class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur-sm border-b border-slate-200 flex items-center justify-between px-6 shadow-sm"></header> -->
                <div class="text-lg font-semibold text-slate-800">
                    @yield('header_title', 'CCD Dashboard')
                </div>
                <div class="flex items-center space-x-4">
                    @if(auth()->check())
                        <!-- Inform unassigned users that their account is operating in read-only access mode  -->
                        @if(auth()->user()->isReadOnly())
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                Read-Only Access
                            </span>
                        @endif
                        <div class="text-sm font-medium text-slate-700">{{ auth()->user()->name }}</div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="text-xs bg-slate-100 hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 px-3 py-1.5 rounded-lg font-medium transition">
                                Sign out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                            class="text-xs bg-brand-600 hover:bg-brand-700 text-white px-3.5 py-1.5 rounded-lg font-medium transition">
                            Sign in
                        </a>
                    @endif
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-6 md:p-8 max-w-7xl mx-auto w-full">
                @if (session('success'))
                    <!-- <div
                            class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center shadow-sm">
                            <svg class="w-5 h-5 mr-3 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div> -->
                @endif

                <!-- Upload Destination & Address Debug Inspector Card -->
                <!-- @if (session('upload_debug') && count(session('upload_debug')) > 0)
                    <div class="mb-6 rounded-xl bg-slate-900 border border-slate-800 text-slate-100 p-4 shadow-sm" x-data="{ expanded: true }">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                            <div class="flex items-center space-x-2">
                                <span class="inline-block w-2.5 h-2.5 bg-emerald-400 rounded-full animate-pulse"></span>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-200">File Upload & Storage Destination Inspector</span>
                            </div>
                            <button type="button" @click="expanded = !expanded" class="text-xs text-slate-400 hover:text-white transition">
                                <span x-text="expanded ? 'Hide Details' : 'Show Details'"></span>
                            </button>
                        </div>
                        <div x-show="expanded" class="mt-3 space-y-3 text-xs">
                            @foreach(session('upload_debug') as $dbg)
                                <div class="p-3 bg-slate-950/70 rounded-lg border border-slate-800 space-y-1.5 font-mono">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-white text-[13px]">{{ $dbg['file_name'] }}</span>
                                        <span class="text-[11px] text-slate-400">{{ number_format(($dbg['size_bytes'] ?? 0) / 1024, 1) }} KB</span>
                                    </div>
                                    {{-- Safely inspect AWS Server EFS /mnt/efs path with fallback --}}
                                    <div class="text-slate-400 flex flex-col sm:flex-row sm:items-start gap-1">
                                        <span class="text-slate-500 w-28 flex-shrink-0">AWS Server EFS:</span>
                                        <span class="text-slate-200 break-all select-all">{{ $dbg['local_server'] ?? ($dbg['local_path'] ?? 'Bypassed') }}</span>
                                    </div>
                                    @if(!empty($dbg['local_url']))
                                    <div class="text-slate-400 flex flex-col sm:flex-row sm:items-start gap-1">
                                        <span class="text-slate-500 w-28 flex-shrink-0">Local URL:</span>
                                        <a href="{{ $dbg['local_url'] }}" target="_blank" class="text-blue-400 hover:underline break-all">{{ $dbg['local_url'] }}</a>
                                    </div>
                                    @endif
                                    <div class="text-slate-400 flex flex-col sm:flex-row sm:items-start gap-1">
                                        <span class="text-slate-500 w-28 flex-shrink-0">AWS S3:</span>
                                        @if(!empty($dbg['s3_synced']))
                                            <span class="text-emerald-400 font-bold">✓ Synced</span>
                                            <span class="text-slate-300 break-all select-all">{{ $dbg['s3_location'] ?? '' }}</span>
                                        @else
                                            <span class="text-amber-400">Not synced to S3</span>
                                        @endif
                                    </div>
                                    @if(!empty($dbg['s3_synced']) && !empty($dbg['s3_url']))
                                    <div class="text-slate-400 flex flex-col sm:flex-row sm:items-start gap-1">
                                        <span class="text-slate-500 w-28 flex-shrink-0">S3 Endpoint:</span>
                                        <a href="{{ $dbg['s3_url'] }}" target="_blank" class="text-emerald-400 hover:underline break-all">{{ $dbg['s3_url'] }}</a>
                                    </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif -->

                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm">
                        <div class="font-semibold mb-1">Please correct the following errors:</div>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Global File Validation Modal for File Upload Failures (Size Over & Format File) -->
    <div id="fileValidationModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div
            class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-150">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-12 h-12 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 id="fileValidationTitle" class="text-sm font-bold text-slate-800">File Attachment Error</h3>
                        <p id="fileValidationMessage" class="text-xs text-slate-600 mt-1.5 leading-relaxed"></p>
                        <div id="fileValidationDetails"
                            class="mt-3 p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-[11px] text-slate-500 font-mono space-y-1">
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-3 border-t border-slate-100 flex justify-end">
                <button type="button" onclick="closeFileValidationModal()"
                    class="px-4 py-2 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-900 rounded-lg shadow-sm transition">
                    Understand & Close
                </button>
            </div>
        </div>
    </div>

    <!-- Client-side File Validation Script (Size limit 20MB & Allowed Extensions) -->
    <script>
        const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'xlsx', 'xls', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        const ALLOWED_EXCEL_EXTENSIONS = ['xlsx', 'xls'];
        const MAX_FILE_SIZE_BYTES = 20 * 1024 * 1024; // 20 MB

        function showFileValidationError(title, message, details = []) {
            const modal = document.getElementById('fileValidationModal');
            if (!modal) return;
            document.getElementById('fileValidationTitle').textContent = title;
            document.getElementById('fileValidationMessage').textContent = message;

            const detailsBox = document.getElementById('fileValidationDetails');
            if (details.length > 0) {
                detailsBox.innerHTML = details.map(d => `<div>• ${d}</div>`).join('');
                detailsBox.classList.remove('hidden');
            } else {
                detailsBox.classList.add('hidden');
            }
            modal.classList.remove('hidden');
        }

        function closeFileValidationModal() {
            const modal = document.getElementById('fileValidationModal');
            if (modal) modal.classList.add('hidden');
        }

        document.addEventListener('change', function (e) {
            if (e.target && e.target.type === 'file') {
                const input = e.target;
                const file = input.files && input.files[0];
                if (!file) return;

                const fileName = file.name;
                const fileSize = file.size;
                const ext = fileName.includes('.') ? fileName.split('.').pop().toLowerCase() : '';
                const isExcelOnly = input.name === 'single_record' || input.name === 'multi_record';
                const allowedExtensions = isExcelOnly ? ALLOWED_EXCEL_EXTENSIONS : ALLOWED_DOCUMENT_EXTENSIONS;

                // 1. Check file format / extension
                if (!allowedExtensions.includes(ext)) {
                    input.value = ''; // Reset invalid file
                    showFileValidationError(
                        'Invalid File Format',
                        'The selected file format is not supported. Please choose an authorized file format.',
                        [
                            `Selected File: ${fileName}`,
                            `Detected Format: .${ext || 'unknown'}`,
                            `Allowed Formats: ${allowedExtensions.map(e => '.' + e).join(', ')}`
                        ]
                    );
                    return;
                }

                // 2. Check file size (Max 20MB)
                if (fileSize > MAX_FILE_SIZE_BYTES) {
                    const currentMb = (fileSize / (1024 * 1024)).toFixed(2);
                    input.value = ''; // Reset oversized file
                    showFileValidationError(
                        'File Size Exceeded Limit',
                        'The selected file is too large to attach. Maximum allowed size is 20 MB per file.',
                        [
                            `Selected File: ${fileName}`,
                            `Current Size: ${currentMb} MB`,
                            `Maximum Limit: 20.00 MB`
                        ]
                    );
                    return;
                }
            }
        });

        // Close on clicking backdrop
        window.addEventListener('click', function (e) {
            const modal = document.getElementById('fileValidationModal');
            if (modal && e.target === modal) {
                closeFileValidationModal();
            }
        });
    </script>
</body>

</html>