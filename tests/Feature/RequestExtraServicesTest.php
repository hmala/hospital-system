<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitExtraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RequestExtraServicesTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;

    protected Patient $patient;

    protected Visit $visit;

    protected MedicalRequest $medicalRequest;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'process medical requests payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process consultation payments', 'guard_name' => 'web']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-001',
            'phone' => '07700000000',
            'email' => 'info@hospital.com',
            'address' => 'بغداد',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الباطنية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 20000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'code' => 'INT01',
        ]);

        $this->cashier = User::factory()->create();
        $this->cashier->givePermissionTo('process medical requests payments');

        $patientUser = User::factory()->create(['name' => 'مريض فحوصات تجريبي']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'none',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. استشاري باطنية']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد عربي',
            'license_number' => 'DOC-INT-'.uniqid(),
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص روتيني واستشارة',
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'status' => 'pending_payment',
            'insurance_type' => 'none',
        ]);

        $labTest = \App\Models\LabTest::create([
            'name' => 'فحص وظائف الكلى',
            'code' => 'RFT01',
            'price' => 30000,
            'is_active' => true,
        ]);

        $this->medicalRequest = MedicalRequest::create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبرية',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => [
                'lab_test_ids' => [$labTest->id],
                'tests' => [$labTest->name],
            ],
            'total_amount' => 30000,
        ]);
    }

    public function test_cashier_can_add_extra_service_with_custom_price_to_medical_request(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('cashier.request.extra-services.add', $this->medicalRequest), [
            'service_name' => 'تخطيط قلب',
            'service_price' => 20000, // سعر مخصص
            'service_quantity' => 1,
            'service_notes' => 'تخطيط قلب عاجل بالاستشارية',
        ]);

        $response->assertRedirect(route('cashier.request.payment.form', $this->medicalRequest));

        $this->assertDatabaseHas('visit_extra_services', [
            'request_id' => $this->medicalRequest->id,
            'visit_id' => $this->visit->id,
            'name' => 'تخطيط قلب',
            'price' => 20000.00,
            'quantity' => 1,
            'total_amount' => 20000.00,
            'payment_status' => 'pending',
            'notes' => 'تخطيط قلب عاجل بالاستشارية',
            'created_by' => $this->cashier->id,
        ]);
    }

    public function test_cashier_can_delete_pending_extra_service_from_medical_request(): void
    {
        $extraService = VisitExtraService::create([
            'request_id' => $this->medicalRequest->id,
            'visit_id' => $this->visit->id,
            'name' => 'خدمة مرافق مريض مبيت',
            'price' => 30000.00,
            'quantity' => 1,
            'total_amount' => 30000.00,
            'payment_status' => 'pending',
            'created_by' => $this->cashier->id,
        ]);

        $response = $this->actingAs($this->cashier)->delete(route('cashier.request.extra-services.remove', [$this->medicalRequest, $extraService]));

        $response->assertRedirect(route('cashier.request.payment.form', $this->medicalRequest));

        $this->assertDatabaseMissing('visit_extra_services', [
            'id' => $extraService->id,
        ]);
    }

    public function test_cashier_can_pay_medical_request_with_extra_services(): void
    {
        $extraEcg = VisitExtraService::create([
            'request_id' => $this->medicalRequest->id,
            'visit_id' => $this->visit->id,
            'name' => 'تخطيط قلب',
            'price' => 15000.00,
            'quantity' => 1,
            'total_amount' => 15000.00,
            'payment_status' => 'pending',
            'created_by' => $this->cashier->id,
        ]);

        $extraAdmin = VisitExtraService::create([
            'request_id' => $this->medicalRequest->id,
            'visit_id' => $this->visit->id,
            'name' => 'اجور فتح طبلة',
            'price' => 5000.00,
            'quantity' => 1,
            'total_amount' => 5000.00,
            'payment_status' => 'pending',
            'created_by' => $this->cashier->id,
        ]);

        // إجمالي المطلوب: 30,000 (فحوصات) + 15,000 (تخطيط) + 5,000 (طبلة) = 50,000 د.ع
        $response = $this->actingAs($this->cashier)->post(route('cashier.request.payment.process', $this->medicalRequest), [
            'payment_method' => 'cash',
            'amount' => 50000,
            'insurance_type' => 'none',
            'included_request_ids' => [$this->medicalRequest->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'patient_id' => $this->patient->id,
            'request_id' => $this->medicalRequest->id,
            'amount' => 50000.00,
            'total_amount' => 50000.00,
        ]);

        $extraEcg->refresh();
        $extraAdmin->refresh();
        $this->assertEquals('paid', $extraEcg->payment_status);
        $this->assertEquals('paid', $extraAdmin->payment_status);
        $this->assertNotNull($extraEcg->payment_id);
        $this->assertNotNull($extraAdmin->payment_id);

        $this->medicalRequest->refresh();
        $this->assertEquals('paid', $this->medicalRequest->payment_status);
    }
}
