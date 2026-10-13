<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;

try {
    $remoteP = DB::connection('remote_hostinger')->table('payments')->where('id', 68)->first();
    $remoteAppt = DB::connection('remote_hostinger')->table('appointments')->where('id', $remoteP->appointment_id)->first();
    $remotePatient = DB::connection('remote_hostinger')->table('patients')->where('id', $remoteP->patient_id)->first();
    $remoteUser = $remotePatient ? DB::connection('remote_hostinger')->table('users')->where('id', $remotePatient->user_id)->first() : null;
    $remoteDoctor = $remoteAppt ? DB::connection('remote_hostinger')->table('doctors')->where('id', $remoteAppt->doctor_id)->first() : null;
    $remoteDocUser = $remoteDoctor ? DB::connection('remote_hostinger')->table('users')->where('id', $remoteDoctor->user_id)->first() : null;

    echo "Remote Appointment: " . json_encode($remoteAppt) . "\n";
    echo "Remote Patient: " . json_encode($remotePatient) . "\n";
    echo "Remote User: " . json_encode($remoteUser) . "\n";
    echo "Remote Doctor: " . json_encode($remoteDoctor) . "\n";

    // Now let's simulate the Payment model with relations:
    $p = new Payment((array)$remoteP);
    $p->id = $remoteP->id;
    
    if ($remoteAppt) {
        $appt = new Appointment((array)$remoteAppt);
        $appt->id = $remoteAppt->id;
        if ($remoteDoctor) {
            $doc = new \App\Models\Doctor((array)$remoteDoctor);
            if ($remoteDocUser) {
                $doc->setRelation('user', new User((array)$remoteDocUser));
            }
            $appt->setRelation('doctor', $doc);
        }
        $p->setRelation('appointment', $appt);
    }
    if ($remotePatient) {
        $pat = new Patient((array)$remotePatient);
        $pat->id = $remotePatient->id;
        if ($remoteUser) {
            $pat->setRelation('user', new User((array)$remoteUser));
        }
        $p->setRelation('patient', $pat);
    }

    $html = view('cashier.receipt-print', ['payment' => $p])->render();
    echo "\nRENDER SUCCESSFUL! Output length: " . strlen($html) . "\n";
} catch (\Throwable $e) {
    echo "\nRENDER ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
