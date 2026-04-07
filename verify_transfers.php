<?php
require "vendor/autoload.php";
require "bootstrap/app.php";
$kernel = app()->make('Illuminate\\Contracts\\Console\\Kernel');
$kernel->bootstrap();
$transfers = App\Models\StockMovement::where('type', 'transfer')->orderBy('created_at', 'desc')->take(10)->get();
echo "Last 10 transfers:\n";
foreach ($transfers as $transfer) {
    $batch = $transfer->batch;
    echo "movement id={$transfer->id} batch_id={$transfer->batch_id} product_id=" . ($batch ? $batch->product_id : 'null') . " from={$transfer->from_location_id} to={$transfer->to_location_id} qty={$transfer->quantity} batch_received_at=" . ($batch ? $batch->received_at : 'null') . " transfer_at={$transfer->created_at}\n";
}
