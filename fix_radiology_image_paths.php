<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RadiologyResult;

echo "Fixing radiology image paths:\n";
echo "============================\n";

$result = RadiologyResult::find(2);

if ($result && $result->images) {
    echo "Current images: " . json_encode($result->images) . "\n";
    
    // تصحيح المسار
    $correctedImages = [];
    foreach ($result->images as $image) {
        // إزالة "results/" من المسار
        $correctedPath = str_replace('radiology/results/', 'radiology/', $image);
        
        // التحقق من وجود الملف
        if (file_exists(storage_path('app/public/' . $correctedPath))) {
            echo "Found file at: {$correctedPath}\n";
            $correctedImages[] = $correctedPath;
        } else {
            echo "File not found at: {$correctedPath}\n";
            // جرب المسار الأصلي
            if (file_exists(storage_path('app/public/' . $image))) {
                echo "Using original path: {$image}\n";
                $correctedImages[] = $image;
            }
        }
    }
    
    if (!empty($correctedImages)) {
        $result->images = $correctedImages;
        $result->save();
        echo "\n✓ Updated images to: " . json_encode($correctedImages) . "\n";
    }
}
