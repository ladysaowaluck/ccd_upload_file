<?php
// Temporary diagnostic script - delete after use
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "=== Admin User Diagnostic ===\n\n";

// 1. Find user via raw DB query on authdb connection
$rawUser = DB::connection('authdb')
    ->table('admin_users')
    ->where('email', 'admin@th.nissin-asia.com')
    ->first();

if (!$rawUser) {
    echo "❌ User NOT FOUND in admin_users table on authdb connection\n";
    echo "   Listing all admin emails:\n";
    $all = DB::connection('authdb')->table('admin_users')->pluck('email');
    foreach ($all as $email) {
        echo "   - $email\n";
    }
    exit(1);
}

echo "✅ User found:\n";
echo "   ID             : {$rawUser->id}\n";
echo "   Email          : {$rawUser->email}\n";
echo "   email_verified : " . ($rawUser->email_verified_at ?? 'NULL') . "\n";
echo "   Password hash  : {$rawUser->password}\n\n";

// 2. Check hash
$passwordToTest = 'Admin@1234';
$hashMatch = Hash::check($passwordToTest, $rawUser->password);
echo "2. Hash::check('Admin@1234', stored_hash) => " . ($hashMatch ? "TRUE ✅" : "FALSE ❌") . "\n\n";

// 3. Try Auth::attempt
$attempted = Auth::attempt(['email' => 'admin@th.nissin-asia.com', 'password' => $passwordToTest]);
echo "3. Auth::attempt => " . ($attempted ? "SUCCESS ✅" : "FAILED ❌") . "\n\n";

// 4. Specific diagnosis
if (!$hashMatch) {
    echo "  PROBLEM: Stored hash does NOT match 'Admin@1234'.\n";
    echo "   SOLUTION: Run the fix command to reset the password.\n";
} elseif (!$attempted) {
    echo "  PROBLEM: Hash matches but Auth::attempt fails.\n";
    echo "   Possible causes:\n";
    echo "   - email_verified_at is NULL (email not verified)\n";
    echo "   - Account is inactive/blocked\n";
    if (!$rawUser->email_verified_at) {
        echo "   => email_verified_at IS NULL — this is likely the cause!\n";
    }
} else {
    echo " Authentication works correctly. Login should succeed.\n";
}

