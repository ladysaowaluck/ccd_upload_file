# CCD Database System Rebuild Blueprint & Architecture Specification

**Project Name:** CCD Database System Modernization  
**Target Stack:** PHP 8.2+ / Laravel 10.x or 11.x / Tailwind CSS v3.4+ / Alpine.js  
**Databases (Preserved Invariants):** `ccd_system_rds` (`mysql`) & `sn_user_auth_rds` (`authdb`)  
**Design Standard:** Responsive Modern Enterprise (Tailwind CSS, Zero Destructive Migrations)

---

## 1. System Overview & Core Invariants

The goal is to rebuild the legacy Laravel-Admin monolith into a clean, modular, and maintainable Laravel application with a modern Tailwind CSS front-end. 

### Mandatory Invariants:
1. **Never alter existing tables or delete production data.**
2. **Dual-Database Integrity:**
   - Default connection `mysql` (`ccd_system_rds`): Business records, master customers, branches, units, statistics, licenses.
   - Secondary connection `authdb` (`sn_user_auth_rds`): User authentication, department trees, and RBAC tables (`ccd_admin_*`).
3. **Email Whitelist:** Self-registration is restricted strictly to `@th.nissin-asia.com`.
4. **Zero Destructive Migrations:** Never execute `migrate:fresh`, `migrate:refresh`, or test suites using `RefreshDatabase`. Only use `DatabaseTransactions`.

---

## 2. Directory Structure & Architectural Blueprint

We decouple business actions from controllers into dedicated Domain Services, Data Transfer Objects (DTOs), and Form Requests:

```
app/
├── Domain/
│   ├── Clearance/
│   │   ├── Actions/
│   │   │   ├── CreateClearanceRecordAction.php
│   │   │   ├── UpdateClearanceRecordAction.php
│   │   │   ├── GenerateJobSequenceAction.php
│   │   │   └── ImportExcelRequisitionAction.php
│   │   ├── DTOs/
│   │   │   └── ClearanceRecordData.php
│   │   └── Services/
│   │       ├── ClearanceSequenceService.php
│   │       ├── ClearanceExcelImportService.php
│   │       └── WeeklyHsExportService.php
│   ├── Compliance/
│   │   └── Queries/
│   │       └── GetOutstandingClearanceQuery.php
│   └── MasterData/
│       └── Services/
│           └── BatchCustomerNameSyncService.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── AdminUserController.php
│   │   │   ├── MasterCustomerController.php
│   │   │   └── MasterDataController.php
│   │   ├── Clearance/
│   │   │   ├── ClearanceGridController.php
│   │   │   ├── ClearanceCreateController.php
│   │   │   ├── ClearanceUploadController.php
│   │   │   └── OutstandingAuditController.php
│   │   ├── Api/
│   │   │   └── SequenceLookupApiController.php
│   │   └── Export/
│   │       └── WeeklyHsExportController.php
│   ├── Middleware/
│   │   ├── EnsureDomainWhitelisted.php
│   │   └── RoleAuthorizationMiddleware.php
│   └── Requests/
│       ├── StoreClearanceRecordRequest.php
│       ├── BulkExcelUploadRequest.php
│       └── MasterCustomerRequest.php
├── Models/
│   ├── Business/                         # Connection: 'mysql'
│   │   ├── CustomerClearanceData.php
│   │   ├── MasterCustomer.php
│   │   ├── MasterBranch.php
│   │   ├── MasterUnit.php
│   │   ├── MasterStatistic.php
│   │   └── MasterLicense.php
│   └── Auth/                             # Connection: 'authdb'
│       ├── AdminUser.php
│       ├── AdminRole.php
│       ├── AdminPermission.php
│       ├── AdminOffice.php
│       ├── AdminDepartment.php
│       └── AdminDivision.php
└── Services/
    └── Storage/
        └── DocumentStorageService.php     # Local storage fallback + S3 sync
```

---

## 3. Step-by-Step CLI Execution Commands

### Phase 1: Laravel Installation & Dependency Setup
Run these commands in your development terminal:

```bash
# 1. Create a fresh Laravel project
composer create-project laravel/laravel ccd-rebuild
cd ccd-rebuild

# 2. Install production dependencies
# Excel processing, AWS S3 Flysystem, Blade UI Kit / Heroicons
composer require maatwebsite/excel:^3.1 \
    league/flysystem-aws-s3-v3:^3.0 \
    blade-ui-kit/blade-icons:^1.7

# 3. Install authentication scaffolding
composer require laravel/breeze --dev
php artisan breeze:install blade --no-interaction

# 4. Install Tailwind CSS plugins & Alpine.js
npm install -D @tailwindcss/forms @tailwindcss/typography @tailwindcss/aspect-ratio autoprefixer postcss tailwindcss
npm install alpinejs
```

### Phase 2: Configuration & Asset Compilation
Update your `tailwind.config.js`:

```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Http/Controllers/**/*.php",
  ],
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
        },
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
```

Compile assets:
```bash
npm run build
```

---

## 4. Multi-Database & Authentication Configuration

### 4.1. `config/database.php`
Configure connections to ensure clear separation:

