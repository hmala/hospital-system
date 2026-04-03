<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Request as MedicalRequest;
use App\Models\LabTest;

$req = MedicalRequest::where('type','lab')->where('payment_status','pending')->with('visit.patient.user')->first();
if(!$req){ echo "NO_PENDING_LAB_REQUEST\n"; exit;
}

echo "REQUEST_ID: {$req->id}\n";
echo "VISIT_ID: {$req->visit_id}\n";
echo "PATIENT: " . ($req->visit->patient->user->name ?? 'N/A') . "\n";
$details = is_string($req->details) ? json_decode($req->details, true) : $req->details;
print_r($details);
if(isset($details['lab_test_ids'])){
    foreach($details['lab_test_ids'] as $id){
        $t = LabTest::find($id);
        if($t) echo "LABTEST {$t->id} {$t->name} price=" . ($t->price ?? 'NULL') . "\n";
        else echo "LABTEST $id NOT FOUND\n";
    }
}
