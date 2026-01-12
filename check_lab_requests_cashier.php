<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص شامل لطلبات التحاليل ===\n\n";

// 1. جميع الطلبات
$allRequests = App\Models\Request::where('type', 'lab')->get();
echo "1️⃣ إجمالي طلبات التحاليل: " . $allRequests->count() . "\n\n";

foreach($allRequests as $req) {
    echo "الطلب #" . $req->id . ":\n";
    echo "  - payment_status: " . $req->payment_status . "\n";
    echo "  - status: " . $req->status . "\n";
    echo "  - visit_id: " . $req->visit_id . "\n";
    if ($req->visit) {
        echo "  - visit status: " . $req->visit->status . "\n";
        echo "  - patient: " . $req->visit->patient->user->name . "\n";
    }
    echo "\n";
}

// 2. الطلبات التي يجب أن تظهر في الكاشير
echo "2️⃣ الطلبات المعلقة (يجب أن تظهر في الكاشير):\n";
$pendingRequests = App\Models\Request::with(['visit.patient.user'])
    ->where('payment_status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->get();

echo "العدد: " . $pendingRequests->count() . "\n\n";

if ($pendingRequests->count() == 0) {
    echo "❌ لا توجد طلبات معلقة!\n";
    echo "\n3️⃣ السبب المحتمل:\n";
    
    // فحص كل طلب لماذا لا يظهر
    foreach($allRequests as $req) {
        echo "\nالطلب #" . $req->id . ":\n";
        
        $checkPayment = ($req->payment_status == 'pending');
        echo "  ✓ payment_status='pending': " . ($checkPayment ? "نعم ✅" : "لا ❌ (القيمة: {$req->payment_status})") . "\n";
        
        $checkVisit = $req->visit && $req->visit->status != 'cancelled';
        echo "  ✓ visit status!='cancelled': " . ($checkVisit ? "نعم ✅" : "لا ❌ (القيمة: " . ($req->visit ? $req->visit->status : 'لا توجد زيارة') . ")") . "\n";
    }
}
?>