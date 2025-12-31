<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "Checking session data...\n";
echo str_repeat("-", 80) . "\n";

if (isset($_SESSION)) {
    echo "Session ID: " . session_id() . "\n";
    echo "Session data: " . print_r($_SESSION, true) . "\n";
}

// Check for auth user via session
if (isset($_SESSION['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'])) {
    $userId = $_SESSION['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'];
    $user = DB::table('users')->where('id', $userId)->first();
    
    if ($user) {
        echo "\nCurrently Logged In User:\n";
        echo "ID: " . $user->id . "\n";
        echo "Name: " . $user->name . "\n";
        echo "Email: " . $user->email . "\n";
        echo "Role: " . $user->role . "\n\n";
        
        $allowedRoles = ['admin', 'lab_staff'];
        $hasAccess = in_array($user->role, $allowedRoles);
        
        echo "Access to Lab Tests: " . ($hasAccess ? "✓ ALLOWED" : "✗ DENIED") . "\n";
        echo "Required roles: " . implode(', ', $allowedRoles) . "\n";
        echo "Current role: " . $user->role . "\n";
    }
} else {
    echo "No user is currently logged in via web session.\n";
}
