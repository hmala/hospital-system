<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;

try {
    // اختبار إلغاء موعد
    $appointment = Appointment::whereIn('status', ['scheduled', 'confirmed'])->first();

    if (!$appointment) {
        echo "لا توجد مواعيد يمكن إلغاؤها للاختبار\n";
        exit(0);
    }

    echo "اختبار إلغاء الموعد ID: {$appointment->id}\n";
    echo "الحالة الحالية: {$appointment->status}\n";

    // اختبار method canBeCancelled
    echo "يمكن إلغاؤه: " . ($appointment->canBeCancelled() ? 'نعم' : 'لا') . "\n";

    if ($appointment->canBeCancelled()) {
        $appointment->cancel('تم الإلغاء لأغراض الاختبار');
        echo "تم إلغاء الموعد بنجاح\n";
        echo "الحالة الجديدة: {$appointment->status}\n";
    }

} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
    exit(1);
}

echo "تم الانتهاء من الاختبار!\n";