<?php
// فحص الزيارة #24

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$visit = \App\Models\Visit::find(24);

if (!$visit) {
    echo "الزيارة #24 غير موجودة\n";
    exit;
}

echo "=== تفاصيل الزيارة #24 ===\n";
echo "ID: {$visit->id}\n";
echo "Patient ID: {$visit->patient_id}\n";
echo "Doctor ID: " . ($visit->doctor_id ?? 'NULL') . "\n";
echo "Department ID: " . ($visit->department_id ?? 'NULL') . "\n";
echo "Visit Type: {$visit->visit_type}\n";
echo "Status: {$visit->status}\n";
echo "Created At: {$visit->created_at}\n";

if ($visit->patient) {
    echo "\nالمريض: {$visit->patient->user->name}\n";
}

if ($visit->doctor) {
    echo "الطبيب: {$visit->doctor->user->name}\n";
} else {
    echo "الطبيب: غير محدد\n";
    
    // البحث عن طبيب من القسم
    if ($visit->department_id) {
        $doctor = \App\Models\Doctor::where('department_id', $visit->department_id)->first();
        if ($doctor) {
            echo "طبيب متاح من القسم: {$doctor->user->name} (ID: {$doctor->id})\n";
        }
    }
}
