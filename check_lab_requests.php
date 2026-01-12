<?php
// فحص طلبات المختبر

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص طلبات المختبر ===\n\n";

// 1. جلب جميع طلبات المختبر
$labRequests = \App\Models\Request::where('type', 'lab')->get();

echo "عدد طلبات المختبر في جدول requests: " . $labRequests->count() . "\n\n";

foreach ($labRequests as $request) {
    echo "طلب #{$request->id}\n";
    echo "  - الزيارة: #{$request->visit_id}\n";
    echo "  - الحالة: {$request->status}\n";
    echo "  - حالة الدفع: " . ($request->payment_status ?? 'غير محدد') . "\n";
    
    $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
    
    if (isset($details['lab_test_ids'])) {
        echo "  - أنواع التحاليل: " . implode(', ', $details['lab_test_ids']) . "\n";
        
        // عرض أسماء التحاليل
        $testNames = \App\Models\LabTest::whereIn('id', $details['lab_test_ids'])->pluck('name')->toArray();
        if (!empty($testNames)) {
            echo "    الأسماء: " . implode(', ', $testNames) . "\n";
        }
    } else {
        echo "  - لا توجد lab_test_ids في التفاصيل\n";
        echo "  - التفاصيل: " . json_encode($details) . "\n";
    }
    
    echo "\n";
}

echo "\n=== ما يراه موظف المختبر ===\n";

// ما يراه موظف المختبر (طلبات مدفوعة فقط)
$paidLabRequests = \App\Models\Request::with(['visit.patient.user', 'visit.doctor.user'])
    ->where('type', 'lab')
    ->where('payment_status', 'paid')
    ->orderBy('created_at', 'desc')
    ->get();

echo "عدد الطلبات المدفوعة: " . $paidLabRequests->count() . "\n\n";

foreach ($paidLabRequests as $request) {
    echo "======================\n";
    echo "طلب #{$request->id}\n";
    echo "  المريض: {$request->visit->patient->user->name}\n";
    echo "  الطبيب: " . ($request->visit->doctor ? $request->visit->doctor->user->name : 'غير محدد') . "\n";
    echo "  الحالة: {$request->status}\n";
    echo "  الوصف: {$request->description}\n";
    
    $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
    if (isset($details['lab_test_ids'])) {
        $tests = \App\Models\LabTest::whereIn('id', $details['lab_test_ids'])->get();
        echo "  التحاليل المطلوبة:\n";
        foreach ($tests as $test) {
            echo "    - {$test->name} ({$test->code})\n";
        }
    }
    echo "\n";
}

echo "\n=== الإحصائيات ===\n";
$pendingLab = \App\Models\Request::where('type', 'lab')
    ->where('payment_status', 'paid')
    ->where('status', 'pending')
    ->count();
echo "المعلقة (مدفوعة): {$pendingLab}\n";

$inProgressLab = \App\Models\Request::where('type', 'lab')
    ->where('payment_status', 'paid')
    ->where('status', 'in_progress')
    ->count();
echo "قيد التنفيذ: {$inProgressLab}\n";

$completedLab = \App\Models\Request::where('type', 'lab')
    ->where('payment_status', 'paid')
    ->where('status', 'completed')
    ->count();
echo "المكتملة: {$completedLab}\n";
