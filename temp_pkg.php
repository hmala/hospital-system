<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pkg = App\Models\Package::find(3);
if (!$pkg) {
    echo "package 3 not found\n";
    exit;
}

echo "Package: {$pkg->name}\n";
$tests = $pkg->labTests()->pluck('name')->toArray();
var_dump($tests);
