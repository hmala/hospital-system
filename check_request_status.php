<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$request = App\Models\Request::find(5);
if($request) {
    echo 'ID: ' . $request->id . PHP_EOL;
    echo 'Status: ' . $request->status . PHP_EOL;
    echo 'Payment Status: ' . $request->payment_status . PHP_EOL;
}
?>