<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RadiologyRequest;

echo "Radiology Requests:\n";
$requests = RadiologyRequest::with('result')->limit(5)->get();

foreach($requests as $r) {
    echo "ID: {$r->id}, Status: {$r->status}, Has Result: " . ($r->result ? 'Yes' : 'No');
    if($r->result && $r->result->images) {
        echo ", Images: " . count($r->result->images);
    }
    echo "\n";
}