<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';
$kernel=app()->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();
$count = App\Models\StockBatch::where('product_id',25)->where('location_id',3)->count();
echo "count={$count}\n";
$batches = App\Models\StockBatch::where('product_id',25)->where('location_id',3)->orderBy('received_at')->get();
foreach($batches as $batch) {
    echo "id={$batch->id} qty={$batch->current_qty} received_at={$batch->received_at}\n";
}
