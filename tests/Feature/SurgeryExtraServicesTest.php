<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\SurgeryExtraService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SurgeryExtraServicesTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;

    protected Department $department;

    protected Patient $patient;

    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

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
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '201',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'code' => 'SURG01',
        ]);

        $this->cashier = User::factory()->create();
        $this->cashier->givePermissionTo('process surgery payments');

        $patientUser = User::factory()->create(['name' => 'مريض عمليات تجريبي']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'none',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. جراح استشاري']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'qualification' => 'بورد جراحة عامة',
            'license_number' => 'DOC-SURG-'.uniqid(),
        ]);
    }

    public function test_cashier_can_add_extra_service_with_custom_price_to_surgery(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'استئصال الزائدة الدودية',
            'surgery_fee' => 500000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.extra-services.add', $surgery), [
            'service_name' => 'خدمة مرافق مريض مبيت',
            'service_price' => 35000, // السعر عدله الكاشير
            'service_quantity' => 2,
            'service_notes' => 'مرافق مبيت غرفة 102 ليلتين',
        ]);

        $response->assertRedirect(route('cashier.surgeries.payment.form', $surgery));

        $this->assertDatabaseHas('surgery_extra_services', [
            'surgery_id' => $surgery->id,
            'name' => 'خدمة مرافق مريض مبيت',
            'price' => 35000.00,
            'quantity' => 2,
            'total_amount' => 70000.00,
            'payment_status' => 'pending',
            'notes' => 'مرافق مبيت غرفة 102 ليلتين',
            'created_by' => $this->cashier->id,
        ]);
    }

    public function test_cashier_can_delete_pending_extra_service(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'استئصال مرارة بالناظور',
            'surgery_fee' => 600000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $extraService = SurgeryExtraService::create([
            'surgery_id' => $surgery->id,
            'name' => 'اجور تزيين غرفة',
            'price' => 50000.00,
            'quantity' => 1,
            'total_amount' => 50000.00,
            'payment_status' => 'pending',
            'created_by' => $this->cashier->id,
        ]);

        $response = $this->actingAs($this->cashier)->delete(route('cashier.surgeries.extra-services.remove', [$surgery, $extraService]));

        $response->assertRedirect(route('cashier.surgeries.payment.form', $surgery));

        $this->assertDatabaseMissing('surgery_extra_services', [
            'id' => $extraService->id,
        ]);
    }

    public function test_cashier_can_pay_surgery_with_extra_services_and_updates_status(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية فتق إربي',
            'surgery_fee' => 400000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $extraEcg = SurgeryExtraService::create([
            'surgery_id' => $surgery->id,
            'name' => 'تخطيط قلب',
            'price' => 15000.00,
            'quantity' => 1,
            'total_amount' => 15000.00,
            'payment_status' => 'pending',
            'created_by' => $this->cashier->id,
        ]);

        $extraEscort = SurgeryExtraService::create([
            'surgery_id' => $surgery->id,
            'name' => 'خدمة مرافق مريض مبيت',
            'price' => 25000.00,
            'quantity' => 1,
            'total_amount' => 25000.00,
            'payment_status' => 'pending',
            'created_by' => $this->cashier->id,
        ]);

        // إجمالي المطلوب: 400,000 (عملية) + 15,000 (تخطيط) + 25,000 (مرافق) = 440,000 د.ع
        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery), [
            'payment_method' => 'cash',
            'amount' => 440000,
            'pay_surgery' => 1,
            'surgery_payment_type' => 'full',
            'pay_extra_services' => [$extraEcg->id, $extraEscort->id],
            'insurance_type' => 'none',
        ]);

        $response->assertRedirect(route('cashier.surgeries.index'));

        $this->assertDatabaseHas('payments', [
            'patient_id' => $this->patient->id,
            'surgery_id' => $surgery->id,
            'amount' => 440000.00,
            'total_amount' => 440000.00,
            'payment_type' => 'surgery',
        ]);

        $extraEcg->refresh();
        $extraEscort->refresh();
        $this->assertEquals('paid', $extraEcg->payment_status);
        $this->assertEquals('paid', $extraEscort->payment_status);
        $this->assertNotNull($extraEcg->payment_id);
        $this->assertNotNull($extraEscort->payment_id);

        $surgery->refresh();
        $this->assertEquals('paid', $surgery->payment_status);
    }

    public function test_surgery_auto_attaches_default_extra_services(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية استئصال لوزتين',
            'surgery_fee' => 300000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $surgery->attachDefaultExtraServices();

        $this->assertCount(3, $surgery->extraServices);
        $this->assertDatabaseHas('surgery_extra_services', [
            'surgery_id' => $surgery->id,
            'name' => 'تخطيط قلب',
            'price' => 15000.00,
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('surgery_extra_services', [
            'surgery_id' => $surgery->id,
            'name' => 'اجور فتح طبلة',
            'price' => 10000.00,
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('surgery_extra_services', [
            'surgery_id' => $surgery->id,
            'name' => 'خدمات فندقية',
            'price' => 30000.00,
            'payment_status' => 'pending',
        ]);
    }

    public function test_cashier_can_update_extra_service_price(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية غضروف الركبة',
            'surgery_fee' => 700000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '11:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $extraService = SurgeryExtraService::create([
            'surgery_id' => $surgery->id,
            'name' => 'خدمات فندقية',
            'price' => 30000.00,
            'quantity' => 1,
            'total_amount' => 30000.00,
            'payment_status' => 'pending',
            'created_by' => $this->cashier->id,
        ]);

        $response = $this->actingAs($this->cashier)->patchJson(route('cashier.surgeries.extra-services.update', [$surgery, $extraService]), [
            'price' => 45000,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'price' => 45000,
            'total_amount' => 45000,
        ]);

        $extraService->refresh();
        $this->assertEquals(45000.00, (float) $extraService->price);
        $this->assertEquals(45000.00, (float) $extraService->total_amount);
    }

    public function test_cashier_can_pay_surgery_with_inline_edited_extra_service_price(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية ترقيع طبلة الأذن',
            'surgery_fee' => 500000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '12:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $extraService = SurgeryExtraService::create([
            'surgery_id' => $surgery->id,
            'name' => 'خدمات فندقية',
            'price' => 30000.00,
            'quantity' => 1,
            'total_amount' => 30000.00,
            'payment_status' => 'pending',
            'created_by' => $this->cashier->id,
        ]);

        // الكاشير عدل السعر إلى 25,000 أثناء الدفع
        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery), [
            'payment_method' => 'cash',
            'amount' => 525000, // 500,000 + 25,000
            'pay_surgery' => 1,
            'surgery_payment_type' => 'full',
            'pay_extra_services' => [$extraService->id],
            'extra_service_prices' => [
                $extraService->id => 25000,
            ],
            'insurance_type' => 'none',
        ]);

        $response->assertRedirect(route('cashier.surgeries.index'));

        $extraService->refresh();
        $this->assertEquals('paid', $extraService->payment_status);
        $this->assertEquals(25000.00, (float) $extraService->price);
        $this->assertEquals(25000.00, (float) $extraService->total_amount);
    }

    public function test_payment_form_renders_modern_6_column_table_with_segmented_control_pills(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'استئصال الزائدة الدودية',
            'surgery_fee' => 750000,
            'room_fee' => 50000,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $surgery->attachDefaultExtraServices($this->cashier->id);

        $response = $this->actingAs($this->cashier)->get(route('cashier.surgeries.payment.form', $surgery));

        $response->assertOk();
        $response->assertSee('الإجراء والشمول بالباقة');
        $response->assertSee('السعر المعتمد');
        $response->assertSee('تحمل المريض (نقداً)');
        $response->assertSee('حصة الضمان (ذمة)');
        $response->assertSee('segmented-control-pills');
        $response->assertSee('pill-included');
        $response->assertSee('pill-unbundled');
        $response->assertSee('extra-service-price-input');
        $response->assertSee('تخطيط قلب');
        $response->assertSee('اجور فتح طبلة');
        $response->assertSee('خدمات فندقية');
        $response->assertDontSee('id="inclusive"', false);
    }
}
