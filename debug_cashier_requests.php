<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== اختبار عرض الطلبات في الكاشير ===\n\n";

$pendingRequests = App\Models\Request::with(['visit.patient.user', 'visit.doctor.user'])
    ->where('payment_status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->orderBy('created_at', 'desc')
    ->paginate(15, ['*'], 'requests_page');

echo "عدد الطلبات المعلقة: " . $pendingRequests->count() . "\n";
echo "إجمالي الطلبات: " . $pendingRequests->total() . "\n";
echo "نوع المتغير: " . gettype($pendingRequests) . "\n";
echo "هل هو object؟ " . (is_object($pendingRequests) ? 'نعم' : 'لا') . "\n";
echo "هل يحتوي على method total؟ " . (method_exists($pendingRequests, 'total') ? 'نعم' : 'لا') . "\n\n";

echo "الطلبات:\n";
foreach($pendingRequests as $request) {
    echo "- ID: " . $request->id . "\n";
    echo "  Type: " . $request->type . "\n";
    echo "  Payment Status: " . $request->payment_status . "\n";
    echo "  Patient: " . $request->visit->patient->user->name . "\n";
    echo "  Doctor: " . ($request->visit->doctor ? $request->visit->doctor->user->name : 'لا يوجد طبيب') . "\n\n";
}
?>