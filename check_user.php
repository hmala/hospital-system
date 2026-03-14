<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('name', 'LIKE', '%استعلامات%')->first();
if($user) {
    echo 'الأدوار: ' . $user->roles->pluck('name')->implode(', ') . PHP_EOL;
    echo 'الصلاحيات: ' . $user->getAllPermissions()->pluck('name')->implode(', ') . PHP_EOL;
} else {
    echo 'لم يتم العثور على مستخدم';
}