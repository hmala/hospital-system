<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Request as MedicalRequest;

$allRequests = MedicalRequest::with(['visit.patient.user'])->orderBy('id', 'desc')->take(10)->get();

echo "Last 10 requests:\n\n";

foreach ($allRequests as $request) {
    echo "ID: " . $request->id . "\n";
    echo "Patient: " . ($request->visit ? $request->visit->patient->user->name : 'No visit') . "\n";
    echo "Type: " . $request->type . "\n";
    echo "Status: " . $request->status . "\n";
    echo "Visit Status: " . ($request->visit ? $request->visit->status : 'No visit') . "\n";
    echo "Created: " . $request->created_at . "\n";
    echo "---\n";
}