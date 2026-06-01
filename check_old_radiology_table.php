<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "فحص جدول radiology_requests القديم:\n";
echo "==========================================\n\n";

// التحقق من وجود الجدول
$tables = DB::select("SHOW TABLES LIKE 'radiology_requests'");

if (empty($tables)) {
    echo "الجدول radiology_requests غير موجود\n";
    exit;
}

// عد الطلبات
$count = DB::table('radiology_requests')->count();
echo "عدد الطلبات في الجدول القديم: {$count}\n\n";

if ($count > 0) {
    // عرض بعض الطلبات
    $requests = DB::table('radiology_requests')
        ->join('radiology_types', 'radiology_requests.radiology_type_id', '=', 'radiology_types.id')
        ->select('radiology_requests.id', 'radiology_requests.status', 'radiology_types.name as type_name', 'radiology_types.subcategory')
        ->limit(10)
        ->get();
    
    echo "أمثلة على الطلبات:\n";
    foreach ($requests as $req) {
        echo "  طلب #{$req->id}: {$req->type_name} ({$req->subcategory}) - {$req->status}\n";
    }
    
    echo "\n\nتوزيع الطلبات حسب subcategory:\n";
    $distribution = DB::table('radiology_requests')
        ->join('radiology_types', 'radiology_requests.radiology_type_id', '=', 'radiology_types.id')
        ->select('radiology_types.subcategory', DB::raw('COUNT(*) as count'))
        ->groupBy('radiology_types.subcategory')
        ->get();
    
    foreach ($distribution as $dist) {
        $subcategory = $dist->subcategory ?? 'NULL';
        echo "  {$subcategory}: {$dist->count} طلبات\n";
    }
}

echo "\n\nفحص طلبات الطوارئ (emergency_radiology_requests):\n";
$emergencyCount = DB::table('emergency_radiology_requests')->count();
echo "عدد طلبات طوارئ الأشعة: {$emergencyCount}\n";

if ($emergencyCount > 0) {
    echo "\nأمثلة على طلبات الطوارئ:\n";
    $emergencyRequests = DB::table('emergency_radiology_requests')
        ->select('id', 'status', 'priority')
        ->limit(5)
        ->get();
    
    foreach ($emergencyRequests as $req) {
        echo "  طلب طوارئ #{$req->id}: {$req->status} - {$req->priority}\n";
    }
}
