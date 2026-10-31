<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Emergency;
use App\Models\EmergencyService;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Request as MedicalRequest;
use App\Models\Surgery;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CashierDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'process-payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process medical requests payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process emergency payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process surgery payments', 'guard_name' => 'web']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-001',
            'phone' => '07700000000',
            'email' => 'info@hospital.com',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الباطنية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'code' => 'INT01',
        ]);

        $this->cashier = User::factory()->create();
        $this->cashier->givePermissionTo([
            'process-payments',
            'process medical requests payments',
            'process emergency payments',
            'process surgery payments',
        ]);
    }

    public function test_cashier_can_apply_discount_on_non_insured_request_payment()
    {
        $patientUser = User::factory()->create(['name' => 'مريض نقدي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'none',
            'gender' => 'male',
            'date_of_birth' => '1995-01-01',
        ]);

        $doctorUser = User::factory()->create();
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'General',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-'.uniqid(),
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->department->id,
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص روتيني',
            'visit_date' => now(),
            'visit_time' => '10:00:00',
            'status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $labTest = LabTest::create([
            'name' => 'CBC Test',
            'code' => 'CBC01',
            'price' => 50000,
            'is_active' => true,
        ]);

        $req = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبرية',
            'status' => 'pending',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
            'details' => [
                'lab_test_ids' => [$labTest->id],
                'tests' => [$labTest->name],
            ],
            'requested_by' => $doctorUser->id,
        ]);

        $postData = [
            'payment_method' => 'cash',
            'insurance_type' => 'none',
            'amount' => 40000, // Net amount after 20% discount
            'discount_percentage' => 20,
            'discount_reason' => 'خصم نقابة الأطباء',
            'items' => [
                $req->id => [
                    'lab' => [
                        $labTest->id => 'pay',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashier)
            ->post(route('cashier.request.payment.process', $req->id), $postData);

        $response->assertRedirect();

        $payment = Payment::where('patient_id', $patient->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(50000, (float) $payment->total_amount);
        $this->assertEquals(50000, (float) $payment->patient_share);
        $this->assertEquals(20.00, (float) $payment->discount_percentage);
        $this->assertEquals(10000.00, (float) $payment->discount_amount);
        $this->assertEquals(40000.00, (float) $payment->amount);
        $this->assertEquals('خصم نقابة الأطباء', $payment->discount_reason);
        $this->assertEquals($this->cashier->id, $payment->discount_by);
        $this->assertEquals('none', $payment->insurance_type);
    }

    public function test_cashier_can_apply_per_item_discount_on_non_insured_request_payment()
    {
        $patientUser = User::factory()->create(['name' => 'مريض نقدي خصم تفصيلي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'none',
            'gender' => 'male',
            'date_of_birth' => '1992-03-10',
        ]);

        $doctorUser = User::factory()->create();
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'General',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-'.uniqid(),
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->department->id,
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحوصات متنوعة',
            'visit_date' => now(),
            'visit_time' => '10:00:00',
            'status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $labTestA = LabTest::create([
            'name' => 'CBC Test',
            'code' => 'CBC99',
            'price' => 40000,
            'is_active' => true,
        ]);

        $labTestB = LabTest::create([
            'name' => 'Lipid Profile',
            'code' => 'LIP99',
            'price' => 20000,
            'is_active' => true,
        ]);

        $req = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبرية متعددة',
            'status' => 'pending',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
            'details' => [
                'lab_test_ids' => [$labTestA->id, $labTestB->id],
                'tests' => [$labTestA->name, $labTestB->name],
            ],
            'requested_by' => $doctorUser->id,
        ]);

        $postData = [
            'payment_method' => 'cash',
            'insurance_type' => 'none',
            'discount_scope' => 'per_item',
            'discount_reason' => 'موافقة الطبيب المعالج',
            'item_discounts' => [
                $req->id => [
                    'lab' => [
                        $labTestA->id => 50, // 50% discount on 40,000 = 20,000 IQD discount
                        $labTestB->id => 0,  // 0% discount on 20,000
                    ],
                ],
            ],
            'amount' => 40000, // 60,000 - 20,000
            'item_actions' => [
                $req->id => [
                    'lab' => [
                        $labTestA->id => 'pay',
                        $labTestB->id => 'pay',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashier)
            ->post(route('cashier.request.payment.process', $req->id), $postData);

        $response->assertRedirect();

        $payment = Payment::where('patient_id', $patient->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(60000.00, (float) $payment->total_amount);
        $this->assertEquals(60000.00, (float) $payment->patient_share);
        $this->assertEquals(20000.00, (float) $payment->discount_amount);
        $this->assertEquals(33.33, round((float) $payment->discount_percentage, 2));
        $this->assertEquals(40000.00, (float) $payment->amount);
        $this->assertStringContainsString('موافقة الطبيب المعالج', $payment->discount_reason);
        $this->assertStringContainsString('خصم تفصيلي', $payment->discount_reason);
        $this->assertStringContainsString('CBC Test (خصم 50%)', $payment->discount_reason);
        $this->assertEquals($this->cashier->id, $payment->discount_by);

        // Check details stored in medical request
        $reqFresh = $req->fresh();
        $details = is_string($reqFresh->details) ? json_decode($reqFresh->details, true) : $reqFresh->details;
        $this->assertArrayHasKey('item_discounts', $details);
        $this->assertEquals(50, $details['item_discounts']['lab'][$labTestA->id]['percentage']);
        $this->assertEquals(20000, $details['item_discounts']['lab'][$labTestA->id]['discount_amount']);
    }

    public function test_discount_is_strictly_prevented_for_insured_patients()
    {
        $patientUser = User::factory()->create(['name' => 'مريض ضمان صحي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'hi',
            'copay_percentage' => 15,
            'gender' => 'female',
            'date_of_birth' => '1990-05-15',
        ]);

        $doctorUser = User::factory()->create();
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'Internal',
            'qualification' => 'FICMS',
            'license_number' => 'DOC-'.uniqid(),
        ]);

        $appointment = \App\Models\Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->department->id,
            'consultation_fee' => 15000,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'insurance_type' => 'hi',
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_id' => $appointment->id,
            'department_id' => $this->department->id,
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص روتيني',
            'visit_date' => now(),
            'visit_time' => '10:00:00',
            'status' => 'pending',
            'insurance_type' => 'hi',
        ]);

        $labTest = LabTest::create([
            'name' => 'Blood Sugar',
            'code' => 'BS01',
            'price' => 30000,
            'hi_price' => 20000,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $req = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبرية',
            'status' => 'pending',
            'payment_status' => 'pending',
            'insurance_type' => 'hi',
            'details' => [
                'lab_test_ids' => [$labTest->id],
                'tests' => [$labTest->name],
            ],
            'requested_by' => $doctorUser->id,
        ]);

        // Attempting to send 50% discount for insured patient
        $postData = [
            'payment_method' => 'cash',
            'insurance_type' => 'hi',
            'copay_percentage' => 15,
            'amount' => 3000,
            'discount_percentage' => 50,
            'discount_reason' => 'محاولة خصم غير مصرح بها للضمان',
            'items' => [
                $req->id => [
                    'lab' => [
                        $labTest->id => 'pay',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashier)
            ->post(route('cashier.request.payment.process', $req->id), $postData);

        $response->assertRedirect();

        $payment = Payment::where('patient_id', $patient->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(0.00, (float) $payment->discount_percentage);
        $this->assertEquals(0.00, (float) $payment->discount_amount);
        $this->assertNull($payment->discount_reason);
        $this->assertNull($payment->discount_by);
        $this->assertEquals(3000.00, (float) $payment->amount);
        $this->assertEquals('hi', $payment->insurance_type);
    }

    public function test_cashier_can_apply_discount_on_emergency_payment()
    {
        $patientUser = User::factory()->create(['name' => 'مريض طوارئ نقدي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'none',
            'gender' => 'male',
            'date_of_birth' => '1985-03-20',
        ]);

        $emergency = Emergency::create([
            'patient_id' => $patient->id,
            'emergency_type' => 'medical',
            'symptoms' => 'ألم حاد في الصدر',
            'status' => 'active',
            'priority' => 'urgent',
            'insurance_type' => 'none',
            'admission_time' => now(),
            'arrival_time' => now(),
        ]);

        $payment = Payment::create([
            'emergency_id' => $emergency->id,
            'patient_id' => $patient->id,
            'total_amount' => 80000,
            'patient_share' => 80000,
            'insurance_share' => 0,
            'amount' => 80000,
            'payment_type' => 'emergency',
            'payment_method' => 'cash',
            'insurance_type' => 'none',
            'receipt_number' => 'EMG-REC-001',
        ]);

        $postData = [
            'payment_method' => 'cash',
            'insurance_type' => 'none',
            'amount' => 68000, // 80,000 - 15% (12,000) = 68,000
            'discount_percentage' => 15,
            'discount_reason' => 'حالة طوارئ إنسانية خاصة',
            'notes' => 'تم تطبيق خصم 15% بموافقة المشرف',
        ];

        $response = $this->actingAs($this->cashier)
            ->post(route('cashier.emergency.payment.process', $payment->id), $postData);

        $response->assertRedirect();

        $freshPayment = $payment->fresh();
        $this->assertEquals(80000.00, (float) $freshPayment->total_amount);
        $this->assertEquals(80000.00, (float) $freshPayment->patient_share);
        $this->assertEquals(15.00, (float) $freshPayment->discount_percentage);
        $this->assertEquals(12000.00, (float) $freshPayment->discount_amount);
        $this->assertEquals(68000.00, (float) $freshPayment->amount);
        $this->assertEquals('حالة طوارئ إنسانية خاصة', $freshPayment->discount_reason);
        $this->assertEquals($this->cashier->id, $freshPayment->discount_by);
        $this->assertNotNull($freshPayment->paid_at);
    }

    public function test_cashier_can_apply_per_item_discount_on_emergency_payment()
    {
        $patientUser = User::factory()->create(['name' => 'مريض طوارئ نقدي 2']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'none',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);

        $emergency = Emergency::create([
            'patient_id' => $patient->id,
            'emergency_type' => 'medical',
            'symptoms' => 'ضيق تنفس',
            'status' => 'active',
            'priority' => 'urgent',
            'insurance_type' => 'none',
            'admission_time' => now(),
            'arrival_time' => now(),
        ]);

        $serviceA = EmergencyService::create([
            'name' => 'جلسة أوكسجين وتبيخير',
            'code' => 'EMG-O2',
            'price' => 30000,
            'is_active' => true,
        ]);

        $serviceB = EmergencyService::create([
            'name' => 'تخطيط قلب طوارئ',
            'code' => 'EMG-ECG',
            'price' => 20000,
            'is_active' => true,
        ]);

        $emergency->services()->attach([
            $serviceA->id => ['quantity' => 1],
            $serviceB->id => ['quantity' => 1],
        ]);

        $payment = Payment::create([
            'emergency_id' => $emergency->id,
            'patient_id' => $patient->id,
            'total_amount' => 50000,
            'patient_share' => 50000,
            'insurance_share' => 0,
            'amount' => 50000,
            'payment_type' => 'emergency',
            'payment_method' => 'cash',
            'insurance_type' => 'none',
            'receipt_number' => 'EMG-REC-002',
        ]);

        $postData = [
            'payment_method' => 'cash',
            'insurance_type' => 'none',
            'discount_scope' => 'per_item',
            'discount_reason' => 'إعفاء خاص من الإدارة',
            'item_discounts' => [
                'service_'.$serviceA->id => 50, // 50% of 30,000 = 15,000 IQD
                'service_'.$serviceB->id => 0,  // 0%
            ],
            'amount' => 35000, // 50,000 - 15,000 = 35,000
            'notes' => 'خصم جزئي على خدمة الأوكسجين فقط',
        ];

        $response = $this->actingAs($this->cashier)
            ->post(route('cashier.emergency.payment.process', $payment->id), $postData);

        $response->assertRedirect();

        $freshPayment = $payment->fresh();
        $this->assertEquals(50000.00, (float) $freshPayment->total_amount);
        $this->assertEquals(50000.00, (float) $freshPayment->patient_share);
        $this->assertEquals(15000.00, (float) $freshPayment->discount_amount);
        $this->assertEquals(30.00, (float) $freshPayment->discount_percentage);
        $this->assertEquals(35000.00, (float) $freshPayment->amount);
        $this->assertStringContainsString('إعفاء خاص من الإدارة', $freshPayment->discount_reason);
        $this->assertStringContainsString('جلسة أوكسجين وتبيخير (خصم 50%)', $freshPayment->discount_reason);
        $this->assertEquals($this->cashier->id, $freshPayment->discount_by);
        $this->assertNotNull($freshPayment->paid_at);
    }

    public function test_cashier_can_apply_discount_on_surgery_payment()
    {
        $patientUser = User::factory()->create(['name' => 'مريض عمليات نقدي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'none',
            'gender' => 'female',
            'date_of_birth' => '1992-11-10',
        ]);

        $doctorUser = User::factory()->create();
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'Surgery',
            'qualification' => 'FICMS',
            'license_number' => 'DOC-'.uniqid(),
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'Appendectomy',
            'surgery_fee' => 200000,
            'room_fee' => 0,
            'payment_status' => 'pending',
            'scheduled_date' => now()->addDay(),
            'scheduled_time' => '10:00:00',
        ]);

        $postData = [
            'payment_method' => 'cash',
            'insurance_type' => 'none',
            'amount' => 150000,
            'pay_surgery' => 1,
            'discount_percentage' => 25,
            'discount_reason' => 'موافقة مجلس الإدارة',
            'notes' => 'خصم 25% على أجور العملية',
        ];

        $response = $this->actingAs($this->cashier)
            ->post(route('cashier.surgeries.payment.process', $surgery->id), $postData);

        $response->assertRedirect();

        $payment = Payment::where('surgery_id', $surgery->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(200000.00, (float) $payment->total_amount);
        $this->assertEquals(200000.00, (float) $payment->patient_share);
        $this->assertEquals(25.00, (float) $payment->discount_percentage);
        $this->assertEquals(50000.00, (float) $payment->discount_amount);
        $this->assertEquals(150000.00, (float) $payment->amount);
        $this->assertEquals('موافقة مجلس الإدارة', $payment->discount_reason);
        $this->assertEquals($this->cashier->id, $payment->discount_by);
        $this->assertEquals('none', $payment->insurance_type);
    }
}
