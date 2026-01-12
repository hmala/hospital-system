<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== فحص جميع الطلبات المدفوعة ===\n\n";

$paidRequests = DB::table('requests')
    ->where('payment_status', 'paid')
    ->where('type', 'lab')
    ->orderBy('created_at', 'desc')
    ->get();

echo "عدد الطلبات المدفوعة للمختبر: " . $paidRequests->count() . "\n\n";

foreach ($paidRequests as $request) {
    echo "Request #{$request->id}:\n";
    echo "  Type: {$request->type}\n";
    echo "  Payment Status: {$request->payment_status}\n";
    echo "  Visit ID: {$request->visit_id}\n";

    $visit = DB::table('visits')->where('id', $request->visit_id)->first();
    if ($visit) {
        echo "  Visit Status: {$visit->status}\n";
        echo "  Patient ID: {$visit->patient_id}\n";

        $patient = DB::table('patients')->where('id', $visit->patient_id)->first();
        if ($patient) {
            $user = DB::table('users')->where('id', $patient->user_id)->first();
            if ($user) {
                echo "  Patient Name: {$user->name}\n";
            }
        }
    }

    echo "  Created At: {$request->created_at}\n";
    echo "  Details: " . substr($request->details ?? 'null', 0, 100) . "\n";
    echo "  ---\n";
}