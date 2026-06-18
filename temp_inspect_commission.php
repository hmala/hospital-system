<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;
use App\Models\ConsultationRevenue;
use App\Models\DoctorCommissionSetting;

$payment = Payment::find(22);
if (! $payment) {
    echo 'payment not found' . PHP_EOL;
    exit(1);
}
$appointment = $payment->appointment;
$doctorId = $appointment?->doctor_id;

echo 'payment_amount=' . $payment->amount . PHP_EOL;
echo 'appointment_id=' . ($appointment?->id ?? 'null') . PHP_EOL;
echo 'consultation_fee=' . ($appointment?->consultation_fee ?? 'null') . PHP_EOL;
echo 'doctor_id=' . ($doctorId ?? 'null') . PHP_EOL;

$setting = DoctorCommissionSetting::where('doctor_id', $doctorId)
    ->where('is_active', true)
    ->orderByDesc('id')
    ->first();

if ($setting) {
    echo 'setting_id=' . $setting->id . PHP_EOL;
    echo 'commission_type=' . $setting->commission_type . PHP_EOL;
    echo 'commission_value=' . $setting->commission_value . PHP_EOL;
    echo 'fixed_amount=' . $setting->fixed_amount . PHP_EOL;
    echo 'is_active=' . ($setting->is_active ? '1' : '0') . PHP_EOL;
    echo 'valid_from=' . $setting->valid_from . PHP_EOL;
    echo 'valid_until=' . $setting->valid_until . PHP_EOL;
}

$revenue = ConsultationRevenue::where('payment_id', 22)->first();
if ($revenue) {
    echo 'revenue_doctor_share=' . $revenue->doctor_share . PHP_EOL;
    echo 'revenue_hospital_share=' . $revenue->hospital_share . PHP_EOL;
    echo 'revenue_total_amount=' . $revenue->total_amount . PHP_EOL;
    echo 'revenue_doctor_percentage=' . $revenue->doctor_percentage . PHP_EOL;
}
