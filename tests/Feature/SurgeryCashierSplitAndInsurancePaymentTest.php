<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Surgery;
use App\Models\SurgeryLabTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SurgeryCashierSplitAndInsurancePaymentTest extends TestCase
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
        Permission::firstOrCreate(['name' => 'view surgery payments', 'guard_name' => 'web']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الفرات الأهلي',
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
        $this->cashier->givePermissionTo('view surgery payments');

        $patientUser = User::factory()->create(['name' => 'حسام عبد الستار']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'hi',
            'insurance_card_no' => 'HI-998877',
            'copay_percentage' => 25.0,
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. حسام الجراح']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'qualification' => 'بورد جراحة عامة',
            'license_number' => 'DOC-SURG-'.uniqid(),
        ]);
    }

    public function test_cashier_can_view_payment_form_with_included_routine_preop_tests(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'استئصال المرارة بالناظور',
            'surgery_fee' => 1000000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'hi',
        ]);

        $test = LabTest::create([
            'name' => 'HBsAg screen',
            'code' => 'HBSAG',
            'price' => 25000,
            'status' => 'active',
            'main_category' => 'Immunology',
        ]);

        SurgeryLabTest::create([
            'surgery_id' => $surgery->id,
            'lab_test_id' => $test->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($this->cashier)->get(route('cashier.surgeries.payment.form', $surgery->id));

        $response->assertStatus(200);
        $response->assertSee('مشمول ضمن باقة العملية (0 د.ع)');
        $response->assertSee('دفع مركب / مختلط (مقسم كاش + بطاقة)');
    }

    public function test_cashier_processes_insured_surgery_with_routine_lab_tests_marked_paid(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'استئصال المرارة بالناظور',
            'surgery_fee' => 1000000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'hi',
        ]);

        $test = LabTest::create([
            'name' => 'HIV screen',
            'code' => 'HIV',
            'price' => 30000,
            'status' => 'active',
            'main_category' => 'Virology',
        ]);

        $surgLabTest = SurgeryLabTest::create([
            'surgery_id' => $surgery->id,
            'lab_test_id' => $test->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $postData = [
            'pay_surgery' => '1',
            'surgery_payment_type' => 'full',
            'pay_lab_tests' => [$surgLabTest->id],
            'payment_method' => 'cash',
            'amount' => 250000, // 25% patient share of 1,000,000
            'insurance_type' => 'hi',
            'copay_percentage' => 25,
        ];

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery->id), $postData);

        $response->assertRedirect(route('cashier.surgeries.index'));

        $payment = Payment::latest()->first();
        $this->assertEquals(250000, (float) $payment->amount);
        $this->assertEquals(250000, (float) $payment->patient_share);
        $this->assertEquals(750000, (float) $payment->insurance_share);
        $this->assertEquals('cash', $payment->payment_method);

        // Verify pre-op routine lab test is paid and linked to payment
        $surgLabTest->refresh();
        $this->assertEquals('paid', $surgLabTest->payment_status);
        $this->assertEquals($payment->id, $surgLabTest->payment_id);
    }

    public function test_cashier_can_process_split_payment_with_cash_and_card(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'استئصال المرارة بالناظور',
            'surgery_fee' => 1000000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'hi',
        ]);

        $postData = [
            'pay_surgery' => '1',
            'surgery_payment_type' => 'full',
            'payment_method' => 'split',
            'amount' => 250000,
            'cash_amount' => 100000,
            'card_amount' => 150000,
            'insurance_type' => 'hi',
            'copay_percentage' => 25,
        ];

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery->id), $postData);

        $payment = Payment::latest()->first();
        $response->assertRedirect(route('cashier.surgeries.index'));

        $this->assertEquals('split', $payment->payment_method);
        $this->assertEquals(250000, (float) $payment->amount);
        $this->assertEquals(100000, (float) $payment->cash_amount);
        $this->assertEquals(150000, (float) $payment->card_amount);

        // Verify receipt shows split information
        $receiptResponse = $this->actingAs($this->cashier)->get(route('cashier.receipt', $payment->id));
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('دفع مركب');
        $receiptResponse->assertSee('100,000');
        $receiptResponse->assertSee('150,000');
    }

    public function test_split_payment_fails_when_split_amounts_do_not_match_patient_paid_amount(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'استئصال المرارة بالناظور',
            'surgery_fee' => 1000000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'hi',
        ]);

        $postData = [
            'pay_surgery' => '1',
            'surgery_payment_type' => 'full',
            'payment_method' => 'split',
            'amount' => 250000,
            'cash_amount' => 50000,
            'card_amount' => 50000, // 50,000 + 50,000 = 100,000 != 250,000
            'insurance_type' => 'hi',
            'copay_percentage' => 25,
        ];

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery->id), $postData);

        $response->assertSessionHas('error');
    }

    public function test_partial_surgery_fee_payment_updates_status_and_amount_correctly(): void
    {
        $cashPatientUser = User::factory()->create(['name' => 'مريض نقدي']);
        $cashPatient = Patient::create([
            'user_id' => $cashPatientUser->id,
            'insurance_type' => 'none',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $cashPatient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'جراحة عظام',
            'surgery_fee' => 1000000,
            'room_fee' => 0,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $postData = [
            'pay_surgery' => '1',
            'surgery_payment_type' => 'partial',
            'surgery_custom_amount' => 400000,
            'payment_method' => 'cash',
            'amount' => 400000,
            'insurance_type' => 'none',
        ];

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery->id), $postData);

        $payment = Payment::latest()->first();
        $response->assertRedirect(route('cashier.surgeries.index'));

        $surgery->refresh();
        $this->assertEquals(400000, (float) $surgery->surgery_fee_paid_amount);
        $this->assertEquals('partial', $surgery->surgery_fee_paid);
        $this->assertEquals('partial', $surgery->payment_status);
    }
}
