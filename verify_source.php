<?php
require "vendor/autoload.php";
require "bootstrap/app.php";
$kernel = app()->make('Illuminate\\Contracts\\Console\\Kernel');
$kernel->bootstrap();
$batches = App\Models\StockBatch::where('location_id', 1)->where('product_id', 3)->orderBy('received_at')->get();
echo "Source location 1 batches for product 3:\n";
foreach ($batches as $batch) {
    echo "id={$batch->id} qty={$batch->current_qty} received_at={$batch->received_at}\n";
}
