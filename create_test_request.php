<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Request as MedicalRequest;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Department;

// إنشاء طلب تجريبي
$patient = App\Models\Patient::first();
if($patient) {
    $inquiryDept = App\Models\Department::where('name', 'LIKE', '%استعلامات%')->first();
    if(!$inquiryDept) {
        $inquiryDept = App\Models\Department::create([
            'name' => 'الاستعلامات',
            'hospital_id' => 1,
            'type' => 'other',
            'room_number' => 'Reception-001',
            'consultation_fee' => 0.00,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '17:00:00',
            'is_active' => true
        ]);
    }
    
    $visit = App\Models\Visit::create([
        'patient_id' => $patient->id,
        'department_id' => $inquiryDept->id,
        'visit_date' => now(),
        'visit_time' => now(),
        'visit_type' => 'lab',
        'chief_complaint' => 'تحاليل تجريبية',
        'status' => 'in_progress'
    ]);
    
    $request = MedicalRequest::create([
        'visit_id' => $visit->id,
        'type' => 'lab',
        'description' => 'تحاليل تجريبية',
        'status' => 'pending',
        'payment_status' => 'pending',
        'details' => json_encode(['lab_test_ids' => ['1', '2'], 'created_at_inquiry' => true])
    ]);
    
    echo 'Created request ID: ' . $request->id . PHP_EOL;
} else {
    echo 'No patients found' . PHP_EOL;
}
?>