<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use Carbon\Carbon;

echo "Testing appointment creation after fix:\n";
echo "========================================\n\n";

// Test 1: Create appointment with datetime
try {
    $appointment = Appointment::create([
        'patient_id' => 8,
        'doctor_id' => 2,
        'department_id' => 4,
        'appointment_date' => Carbon::now(),
        'reason' => 'كشف طبي',
        'notes' => 'اختبار بعد الإصلاح',
        'consultation_fee' => 50000.00,
        'duration' => 30,
        'status' => 'scheduled'
    ]);

    echo "✓ تم إنشاء الموعد بنجاح!\n";
    echo "  - ID: {$appointment->id}\n";
    echo "  - التاريخ والوقت: {$appointment->appointment_date}\n";
    echo "  - الحالة: {$appointment->status}\n\n";
    
    // Clean up
    $appointment->delete();
    echo "✓ تم حذف الموعد التجريبي\n";
    
} catch (Exception $e) {
    echo "✗ خطأ: " . $e->getMessage() . "\n";
    echo "  السطر: " . $e->getLine() . "\n";
}

echo "\nانتهى الاختبار.\n";
