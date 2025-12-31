<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "عدد السجلات في جدول ICD10: " . DB::table('icd10_codes')->count() . "\n\n";

if (DB::table('icd10_codes')->count() > 0) {
    echo "أول 10 سجلات:\n";
    echo str_repeat("-", 100) . "\n";
    
    $codes = DB::table('icd10_codes')->limit(10)->get();
    foreach ($codes as $code) {
        echo "Code: {$code->code} | Description: {$code->description}\n";
    }
} else {
    echo "الجدول فارغ!\n";
}
