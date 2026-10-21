<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// First check Hostinger remote DB if configured in scratch, or local DB
$em = \App\Models\Emergency::with(['patient', 'doctor', 'services', 'labRequests.tests', 'radiologyRequests.types'])->find(315);
if ($em) {
    echo "=== LOCAL DB: EMERGENCY 315 ===\n";
    echo "ID: " . $em->id . "\n";
    echo "Patient: " . ($em->patient ? $em->patient->name : 'N/A') . " (ID: {$em->patient_id})\n";
    echo "Is Insured: " . ($em->is_insured ? 'YES' : 'NO') . " | Type: " . $em->insurance_type . "\n";
    echo "Doctor: " . ($em->doctor ? $em->doctor->name : 'N/A') . "\n";
    echo "Status: " . $em->status . " | Payment Status: " . $em->payment_status . "\n";
    echo "Total Amount: " . $em->total_amount . " | Paid: " . $em->paid_amount . " | Remaining: " . $em->remaining_amount . "\n";
    echo "Services (" . $em->services->count() . "):\n";
    foreach ($em->services as $s) {
        echo " - [{$s->id}] {$s->name} | Price: {$s->price} | HI: {$s->hi_price}\n";
    }
    echo "Lab Requests: " . $em->labRequests->count() . "\n";
    foreach ($em->labRequests as $lr) {
        echo " - LabReq #{$lr->id} (Status: {$lr->status}, Pay: {$lr->payment_status})\n";
        foreach ($lr->tests as $t) {
            echo "   * {$t->name} (Price: {$t->price}, HI: {$t->hi_price})\n";
        }
    }
    echo "Rad Requests: " . $em->radiologyRequests->count() . "\n";
    foreach ($em->radiologyRequests as $rr) {
        echo " - RadReq #{$rr->id} (Status: {$rr->status}, Pay: {$rr->payment_status})\n";
        foreach ($rr->types as $rt) {
            echo "   * {$rt->name} (Price: {$rt->price}, HI: {$rt->hi_price})\n";
        }
    }
} else {
    echo "Emergency 315 not in local DB.\n";
    $latest = \App\Models\Emergency::latest('id')->first();
    echo "Local latest emergency ID: " . ($latest ? $latest->id : 'none') . "\n";
}

// Now let's check Hostinger DB directly if we have credentials
$hostingerEnv = [
    'host' => '195.35.48.1',
    'database' => 'u175868711_her2020',
    'username' => 'u175868711_her2020',
    'password' => 'Hosp@2020#Admin!',
    'port' => 3306,
];

try {
    $pdo = new PDO("mysql:host={$hostingerEnv['host']};port={$hostingerEnv['port']};dbname={$hostingerEnv['database']};charset=utf8mb4", $hostingerEnv['username'], $hostingerEnv['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ]);

    echo "\n=== HOSTINGER REMOTE DB: EMERGENCY 315 ===\n";
    $stmt = $pdo->prepare("SELECT * FROM emergencies WHERE id = 315");
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row) {
        print_r($row);

        // Fetch patient
        $stmtP = $pdo->prepare("SELECT id, name, phone, date_of_birth, gender, national_id, insurance_card_number FROM patients WHERE id = ?");
        $stmtP->execute([$row['patient_id']]);
        $pat = $stmtP->fetch();
        echo "Patient:\n";
        print_r($pat);

        // Fetch services
        $stmtS = $pdo->prepare("SELECT es.id, es.name, es.price, es.hi_price, es.is_hi_active FROM emergency_services es 
            JOIN emergency_service_emergency ese ON es.id = ese.emergency_service_id 
            WHERE ese.emergency_id = 315");
        $stmtS->execute();
        $srvs = $stmtS->fetchAll();
        echo "Services:\n";
        print_r($srvs);

        // Fetch lab requests
        $stmtL = $pdo->prepare("SELECT id, status, payment_status, total_amount, paid_amount, requested_at FROM requests WHERE emergency_id = 315 AND type = 'lab'");
        $stmtL->execute();
        $labs = $stmtL->fetchAll();
        echo "Lab Requests:\n";
        print_r($labs);
        foreach ($labs as $lb) {
            $stmtLt = $pdo->prepare("SELECT lt.id, lt.name, lt.price, lt.hi_price, lt.is_hi_active FROM lab_tests lt 
                JOIN request_lab_test rlt ON lt.id = rlt.lab_test_id 
                WHERE rlt.request_id = ?");
            $stmtLt->execute([$lb['id']]);
            echo " Tests for LabReq #{$lb['id']}:\n";
            print_r($stmtLt->fetchAll());
        }

        // Fetch rad requests
        $stmtR = $pdo->prepare("SELECT id, status, payment_status, total_amount, paid_amount, requested_at FROM requests WHERE emergency_id = 315 AND type = 'radiology'");
        $stmtR->execute();
        $rads = $stmtR->fetchAll();
        echo "Rad Requests:\n";
        print_r($rads);
        foreach ($rads as $rd) {
            $stmtRt = $pdo->prepare("SELECT rt.id, rt.name, rt.price, rt.hi_price, rt.is_hi_active FROM radiology_types rt 
                JOIN request_radiology_type rrt ON rt.id = rrt.radiology_type_id 
                WHERE rrt.request_id = ?");
            $stmtRt->execute([$rd['id']]);
            echo " Tests for RadReq #{$rd['id']}:\n";
            print_r($stmtRt->fetchAll());
        }

        // Fetch payments
        $stmtPay = $pdo->prepare("SELECT * FROM payments WHERE emergency_id = 315 OR payable_id = 315");
        $stmtPay->execute();
        echo "Payments:\n";
        print_r($stmtPay->fetchAll());
    } else {
        echo "Emergency 315 not found on Hostinger DB.\n";
        $stmtMax = $pdo->query("SELECT MAX(id) as max_id FROM emergencies");
        echo "Hostinger Max Emergency ID: " . $stmtMax->fetch()['max_id'] . "\n";
    }
} catch (\Exception $e) {
    echo "Hostinger DB Error: " . $e->getMessage() . "\n";
}
