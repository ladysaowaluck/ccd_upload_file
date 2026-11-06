# Reverse Engineering Report: CCD Database System

**Project:** CCD Database System (`ccd_database_system`)  
**Target Environment:** PHP 8.2.35 / Laravel 10.50.4 / MariaDB (XAMPP & AWS RDS)  
**Methodology:** Static Code Analysis, Dynamic Runtime Inspection, Schema & Routing Reconstruction (`.agents/skills/reverse-engineer`)  
**Output Path:** `/proj_doc/reverse_engineering_analysis.md`  


older version : E:\Task\CCD\backup\html
---

## 1. Target and Objective

### Purpose of Analysis
Analyze the complete application codebase, routing structure, controllers, models, and database dumps to infer:
1. **The Core Purpose of this Project:** What business problem does this application solve, who uses it, and what are its operational goals?
2. **The Functional Breakdown:** What specific business, administrative, API, and batch functions are implemented?
3. **Database & Architecture Invariants:** How do the dual databases (`ccd_system_rds` and `sn_user_auth_rds`) interact?

---

## 2. Scope, Authorization, and Constraints

- **Scope:** Application codebase in `e:\Task\CCD\html`, database dumps in `db_dump/`, models in `app/Models/`, controllers in `app/Admin/Controllers/` and `app/Http/Controllers/`.
- **Operating Constraints:** Multi-database setup (`mysql` for business entities, `authdb` for user authentication and RBAC).
- **Redaction Policy:** AWS access keys, database passwords, and personal employee emails are redacted or omitted in accordance with security guidelines.

---

## 3. Executive Summary: What is the Purpose of this Project?

### Business Domain & Organization
The application is the **CCD (Customer Clearance Data) Database System** developed for **Siam Nistrans Co., Ltd. (SNC)** (`th.nissin-asia.com`), an international logistics, freight forwarding, and customs brokerage provider in Thailand (part of the Nissin Corporation global logistics group).

### Primary Objective
In international trade and Thai Customs clearance, every imported and exported cargo item must be classified under a specific **Harmonized System (HS) Code**, tariff rate, and statistical quantity unit. Incorrect classification leads to customs penalties, delayed cargo release, and tax disputes.

The **CCD Database System** serves as the **centralized customs classification, tariff management, and compliance repository** for Siam Nistrans. It enables customs brokers and clearance operators to:
1. **Assign and track unique Customs Clearance Job Numbers** (e.g. `CCD2305-0001`, `CST2305-0001`, `NTB2305-0001`).
2. **Register product descriptions** in both English and Thai alongside technical specifications (materials, usages, functions).
3. **Determine & record tariff classifications**: 8-digit HS Code, statistical code, sequence code, privilege code (e.g., FTA, JTEPA, Form D), ad valorem rates, and specific duty rates.
4. **Archive customs legal evidence**: Store signed requisition forms, catalog/SDS sheets, advance customs rulings, and official customs reference letters (locally and synced to AWS S3).
5. **Track Outstanding & Incomplete Classifications**: Identify shipments that are missing mandatory HS evidence files to prevent compliance violations.
6. **Generate Weekly HS Code Reports**: Produce official Excel reports by period and job series for internal auditing and customs declaration filings.

---

## 4. Comprehensive Functional Breakdown

The application features **6 primary functional modules**:

```
+-----------------------------------------------------------------------------------+
|                            CCD DATABASE SYSTEM MODULES                            |
+-----------------------------------------------------------------------------------+
| 1. Product Registration (CCD Entry)  | 2. Database Search & Management (CCD Grid) |
|    - Manual Single-Record Creation   |    - Multi-criteria Advanced Search        |
|    - Single Excel Template Upload    |    - Grid Filtering, Sorting, Inline View  |
|    - Multi-Record Batch Upload       |    - Record Editing & Update Tracking      |
+--------------------------------------+--------------------------------------------+
| 3. Compliance & Outstanding Monitor  | 4. Master Data Governance (Settings)       |
|    - Missing HS Evidence Detection   |    - Customer Registry (2,100+ entities)   |
|    - Direct Document Attachment Links|    - Branch Office Codes & Names           |
|    - Clearance Status Auditing       |    - Statistical Codes & Quantity Units    |
|                                      |    - Customs Licenses & Exemption Rulings  |
+--------------------------------------+--------------------------------------------+
| 5. Reporting & Data Exports          | 6. Identity, Security & RBAC               |
|    - Weekly HS Code Excel Generation |    - Multi-guard Auth (Admin + Web)        |
|    - Auto Job Number API Generation  |    - Domain Validation (@th.nissin-asia)   |
|    - S3 Document Storage Sync        |    - Role-Based Menus & Permission Gates   |
+-----------------------------------------------------------------------------------+
```

