<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pendingRequests = App\Models\Request::with(['visit.patient.user', 'visit.doctor.user'])
    ->where('payment_status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->orderBy('created_at', 'desc')
    ->get();

echo 'Pending requests count: ' . $pendingRequests->count() . PHP_EOL;
foreach($pendingRequests as $request) {
    echo 'ID: ' . $request->id . ' - Type: ' . $request->type . PHP_EOL;
}
?>