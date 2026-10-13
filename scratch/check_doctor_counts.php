<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$doctors = \App\Models\Doctor::with('user')->get();
foreach ($doctors as $d) {
    $user = $d->user->name ?? 'Unknown';
    $apptsMonth = \App\Models\Appointment::where('doctor_id', $d->id)->whereYear('appointment_date', 2026)->whereMonth('appointment_date', 10)->count();
    $visitsMonth = \App\Models\Visit::where('doctor_id', $d->id)->whereYear('visit_date', 2026)->whereMonth('visit_date', 10)->count();
    $allAppts = \App\Models\Appointment::where('doctor_id', $d->id)->count();
    $allVisits = \App\Models\Visit::where('doctor_id', $d->id)->count();
    echo "Doctor: {$user} (ID: {$d->id}) | Month: Appts={$apptsMonth}, Visits={$visitsMonth} | All-time: Appts={$allAppts}, Visits={$allVisits}\n";
}
