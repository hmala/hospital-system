<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Emergency;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

echo "=== فحص حالة Emergency #1 ===\n\n";

$emergency = Emergency::with(['payment', 'services', 'labRequests', 'radiologyRequests'])->find(1);

if ($emergency) {
    echo "Emergency ID: {$emergency->id}\n";
    echo "Patient ID: {$emergency->patient_id}\n";
    echo "Doctor Follow-up Fee: {$emergency->doctor_follow_up_fee}\n";
    echo "Follow-up Payment ID: " . ($emergency->follow_up_payment_id ?: 'NULL') . "\n";
    echo "Payment Status: {$emergency->payment_status}\n";
    echo "Payment ID: " . ($emergency->payment_id ?: 'NULL') . "\n\n";

    // فحص الخدمات
    $serviceIds = DB::table('emergency_emergency_service')
        ->where('emergency_id', $emergency->id)
        ->get(['emergency_service_id', 'payment_id']);
    
    echo "Services (" . $serviceIds->count() . "):\n";
    foreach ($serviceIds as $service) {
        echo "  - Service ID: {$service->emergency_service_id}, Payment ID: " . ($service->payment_id ?: 'NULL') . "\n";
    }
    echo "\n";

    // فحص طلبات التحاليل
    $labs = DB::table('emergency_lab_requests')
        ->where('emergency_id', $emergency->id)
        ->get(['id', 'payment_id']);
    
    echo "Lab Requests (" . $labs->count() . "):\n";
    foreach ($labs as $lab) {
        echo "  - Lab Request ID: {$lab->id}, Payment ID: " . ($lab->payment_id ?: 'NULL') . "\n";
    }
    echo "\n";

    // فحص طلبات الأشعة
    $radiology = DB::table('emergency_radiology_requests')
        ->where('emergency_id', $emergency->id)
        ->get(['id', 'payment_id']);
    
    echo "Radiology Requests (" . $radiology->count() . "):\n";
    foreach ($radiology as $rad) {
        echo "  - Radiology Request ID: {$rad->id}, Payment ID: " . ($rad->payment_id ?: 'NULL') . "\n";
    }
    echo "\n";

    // فحص الـ payments
    $payments = Payment::where('emergency_id', $emergency->id)->get();
    echo "Payments (" . $payments->count() . "):\n";
    foreach ($payments as $payment) {
        echo "  - Payment ID: {$payment->id}\n";
        echo "    Amount: {$payment->amount}\n";
        echo "    Description: {$payment->description}\n";
        echo "    Paid At: " . ($payment->paid_at ?: 'NULL') . "\n\n";
    }

} else {
    echo "Emergency #1 not found!\n";
}
