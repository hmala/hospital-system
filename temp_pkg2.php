<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pkg = App\Models\Package::find(3);
if (!$pkg) {
    echo "package not found\n";
    exit;
}
$rows = $pkg->labTests()->get();

echo 'count=' . $rows->count() . "\n";
foreach ($rows as $t) {
    echo $t->id . ' ' . $t->name . "\n";
}
