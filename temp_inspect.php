<?php
use App\Models\Request as MedicalRequest;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = MedicalRequest::where('type','lab')->whereNotNull('details')->orderBy('id','desc')->take(5)->get();
foreach ($rows as $r) {
    echo "ID {$r->id} status {$r->status}\n";
    var_dump($r->details);
}
