<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== تحديث طلب فاطمة ===\n\n";

// إيجاد طلب فاطمة
$request = App\Models\Request::find(8);

if ($request) {
    echo "الطلب #" . $request->id . " - " . $request->type . "\n";
    echo "الزيارة #" . $request->visit_id . "\n";
    echo "حالة الزيارة القديمة: " . $request->visit->status . "\n";
    
    // تحديث حالة الزيارة إلى pending_payment
    $request->visit->update(['status' => 'pending_payment']);
    
    echo "حالة الزيارة الجديدة: " . $request->visit->fresh()->status . "\n";
    
    echo "\n✅ تم التحديث بنجاح!\n";
    echo "الآن يجب أن يظهر الطلب في الكاشير.\n";
} else {
    echo "❌ لم يتم العثور على الطلب!\n";
}
?>