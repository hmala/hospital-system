<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Department;
use App\Models\Appointment;
use App\Models\Visit;
use App\Models\Request as MedicalRequest;
use App\Models\LabTest;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class LabAndCashierDeduplicationAndActiveOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $doctor;
    protected $labStaffUser;
    protected $cashierUser;
    protected $patient;
    protected $visit;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'doctor']);
        Role::firstOrCreate(['name' => 'lab_staff']);
        Role::firstOrCreate(['name' => 'cashier']);
        Role::firstOrCreate(['name' => 'patient']);

        Permission::firstOrCreate(['name' => 'manage own visits']);
        Permission::firstOrCreate(['name' => 'view own visits']);
        Permission::firstOrCreate(['name' => 'view lab tests']);
        Permission::firstOrCreate(['name' => 'view cashier']);
        Permission::firstOrCreate(['name' => 'process medical requests payments']);

        $hospital = \App\Models\Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-TEST-' . rand(100, 999),
        ]);

        $dept = Department::firstOrCreate(
            ['name' => 'عيادة الباطنية'],
            [
                'hospital_id' => $hospital->id,
                'type' => 'internal',
                'room_number' => '1',
                'consultation_fee' => 15000,
                'working_hours_start' => '08:00:00',
                'working_hours_end' => '16:00:00',
            ]
        );

        $this->doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo(['manage own visits', 'view own visits']);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $dept->id,
            'type' => 'consultant',
            'specialization' => 'طب باطني',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-TEST-' . rand(100, 999),
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $this->labStaffUser = User::factory()->create(['role' => 'lab_staff']);
        $this->labStaffUser->assignRole('lab_staff');
        $this->labStaffUser->givePermissionTo('view lab tests');

        $this->cashierUser = User::factory()->create(['role' => 'cashier']);
        $this->cashierUser->assignRole('cashier');
        $this->cashierUser->givePermissionTo(['process medical requests payments', 'view cashier']);

        $patientUser = User::factory()->create(['role' => 'patient', 'name' => 'سعيد محمد']);
        $patientUser->assignRole('patient');
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'blood_group' => 'O+',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $dept->id,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'type' => 'consultation',
            'consultation_fee' => 15000,
            'status' => 'in_progress',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $dept->id,
            'appointment_id' => $appointment->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص دوري',
            'status' => 'in_progress',
        ]);
    }

    public function test_doctor_ordering_test_with_inactive_duplicate_only_links_active_test(): void
    {
        // إنشاء سجلين لنفس الفحص: واحد مفعل وواحد معطل
        $activeTest = LabTest::create([
            'name' => 'Prothrombin time',
            'code' => 'INR',
            'price' => 15000,
            'is_active' => true,
        ]);

        $inactiveTest = LabTest::create([
            'name' => 'Prothrombin time',
            'code' => 'PT',
            'price' => 15000,
            'is_active' => false,
        ]);

        // الطبيب يطلب الفحص
        $response = $this->actingAs($this->doctorUser)->post(
            route('doctor.requests.store'),
            [
                'visit_id' => $this->visit->id,
                'type' => 'lab',
                'tests' => ['Prothrombin time'],
                'priority' => 'normal',
            ]
        );

        $response->assertRedirect();

        $req = MedicalRequest::where('visit_id', $this->visit->id)->where('type', 'lab')->first();
        $this->assertNotNull($req);

        $details = is_string($req->details) ? json_decode($req->details, true) : $req->details;

        // التحقق من أن مصفوفة lab_test_ids تحتوي حصراً على معرف الفحص المفعل فقط ولا تحتوي على المعطل
        $this->assertCount(1, $details['lab_test_ids']);
        $this->assertEquals([$activeTest->id], $details['lab_test_ids']);
        $this->assertNotContains($inactiveTest->id, $details['lab_test_ids']);
    }

    public function test_lab_workstation_page_does_not_show_inactive_test_and_deduplicates(): void
    {
        $activeTest = LabTest::create([
            'name' => 'Gram stain',
            'code' => 'STAIN_GRAM_ACTIVE',
            'price' => 10000,
            'is_active' => true,
        ]);

        $inactiveTest = LabTest::create([
            'name' => 'Gram stain',
            'code' => 'STAIN_GRAM_INACTIVE',
            'price' => 20000,
            'is_active' => false,
        ]);

        // طلب قديم يحتوي على كلا المعرفين في details
        $req = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'status' => 'pending',
            'payment_status' => 'paid',
            'description' => 'طلب مختبر',
            'details' => [
                'tests' => ['Gram stain'],
                'lab_test_ids' => [$activeTest->id, $inactiveTest->id],
            ],
        ]);

        // عرض صفحة المختبر
        $response = $this->actingAs($this->labStaffUser)->get(route('lab.show', $req->id));
        $response->assertOk();

        // يجب أن يظهر الفحص لمرة واحدة فقط برقم 1 ولا يظهر تكرار "2" لنفس الفحص
        $content = $response->getContent();
        $this->assertStringContainsString('Gram stain', $content);
        // نتحقق من عدم ظهور السطر الثاني كفحص مكرر
        $this->assertStringNotContainsString('STAIN_GRAM_INACTIVE', $content);
    }

    public function test_cashier_and_receipt_print_filters_inactive_and_deduplicates_by_name(): void
    {
        $activeTest = LabTest::create([
            'name' => 'Total Iron Binding Capacity',
            'code' => 'TIBC_ACTIVE',
            'price' => 6000,
            'is_active' => true,
        ]);

        $inactiveTest = LabTest::create([
            'name' => 'Total Iron Binding Capacity',
            'code' => 'TIBC_INACTIVE',
            'price' => 25000,
            'is_active' => false,
        ]);

        // طلب يحتوي كلا المعرفين
        $req = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'طلب مختبر',
            'details' => [
                'tests' => ['Total Iron Binding Capacity'],
                'lab_test_ids' => [$activeTest->id, $inactiveTest->id],
            ],
        ]);

        // تسديد الطلب عبر الكاشير
        $response = $this->actingAs($this->cashierUser)->post(
            route('cashier.request.payment.process', $req->id),
            [
                'payment_method' => 'cash',
                'amount' => 6000,
            ]
        );

        $response->assertRedirect();

        // التحقق من أن المبلغ المحسوب هو 6000 فقط (سعر الفحص المفعل) وليس 31000
        $payment = Payment::where('request_id', $req->id)->orWhere('visit_id', $this->visit->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(6000, (float)$payment->amount);

        // التحقق من محتوى طباعة الوصل الحراري 80mm
        $printResponse = $this->actingAs($this->cashierUser)->get(route('cashier.receipt.print', $payment->id));
        $printResponse->assertOk();

        // اسم الفحص يجب أن يظهر لمرة واحدة فقط
        $printContent = $printResponse->getContent();
        $this->assertEquals(1, substr_count($printContent, 'تحليل: Total Iron Binding Capacity'));
    }
}
