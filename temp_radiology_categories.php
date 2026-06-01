<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\RadiologyType::select('category')->distinct()->get() as $t) {
    echo ($t->category ?? 'NULL') . PHP_EOL;
}
