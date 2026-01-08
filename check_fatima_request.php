<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص حالة طلب فاطمة علي محمود ===\n\n";

// البحث عن المريضة
$patient = App\Models\Patient::whereHas('user', function($q) {
    $q->where('name', 'LIKE', '%فاطمة%');
})->first();

if($patient) {
    echo "المريضة: " . $patient->user->name . "\n\n";
    
    // البحث عن طلبات التحاليل
    $requests = App\Models\Request::whereHas('visit', function($q) use ($patient) {
        $q->where('patient_id', $patient->id);
    })->where('type', 'lab')->get();
    
    echo "عدد طلبات التحاليل: " . $requests->count() . "\n\n";
    
    foreach($requests as $request) {
        echo "الطلب #" . $request->id . ":\n";
        echo "  - الحالة: " . $request->status . "\n";
        echo "  - حالة الدفع: " . $request->payment_status . "\n";
        echo "  - حالة الزيارة: " . $request->visit->status . "\n";
        echo "  - التاريخ: " . $request->created_at->format('Y-m-d H:i') . "\n";
        
        // هل يظهر في الكاشير؟
        if($request->payment_status == 'pending') {
            echo "  ✅ يظهر في الكاشير\n";
        } else {
            echo "  ❌ لا يظهر في الكاشير (مدفوع)\n";
        }
        
        // هل يظهر في المختبر؟
        if($request->payment_status == 'paid') {
            echo "  ✅ يظهر في المختبر\n";
        } else {
            echo "  ❌ لا يظهر في المختبر (غير مدفوع)\n";
        }
        echo "\n";
    }
}
?>