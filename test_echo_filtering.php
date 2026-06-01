<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "اختبار فلترة طلبات الإيكو:\n";
echo "==========================================\n\n";

$userId = 193; // منير

echo "اختبار للمستخدم ID: {$userId}\n\n";

// جلب الطلبات كما يفعل RadiologyStaffController
$allRequests = DB::table('requests')
    ->where('type', 'radiology')
    ->where('subtype', 'echo')
    ->whereIn('status', ['pending_service_selection', 'pending', 'in_progress', 'completed'])
    ->get();

echo "عدد طلبات الإيكو الكلي: " . $allRequests->count() . "\n\n";

$filteredCount = 0;
foreach ($allRequests as $request) {
    $details = $request->details;
    
    // التعامل مع double JSON encoding (نفس المنطق من RadiologyStaffController)
    if (is_string($details)) {
        $details = json_decode($details, true);
        // إذا كان النتيجة string، فهذا يعني double encoding
        if (is_string($details)) {
            $details = json_decode($details, true);
        }
    }
    
    echo "طلب #{$request->id}:\n";
    echo "  parsed details type: " . gettype($details) . "\n";
    
    if (is_array($details) && isset($details['echo_staff_id'])) {
        $echoStaffId = $details['echo_staff_id'];
        echo "  echo_staff_id: {$echoStaffId} (type: " . gettype($echoStaffId) . ")\n";
        echo "  userId: {$userId} (type: " . gettype($userId) . ")\n";
        echo "  مقارنة ==: " . ($echoStaffId == $userId ? 'true' : 'false') . "\n";
        echo "  مقارنة ===: " . ($echoStaffId === $userId ? 'true' : 'false') . "\n";
        
        if ($echoStaffId == $userId) {
            $filteredCount++;
            echo "  ✓ يطابق الفلتر\n";
        } else {
            echo "  ✗ لا يطابق الفلتر\n";
        }
    } else {
        echo "  echo_staff_id: غير موجود\n";
        echo "  ✗ لا يطابق الفلتر\n";
    }
    echo "\n";
}

echo "النتيجة النهائية: {$filteredCount} من {$allRequests->count()} طلبات تطابق الفلتر\n";
