<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

$users = User::whereNotNull('role')->get();
$assigned = 0;
$created = [];
foreach ($users as $user) {
    $roleName = trim($user->role);
    if (empty($roleName)) continue;
    if (!Role::where('name', $roleName)->exists()) {
        Role::create(['name' => $roleName]);
        $created[] = $roleName;
        echo "Created role: $roleName\n";
    }
    if (!$user->hasRole($roleName)) {
        $user->assignRole($roleName);
        $assigned++;
        echo "Assigned role '$roleName' to user {$user->id} ({$user->name})\n";
    }
}

echo "Summary: processed " . count($users) . " users. Roles created: " . count($created) . "; roles assigned: $assigned\n";
