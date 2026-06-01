<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "فحص تخصيص طلبات الإيكو:\n";
echo "==========================================\n\n";

// جلب طلبات الإيكو
$echoRequests = DB::table('requests')
    ->where('type', 'radiology')
    ->where('subtype', 'echo')
    ->select('id', 'subtype', 'status', 'details', 'created_at')
    ->orderBy('id', 'desc')
    ->get();

echo "عدد طلبات الإيكو: " . $echoRequests->count() . "\n\n";

foreach ($echoRequests as $req) {
    $details = json_decode($req->details, true);
    $echoStaffId = $details['echo_staff_id'] ?? 'غير محدد';
    
    echo "طلب #{$req->id}:\n";
    echo "  subtype: {$req->subtype}\n";
    echo "  status: {$req->status}\n";
    echo "  echo_staff_id: {$echoStaffId}\n";
    
    if ($echoStaffId !== 'غير محدد') {
        $staff = DB::table('users')->where('id', $echoStaffId)->first();
        if ($staff) {
            echo "  الموظف المخصص: {$staff->name}\n";
        }
    }
    echo "\n";
}

echo "\nموظفو الإيكو:\n";
$echoStaff = DB::table('users')
    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
    ->where('roles.name', 'radiology_echo')
    ->select('users.id', 'users.name')
    ->get();

foreach ($echoStaff as $staff) {
    // استخدام نفس المنطق من RadiologyStaffController
    $allEchoRequests = DB::table('requests')
        ->where('type', 'radiology')
        ->where('subtype', 'echo')
        ->get();
    
    $assignedCount = 0;
    foreach ($allEchoRequests as $req) {
        $details = json_decode($req->details, true);
        if (isset($details['echo_staff_id']) && $details['echo_staff_id'] == $staff->id) {
            $assignedCount++;
        }
    }
    
    echo "  {$staff->name} (ID: {$staff->id}): {$assignedCount} طلبات مخصصة\n";
}
