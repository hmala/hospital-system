<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Removing Old Section Permissions ===\n\n";

$sectionPermissions = \Spatie\Permission\Models\Permission::where('name', 'like', '%section%')->get();

echo "Found " . $sectionPermissions->count() . " section permissions:\n";
foreach ($sectionPermissions as $perm) {
    echo "  - {$perm->name}\n";
}

if ($sectionPermissions->count() > 0) {
    echo "\nDeleting...\n";
    foreach ($sectionPermissions as $perm) {
        $perm->delete();
        echo "  ✓ Deleted: {$perm->name}\n";
    }
    echo "\n✓ All section permissions removed!\n";
} else {
    echo "\nNo section permissions found.\n";
}

// التحقق من صلاحيات الكاشير بعد الحذف
echo "\n=== Cashier Permissions After Cleanup ===\n";
$cashier = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'cashier');
})->first();

if ($cashier) {
    echo "User: {$cashier->name}\n";
    echo "Permissions: " . $cashier->getAllPermissions()->pluck('name')->implode(', ') . "\n";
}
