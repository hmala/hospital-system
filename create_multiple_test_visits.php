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
echo "  إنشاء زيارات اختبارية متنوعة للطبيب  \n";
echo "==========================================\n\n";

// البحث عن طبيب
$doctor = User::where('email', 'doctor@hospital.com')->with('doctor')->first();
if (!$doctor || !$doctor->doctor) {
    echo "⚠️  لم يتم العثور على الطبيب!\n";
    exit;
}

// البحث عن مرضى
$patients = Patient::with('user')->take(5)->get();
if ($patients->isEmpty()) {
    echo "⚠️  لم يتم العثور على مرضى!\n";
    exit;
}

// البحث عن قسم
$department = Department::first();
if (!$department) {
    echo "⚠️  لم يتم العثور على قسم!\n";
    exit;
}

echo "الطبيب: {$doctor->name} (ID: {$doctor->doctor->id})\n";
echo "القسم: {$department->name}\n\n";

$visits = [];

// 1. زيارة من 3 أيام - مكتملة
$visits[] = Visit::create([
    'patient_id' => $patients[0]->id,
    'doctor_id' => $doctor->doctor->id,
    'department_id' => $department->id,
    'visit_date' => Carbon::today()->subDays(3),
    'visit_time' => '09:00:00',
    'visit_type' => 'checkup',
    'chief_complaint' => 'زيارة مكتملة من 3 أيام',
    'status' => 'completed',
]);

// 2. زيارة من يومين - غير مكتملة
$visits[] = Visit::create([
    'patient_id' => $patients[1]->id,
    'doctor_id' => $doctor->doctor->id,
    'department_id' => $department->id,
    'visit_date' => Carbon::today()->subDays(2),
    'visit_time' => '10:00:00',
    'visit_type' => 'followup',
    'chief_complaint' => 'زيارة غير مكتملة من يومين',
    'status' => 'in_progress',
]);

// 3. زيارة من الأمس - غير مكتملة
$visits[] = Visit::create([
    'patient_id' => $patients[2]->id,
    'doctor_id' => $doctor->doctor->id,
    'department_id' => $department->id,
    'visit_date' => Carbon::yesterday(),
    'visit_time' => '14:00:00',
    'visit_type' => 'checkup',
    'chief_complaint' => 'زيارة غير مكتملة من الأمس',
    'status' => 'in_progress',
]);

// 4. زيارة اليوم - قيد الفحص
$visits[] = Visit::create([
    'patient_id' => $patients[3]->id,
    'doctor_id' => $doctor->doctor->id,
    'department_id' => $department->id,
    'visit_date' => Carbon::today(),
    'visit_time' => '11:00:00',
    'visit_type' => 'checkup',
    'chief_complaint' => 'زيارة اليوم - قيد الفحص',
    'status' => 'in_progress',
]);

// 5. زيارة اليوم - مكتملة
$visits[] = Visit::create([
    'patient_id' => $patients[4]->id,
    'doctor_id' => $doctor->doctor->id,
    'department_id' => $department->id,
    'visit_date' => Carbon::today(),
    'visit_time' => '15:00:00',
    'visit_type' => 'emergency',
    'chief_complaint' => 'زيارة اليوم - مكتملة',
    'status' => 'completed',
]);

echo "✓ تم إنشاء " . count($visits) . " زيارات اختبارية:\n\n";

foreach ($visits as $index => $visit) {
    echo ($index + 1) . ". الزيارة #{$visit->id}\n";
    echo "   التاريخ: " . $visit->visit_date->format('Y-m-d') . "\n";
    echo "   الحالة: {$visit->status}\n";
    echo "   الشكوى: {$visit->chief_complaint}\n\n";
}

echo "\nالآن يمكنك تسجيل الدخول كطبيب ومشاهدة جميع الزيارات!\n";
echo "البريد: {$doctor->email}\n";
echo "كلمة المرور: password\n";
