<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Emergency;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentVoidAndReversalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'void payment receipt', 'guard_name' => 'web']);
    }

    public function test_user_without_permission_cannot_void_payment()
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'amount' => 50000,
            'receipt_number' => 'REC-20261027-0001',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'appointment',
        ]);

        $response = $this->actingAs($user)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'rebill',
            'void_reason' => 'محاولة إلغاء بدون صلاحية',
        ]);

        $response->assertStatus(403);
        $this->assertNull($payment->fresh()->voided_at);
        $this->assertDatabaseMissing('payment_void_logs', ['payment_id' => $payment->id]);
    }

    public function test_authorized_user_can_void_payment_and_reopen_for_rebill()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('void payment receipt');

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'female',
            'date_of_birth' => '1995-05-15',
        ]);

        $emergency = Emergency::create([
            'patient_id' => $patient->id,
            'emergency_type' => 'medical',
            'symptoms' => 'حالة طوارئ تجريبية',
            'admission_time' => now(),
            'arrival_time' => now(),
            'payment_status' => 'paid',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'emergency_id' => $emergency->id,
            'amount' => 31000,
            'receipt_number' => 'REC-20261027-0099',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
            'insurance_type' => 'hi',
            'copay_percentage' => 15.0,
            'patient_share' => 4650,
            'insurance_share' => 26350,
        ]);

        $response = $this->actingAs($admin)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'rebill',
            'void_reason' => 'خطأ في فئة الضمان ونسبة التحمل ويجب إعادة التسديد بالنسبة الصحيحة',
        ]);

        $response->assertRedirect(route('cashier.index'));
        $response->assertSessionHas('success');

        $freshPayment = $payment->fresh();
        $this->assertNull($freshPayment->paid_at);
        $this->assertNull($freshPayment->receipt_number);
        $this->assertEquals('pending', $freshPayment->payment_method);
        $this->assertEquals('rebill', $freshPayment->void_type);
        $this->assertEquals('REC-20261027-0099', $freshPayment->original_receipt_number);
        $this->assertEquals($admin->id, $freshPayment->voided_by);
        $this->assertNotNull($freshPayment->voided_at);

        // التأكد من عودة حالة الطوارئ كغير مسددة
        $this->assertEquals('pending', $emergency->fresh()->payment_status);

        // التأكد من توثيق سجل التدقيق في payment_void_logs
        $this->assertDatabaseHas('payment_void_logs', [
            'payment_id' => $payment->id,
            'receipt_number' => 'REC-20261027-0099',
            'voided_by' => $admin->id,
            'void_type' => 'rebill',
        ]);
    }

    public function test_void_requires_mandatory_reason_min_5_chars()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('void payment receipt');

        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'female',
            'date_of_birth' => '1992-03-03',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'amount' => 10000,
            'receipt_number' => 'REC-20261027-0002',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'appointment',
        ]);

        $response = $this->actingAs($admin)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'rebill',
            'void_reason' => 'خطأ', // أقل من 5 أحرف
        ]);

        $response->assertSessionHasErrors('void_reason');
        $this->assertNull($payment->fresh()->voided_at);
    }

    public function test_authorized_user_can_void_payment_with_refund()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('void payment receipt');

        $doctor = User::factory()->create();

        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1985-02-02',
        ]);

        $hospital = \App\Models\Hospital::firstOrCreate(['name' => 'مستشفى الكفاءات'], [
            'owner_name' => 'د. صاحب المستشفى',
            'phone' => '07700000000',
            'email' => 'info@hospital.com',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $dept = \App\Models\Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة باطنية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $doctorRecord = \App\Models\Doctor::create([
            'user_id' => $doctor->id,
            'department_id' => $dept->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-'.uniqid(),
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorRecord->id,
            'department_id' => $dept->id,
            'consultation_fee' => 15000,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'amount' => 15000,
            'receipt_number' => 'REC-20261027-0003',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'appointment',
        ]);

        $response = $this->actingAs($admin)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'refund',
            'void_reason' => 'رغبة المريض في إلغاء الموعد واسترداد المبلغ كاملاً',
        ]);

        $response->assertRedirect(route('cashier.index'));

        $freshPayment = $payment->fresh();
        $this->assertEquals('refund', $freshPayment->void_type);
        $this->assertEquals('cancelled', $freshPayment->claim_status);
        $this->assertNotNull($freshPayment->voided_at);

        // إلغاء الموعد المرتبط
        $this->assertEquals('cancelled', $appointment->fresh()->status);
        $this->assertEquals('cancelled', $appointment->fresh()->payment_status);

        $this->assertDatabaseHas('payment_void_logs', [
            'payment_id' => $payment->id,
            'void_type' => 'refund',
            'amount' => 15000,
        ]);
    }

    public function test_authorized_user_can_view_void_form()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('void payment receipt');

        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'amount' => 25000,
            'receipt_number' => 'REC-20261027-0777',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
        ]);

        $response = $this->actingAs($admin)->get(route('cashier.payments.void.form', $payment->id));
        $response->assertStatus(200);
        $response->assertSee('إبطال الوصل المالي وإعادة فتحه للتصحيح');
        $response->assertSee('REC-20261027-0777');
    }
}
