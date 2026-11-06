# Excel → `customer_clearance_data` Mapping

> Source of truth: `ccd_system_rds.sql` table definition (lines 30–69)

---

## Table: `customer_clearance_data` (ccd_system_rds)

| Column | Type | Nullable | Max |
|---|---|---|---|
| `id` | bigint PK | NO | — |
| `job_no` | varchar | NO | 12 |
| `master_customer_id` | bigint FK | YES | — |
| `customer_name` | varchar | YES | 50 |
| `master_branch_id` | bigint FK | NO | — |
| `imp_exp` | tinyint | NO | — |
| `product_description_en` | varchar | NO | 300 |
| `product_description_th` | varchar | NO | 300 |
| `product_code_1` | varchar | YES | 300 |
| `product_code_2` | varchar | YES | 300 |
| `product_year` | int | YES | — |
| `brand_name` | varchar | YES | 50 |
| `model_name` | varchar | YES | 50 |
| `materials` | varchar | YES | 300 |
| `usage` | varchar | YES | 300 |
| `function` | varchar | YES | 300 |
| `position` | varchar | YES | 300 |
| `country_of_origin` | varchar | YES | 30 |
| `note` | varchar | YES | 500 |
| `hs_code` | varchar | NO | 8 |
| `sequence_code` | varchar | YES | 5 |
| `statistic_code` | varchar | NO | 3 |
| `quantity_unit` | varchar | YES | 3 |
| `privilege_code` | varchar | YES | 3 |
| `tariff_ad_valorem` | decimal(6,2) | YES | — |
| `tariff_specific_rate_unit` | varchar | YES | 10 |
| `tariff_specific_rate_baht` | decimal(10,3) | YES | — |
| `reference_file_1` | varchar | YES | 191 |
| `reference_file_2` | varchar | YES | 191 |
| `hs_evidence_file_1` | varchar | YES | 191 |
| `hs_evidence_file_2` | varchar | YES | 191 |
| `hs_evidence_file_3` | varchar | YES | 191 |
| `hs_evidence_file_4` | varchar | YES | 191 |
| `create_date` | date | NO | — |
| `update_date` | date | NO | — |
| `edit_user_id` | bigint FK | NO | — |
| `created_at` | timestamp | YES | — |
| `updated_at` | timestamp | YES | — |

---

## SingleSheetImport (Cell-mapped template)

File: `app/Imports/SingleSheetImport.php`
Interface: `WithMappedCells` — reads specific cell addresses from the Excel template.

| Excel Cell | Description | DB Column | Transform |
|---|---|---|---|
| N1 | Job number | `job_no` | Auto-generated if blank (`CCD` prefix + sequence). Validated 12-char if provided. |
| D4 | Customer name | `customer_name` + `master_customer_id` | Looked up in `master_customers` by `company_name` where `is_enable=0` |
| O2 | Branch code / name | `master_branch_id` | Looked up in `master_branches` by `branch_code` or `branch_name` |
| — | — | `imp_exp` | Hardcoded `1` (Import) |
| G10 | Product description EN | `product_description_en` | Direct, max 300 |
| G12 | Product description TH | `product_description_th` | Direct, max 300 |
| G8 | Product code 1 | `product_code_1` | Direct, max 300 |
| F25 | Brand name | `brand_name` | Direct, max 50 |
| G14 | Materials | `materials` | Direct, max 300 |
| G16 | Usages | `usage` | Direct, max 300 |
| G18 + A20–A23 | Function (5 cells) | `function` | Concatenated with space, max 300 |
| F24 | Country of origin | `country_of_origin` | Direct, max 30 |
| L5 + H26 + J28–J32 | Notes (7 cells) | `note` | Joined with `\n`, max 500 |
| G37 | HS Code (8 digits) | `hs_code` | `mb_substr(trim(...), 0, 8)` |
| H37 | Sequence code | `sequence_code` | `mb_substr(trim(...), 0, 5)` |
| J37 | Statistic code (3 digits) | `statistic_code` | `mb_substr(trim(...), 0, 3)`, default `'000'` |
| K37 | Quantity unit | `quantity_unit` | `mb_substr(trim(...), 0, 3)` |
| G38 | Privilege code | `privilege_code` | `mb_substr(trim(...), 0, 3)` |
| N37 | Tariff ad valorem rate | `tariff_ad_valorem` | Decimal |
| — | Today | `create_date` | `Carbon::today()` |
| — | Today | `update_date` | `Carbon::today()` |
| — | Auth user | `edit_user_id` | `auth()->id() ?? 1` |

