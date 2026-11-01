<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\EmergencyService;
use App\Models\HealthInsuranceCategory;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MultiServiceInsuranceCopayTest extends TestCase
{
    use RefreshDatabase;

    protected $cashierUser;
    protected $doctorUser;
    protected $patientUser;
    protected $patient;
    protected $doctor;
    protected $visit;
    protected $dept;
    protected $categoryH;
    protected $ecgService;
    protected $labTest1;
    protected $labTest2;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'process medical requests payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process consultation payments', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الفيحاء',
            'owner_name' => 'د. حسام',
            'license_number' => 'LIC-100',
            'phone' => '07700000000',
            'email' => 'info@hospital.com',
            'address' => 'بغداد',
        ]);

        $this->dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'الباطنية',
            'code' => 'INT',
            'type' => 'internal',
            'room_number' => '102',
            'consultation_fee' => 20000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $this->cashierUser = User::factory()->create();
        $this->cashierUser->assignRole('cashier');
        $this->cashierUser->givePermissionTo('process medical requests payments');

        $this->doctorUser = User::factory()->create();
        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $this->dept->id,
            'specialization' => 'طب باطني',
            'qualification' => 'بورد',
            'license_number' => 'DOC-INT-'.uniqid(),
            'is_active' => true,
        ]);

        // الفئة H: الاستشارية 10%، المختبر 25%، الأشعة 25%، الخدمات الساندة 25%
        $this->categoryH = HealthInsuranceCategory::create([
            'code' => 'H',
            'name' => 'الفئة H',
            'description' => 'موظفو الدولة والمتقاعدين',
            'consultation_copay' => 10.00,
            'lab_copay' => 25.00,
            'radiology_copay' => 25.00,
            'support_services_copay' => 25.00,
            'surgery_copay' => 25.00,
            'is_active' => true,
        ]);

        $this->patientUser = User::factory()->create();
        $this->patient = Patient::create([
            'user_id' => $this->patientUser->id,
            'insurance_type' => 'hi',
            'health_insurance_category_id' => $this->categoryH->id,
            'copay_percentage' => 15.00,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->dept->id,
            'consultation_fee' => 20000,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'insurance_type' => 'hi',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->dept->id,
            'appointment_id' => $appointment->id,
            'visit_type' => 'consultation',
            'chief_complaint' => 'ألم في الصدر وخفقان',
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'status' => 'in_progress',
        ]);

        $this->ecgService = EmergencyService::create([
            'id' => 93,
            'name' => 'تخطيط قلب',
            'category' => 'طوارئ',
            'price' => 15000.00,
            'hi_price' => 10000.00,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $this->labTest1 = LabTest::create([
            'name' => 'CBC (5-Part Diff)',
            'code' => 'CBC-01',
            'price' => 15000.00,
            'hi_price' => 10000.00,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $this->labTest2 = LabTest::create([
            'name' => 'Ferritin',
            'code' => 'FER-01',
            'price' => 25000.00,
            'hi_price' => 20000.00,
            'is_hi_active' => true,
            'is_active' => true,
        ]);
    }

    /**
     * التحقق من احتساب تحاليل المختبر بـ 25% وتخطيط القلب بـ 10% في شاشة الوصل حتى لو فُتحت عبر طلب التمريض
     */
    public function test_view_form_applies_25_pct_to_lab_and_10_pct_to_ecg_even_when_opened_via_nursing_request()
    {
        $labReq = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'insurance_type' => 'hi',
            'description' => 'تحاليل مختبرية',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => json_encode([
                'lab_test_ids' => [$this->labTest1->id, $this->labTest2->id],
            ]),
        ]);

        $nursingReq = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'nursing',
            'insurance_type' => 'hi',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'طلب ecg',
            'details' => json_encode([
                'description' => 'طلب ecg',
            ]),
        ]);

        // فتح الشاشة عبر طلب التمريض 
        $response = $this->actingAs($this->cashierUser)
            ->get(route('cashier.request.payment.form', $nursingReq->id));

        $response->assertStatus(200);

        // المعتمد:
        // Lab 1 (10,000) * 0.25 = 2,500
        // Lab 2 (20,000) * 0.25 = 5,000
        // ECG (10,000) * 0.10 = 1,000
        // مجموع حصة المريض = 2,500 + 5,000 + 1,000 = 8,500 د.ع
        $response->assertSee('8,500');
        $response->assertSee('25%'); // شارة التحمل للتحاليل
        $response->assertSee('10%'); // شارة التحمل لتخطيط القلب
    }

    /**
     * التحقق من تسديد الوصل وحفظ حصة المريض وحصة الضمان بدقة
     */
    public function test_cashier_processes_combined_payment_with_per_service_copays_accurately()
    {
        $labReq = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'insurance_type' => 'hi',
            'description' => 'تحاليل مختبرية',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => json_encode([
                'lab_test_ids' => [$this->labTest1->id, $this->labTest2->id],
            ]),
        ]);

        $nursingReq = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'nursing',
            'insurance_type' => 'hi',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'طلب ecg',
            'details' => json_encode([
                'description' => 'طلب ecg',
            ]),
        ]);

        $postData = [
            'included_request_ids' => [$labReq->id, $nursingReq->id],
            'insurance_type' => 'hi',
            'payment_method' => 'cash',
            'amount' => 8500.00,
        ];

        $response = $this->actingAs($this->cashierUser)
            ->post(route('cashier.request.payment.process', $nursingReq->id), $postData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $payment = Payment::latest()->first();
        $this->assertNotNull($payment);
        $this->assertEquals(40000.00, (float)$payment->total_amount); // 10,000 + 20,000 + 10,000
        $this->assertEquals(8500.00, (float)$payment->patient_share);  // 2,500 + 5,000 + 1,000
        $this->assertEquals(31500.00, (float)$payment->insurance_share); // 40,000 - 8,500
    }

    /**
     * التحقق من أن تخطيط القلب المباشر (بدون إحالة استشارية) يحسب كاش 100% بدون تغطية ضمان
     */
    public function test_direct_ecg_without_consultation_doctor_is_100_pct_cash()
    {
        // زيارة مباشرة بدون طبيب استشاري
        $directVisit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => null,
            'department_id' => $this->dept->id,
            'appointment_id' => null,
            'visit_type' => 'external',
            'chief_complaint' => 'تخطيط قلب مباشر بدون استشاري',
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'status' => 'in_progress',
        ]);

        $directNursingReq = MedicalRequest::create([
            'visit_id' => $directVisit->id,
            'type' => 'nursing',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'تخطيط قلب مباشر',
            'details' => json_encode([
                'description' => 'تخطيط قلب',
            ]),
        ]);

        $response = $this->actingAs($this->cashierUser)
            ->get(route('cashier.request.payment.form', $directNursingReq->id));

        $response->assertStatus(200);
        // سعر الكاش 15,000 د.ع بدون تغطية
        $response->assertSee('15,000');
    }
}
