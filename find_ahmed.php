<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = App\Models\User::where('name', 'like', '%احمد%')->get();
foreach ($users as $user) {
    echo $user->id . " | " . $user->name . PHP_EOL;
}
