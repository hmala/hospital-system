<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "فحص آخر طلبات الأشعة:\n";
echo "==========================================\n\n";

echo "آخر 5 طلبات في جدول requests (النظام الجديد):\n";
$newRequests = DB::table('requests')
    ->where('type', 'radiology')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach ($newRequests as $req) {
    echo "  طلب #{$req->id}:\n";
    echo "    subtype: " . ($req->subtype ?? 'NULL') . "\n";
    echo "    status: {$req->status}\n";
    echo "    created_at: {$req->created_at}\n";
    
    if ($req->visit_id) {
        $visit = DB::table('visits')->where('id', $req->visit_id)->first();
        if ($visit && $visit->patient_id) {
            $patient = DB::table('users')
                ->join('patients', 'users.id', '=', 'patients.user_id')
                ->where('patients.id', $visit->patient_id)
                ->select('users.name')
                ->first();
            echo "    المريض: " . ($patient ? $patient->name : 'غير محدد') . "\n";
        }
    }
    
    // فك تشفير details
    $details = $req->details;
    if (is_string($details)) {
        $details = json_decode($details, true);
        if (is_string($details)) {
            $details = json_decode($details, true);
        }
    }
    
    if (is_array($details)) {
        if (isset($details['radiology_type_ids'])) {
            echo "    radiology_type_ids: " . json_encode($details['radiology_type_ids']) . "\n";
        }
        if (isset($details['echo_staff_id'])) {
            echo "    echo_staff_id: " . $details['echo_staff_id'] . "\n";
        }
    }
    echo "\n";
}

echo "\n\nآخر 5 طلبات في جدول radiology_requests (النظام القديم):\n";
$oldRequests = DB::table('radiology_requests')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach ($oldRequests as $req) {
    $type = DB::table('radiology_types')->where('id', $req->radiology_type_id)->first();
    $patient = DB::table('users')
        ->join('patients', 'users.id', '=', 'patients.user_id')
        ->where('patients.id', $req->patient_id)
        ->select('users.name')
        ->first();
    
    echo "  طلب #{$req->id}:\n";
    echo "    نوع الأشعة: " . ($type ? $type->name . " ({$type->subcategory})" : 'غير محدد') . "\n";
    echo "    المريض: " . ($patient ? $patient->name : 'غير محدد') . "\n";
    echo "    status: {$req->status}\n";
    echo "    created_at: {$req->created_at}\n";
    echo "\n";
}
