<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// الطلب رقم 3
$request = App\Models\Request::find(3);

if ($request) {
    echo "Request ID: {$request->id}\n";
    echo "Visit ID: {$request->visit_id}\n";
    echo "Type: {$request->type}\n";
    echo "Description: {$request->description}\n";
    echo "Details: " . json_encode($request->details) . "\n\n";
    
    // البحث عن RadiologyRequest
    $radiologyRequests = App\Models\RadiologyRequest::where('visit_id', $request->visit_id)->get();
    echo "Found " . $radiologyRequests->count() . " radiology requests for visit_id {$request->visit_id}\n\n";
    
    foreach ($radiologyRequests as $rr) {
        echo "RadiologyRequest ID: {$rr->id}\n";
        echo "Visit ID: {$rr->visit_id}\n";
        echo "Radiology Type ID: {$rr->radiology_type_id}\n";
        if ($rr->radiologyType) {
            echo "Radiology Type Name: {$rr->radiologyType->name}\n";
        }
        echo "---\n";
    }
} else {
    echo "Request #3 not found\n";
}
