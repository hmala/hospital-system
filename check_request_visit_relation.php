<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Request as MedicalRequest;

$requests = MedicalRequest::orderBy('id', 'desc')->take(5)->get();

echo "Checking request-visit relationships:\n\n";

foreach ($requests as $request) {
    echo "Request ID: " . $request->id . "\n";
    echo "Visit ID in request: " . ($request->visit_id ?? 'NULL') . "\n";
    
    if ($request->visit_id) {
        $visit = $request->visit()->first();
        if ($visit) {
            echo "Visit exists: Yes\n";
            echo "Visit status: " . $visit->status . "\n";
            echo "Patient: " . ($visit->patient ? $visit->patient->user->name : 'No patient') . "\n";
        } else {
            echo "Visit exists: No (foreign key issue?)\n";
        }
    } else {
        echo "No visit_id in request\n";
    }
    echo "---\n";
}