---

### Module 1: Product Registration (`/admin/ccd`)

Allows clearance clerks to register imported/exported items.

1. **Manual Registration (`GET /admin/ccd/create`):**
   - Direct form entry of Job Number, Customer Name, Branch, Import/Export flag (`0` = Import, `1` = Export).
   - Detailed product descriptions in both English (`product_description_en`) and Thai (`product_description_th`).
   - Technical specifications: Brand name, Model name, Material composition, Usages, Functions, Country of origin.
   - Tariff details: 8-digit HS Code, Sequence Code, Statistical Code, Quantity Unit, Privilege Code, Ad Valorem tariff rate, Specific rate baht/unit.
   - Document upload slots:
     - Reference Files 1 & 2 (Catalog, SDS, Technical Data Sheet).
     - HS Evidence Files 1 to 4 (Signed Requisition Form, Email Evidence, Advance Ruling, Customs Reference).

2. **Single Record Excel Upload (`GET /admin/ccd/upload`):**
   - Form accepts `hs_requisition_form_original.xlsx`.
   - Automatically maps Excel cells (`N1` -> Job No, `D4` -> Customer, `O2` -> Branch, `G10` -> English Desc, `G12` -> Thai Desc, `G8` -> Product Code, `G14` -> Materials, `G16` -> Usages, `G18` -> Function).
   - Validates existence of customer and branch references before insertion.

3. **Multi-Record Batch Upload (`GET /admin/ccd/upload`):**
   - Form accepts `hs_multi_original.xlsx`.
   - Iterates through multi-item manifests, generating or updating multiple CCD entries in a single transaction.
   - Attaches common reference files to all jobs in the batch.

---

### Module 2: Database Search & Filtering (`/admin/ccd`)

The operational search engine used daily by clearance teams:

1. **Search & Filters:**
   - Filter by Job Number (`CCD*`, `CST*`, `NTB*`), Customer Name (with autocomplete), Master Branch.
   - Search by HS Code prefix or exact match (8-digit).
   - Full-text search on English and Thai product descriptions.
   - Filter by Country of Origin, Privilege Code, Tariff Rate.
2. **Grid Features:**
   - Custom column displays with sortable headers.
   - Modal preview of attached evidence and SDS documents.
   - Direct download links for stored PDF/Excel evidence files.

---

### Module 3: Outstanding Management (`/admin/ccd/outstanding`)

A critical risk-control dashboard:

1. **Compliance Rule:**
   - Every clearance record must have authenticated evidence proving why a specific HS Code was applied.
2. **Implementation (`CustomerClearanceOutStandingController`):**
   ```php
   $grid->model()->where([
       ['hs_evidence_file_1', '=', null],
       ['hs_evidence_file_2', '=', null],
       ['hs_evidence_file_3', '=', null],
   ]);
   ```
   Filters all shipments where **no evidence files are attached**.
3. **Action:** Enables operators to immediately identify unverified classifications and attach signed forms or rulings before final customs assessment.

---

### Module 4: Master Data Management (`/admin/master-*`)

Central configuration tables in `ccd_system_rds`:

