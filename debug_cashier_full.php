<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص شامل لطلب فاطمة ===\n\n";

// 1. التحقق من بيانات الطلب
$request = App\Models\Request::find(8);
echo "1️⃣ بيانات الطلب:\n";
echo "   - ID: " . $request->id . "\n";
echo "   - النوع: " . $request->type . "\n";
echo "   - payment_status: " . $request->payment_status . "\n";
echo "   - status: " . $request->status . "\n";
echo "   - visit_id: " . $request->visit_id . "\n\n";

// 2. التحقق من بيانات الزيارة
echo "2️⃣ بيانات الزيارة:\n";
echo "   - ID: " . $request->visit->id . "\n";
echo "   - status: " . $request->visit->status . "\n";
echo "   - patient_id: " . $request->visit->patient_id . "\n\n";

// 3. تشغيل نفس استعلام CashierController
echo "3️⃣ اختبار استعلام CashierController:\n";
$pendingRequests = App\Models\Request::with(['visit.patient.user', 'visit.doctor.user'])
    ->where('payment_status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->orderBy('created_at', 'desc')
    ->get();

echo "   - عدد النتائج: " . $pendingRequests->count() . "\n";

if ($pendingRequests->count() > 0) {
    echo "   ✅ الطلب يظهر في الاستعلام!\n";
    foreach($pendingRequests as $r) {
        echo "   - الطلب #" . $r->id . " (المريض: " . $r->visit->patient->user->name . ")\n";
    }
} else {
    echo "   ❌ الطلب لا يظهر في الاستعلام!\n";
    
    // فحص لماذا لا يظهر
    echo "\n4️⃣ فحص الشروط:\n";
    
    // شرط payment_status
    $test1 = App\Models\Request::where('id', 8)->where('payment_status', 'pending')->count();
    echo "   - شرط payment_status='pending': " . ($test1 > 0 ? "✅ يمر" : "❌ لا يمر") . "\n";
    
    // شرط visit status
    $test2 = App\Models\Request::where('id', 8)
        ->whereHas('visit', function($q) {
            $q->where('status', '!=', 'cancelled');
        })->count();
    echo "   - شرط visit.status!='cancelled': " . ($test2 > 0 ? "✅ يمر" : "❌ لا يمر") . "\n";
    
    // الشرطان معاً
    $test3 = App\Models\Request::where('id', 8)
        ->where('payment_status', 'pending')
        ->whereHas('visit', function($q) {
            $q->where('status', '!=', 'cancelled');
        })->count();
    echo "   - الشرطان معاً: " . ($test3 > 0 ? "✅ يمر" : "❌ لا يمر") . "\n";
}
?>