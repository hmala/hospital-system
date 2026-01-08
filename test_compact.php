<?php
// اختبار خارج Laravel تماماً
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== اختبار Pagination ===\n\n";

$result = \App\Models\Request::where('payment_status', 'pending')->paginate(15, ['*'], 'requests_page');

echo "النوع: " . gettype($result) . "\n";
echo "الكلاس: " . get_class($result) . "\n";
echo "العدد: " . $result->count() . "\n";

// اختبار compact
$testVar = $result;
$data = compact('testVar');

echo "\nبعد compact:\n";
echo "النوع: " . gettype($data['testVar']) . "\n";
if (is_object($data['testVar'])) {
    echo "الكلاس: " . get_class($data['testVar']) . "\n";
}

// اختبار view
echo "\n=== اختبار view ===\n";
$pendingRequests = $result;
$viewData = compact('pendingRequests');
echo "قبل إرسال للـ view - النوع: " . gettype($viewData['pendingRequests']) . "\n";

// محاولة render
try {
    $view = view('test-simple', $viewData);
    echo "View rendered successfully\n";
} catch (\Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>