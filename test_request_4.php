<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Request as MedicalRequest;

$request = MedicalRequest::with(['visit.patient.user'])
    ->where('id', 4)
    ->where('status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->first();

if ($request) {
    echo "Request #4 exists and should be visible\n";
    echo "Patient: " . $request->visit->patient->user->name . "\n";
    echo "Status: " . $request->status . "\n";
    echo "Visit Status: " . $request->visit->status . "\n";
} else {
    echo "Request #4 does not meet criteria or does not exist\n";
}