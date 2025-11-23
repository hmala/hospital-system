<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;

try {
    // Get the first visit to test with
    $visit = Visit::first();
    if (!$visit) {
        echo "No visits found in database. Cannot test treatment_plan update.\n";
        exit(1);
    }

    echo "Testing treatment_plan update...\n";

    // Test updating treatment_plan
    $visit->update([
        'treatment_plan' => 'Test treatment plan: Take medication twice daily, exercise regularly, follow-up in 2 weeks.'
    ]);

    echo "SUCCESS: treatment_plan updated successfully!\n";
    echo "Current treatment_plan: " . $visit->treatment_plan . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Test completed successfully!\n";