-- ==============================================================================
-- SQL Script: Set saowaluck.k@th.nissin-asia.com as Super Administrator
-- Target Database: sn_user_auth_rds
-- ==============================================================================

USE sn_user_auth_rds;

-- 1. Ensure the Administrator role exists with id = 1 and slug = 'administrator'
INSERT INTO ccd_admin_roles (id, name, slug, created_at, updated_at)
VALUES (1, 'Administrator', 'administrator', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    name = 'Administrator', 
    slug = 'administrator',
    updated_at = NOW();

-- 2. Verify user account and ensure active status
UPDATE admin_users
SET 
    email_verified_at = IFNULL(email_verified_at, NOW()),
    disabled = 0,
    updated_at = NOW()
WHERE email = 'saowaluck.k@th.nissin-asia.com';

-- 3. Assign the Administrator role (role_id = 1) to saowaluck.k@th.nissin-asia.com
DELETE FROM ccd_admin_role_users 
WHERE user_id = (SELECT id FROM admin_users WHERE email = 'saowaluck.k@th.nissin-asia.com' LIMIT 1)
  AND role_id = 1;

INSERT INTO ccd_admin_role_users (role_id, user_id, created_at, updated_at)
SELECT 1, id, NOW(), NOW()
FROM admin_users 
WHERE email = 'saowaluck.k@th.nissin-asia.com';

-- 4. Verify the setup
SELECT 
    u.id AS user_id,
    u.username,
    u.name,
    u.email,
    u.email_verified_at,
    u.disabled,
    r.id AS role_id,
    r.name AS role_name,
    r.slug AS role_slug
FROM admin_users u
JOIN ccd_admin_role_users ru ON u.id = ru.user_id
JOIN ccd_admin_roles r ON ru.role_id = r.id
WHERE u.email = 'saowaluck.k@th.nissin-asia.com';

