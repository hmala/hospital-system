<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$visitsByMonth = \App\Models\Visit::selectRaw('DATE_FORMAT(visit_date, "%Y-%m") as ym, count(*) as c')
    ->groupBy('ym')
    ->get();

echo "Visits by month:\n";
foreach($visitsByMonth as $v) {
    echo "{$v->ym}: {$v->c}\n";
}

$apptsByMonth = \App\Models\Appointment::selectRaw('DATE_FORMAT(appointment_date, "%Y-%m") as ym, count(*) as c')
    ->groupBy('ym')
    ->get();

echo "Appointments by month:\n";
foreach($apptsByMonth as $a) {
    echo "{$a->ym}: {$a->c}\n";
}
