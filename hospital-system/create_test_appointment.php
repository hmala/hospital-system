<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use Carbon\Carbon;

echo "=== إنشاء موعد اختبار جديد ===\n\n";

try {
    // الحصول على بيانات عشوائية
    $patient = Patient::first();
    $doctor = Doctor::first();
    $department = Department::first();

    if (!$patient || !$doctor || !$department) {
        echo "خطأ: تأكد من وجود بيانات في جداول patients, doctors, departments\n";
        exit(1);
    }

    // إنشاء موعد جديد بتاريخ غد
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'department_id' => $department->id,
        'appointment_date' => Carbon::tomorrow()->setTime(10, 0, 0), // غداً الساعة 10 صباحاً
        'status' => 'scheduled',
        'reason' => 'موعد اختبار للإلغاء',
        'consultation_fee' => 50000,
        'duration' => 30,
    ]);

    echo "تم إنشاء الموعد بنجاح!\n";
    echo "ID: {$appointment->id}\n";
    echo "التاريخ: {$appointment->appointment_date->format('Y-m-d H:i:s')}\n";
    echo "الحالة: {$appointment->status}\n";
    echo "قابل للإلغاء: " . ($appointment->canBeCancelled() ? 'نعم' : 'لا') . "\n";

} catch (Exception $e) {
    echo "خطأ في إنشاء الموعد: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nيمكنك الآن الذهاب إلى صفحة المواعيد لرؤية زر الإلغاء\n";