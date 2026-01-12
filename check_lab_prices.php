<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص أسعار التحاليل ===\n\n";

$request = App\Models\Request::find(8);
$details = is_string($request->details) ? json_decode($request->details, true) : $request->details;

if (isset($details['lab_test_ids'])) {
    echo "التحاليل المطلوبة:\n";
    $total = 0;
    foreach($details['lab_test_ids'] as $testId) {
        $test = App\Models\LabTest::find($testId);
        if ($test) {
            $price = $test->price ?? 0;
            $total += $price;
            echo "  - " . $test->name . " (ID: $testId): " . number_format($price, 2) . " IQD\n";
        }
    }
    echo "\nالمجموع: " . number_format($total, 2) . " IQD\n";
}
?>