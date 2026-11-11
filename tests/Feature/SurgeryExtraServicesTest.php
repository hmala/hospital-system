<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\RadiologyType;
use App\Models\Surgery;
use App\Models\SurgeryExtraService;
use App\Models\SurgeryLabTest;
use App\Models\SurgeryRadiologyTest;
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

    public function test_cashier_adding_new_custom_service_persists_to_hospital_extra_services_catalog(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية جراحية تجريبية',
            'surgery_fee' => 450000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '11:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $customServiceName = 'خدمة فندقية فاخرة خاصة '.uniqid();

        // التأكد من عدم وجود الخدمة مسبقاً في الدليل المرجعي
        $this->assertDatabaseMissing('hospital_extra_services', [
            'name' => $customServiceName,
        ]);

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.extra-services.add', $surgery), [
            'service_name' => $customServiceName,
            'service_price' => 45000,
            'service_quantity' => 1,
            'service_category' => 'accommodation',
            'service_notes' => 'خدمة مضافة خصيصاً للمريض',
        ]);

        $response->assertRedirect(route('cashier.surgeries.payment.form', $surgery));
        $response->assertSessionHas('success');

        // التحقق من حفظ الخدمة في الدليل المرجعي العام للمستشفى hospital_extra_services
        $this->assertDatabaseHas('hospital_extra_services', [
            'name' => $customServiceName,
            'category' => 'accommodation',
            'default_price' => 45000.00,
            'is_active' => 1,
        ]);

        $catalogService = \App\Models\HospitalExtraService::where('name', $customServiceName)->first();
        $this->assertNotNull($catalogService);

        // التحقق من ربط المعرف في surgery_extra_services
        $this->assertDatabaseHas('surgery_extra_services', [
            'surgery_id' => $surgery->id,
            'service_id' => $catalogService->id,
            'name' => $customServiceName,
            'price' => 45000.00,
        ]);

        // التحقق من ظهور الخدمة تلقائياً في صفحة الدفع للعملية القادمة
        $getRes = $this->actingAs($this->cashier)->get(route('cashier.surgeries.payment.form', $surgery));
        $getRes->assertOk();
        $getRes->assertSee($customServiceName);
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
        $response->assertSee('استقطاع من حصة الجراح:');
        $response->assertSee('package-status-badge');
        $response->assertSee('doctorDeductionsRow');
        $response->assertSee('إجمالي استقطاعات الباقة المخصومة من حصة الجراح');
        $response->assertDontSee('id="inclusive"', false);
    }

    public function test_payment_form_auto_attaches_default_services_when_missing_and_allows_payment_if_pending_items_exist(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'بتر اصبع قدم سكري',
            'surgery_fee' => 1000000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'scheduled',
            'payment_status' => 'paid',
            'insurance_type' => 'hi',
        ]);

        $this->assertEquals(0, $surgery->extraServices()->count());

        $response = $this->actingAs($this->cashier)->get(route('cashier.surgeries.payment.form', $surgery));

        $response->assertOk();
        $this->assertEquals(3, $surgery->extraServices()->count());
        $response->assertSee('بتر اصبع قدم سكري');
        $response->assertSee('تخطيط قلب');
        $response->assertSee('اجور فتح طبلة');
        $response->assertSee('خدمات فندقية');
    }

    public function test_cashier_can_delete_pending_surgery_lab_test_and_radiology_test(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'تفتيت حصى كلى بالليزر',
            'surgery_fee' => 750000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $labTest = LabTest::create([
            'name' => 'تحليل وظائف كلى KFT',
            'code' => 'KFT-01',
            'price' => 25000,
            'is_active' => true,
        ]);

        $surgeryLab = SurgeryLabTest::create([
            'surgery_id' => $surgery->id,
            'lab_test_id' => $labTest->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $radType = RadiologyType::create([
            'name' => 'سونار البطن والحوض',
            'code' => 'US-ABD-01',
            'base_price' => 40000,
            'is_active' => true,
        ]);

        $surgeryRad = SurgeryRadiologyTest::create([
            'surgery_id' => $surgery->id,
            'radiology_type_id' => $radType->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // Cashier deletes lab test via AJAX
        $labResponse = $this->actingAs($this->cashier)->deleteJson(
            route('cashier.surgeries.lab-tests.remove', [$surgery, $surgeryLab])
        );
        $labResponse->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('surgery_lab_tests', ['id' => $surgeryLab->id]);

        // Cashier deletes radiology test via AJAX
        $radResponse = $this->actingAs($this->cashier)->deleteJson(
            route('cashier.surgeries.radiology-tests.remove', [$surgery, $surgeryRad])
        );
        $radResponse->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('surgery_radiology_tests', ['id' => $surgeryRad->id]);
    }

    public function test_cashier_can_include_room_in_surgery_package_and_pay_with_zero_room_fee(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'تبديل مفصل ركبة',
            'surgery_fee' => 1200000,
            'room_fee' => 150000,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery), [
            'pay_surgery' => 1,
            'pay_room' => 1,
            'package_included' => ['room' => '1'],
            'amount' => 1200000,
            'payment_mode' => 'full',
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('cashier.surgeries.index'));
        $this->assertDatabaseHas('surgeries', [
            'id' => $surgery->id,
            'room_fee_paid_amount' => 150000,
            'surgery_fee_paid' => 'paid',
            'payment_status' => 'paid',
        ]);

        // Verify receipt shows room included under surgery package (0 IQD)
        $this->assertDatabaseHas('payments', [
            'patient_id' => $this->patient->id,
            'surgery_id' => $surgery->id,
            'amount' => 1200000,
        ]);
    }

    public function test_cashier_can_make_global_partial_payment_at_bottom(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية عمود فقري وتثبيت فقرات',
            'surgery_fee' => 2000000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        // Cashier selects surgery (2,000,000 IQD) but pays 800,000 IQD via global partial mode
        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery), [
            'pay_surgery' => 1,
            'amount' => 800000,
            'payment_mode' => 'partial',
            'custom_paid_amount' => 800000,
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('cashier.surgeries.index'));
        $this->assertDatabaseHas('surgeries', [
            'id' => $surgery->id,
            'surgery_fee_paid' => 'partial',
            'surgery_fee_paid_amount' => 800000,
            'payment_status' => 'partial',
        ]);

        // Verify payment receipt recorded the 800,000 IQD
        $this->assertDatabaseHas('payments', [
            'patient_id' => $this->patient->id,
            'surgery_id' => $surgery->id,
            'amount' => 800000,
        ]);
    }

    public function test_cash_included_services_are_deducted_from_doctor_due_and_saved_in_payment(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية استئصال الزائدة بالمنظار',
            'surgery_fee' => 1000000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $extraService = SurgeryExtraService::create([
            'surgery_id' => $surgery->id,
            'name' => 'خدمات فندقية',
            'price' => 50000.00,
            'quantity' => 1,
            'total_amount' => 50000.00,
            'payment_status' => 'pending',
            'created_by' => $this->cashier->id,
        ]);

        $labCatalog = LabTest::create([
            'name' => 'تحليل سكر تراكمي HbA1c',
            'code' => 'HBA1C-99',
            'price' => 25000,
            'is_active' => true,
        ]);

        $surgeryLab = SurgeryLabTest::create([
            'surgery_id' => $surgery->id,
            'lab_test_id' => $labCatalog->id,
            'payment_status' => 'pending',
            'created_by' => $this->cashier->id,
        ]);

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery), [
            'pay_surgery' => 1,
            'surgery_payment_type' => 'full',
            'amount' => 1000000,
            'pay_extra_services' => [$extraService->id],
            'pay_lab_tests' => [$surgeryLab->id],
            'package_included' => [
                'extra' => [
                    $extraService->id => '1',
                ],
                'lab' => [
                    $surgeryLab->id => '1',
                ],
            ],
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('cashier.surgeries.index'));

        // 1. Check item records updated with deduction bearer doctor
        $extraService->refresh();
        $this->assertEquals(1, $extraService->is_package_included);
        $this->assertEquals(50000.00, (float) $extraService->included_deductible_price);
        $this->assertEquals('doctor', $extraService->deduction_bearer);

        $surgeryLab->refresh();
        $this->assertEquals(1, $surgeryLab->is_package_included);
        $this->assertEquals(25000.00, (float) $surgeryLab->included_deductible_price);
        $this->assertEquals('doctor', $surgeryLab->deduction_bearer);

        // 2. Check payment recorded total deductions = 75,000 (50k extra + 25k lab)
        $payment = \App\Models\Payment::where('surgery_id', $surgery->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(75000.00, (float) $payment->doctor_deductions_amount);
        $this->assertNotEmpty($payment->included_package_items);

        // 3. Check doctor due created with net amount = 925,000 (1,000,000 - 75,000)
        $doctorDue = \App\Models\DoctorDue::where('payment_id', $payment->id)->first();
        $this->assertNotNull($doctorDue);
        $this->assertEquals($this->doctor->id, $doctorDue->doctor_id);
        $this->assertEquals(925000.00, (float) $doctorDue->amount);
        $this->assertEquals('pending', $doctorDue->status);
    }

    public function test_accountant_can_view_surgery_doctor_accounts_and_details_page(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية تكميم المعدة',
            'surgery_fee' => 1500000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'insurance_type' => 'none',
        ]);

        $payment = \App\Models\Payment::create([
            'patient_id' => $this->patient->id,
            'cashier_id' => $this->cashier->id,
            'surgery_id' => $surgery->id,
            'receipt_number' => \App\Models\Payment::generateReceiptNumber(),
            'amount' => 1500000,
            'payment_method' => 'cash',
            'payment_type' => 'surgery',
            'doctor_deductions_amount' => 100000,
            'included_package_items' => [
                ['type' => 'extra', 'name' => 'خدمات فندقية', 'price' => 100000, 'bearer' => 'doctor'],
            ],
            'paid_at' => now(),
        ]);

        $doctorDue = \App\Models\DoctorDue::create([
            'doctor_id' => $this->doctor->id,
            'payment_id' => $payment->id,
            'amount' => 1400000,
            'status' => 'pending',
            'notes' => 'أتعاب جراحة مستحقة',
        ]);

        // 1. View doctor accounts index
        $indexResponse = $this->actingAs($this->cashier)->get(route('cashier.surgeries.doctor-accounts'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($this->doctor->user->name);

        // 2. View doctor account details page
        $detailsResponse = $this->actingAs($this->cashier)->get(route('cashier.surgeries.doctor-account', $this->doctor));
        $detailsResponse->assertOk();
        $detailsResponse->assertSee('عملية تكميم المعدة');
        $detailsResponse->assertSee('1,400,000');

        // 3. Settle single surgery
        $settleResponse = $this->actingAs($this->cashier)->postJson(route('cashier.surgeries.settle-single-surgery', [$this->doctor, $surgery]));
        $settleResponse->assertOk();
        $settleResponse->assertJson(['success' => true]);

        $doctorDue->refresh();
        $this->assertEquals('paid', $doctorDue->status);

        // 4. Test doctor payout
        $payoutResponse = $this->actingAs($this->cashier)->post(route('cashier.surgeries.doctor-payout', $this->doctor), [
            'amount' => 100000,
            'notes' => 'سند صرف إضافي',
        ]);
        // Since balance was settled (1,400,000 paid out of 1,400,000 earned, balance is 0), payout of 100k should return with error
        $payoutResponse->assertSessionHas('error');
    }

    public function test_cancelled_and_refunded_surgeries_are_separated_in_doctor_account_with_zero_due(): void
    {
        // 1. Create active paid surgery
        $activeSurgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية استئصال الغضروف',
            'surgery_fee' => 1200000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'insurance_type' => 'none',
        ]);

        $activePayment = \App\Models\Payment::create([
            'patient_id' => $this->patient->id,
            'cashier_id' => $this->cashier->id,
            'surgery_id' => $activeSurgery->id,
            'receipt_number' => \App\Models\Payment::generateReceiptNumber(),
            'amount' => 1200000,
            'payment_method' => 'cash',
            'payment_type' => 'surgery',
            'paid_at' => now(),
        ]);

        \App\Models\DoctorDue::create([
            'doctor_id' => $this->doctor->id,
            'payment_id' => $activePayment->id,
            'amount' => 1200000,
            'status' => 'pending',
            'notes' => 'أتعاب عملية استئصال الغضروف',
        ]);

        // 2. Create cancelled & refunded surgery
        $cancelledSurgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية رباط صليبي ملغاة',
            'surgery_fee' => 1500000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '12:00:00',
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'insurance_type' => 'none',
        ]);

        // 3. View doctor account details
        $response = $this->actingAs($this->cashier)->get(route('cashier.surgeries.doctor-account', $this->doctor));
        $response->assertOk();

        // Active tab should have the active surgery and active count = 1
        $response->assertSee('عملية استئصال الغضروف');
        $response->assertSee('العمليات الجراحية المستحقة');
        $response->assertSee('العمليات الملغاة والمسترجعة (للعلم)');
        $response->assertSee('عملية رباط صليبي ملغاة');
        $response->assertSee('0 د.ع (لا يترتب استحقاق)');

        // Gross fees and balance must only count the active surgery (1,200,000 not 2,700,000)
        $stats = $response->viewData('stats');
        $this->assertEquals(1, $stats['surgeries_count']);
        $this->assertEquals(1, $stats['cancelled_count']);
        $this->assertEquals(1200000.0, (float) $stats['gross_fees']);
        $this->assertEquals(1200000.0, (float) $stats['balance']);
    }
}
