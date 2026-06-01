<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "اختبار فلترة RadiologyController:\n";
echo "==========================================\n\n";

$userId = 193; // منير

echo "اختبار للمستخدم ID: {$userId} (منير - radiology_echo)\n\n";

// محاكاة الفلترة من RadiologyController
$category = 'إيكو';

$requests = DB::table('radiology_requests')
    ->join('radiology_types', 'radiology_requests.radiology_type_id', '=', 'radiology_types.id')
    ->where('radiology_types.subcategory', $category)
    ->whereIn('radiology_requests.status', ['pending', 'scheduled', 'in_progress', 'completed'])
    ->where('radiology_requests.doctor_id', $userId) // فلترة الإيكو
    ->select('radiology_requests.id', 'radiology_requests.status', 'radiology_requests.doctor_id', 'radiology_types.name', 'radiology_types.subcategory')
    ->get();

echo "الطلبات التي سيراها منير:\n";
echo "عدد الطلبات: " . $requests->count() . "\n\n";

foreach ($requests as $req) {
    echo "  طلب #{$req->id}: {$req->name} ({$req->subcategory}) - doctor_id: {$req->doctor_id}\n";
}

echo "\n\n";

$userId2 = 194; // مريم
echo "اختبار للمستخدم ID: {$userId2} (مريم - radiology_echo)\n\n";

$requests2 = DB::table('radiology_requests')
    ->join('radiology_types', 'radiology_requests.radiology_type_id', '=', 'radiology_types.id')
    ->where('radiology_types.subcategory', $category)
    ->whereIn('radiology_requests.status', ['pending', 'scheduled', 'in_progress', 'completed'])
    ->where('radiology_requests.doctor_id', $userId2) // فلترة الإيكو
    ->select('radiology_requests.id', 'radiology_requests.status', 'radiology_requests.doctor_id', 'radiology_types.name', 'radiology_types.subcategory')
    ->get();

echo "الطلبات التي سترها مريم:\n";
echo "عدد الطلبات: " . $requests2->count() . "\n\n";

foreach ($requests2 as $req) {
    echo "  طلب #{$req->id}: {$req->name} ({$req->subcategory}) - doctor_id: {$req->doctor_id}\n";
}