| Master Entity | Route | Table | Business Purpose |
|---|---|---|---|
| **Customers** | `/admin/master-customers` | `master_customers` | Over 2,100 corporate clients with Tax IDs and active status flags. |
| **Customer Name Tool** | `/admin/master-customers/updatename` | `master_customers` | Utility to batch-replace or harmonize customer names across historical CCD records. |
| **Branches** | Handled internally | `master_branches` | SNC branch locations (e.g. `B01` Head Office, `B02` Laem Chabang, `B03` Bangplee). |
| **Quantity Units** | `/admin/master-units` | `master_units` | Customs measurement codes (e.g., KGM, C62, LTR, MTK, NMB). |
| **Statistics Codes** | `/admin/master-statistics` | `master_statistics` | 3-digit Thai Customs statistical classifications. |
| **Customs Licenses** | `/admin/master-licenses` | `master_licenses` | Import/Export license types (e.g. TISI, FDA, Hazardous Substance permits). |

---

### Module 5: Reporting, APIs & Cloud Storage

1. **Weekly HS Code Export (`POST /export_weekly_hs_code`):**
   - Handled by `ExcelCreateController::ExportWeekklyHsCode`.
   - Allows exporting classification records by Job Category (`CCD`, `CST`, `NTB`) and date/number range.
   - Produces formatted `.xlsx` files: `HS_Code{MM}-{From}_{MM}-{To}.xlsx`.
2. **Job Number Sequence API (`/api/ccd_get_ym/{yymm}`):**
   - Handled by `apiCcdController@getMonthlyData`.
   - Queries `CustomerClearanceData` for existing records matching prefix `CCD{YYMM}%`.
   - Computes the next zero-padded sequence number (e.g. `CCD2305-0001` -> `CCD2305-0002`).
   - Returns the latest 5 registered jobs for live client-side validation.
3. **AWS S3 Document Sync:**
   - In production (`APP_ENV=production`), all uploaded documents are mirrored to AWS S3 (`AWS_BUCKET=sn-ccd-database-system-th` in `ap-southeast-7`) via Flysystem v3.

---

### Module 6: Identity, Security & Role-Based Access Control (RBAC)

1. **Dual Guard Model:**
   - `web` guard: Standard session auth via `App\Models\Administrator` (on `authdb`).
   - `admin` guard: Laravel-Admin session auth via `Encore\Admin\Auth\Database\Administrator`.
2. **Domain Whitelisting:**
   - Self-registration in `RegisteredUserController` strictly requires `@th.nissin-asia.com` email addresses:
     ```php
     if ($domain[1] !== "th.nissin-asia.com") {
         return back()->withErrors(['session_error' => 'Not available to external Siam Nistrans users.']);
     }
     ```
3. **Roles & Permissions:**
   - **Administrator (Role ID 1):** Full access (`*`) to all data, menus, logs, user accounts, and system configuration.
   - **PowerUser (Role ID 2):** Can search, create, upload, and edit CCDs and master tables; restricted from IT user management.
   - **Staff (Role ID 3):** Standard operators; can search CCD records and view master reference data.
   - **Viewer / Restricted (Role IDs 4 & 5):** Read-only access to search screens.

---

## 5. Summary of Architecture & Data Model

```
+-------------------------------------------------------------+
|                     CLIENT / BROWSER                        |
+------------------------------+------------------------------+
                               |
            +------------------+------------------+
            |                                     |
            v                                     v
+-----------------------+             +-----------------------+
|  Laravel-Admin Panel  |             | Standard Web Routes   |
|  - Dashboard Home     |             | - /profile            |
|  - CCD Grid & Form    |             | - /dashboard          |
|  - Upload Form        |             | - /login, /register   |
|  - Outstanding Grid   |             | - /export_weekly_...  |
|  - Master Settings    |             | - /ccd/import/*       |
+-----------+-----------+             +-----------+-----------+
            |                                     |
            +------------------+------------------+
                               |
                               v
            +-------------------------------------+
            |        Eloquent ORM Layer           |
            +------------------+------------------+
                               |
            +------------------+------------------+
            |                                     |
            v                                     v
+-----------------------+             +-----------------------+
| Connection: `mysql`   |             | Connection: `authdb`  |
| DB: `ccd_system_rds`  |             | DB: `sn_user_auth_rds`|
| --------------------- |             | --------------------- |
| customer_clearance_...|             | admin_users           |
| master_customers      |             | ccd_admin_roles       |
| master_branches       |             | ccd_admin_permissions |
| master_units          |             | ccd_admin_menu        |
| master_statistics     |             | ccd_admin_role_users  |
| master_licenses       |             | admin_departments     |
| password_resets       |             | admin_divisions       |
+-----------------------+             +-----------------------+
```

