<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Request as MedicalRequest;

$pendingRequests = MedicalRequest::with(['visit.patient.user'])
    ->where('status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->get();

echo "Found " . $pendingRequests->count() . " pending requests:\n\n";

foreach ($pendingRequests as $request) {
    echo "ID: " . $request->id . "\n";
    echo "Patient: " . $request->visit->patient->user->name . "\n";
    echo "Type: " . $request->type . "\n";
    echo "Status: " . $request->status . "\n";
    echo "Visit Status: " . $request->visit->status . "\n";
    echo "Created: " . $request->created_at . "\n";
    echo "---\n";
}