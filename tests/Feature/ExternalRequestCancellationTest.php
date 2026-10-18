<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Request as MedicalRequest;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ExternalRequestCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected $labUser;
    protected $radUser;
    protected $doctorUser;
    protected $patient;
    protected $inquiryDept;
    protected $clinicDept;

    protected function setUp(): void
    {
        parent::setUp();

        $hospital = \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        Permission::firstOrCreate(['name' => 'view lab tests', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view radiology', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process radiology requests', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'lab_staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'radiology_staff', 'guard_name' => 'web']);

        $this->labUser = User::factory()->create();
        $this->labUser->assignRole('lab_staff');
        $this->labUser->givePermissionTo('view lab tests');

        $this->radUser = User::factory()->create();
        $this->radUser->assignRole('radiology_staff');
        $this->radUser->givePermissionTo(['view radiology', 'process radiology requests']);

        $this->doctorUser = User::factory()->create();

        $this->inquiryDept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'الاستعلامات',
            'type' => 'other',
            'room_number' => 'REC-01',
            'consultation_fee' => 0,
            'working_hours_start' => '08:00',
            'working_hours_end' => '22:00',
            'is_active' => true,
        ]);

        $this->clinicDept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'العيادة الباطنية',
            'type' => 'internal',
            'room_number' => 'CLN-01',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'أحمد علي']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'birth_date' => '1990-01-01',
        ]);
    }

    public function test_lab_staff_can_cancel_unpaid_direct_lab_request()
    {
        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->inquiryDept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'lab',
            'chief_complaint' => 'تحاليل مخبرية',
            'status' => 'pending_service_selection',
        ]);

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => ['created_at_inquiry' => true, 'tests' => ['CBC', 'Lipid']],
        ]);

        $response = $this->actingAs($this->labUser)
            ->postJson(route('lab.cancel', $medicalRequest), [
                'cancel_reason' => 'بطلب من المريض لمغادرته',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $medicalRequest->refresh();
        $this->assertEquals('cancelled', $medicalRequest->status);
        $this->assertEquals('cancelled', $medicalRequest->payment_status);
        $this->assertEquals('بطلب من المريض لمغادرته', $medicalRequest->details['cancel_reason']);

        $visit->refresh();
        $this->assertEquals('cancelled', $visit->status);
    }

    public function test_lab_staff_cannot_cancel_clinic_doctor_request()
    {
        $doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $this->clinicDept->id,
            'specialization' => 'باطنية',
            'qualification' => 'استشاري',
            'license_number' => 'DOC-101',
            'is_active' => true,
        ]);

        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->clinicDept->id,
            'doctor_id' => $doctor->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'ألم في البطن',
            'status' => 'in_progress',
        ]);

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب تحليل سكر من الطبيب',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => ['tests' => ['Glucose']],
        ]);

        $response = $this->actingAs($this->labUser)
            ->postJson(route('lab.cancel', $medicalRequest));

        $response->assertStatus(422);
        $this->assertStringContainsString('طبيب استشاري', $response->json('message'));

        $medicalRequest->refresh();
        $this->assertEquals('pending', $medicalRequest->status);
    }

    public function test_lab_staff_can_cancel_paid_direct_lab_request_with_refund_flag()
    {
        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->inquiryDept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'lab',
            'chief_complaint' => 'تحاليل مخبرية',
            'status' => 'pending_service_selection',
        ]);

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل',
            'status' => 'pending',
            'payment_status' => 'paid',
            'details' => ['created_at_inquiry' => true, 'tests' => ['CBC']],
        ]);

        Payment::create([
            'request_id' => $medicalRequest->id,
            'patient_id' => $this->patient->id,
            'amount' => 15000,
            'total_amount' => 15000,
            'receipt_number' => 'REC-LAB-999',
            'payment_type' => 'lab',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->labUser)
            ->postJson(route('lab.cancel', $medicalRequest), [
                'cancel_reason' => 'تعذر سحب الدم من المريض',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertStringContainsString('الكاشير لاسترداد المبلغ', $response->json('message'));

        $medicalRequest->refresh();
        $this->assertEquals('cancelled', $medicalRequest->status);
        $this->assertEquals('cancelled', $medicalRequest->payment_status);
        $this->assertTrue($medicalRequest->details['cancelled_after_payment']);
        $this->assertEquals('pending_refund', $medicalRequest->details['refund_status']);
        $this->assertEquals(15000, $medicalRequest->details['paid_amount']);
        $this->assertEquals('تعذر سحب الدم من المريض', $medicalRequest->details['cancel_reason']);
    }

    public function test_radiology_staff_can_cancel_unpaid_direct_radiology_request()
    {
        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->inquiryDept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'radiology',
            'chief_complaint' => 'فحص سونار',
            'status' => 'pending_service_selection',
        ]);

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'radiology',
            'subtype' => 'ultrasound',
            'description' => 'طلب سونار بطن',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => ['created_at_inquiry' => true],
        ]);

        $response = $this->actingAs($this->radUser)
            ->postJson(route('radiology-staff.cancel', $medicalRequest), [
                'cancel_reason' => 'المريض قرر إجراء الفحص في يوم آخر',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $medicalRequest->refresh();
        $this->assertEquals('cancelled', $medicalRequest->status);
        $this->assertEquals('cancelled', $medicalRequest->payment_status);
        $this->assertEquals('المريض قرر إجراء الفحص في يوم آخر', $medicalRequest->details['cancel_reason']);

        $visit->refresh();
        $this->assertEquals('cancelled', $visit->status);
    }

    public function test_radiology_staff_cannot_cancel_clinic_doctor_request()
    {
        $doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $this->clinicDept->id,
            'specialization' => 'باطنية',
            'qualification' => 'استشاري',
            'license_number' => 'DOC-102',
            'is_active' => true,
        ]);

        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->clinicDept->id,
            'doctor_id' => $doctor->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'سعال حاد',
            'status' => 'in_progress',
        ]);

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'radiology',
            'subtype' => 'general',
            'description' => 'أشعة صدر من الطبيب',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => [],
        ]);

        $response = $this->actingAs($this->radUser)
            ->postJson(route('radiology-staff.cancel', $medicalRequest));

        $response->assertStatus(422);
        $this->assertStringContainsString('طبيب استشاري', $response->json('message'));

        $medicalRequest->refresh();
        $this->assertEquals('pending', $medicalRequest->status);
    }

    public function test_radiology_staff_can_cancel_paid_direct_radiology_request_with_refund_flag()
    {
        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->inquiryDept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'radiology',
            'chief_complaint' => 'رنين',
            'status' => 'pending_service_selection',
        ]);

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'radiology',
            'subtype' => 'mri',
            'description' => 'رنين مغناطيسي',
            'status' => 'pending',
            'payment_status' => 'paid',
            'details' => ['created_at_inquiry' => true],
        ]);

        Payment::create([
            'request_id' => $medicalRequest->id,
            'patient_id' => $this->patient->id,
            'amount' => 60000,
            'total_amount' => 60000,
            'receipt_number' => 'REC-RAD-888',
            'payment_type' => 'radiology',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->radUser)
            ->postJson(route('radiology-staff.cancel', $medicalRequest), [
                'cancel_reason' => 'المريض يعاني من رهاب الأماكن المغلقة (Claustrophobia)',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertStringContainsString('الكاشير لاسترداد المبلغ', $response->json('message'));

        $medicalRequest->refresh();
        $this->assertEquals('cancelled', $medicalRequest->status);
        $this->assertEquals('cancelled', $medicalRequest->payment_status);
        $this->assertTrue($medicalRequest->details['cancelled_after_payment']);
        $this->assertEquals('pending_refund', $medicalRequest->details['refund_status']);
        $this->assertEquals(60000, $medicalRequest->details['paid_amount']);
        $this->assertEquals('المريض يعاني من رهاب الأماكن المغلقة (Claustrophobia)', $medicalRequest->details['cancel_reason']);
    }

    public function test_cashier_can_refund_cancelled_paid_medical_request()
    {
        Permission::firstOrCreate(['name' => 'process medical requests payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process refunds', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $cashierUser = User::factory()->create();
        $cashierUser->assignRole('cashier');
        $cashierUser->givePermissionTo(['process medical requests payments', 'process refunds']);

        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->inquiryDept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'lab',
            'chief_complaint' => 'تحاليل',
            'status' => 'cancelled',
        ]);

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل',
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'details' => [
                'created_at_inquiry' => true,
                'cancelled_after_payment' => true,
                'refund_status' => 'pending_refund',
                'paid_receipt_number' => 'REC-ORIG-001',
                'paid_amount' => 50000,
            ],
        ]);

        Payment::create([
            'request_id' => $medicalRequest->id,
            'patient_id' => $this->patient->id,
            'cashier_id' => $cashierUser->id,
            'amount' => 50000,
            'total_amount' => 50000,
            'receipt_number' => 'REC-ORIG-001',
            'payment_type' => 'lab',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($cashierUser)
            ->post(route('cashier.request.refund.process', $medicalRequest), [
                'payment_method' => 'cash',
                'notes' => 'تسليم المبلغ للمريض نقداً',
            ]);

        $response->assertRedirect(route('cashier.index'));
        $response->assertSessionHas('success');

        $medicalRequest->refresh();
        $this->assertEquals('refunded', $medicalRequest->details['refund_status']);
        $this->assertEquals(50000, $medicalRequest->details['refunded_amount']);

        $refundPayment = Payment::where('request_id', $medicalRequest->id)->where('amount', '<', 0)->first();
        $this->assertNotNull($refundPayment);
        $this->assertEquals(-50000, (float)$refundPayment->amount);
        $this->assertEquals('cash', $refundPayment->payment_method);
    }
}
