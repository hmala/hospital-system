<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$requests = App\Models\Request::where('payment_status', 'pending')->get();
echo 'Pending requests: ' . $requests->count() . PHP_EOL;
foreach($requests as $request) {
    echo 'ID: ' . $request->id . ' - Type: ' . $request->type . ' - Payment Status: ' . $request->payment_status . PHP_EOL;
}
?>