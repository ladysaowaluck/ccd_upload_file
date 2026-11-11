<?php
// Clean verification using trimmed hash read
$plainPassword = 'Admin@1234';

// Read the stored hash cleanly
exec('"C:\\xampp\\mysql\\bin\\mysql.exe" -u root --skip-column-names --raw -e "SELECT TRIM(password) FROM sn_user_auth_rds.admin_users WHERE email=\'admin@th.nissin-asia.com\' LIMIT 1" 2>&1', $out, $code);

echo "Raw output lines:\n";
foreach ($out as $i => $line) {
    echo "  [$i] len=" . strlen($line) . ": $line\n";
}

$hash = trim($out[0] ?? '');
echo "\nTrimmed hash: $hash\n";
echo "Hash length: " . strlen($hash) . "\n";
echo "password_verify: " . (password_verify($plainPassword, $hash) ? "✅ TRUE - LOGIN WORKS!" : "❌ FALSE - still wrong") . "\n";

