<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== فحص جدول emergencies - العلامات الحيوية ===\n\n";

$columns = DB::select('DESCRIBE emergencies');

echo "الأعمدة المتعلقة بالعلامات الحيوية:\n";
foreach ($columns as $col) {
    if (stripos($col->Field, 'vital') !== false || 
        stripos($col->Field, 'blood') !== false || 
        stripos($col->Field, 'heart') !== false || 
        stripos($col->Field, 'temperature') !== false ||
        stripos($col->Field, 'oxygen') !== false ||
        stripos($col->Field, 'respiratory') !== false) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }
}

echo "\n=== جميع أعمدة جدول emergencies ===\n";
foreach ($columns as $col) {
    echo "{$col->Field}\n";
}
