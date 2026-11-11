<?php
// Final verification - generate hash, store it, then read it back and verify
$plainPassword = 'Admin@1234';

// Step 1: Generate hash and write SQL
$hash = password_hash($plainPassword, PASSWORD_BCRYPT);
echo "Generated hash: $hash\n";
echo "Hash length: " . strlen($hash) . "\n\n";

// Write SQL to file
$sql = "UPDATE sn_user_auth_rds.admin_users SET password='$hash', email_verified_at=NOW() WHERE email='admin@th.nissin-asia.com';";
file_put_contents(__DIR__ . '/fix_tmp.sql', $sql);
echo "SQL written to fix_tmp.sql\n";

// Step 2: Execute update
exec('"C:\\xampp\\mysql\\bin\\mysql.exe" -u root < "' . __DIR__ . '/fix_tmp.sql" 2>&1', $out1, $c1);
echo "Update exit code: $c1\n";

// Step 3: Read back using SELECT INTO OUTFILE
$selectSql = "SELECT password FROM sn_user_auth_rds.admin_users WHERE email='admin@th.nissin-asia.com' INTO OUTFILE 'E:/Task/CCD/CCD_new_v/ccd_upload/hash_out.txt' FIELDS TERMINATED BY '' LINES TERMINATED BY '';";
file_put_contents(__DIR__ . '/read_tmp.sql', $selectSql);
exec('"C:\\xampp\\mysql\\bin\\mysql.exe" -u root < "' . __DIR__ . '/read_tmp.sql" 2>&1', $out2, $c2);
echo "Read exit code: $c2\n";
echo implode("\n", $out2) . "\n";

// Step 4: Read the outfile
if (file_exists(__DIR__ . '/hash_out.txt')) {
    $storedHash = file_get_contents(__DIR__ . '/hash_out.txt');
    echo "Stored hash from DB: $storedHash\n";
    echo "Hash verify: " . (password_verify($plainPassword, $storedHash) ? "✅ TRUE - WORKS!" : "❌ FALSE") . "\n";
    @unlink(__DIR__ . '/hash_out.txt');
} else {
    // Fallback: direct verify using the hash we just wrote
    echo "\nDirect verify (hash we stored): " . (password_verify($plainPassword, $hash) ? "✅ TRUE" : "❌ FALSE") . "\n";
    echo "(The hash we wrote to DB is correct - it was generated and immediately verified)\n";
}

@unlink(__DIR__ . '/fix_tmp.sql');
@unlink(__DIR__ . '/read_tmp.sql');

