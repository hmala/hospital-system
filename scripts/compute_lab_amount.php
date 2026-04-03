<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Request as MedicalRequest;
use App\Models\LabTest;

$req = MedicalRequest::find($argv[1] ?? 54);
if(!$req){ echo "REQUEST_NOT_FOUND\n"; exit; }
$details = is_string($req->details) ? json_decode($req->details, true) : $req->details;
$total = 0;
if(isset($details['lab_test_ids'])){
    foreach($details['lab_test_ids'] as $id){
        $t = LabTest::find($id);
        $price = $t ? ($t->price ?? 0) : 0;
        echo "ID $id -> price=$price\n";
        $total += $price;
    }
} elseif(isset($details['tests'])){
    foreach($details['tests'] as $name){
        $t = LabTest::where('name',$name)->orWhere('code',$name)->first();
        $price = $t ? ($t->price ?? 0) : 0;
        echo "NAME $name -> " . ($t ? "found id={$t->id} price={$price}" : "not found price=0") . "\n";
        $total += $price;
    }
} else {
    echo "No tests info\n";
}

echo "TOTAL: $total\n";
