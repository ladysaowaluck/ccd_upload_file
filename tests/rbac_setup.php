<?php
/**
 * RBAC Setup Script
 * Seeds the 4 required roles, their permissions, and wires them together.
 *
 * Roles:
 *   standard  → read-only  (GET clearance, outstanding, export)
 *   staff     → upload, edit, create  (+ standard)
 *   power_user→ upload, edit, create, download, assign roles  (+ staff)
 *   administrator → everything
 *
 * Run with: C:\xampp\php\php.exe rbac_setup.php
 */

$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=sn_user_auth_rds', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
echo "Connected.\n\n";

// ─────────────────────────────────────────────
// 1. Upsert Roles
// ─────────────────────────────────────────────
$roles = [
    ['name' => 'Standard User',   'slug' => 'standard'],
    ['name' => 'Staff User',      'slug' => 'staff'],
    ['name' => 'Power User',      'slug' => 'power_user'],
    ['name' => 'Administrator',   'slug' => 'administrator'],
];

$roleIds = [];
foreach ($roles as $r) {
    $stmt = $pdo->prepare("SELECT id FROM ccd_admin_roles WHERE slug = ?");
    $stmt->execute([$r['slug']]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $pdo->prepare("UPDATE ccd_admin_roles SET name = ? WHERE slug = ?")
            ->execute([$r['name'], $r['slug']]);
        $roleIds[$r['slug']] = (int)$existing;
        echo "  Updated role: {$r['name']} (id={$existing})\n";
    } else {
        $pdo->prepare("INSERT INTO ccd_admin_roles (name, slug) VALUES (?, ?)")
            ->execute([$r['name'], $r['slug']]);
        $id = (int)$pdo->lastInsertId();
        $roleIds[$r['slug']] = $id;
        echo "  Created role: {$r['name']} (id={$id})\n";
    }
}

// ─────────────────────────────────────────────
// 2. Upsert Permissions (clean, minimal set)
// ─────────────────────────────────────────────
$permissions = [
    // slug                  name                        method      path
    ['clearance.read',      'View Clearances',           'GET',      '/clearance|/clearance/*|/compliance/outstanding'],
    ['clearance.create',    'Create Clearances',         'GET,POST', '/clearance/create|/clearance/store'],
    ['clearance.edit',      'Edit Clearances',           'GET,PUT',  '/clearance/*/edit|/clearance/*'],
    ['clearance.upload',    'Upload / Import Excel',     'GET,POST', '/clearance/upload|/clearance/import/*'],
    ['clearance.download',  'Download Documents',        'GET',      '/clearance/*/download/*'],
    ['clearance.replicate', 'Replicate Clearances',      'GET,POST', '/clearance/*/replicate'],
    ['export.weekly',       'Weekly HS Export',          'GET,POST', '/export/weekly'],
    ['admin.users',         'Assign Roles / User Mgmt', 'GET,PUT',  '/admin/users|/admin/users/*'],
    ['admin.master',        'Master Data & Customers',   'GET,POST', '/admin/customers|/admin/master/*'],
];

$permIds = [];
foreach ($permissions as [$slug, $name, $method, $path]) {
    $stmt = $pdo->prepare("SELECT id FROM ccd_admin_permissions WHERE slug = ?");
    $stmt->execute([$slug]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $pdo->prepare("UPDATE ccd_admin_permissions SET name=?, http_method=?, http_path=? WHERE slug=?")
            ->execute([$name, $method, $path, $slug]);
        $permIds[$slug] = (int)$existing;
        echo "  Updated permission: $slug\n";
    } else {
        $pdo->prepare("INSERT INTO ccd_admin_permissions (name, slug, http_method, http_path) VALUES (?,?,?,?)")
            ->execute([$name, $slug, $method, $path]);
        $id = (int)$pdo->lastInsertId();
        $permIds[$slug] = $id;
        echo "  Created permission: $slug (id=$id)\n";
    }
}

// ─────────────────────────────────────────────
// 3. Clear and re-assign role→permission mappings
// ─────────────────────────────────────────────
$rolePermissions = [
    'standard' => [
        'clearance.read',
        'export.weekly',
    ],
    'staff' => [
        'clearance.read',
        'clearance.create',
        'clearance.edit',
        'clearance.upload',
        'clearance.replicate',
        'export.weekly',
    ],
    'power_user' => [
        'clearance.read',
        'clearance.create',
        'clearance.edit',
        'clearance.upload',
        'clearance.download',
        'clearance.replicate',
        'export.weekly',
        'admin.users',   // assign roles
    ],
    'administrator' => array_column($permissions, 0), // all
];

echo "\n--- Assigning role permissions ---\n";
foreach ($rolePermissions as $roleSlug => $permSlugs) {
    if (!isset($roleIds[$roleSlug])) {
        echo "  SKIP (role not found): $roleSlug\n";
        continue;
    }
    $rid = $roleIds[$roleSlug];

    // Clear existing
    $pdo->prepare("DELETE FROM ccd_admin_role_permissions WHERE role_id = ?")
        ->execute([$rid]);

    // Insert fresh
    foreach ($permSlugs as $ps) {
        if (!isset($permIds[$ps])) {
            echo "  WARNING: permission not found: $ps\n";
            continue;
        }
        $pdo->prepare("INSERT INTO ccd_admin_role_permissions (role_id, permission_id) VALUES (?,?)")
            ->execute([$rid, $permIds[$ps]]);
    }

    echo "  Role '$roleSlug' => " . implode(', ', $permSlugs) . "\n";
}

// ─────────────────────────────────────────────
// 4. Summary
// ─────────────────────────────────────────────
echo "\n=== Role IDs (use these in DB) ===\n";
foreach ($roleIds as $slug => $id) {
    echo "  [$id] $slug\n";
}
echo "\nDone.\n";

