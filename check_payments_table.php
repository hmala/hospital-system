<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== أعمدة جدول payments ===\n\n";
$columns = DB::select('DESCRIBE payments');
foreach($columns as $col) {
    echo $col->Field . " (" . $col->Type . ")\n";
}
?>