```php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'url' => env('DATABASE_URL'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'ccd_system_rds'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ],

    'authdb' => [
        'driver' => 'mysql',
        'url' => env('AUTH_DB_URL'),
        'host' => env('AUTH_DB_HOST', '127.0.0.1'),
        'port' => env('AUTH_DB_PORT', '3306'),
        'database' => env('AUTH_DB_DATABASE', 'sn_user_auth_rds'),
        'username' => env('AUTH_DB_USERNAME', 'root'),
        'password' => env('AUTH_DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ],
],
```

### 4.2. `config/auth.php`
Bind user authentication and password resets to `authdb`:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\Auth\AdminUser::class,
    ],
],

'passwords' => [
    'users' => [
        'provider' => 'users',
        'connection' => 'authdb',
        'table' => 'password_resets',
        'expire' => 60,
        'throttle' => 60,
    ],
],
```

---

## 5. Domain Models & Architecture Blueprint

### 5.1. Authentication Model (`app/Models/Auth/AdminUser.php`)
```php
namespace App\Models\Auth;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $connection = 'authdb';
    protected $table = 'admin_users';

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'avatar',
        'office_id',
        'department_id',
        'division_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            AdminRole::class,
            'ccd_admin_role_users',
            'user_id',
            'role_id'
        );
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->roles->contains('slug', $roleSlug);
    }

    public function isAdmin(): bool
    {
        return $this->roles->contains('id', 1) || $this->hasRole('administrator');
    }
}
```

### 5.2. Core Clearance Model (`app/Models/Business/CustomerClearanceData.php`)
```php
namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;

class CustomerClearanceData extends Model
{
    protected $connection = 'mysql';
    protected $table = 'customer_clearance_data';
    
    // Explicitly configure timestamps matching legacy schema
    public $timestamps = true;

    protected $fillable = [
        'job_no',
        'customer_id',
        'customer_name',
        'branch_code',
        'import_export',
        'product_code',
        'product_description_en',
        'product_description_th',
        'brand_name',
        'model_name',
        'material',
        'usages',
        'functions',
        'country_of_origin',
        'hs_code',
        'sequence_code',
        'statistical_code',
        'quantity_unit',
        'privilege_code',
        'tariff_rate',
        'specific_rate',
        'reference_file_1',
        'reference_file_2',
        'hs_evidence_file_1',
        'hs_evidence_file_2',
        'hs_evidence_file_3',
        'hs_evidence_file_4',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    public function isMissingEvidence(): bool
    {
        return empty($this->hs_evidence_file_1) 
            && empty($this->hs_evidence_file_2) 
            && empty($this->hs_evidence_file_3);
    }
}
```

---

## 6. Implementation of Core Business Logic

### 6.1. Job Number Sequence Generator (`GenerateJobSequenceAction.php`)
Reimplements `/api/ccd_get_ym/{yymm}` sequence logic:

```php
namespace App\Domain\Clearance\Actions;

use App\Models\Business\CustomerClearanceData;

class GenerateJobSequenceAction
{
    public function execute(string $type = 'CCD', ?string $yymm = null): string
    {
        $yymm = $yymm ?: date('ym');
        $prefix = "{$type}{$yymm}-";

        $lastRecord = CustomerClearanceData::where('job_no', 'LIKE', "{$prefix}%")
            ->orderBy('job_no', 'desc')
            ->first();

        if (!$lastRecord) {
            return "{$prefix}0001";
        }

        $lastSequence = (int) substr($lastRecord->job_no, strlen($prefix));
        $nextSequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$nextSequence}";
    }
}
```

### 6.2. Domain Check Middleware (`EnsureDomainWhitelisted.php`)
```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureDomainWhitelisted
{
    public function handle(Request $request, Closure $next)
    {
        $email = $request->input('email');
        if ($email && !str_ends_with(strtolower($email), '@th.nissin-asia.com')) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors(['email' => 'Registration is restricted to @th.nissin-asia.com addresses.']);
        }

        return $next($request);
    }
}
```

---

## 7. Modern UI/UX Design System (Tailwind CSS)

### 7.1. Color Palette & Typography Guidelines
* **Primary / Corporate Identity:** Nissin Blue (`#0d59e1` to `#0b2456`).
* **Backgrounds:** Slate clean light mode (`bg-slate-50`) with high-contrast slate borders (`border-slate-200`).
* **Compliance Indicators:**
  * **Verified / Complete:** Emerald badge (`bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20`).
  * **Missing Evidence (Outstanding):** Rose badge (`bg-rose-50 text-rose-700 ring-1 ring-rose-600/20`).
  * **In Progress / Under Review:** Amber badge (`bg-amber-50 text-amber-700 ring-1 ring-amber-600/20`).

