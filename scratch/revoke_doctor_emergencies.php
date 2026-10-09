<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$doctorRoles = Role::whereIn('name', ['doctor', 'طبيب', 'طبيب مقيم', 'resident'])->get();

$emergencyPermissions = ['view emergencies', 'create emergencies', 'edit emergencies', 'manage emergency vitals', 'delete emergencies'];

foreach ($doctorRoles as $role) {
    echo "Processing role: " . $role->name . "\n";
    foreach ($emergencyPermissions as $permName) {
        if ($role->hasPermissionTo($permName)) {
            $role->revokePermissionTo($permName);
            echo " - Revoked $permName from {$role->name}\n";
        }
    }
}

app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "Permission cache cleared successfully.\n";
