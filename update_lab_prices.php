<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== تحديث أسعار التحاليل ===\n\n";

// تحديث أسعار تجريبية
$prices = [
    1 => 15000,  // 17 hydroxy prog
    2 => 20000,  // 24 hr urine for protein
    4 => 25000,  // A.M.H. ABG
];

foreach($prices as $id => $price) {
    $test = App\Models\LabTest::find($id);
    if ($test) {
        $test->update(['price' => $price]);
        echo "✅ " . $test->name . " - السعر: " . number_format($price, 2) . " IQD\n";
    }
}

echo "\n✅ تم تحديث الأسعار بنجاح!\n";
?>