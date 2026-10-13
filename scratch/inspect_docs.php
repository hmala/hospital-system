<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PatientDocument;
use Illuminate\Support\Facades\Storage;

$documents = PatientDocument::latest()->take(10)->get();

echo "=== Patient Documents Inspect ===\n";
foreach ($documents as $doc) {
    echo "ID: {$doc->id} | Title: {$doc->title}\n";
    echo "file_path in DB: {$doc->file_path}\n";
    
    $cleanPath = ltrim($doc->file_path, '/');
    if (str_starts_with($cleanPath, 'public/')) $cleanPath = substr($cleanPath, 7);
    if (str_starts_with($cleanPath, 'storage/')) $cleanPath = substr($cleanPath, 8);

    $p1 = storage_path('app/public/' . $cleanPath);
    $p2 = storage_path('app/' . $cleanPath);
    $p3 = public_path('storage/' . $cleanPath);
    $p4 = public_path($cleanPath);

    echo "Path 1 (storage/app/public): " . (file_exists($p1) ? "FOUND" : "NOT FOUND") . " ($p1)\n";
    echo "Path 2 (storage/app): " . (file_exists($p2) ? "FOUND" : "NOT FOUND") . " ($p2)\n";
    echo "Path 3 (public/storage): " . (file_exists($p3) ? "FOUND" : "NOT FOUND") . " ($p3)\n";
    echo "Path 4 (public/): " . (file_exists($p4) ? "FOUND" : "NOT FOUND") . " ($p4)\n";
    echo "--------------------------------------------------\n";
}
