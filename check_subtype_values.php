<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "فحص قيم subtype في جدول requests:" . PHP_EOL . PHP_EOL;

$radiologyRequests = \App\Models\Request::where('type', 'radiology')->get();

echo "إجمالي طلبات الأشعة: " . $radiologyRequests->count() . PHP_EOL . PHP_EOL;

// تجميع حسب subtype
$grouped = $radiologyRequests->groupBy('subtype');

foreach ($grouped as $subtype => $requests) {
    $subtypeLabel = $subtype ?? 'NULL';
    echo "subtype = '{$subtypeLabel}': {$requests->count()} طلب" . PHP_EOL;
}

echo PHP_EOL . "عينة من الطلبات:" . PHP_EOL;
foreach ($radiologyRequests->take(5) as $req) {
    echo "  ID: {$req->id} | subtype: " . ($req->subtype ?? 'NULL') . " | status: {$req->status}" . PHP_EOL;
}
