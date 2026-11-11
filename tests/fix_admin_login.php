<?php
// Uses mysql CLI to run a PHP-generated SQL file with the correct bcrypt hash

$email = 'admin@th.nissin-asia.com';
$plainPassword = 'Admin@1234';
$newHash = password_hash($plainPassword, PASSWORD_BCRYPT);

$sql = "UPDATE sn_user_auth_rds.admin_users "
    . "SET password='" . addslashes($newHash) . "', email_verified_at=NOW() "
    . "WHERE email='" . addslashes($email) . "';";

$sqlFile = __DIR__ . '/fix_password_tmp.sql';
file_put_contents($sqlFile, $sql);

echo "Generated hash: $newHash\n";
echo "SQL file: $sqlFile\n";
echo "SQL: $sql\n";

// Run mysql via exec (safe path, no shell interpolation)
$mysqlPath = 'C:\\xampp\\mysql\\bin\\mysql.exe';
$cmd = '"' . $mysqlPath . '" -u root < "' . $sqlFile . '" 2>&1';
echo "Running: $cmd\n";
$output = [];
$code = 0;
exec($cmd, $output, $code);
echo "Exit code: $code\n";
echo implode("\n", $output) . "\n";

// Verify
$verify_sql = "SELECT id, email, email_verified_at, password FROM sn_user_auth_rds.admin_users WHERE email='" . addslashes($email) . "';";
$verify_file = __DIR__ . '/verify_tmp.sql';
file_put_contents($verify_file, $verify_sql);
$vcmd = '"' . $mysqlPath . '" -u root < "' . $verify_file . '" 2>&1';
exec($vcmd, $vout, $vcode);
echo "\nVerification:\n";
echo implode("\n", $vout) . "\n";

$storedHash = '';
foreach ($vout as $line) {
    if (strpos($line, '$2y$') !== false) {
        $storedHash = $line;
        break;
    }
}

if ($storedHash) {
    $ok = password_verify($plainPassword, $storedHash);
    echo "\npassword_verify check: " . ($ok ? "✅ TRUE - Login will work!" : "❌ FALSE - Hash mismatch!") . "\n";
} else {
    echo "\nCould not extract hash from output for verification.\n";
}

// Cleanup
@unlink($sqlFile);
@unlink($verify_file);
