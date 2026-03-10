<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();
$role=\Spatie\Permission\Models\Role::findByName('lab_staff');
echo 'lab_staff perms: '.implode(',', $role->permissions->pluck('name')->toArray());
