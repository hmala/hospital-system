<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "التحقق من طلبات الأشعة التي يجب أن تظهر:\n";
echo "==========================================\n\n";

// جلب المستخدمين
$generalStaff = User::whereHas('roles', function($q) {
    $q->where('name', 'radiology_general');
})->first();

$oldStaff = User::whereHas('roles', function($q) {
    $q->where('name', 'radiology_staff');
})->first();

echo "موظفي الأشعة العامة:\n";
if ($generalStaff) {
    echo "  {$generalStaff->name} (#{$generalStaff->id}) - radiology_general\n";
} else {
    echo "  لا يوجد\n";
}
if ($oldStaff) {
    echo "  {$oldStaff->name} (#{$oldStaff->id}) - radiology_staff (قديم)\n";
}

echo "\n\nطلبات الأشعة العامة في جدول requests:\n";
$generalRequests = DB::table('requests')
    ->where('type', 'radiology')
    ->where(function($q) {
        $q->where('subtype', 'general')
          ->orWhereNull('subtype');
    })
    ->whereIn('status', ['pending_service_selection', 'pending', 'in_progress', 'completed'])
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($generalRequests as $req) {
    $patient = DB::table('visits')
        ->join('patients', 'visits.patient_id', '=', 'patients.id')
        ->join('users', 'patients.user_id', '=', 'users.id')
        ->where('visits.id', $req->visit_id)
        ->select('users.name')
        ->first();
    
    echo "  طلب #{$req->id}:\n";
    echo "    المريض: " . ($patient->name ?? 'غير معروف') . "\n";
    echo "    subtype: " . ($req->subtype ?? 'NULL') . "\n";
    echo "    status: {$req->status}\n";
    echo "    created_at: {$req->created_at}\n";
    echo "\n";
}

echo "إجمالي: " . $generalRequests->count() . " طلبات\n";
