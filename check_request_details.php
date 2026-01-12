<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$request = App\Models\Request::find(8);
if($request) {
    echo 'ID: ' . $request->id . PHP_EOL;
    echo 'Type: ' . $request->type . PHP_EOL;
    echo 'Status: ' . $request->status . PHP_EOL;
    echo 'Details: ' . $request->details . PHP_EOL;
    $details = json_decode($request->details, true);
    if(isset($details['lab_test_ids'])) {
        echo 'Lab test IDs: ' . implode(', ', $details['lab_test_ids']) . PHP_EOL;
    } else {
        echo 'No lab_test_ids found' . PHP_EOL;
    }
    if(isset($details['created_at_inquiry'])) {
        echo 'Created at inquiry: ' . $details['created_at_inquiry'] . PHP_EOL;
    }
}
?>