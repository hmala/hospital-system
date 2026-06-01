<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Emergency;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

echo "=== إصلاح Emergency #1 ===\n\n";

DB::beginTransaction();

try {
    $emergency = Emergency::find(1);
    
    if (!$emergency) {
        echo "Emergency #1 not found!\n";
        exit;
    }

    // 1. تحديث follow_up_payment_id لأن رسوم المتابعة دُفعت في Payment ID 1
    echo "تحديث follow_up_payment_id إلى 1...\n";
    $emergency->update(['follow_up_payment_id' => 1]);

    // 2. إعادة حساب Payment ID 2 (يجب أن يحتوي فقط على الخدمة الجديدة)
    echo "إعادة حساب Payment #2...\n";
    
    // الحصول على الخدمات غير المدفوعة
    $unpaidServiceIds = DB::table('emergency_emergency_service')
        ->where('emergency_id', $emergency->id)
        ->whereNull('payment_id')
        ->pluck('emergency_service_id');
    
    $servicesAmount = $emergency->services()
        ->whereIn('emergency_services.id', $unpaidServiceIds)
        ->sum('price');
    
    // رسوم المتابعة لا تُحسب لأن follow_up_payment_id != null الآن
    $followUpFee = 0;
    
    $totalAmount = $servicesAmount;
    
    $serviceNames = $emergency->services()
        ->whereIn('emergency_services.id', $unpaidServiceIds)
        ->pluck('name')
        ->implode('، ');
    
    $description = 'خدمات طوارئ: ' . $serviceNames;
    
    $payment2 = Payment::find(2);
    if ($payment2) {
        echo "تحديث Payment #2:\n";
        echo "  - المبلغ القديم: {$payment2->amount}\n";
        echo "  - المبلغ الجديد: {$totalAmount}\n";
        echo "  - الوصف القديم: {$payment2->description}\n";
        echo "  - الوصف الجديد: {$description}\n";
        
        $payment2->update([
            'amount' => $totalAmount,
            'description' => $description,
        ]);
    }

    DB::commit();
    echo "\n✅ تم إصلاح Emergency #1 بنجاح!\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ حدث خطأ: " . $e->getMessage() . "\n";
}
