<?php
require "vendor/autoload.php";
require "bootstrap/app.php";
$kernel = app()->make("Illuminate\\Contracts\\Console\\Kernel");
$kernel->bootstrap();
$rows = App\Models\StockBatch::orderBy('id')->take(10)->get();
foreach ($rows as $row) {
    echo 'id=' . $row->id . ' product=' . $row->product_id . ' loc=' . $row->location_id . ' qty=' . $row->current_qty . ' internal=' . $row->internal_barcode . ' original=' . ($row->original_barcode ?: 'NULL') . ' received=' . $row->received_at . ' original_received=' . ($row->original_received_at ?: 'NULL') . "\n";
}
