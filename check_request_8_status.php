<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== فحص حالة الطلب #8 ===\n\n";

$request = DB::table('requests')->where('id', 8)->first();
if ($request) {
    echo "Request ID: {$request->id}\n";
    echo "Type: {$request->type}\n";
    echo "Payment Status: {$request->payment_status}\n";
    echo "Visit ID: {$request->visit_id}\n";
    echo "Created At: {$request->created_at}\n\n";

    $visit = DB::table('visits')->where('id', $request->visit_id)->first();
    if ($visit) {
        echo "Visit Status: {$visit->status}\n";
        echo "Patient ID: {$visit->patient_id}\n";
    }

    $payment = DB::table('payments')->where('request_id', 8)->first();
    if ($payment) {
        echo "\nPayment Found:\n";
        echo "Payment ID: {$payment->id}\n";
        echo "Amount: {$payment->amount}\n";
        echo "Payment Method: {$payment->payment_method}\n";
        echo "Paid At: {$payment->paid_at}\n";
    } else {
        echo "\nNo Payment Found!\n";
    }
} else {
    echo "Request #8 not found!\n";
}