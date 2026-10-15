<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Emergency;
use App\Models\EmergencyService;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyMedicalHistoryAndPaidServicesTest extends TestCase
{
    use RefreshDatabase;

    protected $hospital;
    protected $department;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'د. حسام',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-LIC-' . uniqid(),
        ]);

        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'قسم الباطنية',
            'type' => 'emergency',
            'room_number' => '101',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
        ]);
    }

    public function test_emergency_show_renders_medical_history_tab_with_past_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $patientUser = User::factory()->create(['name' => 'عمر عبد الله حسن']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'medical_number' => 'MED-TEST-' . rand(1000, 9999),
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'blood_group' => 'A+',
        ]);

        // Create past visit
        $pastVisit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'visit_type' => 'checkup',
            'visit_date' => now()->subDays(5)->format('Y-m-d'),
            'visit_time' => '10:00',
            'chief_complaint' => 'صداع مستمر وألم بالبطن',
            'status' => 'completed',
            'diagnosis' => ['description' => 'التهاب حاد في المعدة'],
        ]);

        // Create past lab request
        MedicalRequest::create([
            'visit_id' => $pastVisit->id,
            'type' => 'lab',
            'status' => 'completed',
            'description' => 'تحليل دم عام',
            'details' => ['tests' => ['CBC', 'Blood Sugar']],
            'result' => json_encode([
                'test_results' => [
                    'WBC' => ['value' => '11.5', 'unit' => '10^3/uL', 'reference' => '4.0-10.0', 'abnormal' => true]
                ]
            ]),
        ]);

        $emergency = Emergency::create([
            'patient_id' => $patient->id,
            'admission_time' => now(),
            'status' => 'in_progress',
            'priority' => 'urgent',
            'emergency_type' => 'medical',
            'symptoms' => 'ألم حاد في الصدر',
        ]);

        $response = $this->actingAs($admin)->get(route('emergency.show', $emergency));

        $response->assertStatus(200);
        $response->assertSee('السجل الطبي ونتائج الفحوصات');
        $response->assertSee('نتائج التحاليل السابقة');
        $response->assertSee('الزيارات الاستشارية السابقة');
        $response->assertSee('عمر عبد الله حسن');
        $response->assertSee('التهاب حاد في المعدة');
        $response->assertSee('11.5');
    }

    public function test_paid_emergency_services_are_safeguarded_from_deletion_in_update_medical()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'medical_number' => 'MED-TEST-' . rand(1000, 9999),
        ]);

        $emergency = Emergency::create([
            'patient_id' => $patient->id,
            'admission_time' => now(),
            'status' => 'in_progress',
            'priority' => 'urgent',
            'emergency_type' => 'medical',
            'symptoms' => 'ألم بالبطن',
        ]);

        $srvPaid = EmergencyService::create([
            'name' => 'تخطيط قلب ECG',
            'price' => 15000,
            'category' => 'تشخيصي',
            'is_active' => true,
        ]);

        $srvUnpaid = EmergencyService::create([
            'name' => 'خياطة جرح صغرى',
            'price' => 20000,
            'category' => 'علاجي',
            'is_active' => true,
        ]);

        $payment = \App\Models\Payment::create([
            'patient_id' => $patient->id,
            'emergency_id' => $emergency->id,
            'amount' => 15000,
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Attach both services
        $emergency->services()->attach([
            $srvPaid->id => ['payment_id' => $payment->id],
            $srvUnpaid->id => ['payment_id' => null],
        ]);

        // Verify initial state
        $this->assertCount(2, $emergency->services);

        // Submit updateMedical with ONLY a new service, omitting both srvPaid and srvUnpaid
        $srvNew = EmergencyService::create([
            'name' => 'كمام رذاذ',
            'price' => 5000,
            'category' => 'تنفسي',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('emergency.update-medical', $emergency), [
            'diagnosis' => 'حالة مستقرة',
            'service_ids' => [$srvNew->id], // Intentionally does NOT include $srvPaid
        ]);

        $response->assertRedirect();

        // Refresh services from DB
        $emergency->load('services');

        // $srvPaid MUST still be attached and still have payment_id!
        $paidPivot = DB::table('emergency_emergency_service')
            ->where('emergency_id', $emergency->id)
            ->where('emergency_service_id', $srvPaid->id)
            ->first();

        $this->assertNotNull($paidPivot, 'Paid service should NOT be detached');
        $this->assertEquals($payment->id, $paidPivot->payment_id);

        // $srvUnpaid should have been removed because it was unpaid and omitted
        $unpaidPivot = DB::table('emergency_emergency_service')
            ->where('emergency_id', $emergency->id)
            ->where('emergency_service_id', $srvUnpaid->id)
            ->first();
        $this->assertNull($unpaidPivot, 'Unpaid service can be removed when omitted');

        // $srvNew should be attached
        $newPivot = DB::table('emergency_emergency_service')
            ->where('emergency_id', $emergency->id)
            ->where('emergency_service_id', $srvNew->id)
            ->first();
        $this->assertNotNull($newPivot, 'New service should be attached');
    }
}
