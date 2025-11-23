<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RadiologyRequest;

echo "Checking radiology requests with results:\n";
echo "========================================\n";

$radiology = RadiologyRequest::with('result')->whereHas('result', function($q) {
    $q->whereNotNull('images');
})->first();

if ($radiology && $radiology->result) {
    echo "Request ID: {$radiology->id}\n";
    echo "Result ID: {$radiology->result->id}\n";
    echo "Images JSON: " . json_encode($radiology->result->images) . "\n\n";
    
    if (is_array($radiology->result->images)) {
        foreach($radiology->result->images as $image) {
            echo "Image path in DB: {$image}\n";
            echo "Asset URL would be: " . url('storage/' . $image) . "\n";
            echo "File exists at storage/app/public/{$image}: " . (file_exists(storage_path('app/public/' . $image)) ? 'Yes' : 'No') . "\n";
            echo "File exists at public/storage/{$image}: " . (file_exists(public_path('storage/' . $image)) ? 'Yes' : 'No') . "\n\n";
        }
    }
}