### 7.2. Master Layout Shell (`resources/views/layouts/app.blade.php`)
```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'CCD System') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased font-sans text-slate-800" x-data="{ sidebarOpen: false }">
    <div class="min-h-full flex flex-col lg:flex-row">
        <!-- Sidebar Navigation -->
        <aside class="w-full lg:w-64 bg-slate-900 border-r border-slate-800 text-slate-300 flex-shrink-0">
            <div class="h-16 flex items-center px-6 bg-slate-950 font-bold tracking-wide text-white">
                <span class="text-brand-400 mr-2">SNC</span> CCD Database
            </div>
            <nav class="p-4 space-y-1 text-sm font-medium">
                <a href="{{ route('clearance.index') }}" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition">
                    Customs Clearances
                </a>
                <a href="{{ route('clearance.create') }}" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition">
                    Register New CCD
                </a>
                <a href="{{ route('clearance.upload') }}" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition">
                    Excel Requisition Ingestion
                </a>
                <a href="{{ route('compliance.outstanding') }}" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition">
                    Outstanding Evidence Auditing
                </a>
                <a href="{{ route('export.weekly') }}" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition">
                    Weekly HS Exports
                </a>

                @if(auth()->user()->isAdmin())
                <div class="pt-4 pb-1 text-xs uppercase tracking-wider text-slate-500 font-semibold px-3">
                    Administration
                </div>
                <a href="{{ route('admin.customers.index') }}" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition">
                    Customer Registry
                </a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition">
                    User Management
                </a>
                @endif
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6">
                <div class="text-lg font-semibold text-slate-800">
                    @yield('header_title', 'Dashboard')
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-slate-600">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-rose-600 hover:underline font-medium">Log out</button>
                    </form>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-6 md:p-8 max-w-7xl mx-auto w-full">
                @if (session('success'))
                    <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm">
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
```

### 7.3. Clearance Grid View (`resources/views/clearance/index.blade.php`)
```html
@extends('layouts.app')

@section('header_title', 'Customer Clearance Data Repository')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <!-- Filter Section -->
    <div class="p-5 border-b border-slate-200 bg-slate-50/50">
        <form method="GET" action="{{ route('clearance.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Job Number</label>
                <input type="text" name="job_no" value="{{ request('job_no') }}" placeholder="CCD2305-..." class="w-full text-sm rounded-lg border-slate-300 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">HS Code</label>
                <input type="text" name="hs_code" value="{{ request('hs_code') }}" placeholder="8-digit HS Code" class="w-full text-sm rounded-lg border-slate-300 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Customer Name</label>
                <input type="text" name="customer_name" value="{{ request('customer_name') }}" placeholder="Search client..." class="w-full text-sm rounded-lg border-slate-300 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition">
                    Search
                </button>
                <a href="{{ route('clearance.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 py-2 px-3 rounded-lg text-sm transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
            <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider text-xs">
                <tr>
                    <th class="px-4 py-3.5">Job Number</th>
                    <th class="px-4 py-3.5">Customer</th>
                    <th class="px-4 py-3.5">HS Code</th>
                    <th class="px-4 py-3.5">Description (EN / TH)</th>
                    <th class="px-4 py-3.5">Duty Rate</th>
                    <th class="px-4 py-3.5 text-center">Compliance</th>
                    <th class="px-4 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($records as $item)
                <tr class="hover:bg-slate-50/80 transition">
                    <td class="px-4 py-3 font-semibold text-brand-700">
                        {{ $item->job_no }}
                    </td>
                    <td class="px-4 py-3 max-w-xs truncate text-slate-700">
                        {{ $item->customer_name }}
                    </td>
                    <td class="px-4 py-3 font-mono font-medium text-slate-900">
                        {{ $item->hs_code }}
                    </td>
                    <td class="px-4 py-3 max-w-md truncate">
                        <div class="text-slate-900">{{ $item->product_description_en }}</div>
                        <div class="text-xs text-slate-500">{{ $item->product_description_th }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                        {{ $item->tariff_rate ? $item->tariff_rate . '%' : '-' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($item->isMissingEvidence())
                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                Missing Evidence
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                Verified
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="{{ route('clearance.edit', $item->id) }}" class="text-brand-600 hover:text-brand-900 font-medium text-xs">Edit</a>
                        <a href="{{ route('clearance.show', $item->id) }}" class="text-slate-600 hover:text-slate-900 font-medium text-xs">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                        No clearance records match your search criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="p-4 border-t border-slate-200">
        {{ $records->links() }}
    </div>
</div>
@endsection
```

---

## 8. Verification & Delivery Plan

| Phase | Milestone | Validation Criteria |
|---|---|---|
| **Phase 1** | Scaffolding & DB Config | Connection check passes without executing destructive migrations on `mysql` or `authdb`. |
| **Phase 2** | Auth & Domain Restriction | Users with `@th.nissin-asia.com` register cleanly; outside emails are rejected with validation errors. |
| **Phase 3** | CCD Core Grid & Lookup | Existing records are queryable by Job No, HS Code, and Description. |
| **Phase 4** | Job Sequence & Requisition | Auto-numbering sequence generates valid next indices; single & batch Excel uploads correctly parse template rows. |
| **Phase 5** | Compliance & Auditing | Outstanding view lists all records where `hs_evidence_file_1..3` are null and allows direct attachment upload. |
| **Phase 6** | Reports & Master Admin | Weekly HS Code Excel files generate formatted `.xlsx` downloads; Master customer name sync utility updates records. |