---

## 6. Recommendations & Invariants for Maintenance

1. **Preserve `DatabaseTransactions` in Test Suite:**
   Never revert to `RefreshDatabase` in tests. `DatabaseTransactions` isolates tests via SQL transaction rollback (`BEGIN ... ROLLBACK`) on both `mysql` and `authdb`, ensuring that business records in `ccd_system_rds` are never dropped.
2. **Keep Controller Visibility Public:**
   All action methods on `CustomerClearanceDataController` (such as `public function upload()`) must remain `public` so Laravel's router can invoke them without 500/403 reflection errors.
3. **Keep `authdb` Connection on Password Resets:**
   `config/auth.php` must maintain `'connection' => 'authdb'` under `passwords.users` so password reset tokens query the same database where `admin_users` resides.



------------------------------------------------------------------------------------
The application is designed around two distinct MySQL/MariaDB database schemas:

```
+-------------------------------------------------------+
|                   Laravel Application                 |
+---------------------------+---------------------------+
                            |
           +----------------+----------------+
           |                                 |
           v                                 v
   [mysql Connection]               [authdb Connection]
    `ccd_system_rds`                 `sn_user_auth_rds`
   -------------------              -------------------
   - customer_clearance_data        - admin_users (User / Administrator)
   - master_customers               - admin_offices
   - master_branches                - admin_departments
   - master_units                   - admin_divisions
   - master_licenses                - ccd_admin_roles (RBAC)
   - master_statistics              - ccd_admin_permissions (RBAC)
   - password_resets                - ccpm_admin_user_permissions
   - failed_jobs                    - ccpm_admin_role_users
   - migrations                     - ccpm_admin_role_permissions
   - personal_access_tokens         - ccd_admin_menu (Admin Menu)
                                    - ccd_admin_role_users (Pivot)
                                    - ccd_admin_role_permissions
                                    - ccd_admin_operation_log
                                    - password_resets (auth tokens)
                                    - ccd_admin_role_menu
                                    - ccd_admin_role_users
                                    - ccd_admin_user_permissions
                                    - ccpm_admin_permissions
                                    - ccpm_admin_roles
                                    - ccpm_admin_role_menu
                                    - sfa_user_roles

```
------------------------------------------------------------------------------------
## 7. Test Suite Results (100% PASS)

All 26 automated PHPUnit tests pass cleanly:

```text
PASS  Tests\Unit\ExampleTest
✓ that true is true

PASS  Tests\Feature\Auth\AuthenticationTest
✓ login screen can be rendered
✓ users can authenticate using the login screen
✓ users can not authenticate with invalid password

PASS  Tests\Feature\Auth\EmailVerificationTest
✓ email verification screen can be rendered
✓ email can be verified
✓ email is not verified with invalid hash

PASS  Tests\Feature\Auth\PasswordConfirmationTest
✓ confirm password screen can be rendered
✓ password can be confirmed
✓ password is not confirmed with invalid password

PASS  Tests\Feature\Auth\PasswordResetTest
✓ reset password link screen can be rendered
✓ reset password link can be requested
✓ reset password screen can be rendered
✓ password can be reset with valid token

PASS  Tests\Feature\Auth\PasswordUpdateTest
✓ password can be updated
✓ correct password must be provided to update password

PASS  Tests\Feature\Auth\RegistrationTest
✓ registration screen can be rendered
✓ new users can register

PASS  Tests\Feature\ExampleTest
✓ the application returns a successful response

PASS  Tests\Feature\ProfileTest
✓ profile page is displayed
✓ profile information can be updated
✓ email verification status is unchanged when the email address is unchanged
✓ user can delete their account
✓ correct password must be provided to delete account

PASS  Tests\Feature\UploadRouteTest
✓ guest is redirected to admin login when accessing upload
✓ authenticated admin can access upload page