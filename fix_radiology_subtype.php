<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "تحديث قيمة subtype للطلبات:\n";
echo "==========================================\n\n";

// جلب الطلبات التي لها subtype = 'radiology'
$requests = DB::table('requests')
    ->where('type', 'radiology')
    ->where('subtype', 'radiology')
    ->get();

echo "عدد الطلبات التي تحتاج تحديث: " . $requests->count() . "\n\n";

if ($requests->count() > 0) {
    foreach ($requests as $req) {
        echo "تحديث طلب #{$req->id}...\n";
    }
    
    // تحديث الطلبات
    $updated = DB::table('requests')
        ->where('type', 'radiology')
        ->where('subtype', 'radiology')
        ->update(['subtype' => 'general']);
    
    echo "\nتم تحديث {$updated} طلب بنجاح ✓\n";
} else {
    echo "لا توجد طلبات تحتاج تحديث.\n";
}

echo "\n\nالتحقق من النتيجة:\n";
$afterUpdate = DB::table('requests')
    ->where('type', 'radiology')
    ->select('subtype', DB::raw('COUNT(*) as count'))
    ->groupBy('subtype')
    ->get();

foreach ($afterUpdate as $row) {
    $subtype = $row->subtype ?? 'NULL';
    echo "  subtype '{$subtype}': {$row->count} طلبات\n";
}
