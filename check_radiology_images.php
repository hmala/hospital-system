<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RadiologyResult;

echo "Checking radiology results with images:\n";
echo "======================================\n";

$results = RadiologyResult::whereNotNull('images')->get();

foreach($results as $result) {
    echo "Result ID: {$result->id}\n";
    echo "Images: " . json_encode($result->images) . "\n";
    echo "Type: " . gettype($result->images) . "\n";
    if (is_array($result->images) && count($result->images) > 0) {
        foreach($result->images as $img) {
            echo "  - {$img}\n";
            $fullPath = storage_path('app/public/' . $img);
            echo "    Exists: " . (file_exists($fullPath) ? 'Yes' : 'No') . "\n";
        }
    }
    echo "\n";
}
