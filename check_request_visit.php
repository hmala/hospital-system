<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$request = App\Models\Request::find(8);
if($request) {
    echo 'Request ID: ' . $request->id . PHP_EOL;
    echo 'Visit ID: ' . $request->visit_id . PHP_EOL;
    echo 'Visit Status: ' . $request->visit->status . PHP_EOL;
    echo 'Payment Status: ' . $request->payment_status . PHP_EOL;
}
?>