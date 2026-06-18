<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$roles = Spatie\Permission\Models\Role::all();
foreach ($roles as $role) {
    echo $role->name . " | " . $role->guard_name . PHP_EOL;
}
