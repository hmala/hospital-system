<?php

require_once 'vendor/autoload.php';

use App\Models\Surgery;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "Testing Time Conversion Logic\n";
echo "=============================\n\n";

// Get a completed surgery
$surgery = Surgery::where('status', 'completed')->first();

if (!$surgery) {
    echo "No completed surgeries found for testing.\n";
    exit(1);
}

echo "Surgery ID: {$surgery->id}\n";
echo "Scheduled Date: {$surgery->scheduled_date}\n";
echo "Current start_time: " . ($surgery->start_time ? $surgery->start_time->format('Y-m-d H:i:s') : 'null') . "\n";
echo "Current end_time: " . ($surgery->end_time ? $surgery->end_time->format('H:i') : 'null') . "\n\n";

// Test time conversion logic
$startTimeInput = '10:30';
$endTimeInput = '12:45';

$startDateTime = $surgery->scheduled_date->format('Y-m-d') . ' ' . $startTimeInput . ':00';
$endDateTime = $surgery->scheduled_date->format('Y-m-d') . ' ' . $endTimeInput . ':00';

echo "Input start time: {$startTimeInput}\n";
echo "Converted start datetime: {$startDateTime}\n\n";

echo "Input end time: {$endTimeInput}\n";
echo "Converted end datetime: {$endDateTime}\n\n";

echo "Test completed successfully!\n";