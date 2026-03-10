<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص طلبات الأشعة ===\n\n";

// آخر طلبات الأشعة
$requests = \App\Models\Request::where('type', 'radiology')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

echo "عدد طلبات الأشعة: " . $requests->count() . "\n\n";

foreach ($requests as $request) {
    echo "طلب #{$request->id}\n";
    echo "  - الحالة: {$request->status}\n";
    echo "  - حالة الدفع: {$request->payment_status}\n";
    echo "  - النوع: {$request->type}\n";
    echo "  - تاريخ الإنشاء: {$request->created_at}\n";
    
    if ($request->visit) {
        echo "  - الزيارة: #{$request->visit_id}\n";
        if ($request->visit->patient) {
            echo "  - المريض: {$request->visit->patient->user->name}\n";
        }
    }
    
    $details = $request->details;
    if (is_string($details)) {
        $details = json_decode($details, true);
    }
    if ($details && isset($details['radiology_type_ids'])) {
        echo "  - أنواع الأشعة المحددة: " . count($details['radiology_type_ids']) . "\n";
    } else {
        echo "  - لم يتم تحديد أنواع الأشعة بعد\n";
    }
    
    echo "\n";
}

// فحص موظفي الأشعة
echo "\n=== موظفو الأشعة ===\n\n";
$radiologyStaff = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'radiology_staff');
})->get();

echo "عدد موظفي الأشعة: " . $radiologyStaff->count() . "\n\n";

foreach ($radiologyStaff as $staff) {
    echo "- {$staff->name} ({$staff->email})\n";
}

// اختبار الشرط المستخدم في StaffRequestController
echo "\n=== اختبار شرط العرض ===\n\n";
$visibleRequests = \App\Models\Request::where('type', 'radiology')
    ->where(function($q) {
        $q->where('payment_status', 'paid')
          ->orWhere('status', 'pending_service_selection');
    })
    ->orderBy('created_at', 'desc')
    ->get();

echo "عدد طلبات الأشعة التي يجب أن تظهر لموظف الأشعة: " . $visibleRequests->count() . "\n\n";

foreach ($visibleRequests as $req) {
    echo "طلب #{$req->id} - الحالة: {$req->status} - الدفع: {$req->payment_status}\n";
}
