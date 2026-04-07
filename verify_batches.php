<?php
require "vendor/autoload.php";
require "bootstrap/app.php";
$kernel = app()->make('Illuminate\\Contracts\\Console\\Kernel');
$kernel->bootstrap();
$batches = App\Models\StockBatch::where('location_id', 3)->orderBy('received_at')->get();
echo "Batches at location 3:\n";
foreach ($batches as $batch) {
    echo "id={$batch->id} product_id={$batch->product_id} qty={$batch->current_qty} received_at={$batch->received_at} purchase_item_id={$batch->purchase_item_id}\n";
}
