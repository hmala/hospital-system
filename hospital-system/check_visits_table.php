<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Visits table columns:\n";
$columns = DB::select('DESCRIBE visits');
foreach ($columns as $column) {
    echo "- {$column->Field}: {$column->Type}\n";
}