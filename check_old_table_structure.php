<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "فحص بنية جدول radiology_requests:\n";
echo "==========================================\n\n";

$columns = DB::select("DESCRIBE radiology_requests");

echo "الأعمدة الموجودة:\n";
foreach ($columns as $col) {
    echo "  - {$col->Field} ({$col->Type})\n";
}

echo "\n\nفحص طلب إيكو من الجدول القديم:\n";
$request = DB::table('radiology_requests')->where('id', 10)->first();

if ($request) {
    echo "طلب #10:\n";
    foreach ($request as $key => $value) {
        $displayValue = $value ?? 'NULL';
        if (strlen($displayValue) > 100) {
            $displayValue = substr($displayValue, 0, 100) . '...';
        }
        echo "  {$key}: {$displayValue}\n";
    }
}
