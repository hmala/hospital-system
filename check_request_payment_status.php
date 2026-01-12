<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص حالة طلب فاطمة ===\n\n";

$request = App\Models\Request::find(8);

if ($request) {
    echo "الطلب #" . $request->id . ":\n";
    echo "  - payment_status: " . $request->payment_status . "\n";
    echo "  - payment_id: " . ($request->payment_id ?? 'null') . "\n";
    echo "  - status: " . $request->status . "\n\n";
    
    echo "الزيارة #" . $request->visit_id . ":\n";
    echo "  - status: " . $request->visit->status . "\n\n";
    
    if ($request->payment_id) {
        $payment = App\Models\Payment::find($request->payment_id);
        echo "الدفع #" . $payment->id . ":\n";
        echo "  - receipt_number: " . $payment->receipt_number . "\n";
        echo "  - amount: " . $payment->amount . " IQD\n";
        echo "  - payment_method: " . $payment->payment_method . "\n";
        echo "  - paid_at: " . $payment->paid_at . "\n";
    } else {
        echo "❌ لم يتم الدفع بعد\n";
    }
}
?>