### Columns NOT populated from SingleSheet Excel
| Column | Reason |
|---|---|
| `product_code_2` | Not in template — entered manually via edit UI |
| `product_year` | Not in template |
| `model_name` | Not in template |
| `position` | Not in template |
| `tariff_specific_rate_unit` | Not in template |
| `tariff_specific_rate_baht` | Not in template |
| `reference_file_1`, `reference_file_2` | Uploaded separately via file upload UI |
| `hs_evidence_file_1`–`hs_evidence_file_4` | Uploaded separately via file upload UI |

---

## MultiSheetImport (Tabular / CSV-style template)

File: `app/Imports/MultiSheetImport.php`
Interface: `WithHeadingRow` — reads rows with column headers.

| Excel Column Header | DB Column | Transform |
|---|---|---|
| `job_no` | `job_no` | Auto-generated if blank; duplicate-checked |
| `customer` | `customer_name` + `master_customer_id` | Looked up in `master_customers.company_name` where `is_enable=0` |
| `snc_branch` | `master_branch_id` | Looked up in `master_branches` by `branch_code` or `branch_name` |
| `import_export` | `imp_exp` | `imp`/`import`/`0` → 0, `exp`/`export`/`1` → 1, default 1 |
| `english_description` | `product_description_en` | Direct, max 300 |
| `thai_description` | `product_description_th` | Direct, max 300 |
| `product_code_1` | `product_code_1` | Direct, max 300 |
| `brand_name` | `brand_name` | Direct, max 50 |
| `materials` | `materials` | Direct, max 300 |
| `usage` | `usage` | Direct, max 300 |
| `function` | `function` | Direct, max 300 |
| `country_of_origin` | `country_of_origin` | Direct, max 30 |
| `note` | `note` | Direct, max 500 |
| `hs_code_8_digits` | `hs_code` | `mb_substr(trim(...), 0, 8)` |
| `sequence_code_5_digits` | `sequence_code` | `mb_substr(trim(...), 0, 5)` |
| `statistic_code_3_digits` | `statistic_code` | `mb_substr(trim(...), 0, 3)`, default `'000'` |
| `quantity_unit_3_digits` | `quantity_unit` | `mb_substr(trim(...), 0, 3)` |
| `privilege_code_3_digits` | `privilege_code` | `mb_substr(trim(...), 0, 3)` |
| `tariff_rate_ad_valorem_rate` | `tariff_ad_valorem` | Decimal |
| — | `create_date` | `Carbon::today()` |
| — | `update_date` | `Carbon::today()` |
| — | `edit_user_id` | `auth()->id() ?? 1` |

### Columns NOT populated from MultiSheet Excel
Same as SingleSheet: `product_code_2`, `product_year`, `model_name`, `position`,
`tariff_specific_rate_unit`, `tariff_specific_rate_baht`,
`reference_file_1`, `reference_file_2`, `hs_evidence_file_1`–`hs_evidence_file_4`.

---

## Database Config Summary

| Connection | `.env` vars | Database | Used by |
|---|---|---|---|
| `mysql` (default) | `DB_HOST`, `DB_DATABASE=ccd_system_rds` | `ccd_system_rds` | All Business models |
| `authdb` | `AUTH_DB_HOST`, `AUTH_DB_DATABASE=sn_user_auth_rds` | `sn_user_auth_rds` | Auth models (AdminUser, roles, permissions) |

## Migrations — Allowed Tables Only

| Migration file | Tables created | In SQL? |
|---|---|---|
| `0001_01_01_000000_create_users_table.php` | ~~users, password_reset_tokens, sessions~~ (commented out) | ❌ none of these |
| `0001_01_01_000001_create_cache_table.php` | ~~cache, cache_locks~~ (commented out) | ❌ none of these |
| `0001_01_01_000002_create_jobs_table.php` | ~~jobs, job_batches~~ (commented out), **failed_jobs** (active) | ✅ failed_jobs only |

