<?php
// إنشاء طلب مختبر تجريبي

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== إنشاء طلب مختبر تجريبي ===\n\n";

// 1. إيجاد مريض
$patient = \App\Models\Patient::first();
if (!$patient) {
    echo "لا يوجد مرضى في النظام\n";
    exit;
}

echo "المريض: {$patient->user->name} (ID: {$patient->id})\n";

// 2. إيجاد قسم
$department = \App\Models\Department::first();
if (!$department) {
    echo "لا يوجد أقسام في النظام\n";
    exit;
}

echo "القسم: {$department->name} (ID: {$department->id})\n";

// 3. إنشاء زيارة
$visit = \App\Models\Visit::create([
    'patient_id' => $patient->id,
    'department_id' => $department->id,
    'doctor_id' => null, // من الاستعلامات
    'visit_date' => now(),
    'visit_time' => now(),
    'visit_type' => 'lab',
    'chief_complaint' => 'طلب تحاليل من الاستعلامات',
    'status' => 'pending_payment',
    'notes' => 'طلب تجريبي للاختبار'
]);

echo "الزيارة: #{$visit->id}\n";

// 4. إيجاد تحاليل
$labTests = \App\Models\LabTest::where('is_active', true)->take(3)->pluck('id')->toArray();
if (empty($labTests)) {
    echo "لا توجد تحاليل نشطة في النظام\n";
    exit;
}

echo "التحاليل المختارة: " . implode(', ', $labTests) . "\n";

// 5. إنشاء الطلب
$details = [
    'lab_test_ids' => $labTests,
    'priority' => 'normal',
    'created_by' => 1,
    'created_at_inquiry' => true,
];

$request = \App\Models\Request::create([
    'visit_id' => $visit->id,
    'type' => 'lab',
    'description' => 'طلب تحاليل',
    'status' => 'pending',
    'payment_status' => 'pending',
    'details' => json_encode($details)
]);

echo "الطلب: #{$request->id}\n";

echo "\n✓ تم إنشاء الطلب بنجاح!\n";
echo "\nالخطوات التالية:\n";
echo "1. ادفع للطلب من الكاشير\n";
echo "2. سيتم توجيهك لصفحة طلبات المختبر\n";
echo "3. سيظهر الطلب لموظف المختبر\n";

echo "\nلمحاكاة الدفع، استخدم:\n";
echo "http://127.0.0.1:8000/cashier/request-payment/{$request->id}\n";
