<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Emergency;
use App\Models\EmergencyPatient;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyCashierStatementsNullSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_emergency_statements_renders_safely_with_emergency_patient_and_null_patient()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // 1. مريض طوارئ سريع بدون ملف دائم (emergency_patient_id فقط)
        $emergencyPatient = EmergencyPatient::create([
            'name' => 'حيدر علي طوارئ',
            'phone' => '07700000001',
            'gender' => 'male',
            'is_active' => true,
        ]);

        $emergency1 = Emergency::create([
            'emergency_patient_id' => $emergencyPatient->id,
            'patient_id' => null,
            'emergency_type' => 'general',
            'symptoms' => 'ألم حاد بالبطن',
            'admission_time' => now(),
            'is_insured' => false,
            'insurance_type' => 'none',
            'priority' => 'yellow',
            'status' => 'completed',
            'is_active' => true,
            'arrival_mode' => 'ambulance',
        ]);

        $payment1 = Payment::create([
            'emergency_id' => $emergency1->id,
            'patient_id' => null,
            'cashier_id' => $admin->id,
            'receipt_number' => 'REC-EMG-001',
            'amount' => 15000,
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
            'paid_at' => now(),
        ]);

        // 2. مريض عادي مرتبط بملف دائم
        $patientUser = User::factory()->create(['name' => 'زينب كاظم نظامي']);
        $patient2 = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'female',
            'insurance_type' => 'none',
        ]);

        $emergency2 = Emergency::create([
            'patient_id' => $patient2->id,
            'emergency_type' => 'general',
            'symptoms' => 'ارتفاع ضغط الدم',
            'admission_time' => now(),
            'is_insured' => false,
            'insurance_type' => 'none',
            'priority' => 'green',
            'status' => 'completed',
            'is_active' => true,
        ]);

        $payment2 = Payment::create([
            'emergency_id' => $emergency2->id,
            'patient_id' => $patient2->id,
            'cashier_id' => $admin->id,
            'receipt_number' => 'REC-EMG-002',
            'amount' => 25000,
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
            'paid_at' => now(),
        ]);

        // 3. دفعة طوارئ يتيمة بدون emergency ولا patient
        $payment3 = Payment::create([
            'emergency_id' => null,
            'patient_id' => null,
            'cashier_id' => $admin->id,
            'receipt_number' => 'REC-EMG-003',
            'amount' => 5000,
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('cashier.emergency.statements'));

        $response->assertStatus(200);
        $response->assertSee('حيدر علي طوارئ');
        $response->assertSee('زينب كاظم نظامي');
        $response->assertSee('غير معروف');
    }

    public function test_emergency_financial_movements_renders_safely_with_emergency_patient()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $emergencyPatient = EmergencyPatient::create([
            'name' => 'كرار جاسم طوارئ',
            'phone' => '07700000002',
            'gender' => 'male',
            'is_active' => true,
        ]);

        $emergency = Emergency::create([
            'emergency_patient_id' => $emergencyPatient->id,
            'patient_id' => null,
            'emergency_type' => 'general',
            'symptoms' => 'ضيق تنفس حاد',
            'admission_time' => now(),
            'is_insured' => false,
            'insurance_type' => 'none',
            'priority' => 'red',
            'status' => 'completed',
            'is_active' => true,
        ]);

        Payment::create([
            'emergency_id' => $emergency->id,
            'patient_id' => null,
            'cashier_id' => $admin->id,
            'receipt_number' => 'REC-EMG-MOV-001',
            'amount' => 30000,
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('cashier.emergency.financial-movements'));

        $response->assertStatus(200);
        $response->assertSee('كرار جاسم طوارئ');
    }

    public function test_emergency_doctor_account_renders_safely_with_emergency_patient()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'مستشفى الشفاء التخصصي',
            'owner_name' => 'د. محمد',
            'phone' => '07701234567',
            'address' => 'بغداد',
            'license_number' => 'LIC-TEST-001',
            'bed_capacity' => 50,
            'has_emergency' => true,
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الطوارئ',
            'room_number' => 'ER-101',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
            'type' => 'emergency',
            'is_active' => true,
        ]);

        $docUser = User::factory()->create(['name' => 'د. حسام طوارئ']);
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'طب طوارئ',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-'.uniqid(),
            'is_active' => true,
        ]);

        $emergencyPatient = EmergencyPatient::create([
            'name' => 'مرتضى سعد طوارئ',
            'gender' => 'male',
            'is_active' => true,
        ]);

        $payment = Payment::create([
            'cashier_id' => $admin->id,
            'receipt_number' => 'REC-DOC-001',
            'amount' => 10000,
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
            'paid_at' => now(),
        ]);

        Emergency::create([
            'emergency_patient_id' => $emergencyPatient->id,
            'patient_id' => null,
            'doctor_id' => $doctor->id,
            'emergency_type' => 'general',
            'symptoms' => 'متابعة سريرية بعد الإسعاف',
            'admission_time' => now(),
            'is_insured' => false,
            'insurance_type' => 'none',
            'priority' => 'yellow',
            'status' => 'completed',
            'is_active' => true,
            'doctor_follow_up_fee' => 10000,
            'follow_up_payment_id' => $payment->id,
        ]);

        $response = $this->actingAs($admin)->get(route('cashier.emergency.doctor-account', $doctor));

        $response->assertStatus(200);
        $response->assertSee('مرتضى سعد طوارئ');
    }
}
