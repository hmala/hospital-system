<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Doctor;

$doctor = Doctor::whereHas('user', function ($q) {
    $q->where('name', 'د. قمر سعد');
})->with(['user', 'financialAccount'])->first();

if (! $doctor) {
    echo "Not found\n";
    exit(0);
}

$account = $doctor->financialAccount;
echo "doctor_id=" . $doctor->id . "\n";
echo "name=" . optional($doctor->user)->name . "\n";
echo "balance=" . ($account ? $account->balance : 'null') . "\n";
echo "total_earned=" . ($account ? $account->total_earned : 'null') . "\n";
echo "total_paid=" . ($account ? $account->total_paid : 'null') . "\n";
