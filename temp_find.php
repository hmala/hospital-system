<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = App\Models\Request::where('type','lab')->whereNotNull('details')->where('status','pending')->take(20)->get();
foreach ($rows as $r) {
    $details = $r->details;
    if (is_string($details)) {
        $details = json_decode($details,true);
    }
    echo "ID $r->id status $r->status\n";
    var_dump($details);
}
