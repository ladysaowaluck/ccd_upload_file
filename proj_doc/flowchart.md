# CCD System Architecture & Technical Flowchart

This document illustrates the technical architecture, authentication workflows, file upload process, and database interactions of the CCD (Customer Clearance Data) application.

---

## 1. High-Level Technical Architecture

```mermaid
graph TD
    Client["Client Browser"] -->|HTTP / HTTPS| WebServer["Web Server / Nginx / Artisan"]
    
    subgraph LaravelApp ["Laravel 10.50.4 (PHP 8.2.35)"]
        Kernel["HTTP Kernel & Middleware Stack"]
        Router["Route Dispatcher"]
        
        AdminController["Admin Controllers<br/>(Laravel-Admin v1.8.19)"]
        UploadController["CcdUploadController<br/>(Excel & File Uploads)"]
        AuthController["Auth Controllers<br/>(Breeze & Admin Auth)"]
        
        ExcelEngine["Maatwebsite Excel 3.1.70"]
        S3Storage["AWS S3 Flysystem v3"]
    end
    
    WebServer --> Kernel
    Kernel --> Router
    Router --> AdminController
    Router --> UploadController
    Router --> AuthController
    
    UploadController --> ExcelEngine
    UploadController -.->|Production Environment| S3Storage
    
    subgraph Databases ["MySQL / MariaDB Datastores"]
        MainDB[("ccd_system_rds<br/>(Default DB Connection)")]
        AuthDB[("sn_user_auth_rds<br/>(authdb Connection)")]
    end
    
    AdminController --> AuthDB
    AdminController --> MainDB
    UploadController --> MainDB
    AuthController --> AuthDB
    ExcelEngine --> MainDB
```

---

## 2. Authentication & RBAC Flow

```mermaid
sequenceDiagram
    autonumber
    actor User as User / Staff
    participant Browser
    participant LaravelRoute as Route Dispatcher
    participant AdminAuth as Laravel-Admin Guard (admin)
    participant AuthDB as Database (sn_user_auth_rds)

    User->>Browser: Navigate to /admin or /admin/ccd/upload
    Browser->>LaravelRoute: GET /admin/ccd/upload
    LaravelRoute->>AdminAuth: Admin Middleware Check
    
    alt Not Logged In
        AdminAuth-->>Browser: 302 Redirect to /admin/auth/login
        User->>Browser: Enter credentials (username & password)
        Browser->>LaravelRoute: POST /admin/auth/login
        LaravelRoute->>AuthDB: Query admin_users & verify bcrypt hash
        
        alt Invalid Credentials
            AuthDB-->>Browser: Return 422 with Login Failed
        else Valid Credentials
            AuthDB-->>AdminAuth: Retrieve Roles & Permissions (ccd_admin_roles)
            AdminAuth-->>Browser: Set admin session cookie & redirect to /admin
        end
    else Logged In
        AdminAuth->>AuthDB: Check ccd_admin_permissions for current route
        alt Permission Granted
            AdminAuth->>LaravelRoute: Dispatch CustomerClearanceDataController@upload
            LaravelRoute-->>Browser: 200 OK (Render Upload UI)
        else Permission Denied
            AdminAuth-->>Browser: 403 Forbidden
        end
    end
```

---

## 3. HS Requisition Excel File Upload Flow

```mermaid
sequenceDiagram
    autonumber
    actor User as User / Operator
    participant Browser
    participant UploadUI as CustomerClearanceDataController@upload
    participant UploadController as CcdUploadController@storeHsRequestForm
    participant ExcelParser as Maatwebsite Excel Parser
    participant MainDB as Database (ccd_system_rds)
    participant LocalDisk as Local Storage (/public/reference)
    participant AWSS3 as AWS S3 Bucket

    User->>Browser: Open /admin/ccd/upload
    Browser->>UploadUI: GET /admin/ccd/upload
    UploadUI-->>Browser: Render Upload Form (Single & Multi template)

    User->>Browser: Select Excel template (.xlsx) + Reference Files & Click Submit
    Browser->>UploadController: POST /ccd/import/hsrequestform (multipart/form-data)
    
    UploadController->>ExcelParser: CustomerClearanceDataSingleImport::import()
    ExcelParser->>ExcelParser: Map cells (Job No, Customer, HS Code, Tariff, etc.)
    ExcelParser->>MainDB: Insert / Update customer_clearance_data records
    
    opt Reference or HS Evidence Files Attached
        UploadController->>LocalDisk: Store file in storage/app/public/reference/
        opt On Production Environment
            UploadController->>AWSS3: Storage::disk('s3')->putFileAs()
        end
        UploadController->>MainDB: Update reference_file_* path in customer_clearance_data
    end
    
    UploadController->>MainDB: Update imp_exp flag (Import=0, Export=1)
    UploadController-->>Browser: 302 Redirect to /admin/ccd with flash message "Update complete!"
```

---

## 4. Multi-Database Data Mapping

```mermaid
erDiagram
    %% Auth Database (sn_user_auth_rds)
    admin_users ||--o{ ccd_admin_role_users : "has"
    ccd_admin_roles ||--o{ ccd_admin_role_users : "assigned to"
    ccd_admin_roles ||--o{ ccd_admin_role_permissions : "grants"
    ccd_admin_permissions ||--o{ ccd_admin_role_permissions : "linked to"
    ccd_admin_roles ||--o{ ccd_admin_role_menu : "accesses"
    ccd_admin_menu ||--o{ ccd_admin_role_menu : "assigned to"
    admin_offices ||--o{ admin_users : "belongs to"
    admin_departments ||--o{ admin_users : "belongs to"
    admin_divisions ||--o{ admin_users : "belongs to"

    %% Main Database (ccd_system_rds)
    master_customers ||--o{ customer_clearance_data : "orders"
    master_branches ||--o{ customer_clearance_data : "handled by"
    master_units ||--o{ customer_clearance_data : "measures"
    master_statistics ||--o{ customer_clearance_data : "categorizes"
    master_licenses ||--o{ customer_clearance_data : "authorizes"

    admin_users {
        int id PK
        string username
        string password
        string email
        string name
        int office_id FK
        int department_id FK
        int division_id FK
    }

    customer_clearance_data {
        bigint id PK
        string job_no
        bigint master_customer_id FK
        bigint master_branch_id FK
        tinyint imp_exp
        string product_description_en
        string product_description_th
        string hs_code
        string reference_file_1
        string reference_file_2
        string hs_evidence_file_1
        string hs_evidence_file_2
    }
```

