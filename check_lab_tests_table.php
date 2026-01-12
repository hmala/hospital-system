<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص جدول lab_tests ===\n\n";
$columns = DB::select('DESCRIBE lab_tests');
echo "الأعمدة الموجودة:\n";
foreach($columns as $col) {
    echo "  - " . $col->Field . " (" . $col->Type . ")\n";
}
?>