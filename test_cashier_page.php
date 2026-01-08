<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// تسجيل الدخول كمستخدم كاشير
$cashier = App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'cashier');
})->first();

if (!$cashier) {
    echo "لا يوجد مستخدم كاشير!\n";
    exit;
}

Auth::login($cashier);
echo "تم تسجيل الدخول كـ: " . $cashier->name . "\n\n";

// محاولة الوصول إلى صفحة الكاشير
echo "=== محاكاة CashierController::index() ===\n\n";

try {
    $controller = new App\Http\Controllers\CashierController();
    $response = $controller->index();
    
    echo "✅ الصفحة تعمل بدون أخطاء!\n\n";
    
    // فحص البيانات المُمررة
    $data = $response->getData();
    echo "المتغيرات المُمررة للـ view:\n";
    foreach($data as $key => $value) {
        if (is_object($value) && method_exists($value, 'count')) {
            echo "  - $key: " . $value->count() . " عنصر";
            if (method_exists($value, 'total')) {
                echo " (total: " . $value->total() . ")";
            }
            echo "\n";
        } elseif (is_array($value)) {
            echo "  - $key: array\n";
        } else {
            echo "  - $key: " . gettype($value) . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
    echo "في الملف: " . $e->getFile() . "\n";
    echo "السطر: " . $e->getLine() . "\n";
}
?>