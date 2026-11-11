UPDATE sn_user_auth_rds.admin_users 
SET 
    email_verified_at = NOW()
WHERE email = 'admin@th.nissin-asia.com';

SELECT id, email, email_verified_at, password FROM sn_user_auth_rds.admin_users WHERE email = 'admin@th.nissin-asia.com';

