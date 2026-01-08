<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص بيانات الكاشير ===\n\n";

// محاكاة ما يحدث في CashierController
$pendingRequests = App\Models\Request::with(['visit.patient.user', 'visit.doctor.user'])
    ->where('payment_status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->orderBy('created_at', 'desc')
    ->get();

echo "عدد الطلبات المعلقة في الاستعلام: " . $pendingRequests->count() . "\n\n";

foreach($pendingRequests as $request) {
    echo "الطلب #" . $request->id . ":\n";
    echo "  - النوع: " . $request->type . "\n";
    echo "  - حالة الدفع: " . $request->payment_status . "\n";
    echo "  - المريض: " . ($request->visit->patient->user->name ?? 'غير محدد') . "\n";
    echo "  - الزيارة #" . $request->visit_id . " (حالة: " . $request->visit->status . ")\n";
    echo "\n";
}

echo "\n=== فحص إذا كانت الصفحة تُمرر البيانات ===\n";
echo "is_object: " . (is_object($pendingRequests) ? 'نعم' : 'لا') . "\n";
echo "count(): " . $pendingRequests->count() . "\n";

// فحص pagination
$pendingRequestsPaginated = App\Models\Request::with(['visit.patient.user', 'visit.doctor.user'])
    ->where('payment_status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->orderBy('created_at', 'desc')
    ->paginate(15, ['*'], 'requests_page');

echo "\nمع pagination:\n";
echo "total(): " . $pendingRequestsPaginated->total() . "\n";
echo "count(): " . $pendingRequestsPaginated->count() . "\n";
echo "is_object: " . (is_object($pendingRequestsPaginated) ? 'نعم' : 'لا') . "\n";
echo "method_exists total: " . (method_exists($pendingRequestsPaginated, 'total') ? 'نعم' : 'لا') . "\n";
?>