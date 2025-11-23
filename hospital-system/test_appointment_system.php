<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;

echo "اختبار النظام الجديد:\n";
echo "=====================\n";

// محاولة إنشاء موعد في وقت مختلف
try {
    $appointment = Appointment::create([
        'patient_id' => 11,
        'doctor_id' => 5,
        'department_id' => 5,
        'appointment_date' => '2025-11-07 10:00:00',
        'reason' => 'متابعة حالة مرضية',
        'consultation_fee' => 25000,
        'status' => 'scheduled'
    ]);

    echo "تم إنشاء الموعد بنجاح!\n";
    echo "ID: {$appointment->id}\n";
    echo "الوقت: {$appointment->appointment_date}\n";

} catch (Exception $e) {
    echo "خطأ في إنشاء الموعد: " . $e->getMessage() . "\n";
}

// محاولة إنشاء موعد في نفس الوقت (يجب أن يفشل)
try {
    $appointment2 = Appointment::create([
        'patient_id' => 11,
        'doctor_id' => 5,
        'department_id' => 5,
        'appointment_date' => '2025-11-07 09:00:00',
        'reason' => 'متابعة حالة مرضية',
        'consultation_fee' => 25000,
        'status' => 'scheduled'
    ]);

    echo "تم إنشاء الموعد الثاني بنجاح (هذا خطأ!)\n";

} catch (Exception $e) {
    echo "تم منع إنشاء الموعد المكرر كما هو متوقع: " . substr($e->getMessage(), 0, 100) . "...\n";
}