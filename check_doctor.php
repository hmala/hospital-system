<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$request = App\Models\Request::find(8);
if($request) {
    echo 'Request ID: ' . $request->id . PHP_EOL;
    echo 'Visit ID: ' . $request->visit_id . PHP_EOL;
    echo 'Doctor ID: ' . $request->visit->doctor_id . PHP_EOL;
    if($request->visit->doctor) {
        echo 'Doctor Name: ' . $request->visit->doctor->user->name . PHP_EOL;
    } else {
        echo 'No doctor' . PHP_EOL;
    }
}
?>