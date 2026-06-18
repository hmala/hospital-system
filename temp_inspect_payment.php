<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$payment = App\Models\Payment::find(22);
if (! $payment) {
    echo 'payment not found';
    exit(1);
}
$appointment = $payment->appointment;
echo 'payment_amount=' . $payment->amount . PHP_EOL;
if ($appointment) {
    echo 'appointment_id=' . $appointment->id . PHP_EOL;
    echo 'consultation_fee=' . $appointment->consultation_fee . PHP_EOL;
    echo 'doctor_id=' . $appointment->doctor_id . PHP_EOL;
} else {
    echo 'no appointment' . PHP_EOL;
}
