<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DoctorCommissionSetting;

$doctorId = 99;
$settings = DoctorCommissionSetting::where('doctor_id', $doctorId)->orderByDesc('id')->get();
foreach ($settings as $setting) {
    echo "id={$setting->id}, type={$setting->commission_type}, value={$setting->commission_value}, fixed={$setting->fixed_amount}, active={$setting->is_active}, dept={$setting->department_id}, service={$setting->service_type_id}\n";
}
