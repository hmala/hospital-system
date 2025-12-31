<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;
use App\Models\User;
use App\Models\Patient;
use App\Models\Department;
use Carbon\Carbon;

echo "==========================================\n";
echo "    إنشاء زيارة اختبارية من الأمس       \n";
echo "==========================================\n\n";

// البحث عن طبيب
$doctor = User::role('doctor')->with('doctor')->first();
if (!$doctor || !$doctor->doctor) {
    echo "⚠️  لم يتم العثور على طبيب!\n";
    exit;
}

// البحث عن مريض
$patient = Patient::with('user')->first();
if (!$patient) {
    echo "⚠️  لم يتم العثور على مريض!\n";
    exit;
}

// البحث عن قسم
$department = Department::first();
if (!$department) {
    echo "⚠️  لم يتم العثور على قسم!\n";
    exit;
}

echo "الطبيب: {$doctor->name}\n";
echo "المريض: {$patient->user->name}\n";
echo "القسم: {$department->name}\n\n";

// إنشاء زيارة من الأمس بحالة غير مكتملة
$visit = Visit::create([
    'patient_id' => $patient->id,
    'doctor_id' => $doctor->doctor->id,
    'department_id' => $department->id,
    'visit_date' => Carbon::yesterday(),
    'visit_time' => '10:00:00',
    'visit_type' => 'checkup',
    'chief_complaint' => 'زيارة اختبارية من الأمس - غير مكتملة',
    'status' => 'in_progress',  // حالة غير مكتملة
    'notes' => 'تم إنشاؤها للاختبار'
]);

echo "✓ تم إنشاء زيارة اختبارية:\n";
echo "  - رقم الزيارة: #{$visit->id}\n";
echo "  - التاريخ: " . $visit->visit_date->format('Y-m-d') . "\n";
echo "  - الحالة: {$visit->status}\n";
echo "  - منذ: " . $visit->visit_date->diffInDays(today()) . " يوم\n\n";

// التحقق من الزيارات غير المكتملة
$incompleteVisits = Visit::where('doctor_id', $doctor->doctor->id)
    ->where('status', '!=', 'completed')
    ->where('status', '!=', 'cancelled')
    ->whereDate('visit_date', '<=', today())
    ->get();

echo "عدد الزيارات غير المكتملة الآن: " . $incompleteVisits->count() . "\n\n";

echo "يمكنك الآن تسجيل الدخول كطبيب ومشاهدة التنبيه!\n";
echo "البريد: {$doctor->email}\n";
