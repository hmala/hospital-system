<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Emergency;
use App\Models\EmergencyRadiologyRequest;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\RadiologyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyRequestCancellationAndRefundTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $cashierUser;

    protected Doctor $doctor;

    protected Patient $patient;

    protected Emergency $emergency;

    protected RadiologyType $radType;

    protected function setUp(): void
    {
        parent::setUp();

        $hospital = Hospital::create([
            'name' => 'مستشفى الشفاء التخصصي',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الطوارئ',
            'type' => 'emergency',
            'room_number' => 'ER-1',
            'consultation_fee' => 10000,
            'working_hours_start' => '00:00:00',
            'working_hours_end' => '23:59:59',
            'status' => 'active',
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $doctorRole = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'view cashier', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process refunds', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process emergency payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view emergency', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage emergency', 'guard_name' => 'web']);

        $cashierRole->givePermissionTo(['view cashier', 'process refunds', 'process emergency payments']);
        $adminRole->givePermissionTo(Permission::all());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cashierUser = User::factory()->create();
        $this->cashierUser->assignRole('cashier');

        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'طوارئ',
            'qualification' => 'بورد عربي',
            'license_number' => 'DOC-'.uniqid(),
            'phone' => '07701234567',
            'consultation_fee' => 10000,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'أحمد علي حسن']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);

        $this->emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'admission_time' => now(),
            'symptoms' => 'ألم في القدم',
            'emergency_type' => 'medical',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'payment_status' => 'paid',
            'is_active' => true,
        ]);

        $this->radType = RadiologyType::create([
            'name' => 'أشعة ركبة مقطعين',
            'code' => 'XRAY-KNEE-01',
            'base_price' => 50000,
            'is_active' => true,
        ]);
    }

    public function test_doctor_can_cancel_paid_emergency_radiology_request()
    {
        $payment = Payment::create([
            'patient_id' => $this->patient->id,
            'cashier_id' => $this->cashierUser->id,
            'emergency_id' => $this->emergency->id,
            'receipt_number' => 'REC-TEST-CANCEL-01',
            'amount' => 50000,
            'total_amount' => 50000,
            'patient_share' => 50000,
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
            'paid_at' => now(),
        ]);

        $radReq = EmergencyRadiologyRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'payment_id' => $payment->id,
            'payment_status' => 'paid',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'requested_at' => now(),
        ]);
        $radReq->radiologyTypes()->attach($this->radType->id);

        $response = $this->actingAs($this->admin)->postJson(
            route('emergency.cancel-request', $this->emergency->id),
            [
                'type' => 'radiology',
                'request_id' => $radReq->id,
                'reason' => 'طلب بالخطأ',
            ]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $radReq->refresh();
        $this->assertEquals('cancelled', $radReq->status);
        $this->assertStringContainsString('ألغي بطلب الطبيب/المريض', $radReq->notes);
    }

    public function test_cancelled_paid_emergency_request_appears_in_cashier_refunds()
    {
        $originalPayment = Payment::create([
            'patient_id' => $this->patient->id,
            'cashier_id' => $this->cashierUser->id,
            'emergency_id' => $this->emergency->id,
            'receipt_number' => 'REC-TEST-ORIG-01',
            'amount' => 50000,
            'total_amount' => 50000,
            'patient_share' => 50000,
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
            'paid_at' => now(),
        ]);

        $radReq = EmergencyRadiologyRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'payment_id' => $originalPayment->id,
            'payment_status' => 'paid',
            'status' => 'cancelled',
            'priority' => 'urgent',
            'requested_at' => now(),
            'notes' => 'طلب بالخطأ',
        ]);
        $radReq->radiologyTypes()->attach($this->radType->id);

        $response = $this->actingAs($this->cashierUser)->get(route('cashier.index'));
        $response->assertStatus(200);
        $response->assertSee('أشعة طوارئ #'.$radReq->id);
        $response->assertSee('50,000 د.ع');
        $response->assertSee(route('cashier.emergency.request.refund.process', ['type' => 'radiology', 'id' => $radReq->id]));
    }

    public function test_cashier_can_process_emergency_request_refund_and_generate_voucher()
    {
        $originalPayment = Payment::create([
            'patient_id' => $this->patient->id,
            'cashier_id' => $this->cashierUser->id,
            'emergency_id' => $this->emergency->id,
            'receipt_number' => 'REC-TEST-ORIG-02',
            'amount' => 50000,
            'total_amount' => 50000,
            'patient_share' => 50000,
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
            'paid_at' => now(),
        ]);

        $radReq = EmergencyRadiologyRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'payment_id' => $originalPayment->id,
            'payment_status' => 'paid',
            'status' => 'cancelled',
            'priority' => 'urgent',
            'requested_at' => now(),
            'notes' => 'طلب بالخطأ',
        ]);
        $radReq->radiologyTypes()->attach($this->radType->id);

        $response = $this->actingAs($this->cashierUser)->post(
            route('cashier.emergency.request.refund.process', ['type' => 'radiology', 'id' => $radReq->id]),
            [
                'payment_method' => 'cash',
                'notes' => 'استرجاع نقدي للمريض',
            ]
        );

        $response->assertStatus(302);

        $radReq->refresh();
        $this->assertEquals('refunded', $radReq->payment_status);

        $refundPayment = Payment::where('emergency_id', $this->emergency->id)
            ->where('amount', '<', 0)
            ->first();

        $this->assertNotNull($refundPayment);
        $this->assertEquals(-50000, (float) $refundPayment->amount);
        $this->assertStringContainsString('استرجاع مالي للمريض', $refundPayment->description);
    }
}
