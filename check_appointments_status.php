<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;

echo "=== فحص حالة المواعيد ===\n\n";

$appointments = Appointment::all();

if ($appointments->count() == 0) {
    echo "لا توجد مواعيد في قاعدة البيانات\n";
    exit(0);
}

foreach ($appointments as $appointment) {
    $canCancel = $appointment->canBeCancelled() ? 'نعم' : 'لا';
    $isUpcoming = $appointment->isUpcoming() ? 'نعم' : 'لا';

    echo "الموعد ID: {$appointment->id}\n";
    echo "  الحالة: {$appointment->status}\n";
    echo "  التاريخ: {$appointment->appointment_date->format('Y-m-d H:i:s')}\n";
    echo "  قادم: {$isUpcoming}\n";
    echo "  قابل للإلغاء: {$canCancel}\n";
    echo "  ---\n";
}

echo "\nتم الانتهاء من الفحص\n";