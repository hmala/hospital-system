<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$visitIds = [11, 12];
$visits = DB::table('visits')->whereIn('id', $visitIds)->get();

echo "Checking visits table for IDs 11 and 12:\n\n";

if ($visits->isEmpty()) {
    echo "No visits found with IDs 11 or 12\n";
} else {
    foreach ($visits as $visit) {
        echo "Visit ID: " . $visit->id . "\n";
        echo "Patient ID: " . $visit->patient_id . "\n";
        echo "Status: " . $visit->status . "\n";
        echo "Created: " . $visit->created_at . "\n";
        echo "---\n";
    }
}

// Check max visit ID
$maxVisitId = DB::table('visits')->max('id');
echo "\nMax visit ID in database: " . ($maxVisitId ?? 'No visits') . "\n";

// Check recent visits
$recentVisits = DB::table('visits')->orderBy('id', 'desc')->take(5)->get();
echo "\nRecent visits:\n";
foreach ($recentVisits as $visit) {
    echo "ID: {$visit->id}, Patient: {$visit->patient_id}, Status: {$visit->status}, Created: {$visit->created_at}\n";
}