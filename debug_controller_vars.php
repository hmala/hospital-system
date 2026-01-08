<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// تسجيل الدخول
$cashier = App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'cashier');
})->first();
Auth::login($cashier);

echo "=== فحص متغيرات CashierController ===\n\n";

// محاكاة الكود في CashierController
$pendingRequests = App\Models\Request::with(['visit.patient.user', 'visit.doctor.user'])
    ->where('payment_status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->orderBy('created_at', 'desc')
    ->paginate(15, ['*'], 'requests_page');

echo "1️⃣ بعد الاستعلام مباشرة:\n";
echo "   Type: " . gettype($pendingRequests) . "\n";
echo "   Class: " . get_class($pendingRequests) . "\n";
echo "   Count: " . $pendingRequests->count() . "\n";
echo "   Total: " . $pendingRequests->total() . "\n\n";

// حساب العدد كما في Controller
$pendingRequestsCount = is_object($pendingRequests) ? $pendingRequests->total() : 0;

echo "2️⃣ بعد حساب pendingRequestsCount:\n";
echo "   pendingRequestsCount = " . $pendingRequestsCount . "\n";
echo "   pendingRequests type = " . gettype($pendingRequests) . "\n\n";

// استخدام compact كما في Controller
$data = compact('pendingRequests');

echo "3️⃣ بعد compact:\n";
foreach($data as $key => $value) {
    echo "   $key => " . gettype($value) . "\n";
    if (is_object($value)) {
        echo "      Class: " . get_class($value) . "\n";
        echo "      Count: " . $value->count() . "\n";
    }
}
?>