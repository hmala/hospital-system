<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgeryLabTest;
use App\Models\SurgicalOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MoiSurgeryBookingAndCashierTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $surgeonUser;
    protected Department $department;
    protected Patient $moiPatient;
    protected Doctor $doctor;
    protected Room $room;
    protected SurgicalOperation $operation;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'create surgeries', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process surgery payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view surgery payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view cashier surgeries', 'guard_name' => 'web']);

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

        $this->surgeonUser = User::factory()->create(['name' => 'د. جراح العمليات']);
        $this->doctor = Doctor::create([
            'user_id' => $this->surgeonUser->id,
            'department_id' => $this->department->id,
            'type' => 'surgeon',
            'specialization' => 'جراحة عامة',
            'qualification' => 'استشاري جراحة عامة',
            'license_number' => 'DOC-9988',
            'is_active' => true,
        ]);

        $this->room = Room::create([
            'room_number' => '101',
            'type' => 'regular',
            'capacity' => 1,
            'current_occupancy' => 0,
            'price_per_day' => 50000,
            'status' => 'available',
        ]);

        $this->operation = SurgicalOperation::create([
            'name' => 'استئصال المرارة بالمنظار',
            'category' => 'جراحة عامة',
            'fee' => 1000000,
        ]);

        $this->cashier = User::factory()->create();
        $this->cashier->givePermissionTo('create surgeries');
        $this->cashier->givePermissionTo('process surgery payments');
        $this->cashier->givePermissionTo('view surgery payments');
        $this->cashier->givePermissionTo('view cashier surgeries');

        $patientUser = User::factory()->create(['name' => 'منتسب قوى الأمن الداخلي']);
        $this->moiPatient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'moi',
            'insurance_card_no' => 'MOI-778899',
            'copay_percentage' => 15.0,
        ]);
    }

    public function test_moi_patient_can_be_booked_with_copay_percentage(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('surgeries.store'), [
            'patient_id' => $this->moiPatient->id,
            'doctor_id' => $this->doctor->id,
            'surgery_category' => 'جراحة عامة',
            'referring_doctor_name' => 'د. حسام الطبيب',
            'surgical_operation_id' => $this->operation->id,
            'custom_surgery_fee' => 1200000,
            'scheduled_date' => now()->addDays(2)->format('Y-m-d'),
            'scheduled_time' => '10:00',
            'apply_insurance' => '1',
            'copay_percentage' => '15',
            'room_id' => $this->room->id,
            'expected_stay_days' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $surgery = Surgery::where('patient_id', $this->moiPatient->id)->latest('id')->first();
        $this->assertNotNull($surgery);
        $this->assertEquals('moi', $surgery->insurance_type);
        $this->assertEquals(15.0, (float) $surgery->copay_percentage);
        $this->assertEquals(1200000, (float) $surgery->original_surgery_fee);
        $this->assertEquals(1200000, (float) $surgery->surgery_fee);
    }

    public function test_moi_surgery_cashier_payment_splits_patient_and_moi_claim(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->moiPatient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => $this->operation->name,
            'surgical_operation_id' => $this->operation->id,
            'surgery_fee' => 1000000,
            'original_surgery_fee' => 1000000,
            'copay_percentage' => 15.0,
            'insurance_type' => 'moi',
            'insurance_card_no' => 'MOI-778899',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => now(),
            'status' => 'scheduled',
            'payment_status' => 'pending',
            'billing_status' => 'approved',
        ]);

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery->id), [
            'pay_surgery' => '1',
            'amount' => 150000,
            'insurance_type' => 'moi',
            'copay_percentage' => '15',
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('cashier.surgeries.index'));

        $payment = Payment::where('surgery_id', $surgery->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(150000, (float) $payment->amount);
        $this->assertEquals(850000, (float) $payment->insurance_share);
        $this->assertEquals('pending', $payment->claim_status);
    }

    public function test_moi_included_lab_test_is_claimed_on_moi_without_doctor_deductions(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->moiPatient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => $this->operation->name,
            'surgical_operation_id' => $this->operation->id,
            'surgery_fee' => 1000000,
            'original_surgery_fee' => 1000000,
            'copay_percentage' => 15.0,
            'insurance_type' => 'moi',
            'insurance_card_no' => 'MOI-778899',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => now(),
            'status' => 'scheduled',
            'payment_status' => 'pending',
            'billing_status' => 'approved',
        ]);

        $labTest = LabTest::create([
            'name' => 'فحص وظائف الكبد',
            'code' => 'LFT01',
            'price' => 40000,
            'is_active' => true,
        ]);

        $surgeryLab = SurgeryLabTest::create([
            'surgery_id' => $surgery->id,
            'lab_test_id' => $labTest->id,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery->id), [
            'pay_lab_tests' => [$surgeryLab->id],
            'package_included' => [
                'lab' => [$surgeryLab->id => '1'],
            ],
            'amount' => 0,
            'insurance_type' => 'moi',
            'payment_method' => 'insurance',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $payment = Payment::where('surgery_id', $surgery->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(0, (float) $payment->amount);
        $this->assertEquals(40000, (float) $payment->insurance_share);
        $this->assertEquals(0, (float) ($payment->doctor_deductions_amount ?? 0));
    }

    public function test_moi_unbundled_lab_test_is_charged_full_cash_to_patient(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->moiPatient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => $this->operation->name,
            'surgical_operation_id' => $this->operation->id,
            'surgery_fee' => 1000000,
            'original_surgery_fee' => 1000000,
            'copay_percentage' => 15.0,
            'insurance_type' => 'moi',
            'insurance_card_no' => 'MOI-778899',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => now(),
            'status' => 'scheduled',
            'payment_status' => 'pending',
            'billing_status' => 'approved',
        ]);

        $labTest = LabTest::create([
            'name' => 'فحص سيولة الدم PT',
            'code' => 'PT01',
            'price' => 25000,
            'is_active' => true,
        ]);

        $surgeryLab = SurgeryLabTest::create([
            'surgery_id' => $surgery->id,
            'lab_test_id' => $labTest->id,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.payment.process', $surgery->id), [
            'pay_lab_tests' => [$surgeryLab->id],
            'package_included' => [],
            'package_included_submitted' => '1',
            'amount' => 25000,
            'insurance_type' => 'moi',
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $payment = Payment::where('surgery_id', $surgery->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(25000, (float) $payment->amount);
        $this->assertEquals(0, (float) $payment->insurance_share);
    }
}
