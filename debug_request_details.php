<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Request as MedicalRequest;

echo "=== فحص تفاصيل الطلبات ===\n\n";

$requests = MedicalRequest::with(['visit.patient.user'])
    ->where('type', 'lab')
    ->where('payment_status', 'paid')
    ->get();

foreach ($requests as $request) {
    echo "Request #{$request->id}:\n";
    echo "Type: {$request->type}\n";
    echo "Details type: " . gettype($request->details) . "\n";
    
    if (is_array($request->details)) {
        echo "Details keys: " . implode(', ', array_keys($request->details)) . "\n";
        
        if (isset($request->details['lab_test_ids'])) {
            echo "Lab test IDs: " . implode(', ', $request->details['lab_test_ids']) . "\n";
            
            foreach ($request->details['lab_test_ids'] as $testId) {
                $test = \App\Models\LabTest::find($testId);
                if ($test) {
                    echo "  - {$test->name}\n";
                }
            }
        } else {
            echo "NO lab_test_ids found!\n";
        }
    } else {
        echo "Details is NOT an array: {$request->details}\n";
    }
    
    echo "Description: " . ($request->description ?? 'NULL') . "\n";
    echo "---\n\n";
}