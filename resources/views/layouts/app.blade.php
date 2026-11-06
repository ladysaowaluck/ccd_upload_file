<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SNC CCD System') }} - @yield('title', 'Customs Clearance Repository')</title>

    <!-- Tailwind CSS CDN Fallback for Zero-Build Immediate High-Performance Rendering -->
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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
<body class="h-full antialiased font-sans text-slate-800 bg-slate-100/60" x-data="{ sidebarOpen: false }">
    <div class="min-h-full flex flex-col lg:flex-row">
        <!-- Sidebar Navigation -->
        <aside class="w-full lg:w-64 bg-slate-900 border-r border-slate-800 text-slate-300 flex-shrink-0 flex flex-col">
            <div class="h-16 flex items-center justify-between px-6 bg-slate-950 font-bold tracking-wide text-white border-b border-slate-800">
                <a href="{{ route('clearance.index') }}" class="flex items-center space-x-2">
                    <span class="inline-block w-3 h-3 bg-blue-500 rounded-sm"></span>
                    <span class="text-white tracking-wider text-sm font-semibold">SNC CCD SYSTEM</span>
                </a>
            </div>
            
            <nav class="p-4 space-y-1.5 text-sm font-medium flex-1">
                <a href="{{ route('clearance.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('clearance.index') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    Customs Clearances
                </a>
                {{-- Lady : Sidebar was missing the Excel Requisition Ingestion navigation link. --}}
                {{--
                <a href="{{ route('clearance.create') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('clearance.create') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Register New CCD
                </a>
                --}}
                {{-- Gemini : GAP-03 — Add Excel Requisition Ingestion link in sidebar navigation --}}
                <a href="{{ route('clearance.create') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('clearance.create') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Register New CCD
                </a>
                <a href="{{ route('clearance.upload') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('clearance.upload*') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    Excel Ingestion
                </a>
                {{-- Lady : Sidebar was missing the Weekly HS Exports navigation link. --}}
                {{--
                <a href="{{ route('compliance.outstanding') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('compliance.outstanding') ? 'bg-rose-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Outstanding Auditing
                </a>
                --}}
                {{-- Gemini : GAP-04 — Add Weekly HS Exports navigation link to sidebar --}}
                <a href="{{ route('compliance.outstanding') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('compliance.outstanding') ? 'bg-rose-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Outstanding Auditing
                </a>
                <a href="{{ route('export.weekly') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('export.weekly*') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Weekly HS Exports
                </a>

                {{-- Lady : Master data links were shown to all authenticated users regardless of their role, leading to 403 errors when non-admins clicked them. --}}
                {{--
                <div class="pt-5 pb-1 text-xs uppercase tracking-wider text-slate-500 font-bold px-3">
                    Master Data
                </div>
                <a href="{{ route('admin.customers.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.customers.*') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Customer Registry
                </a>
                --}}
                {{-- Gemini : GAP-02 — Only display administrative menus to users with administrator role --}}
                @if(auth()->user() && auth()->user()->isAdmin())
                <div class="pt-5 pb-1 text-xs uppercase tracking-wider text-slate-500 font-bold px-3">
                    Master Data
                </div>
                <a href="{{ route('admin.customers.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.customers.*') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Customer Registry
                </a>
                {{-- Gemini : GAP-08 — User Management link, admin-only, links to admin.users.index --}}
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    User Management
                </a>

                {{-- Gemini : GAP-09 — Master data navigation links (admin-only section) --}}
                <div class="pt-3 pb-1 text-xs uppercase tracking-wider text-slate-500 font-bold px-3">
                    Reference Data
                </div>
                <a href="{{ route('admin.master.branches') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.master.branches') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Branches
                </a>
                <a href="{{ route('admin.master.units') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.master.units') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                    Units
                </a>
                <a href="{{ route('admin.master.statistics') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.master.statistics') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Statistical Codes
                </a>
                <a href="{{ route('admin.master.licenses') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.master.licenses') ? 'bg-brand-700 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                    <svg class="w-4 h-4 mr-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Customs Licenses
                </a>
                @endif
            </nav>

            <div class="p-4 border-t border-slate-800 bg-slate-950 text-xs text-slate-400">
                <div>Dual RDS Connected</div>
                <div class="text-[11px] text-emerald-400 font-mono mt-0.5">● mysql + authdb OK</div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shadow-sm">
                <div class="text-lg font-semibold text-slate-800">
                    @yield('header_title', 'CCD Dashboard')
                </div>
                <div class="flex items-center space-x-4">
                    @if(auth()->check())
                        <div class="text-sm font-medium text-slate-700">{{ auth()->user()->name }}</div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-xs bg-slate-100 hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 px-3 py-1.5 rounded-lg font-medium transition">
                                Sign out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-xs bg-brand-600 hover:bg-brand-700 text-white px-3.5 py-1.5 rounded-lg font-medium transition">
                            Sign in
                        </a>
                    @endif
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-6 md:p-8 max-w-7xl mx-auto w-full">
                @if (session('success'))
                    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center shadow-sm">
                        <svg class="w-5 h-5 mr-3 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                {{-- Gemini : Upload Destination & Address Debug Inspector Card --}}
                @if (session('upload_debug') && count(session('upload_debug')) > 0)
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
                                    <div class="text-slate-400 flex flex-col sm:flex-row sm:items-start gap-1">
                                        <span class="text-slate-500 w-28 flex-shrink-0">Local Storage:</span>
                                        <span class="text-slate-200 break-all select-all">{{ $dbg['local_path'] }}</span>
                                    </div>
                                    {{-- Lady : Unconditionally rendering local URL links leads to broken empty anchor tags when local disk storage is eliminated. --}}
                                    @if(!empty($dbg['local_url']))
                                    <div class="text-slate-400 flex flex-col sm:flex-row sm:items-start gap-1">
                                        <span class="text-slate-500 w-28 flex-shrink-0">Local URL:</span>
                                        <a href="{{ $dbg['local_url'] }}" target="_blank" class="text-blue-400 hover:underline break-all">{{ $dbg['local_url'] }}</a>
                                    </div>
                                    @endif
                                    <div class="text-slate-400 flex flex-col sm:flex-row sm:items-start gap-1">
                                        <span class="text-slate-500 w-28 flex-shrink-0">AWS S3:</span>
                                        @if($dbg['s3_synced'])
                                            <span class="text-emerald-400 font-bold">✓ Synced</span>
                                            <span class="text-slate-300 break-all select-all">{{ $dbg['s3_location'] }}</span>
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
                @endif

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
</body>
</html>

