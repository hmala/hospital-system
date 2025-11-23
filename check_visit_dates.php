<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;

echo "Checking visit statuses and dates:\n";
echo "=================================\n";

$visits = Visit::all();
foreach($visits as $v) {
    echo "Visit ID: {$v->id}, Status: {$v->status}, Date: {$v->visit_date}\n";
}

echo "\nToday visits:\n";
$today = Visit::whereDate('visit_date', today())->get();
foreach($today as $v) {
    echo "Visit ID: {$v->id}, Status: {$v->status}, Date: {$v->visit_date}\n";
}