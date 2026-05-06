<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== أسماء أشعة عادية ===\n\n";

$records = DB::table('radiology_types')
    ->where('subcategory', 'أشعة عادية')
    ->take(10)
    ->get(['name', 'code']);

foreach ($records as $record) {
    echo $record->name . ' (' . $record->code . ')' . "\n";
}

echo "\n=== أسماء أشعة مقطعية ===\n\n";

$records = DB::table('radiology_types')
    ->where('subcategory', 'أشعة مقطعية')
    ->take(10)
    ->get(['name', 'code']);

foreach ($records as $record) {
    echo $record->name . ' (' . $record->code . ')' . "\n";
}
