<?php
// محاكاة دفع طلب مختبر

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== محاكاة دفع طلب المختبر ===\n\n";

$requestId = 16;

$request = \App\Models\Request::find($requestId);
if (!$request) {
    echo "الطلب #{$requestId} غير موجود\n";
    exit;
}

echo "الطلب: #{$request->id}\n";
echo "النوع: {$request->type}\n";
echo "حالة الدفع الحالية: {$request->payment_status}\n\n";

// حساب المبلغ
$details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
$totalAmount = 0;

if (isset($details['lab_test_ids'])) {
    $tests = \App\Models\LabTest::whereIn('id', $details['lab_test_ids'])->get();
    foreach ($tests as $test) {
        $price = $test->price ?? 0;
        $totalAmount += $price;
        echo "  - {$test->name}: " . number_format($price, 2) . " د.ع\n";
    }
}

echo "\nالمبلغ الإجمالي: " . number_format($totalAmount, 2) . " د.ع\n\n";

// إنشاء الدفع
try {
    DB::beginTransaction();
    
    $payment = \App\Models\Payment::create([
        'request_id' => $request->id,
        'patient_id' => $request->visit->patient_id,
        'cashier_id' => 1, // افتراضي
        'receipt_number' => \App\Models\Payment::generateReceiptNumber(),
        'amount' => $totalAmount,
        'payment_method' => 'cash',
        'payment_type' => 'lab',
        'description' => 'دفع رسوم طلب ' . $request->type . ' #' . $request->id,
        'notes' => 'دفع تجريبي',
        'paid_at' => now()
    ]);
    
    echo "✓ تم إنشاء الدفع: رقم الإيصال {$payment->receipt_number}\n";
    
    // تحديث حالة الدفع للطلب
    $request->update([
        'payment_status' => 'paid',
        'payment_id' => $payment->id
    ]);
    
    echo "✓ تم تحديث حالة الدفع للطلب\n";
    
    // تحديث حالة الزيارة
    $request->visit->update([
        'status' => 'in_progress'
    ]);
    
    echo "✓ تم تحديث حالة الزيارة إلى in_progress\n";
    
    DB::commit();
    
    echo "\n✅ تم الدفع بنجاح!\n";
    echo "\nالطلب الآن:\n";
    echo "  - حالة الدفع: {$request->fresh()->payment_status}\n";
    echo "  - حالة الطلب: {$request->fresh()->status}\n";
    echo "  - حالة الزيارة: {$request->visit->fresh()->status}\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}
