<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Emergency;
use App\Models\EmergencyService;
use App\Models\FinancialTransaction;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyFinancialClearanceAndCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $cashierUser;
    protected $doctorUser;
    protected $doctor;
    protected $patient;
    protected $hospitalId;
    protected $deptId;
    protected $emergencyService;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles & Permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);

        $perms = [
            'view emergencies',
            'create emergencies',
            'edit emergencies',
            'delete emergencies',
            'manage emergencies',
            'process emergency payments',
            'view cashier',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $adminRole->givePermissionTo($p);
        }
        $cashierRole->givePermissionTo(['view cashier', 'process emergency payments']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cashierUser = User::factory()->create();
        $this->cashierUser->assignRole('cashier');

        $this->doctorUser = User::factory()->create(['name' => 'د. حسام الطوارئ']);
        $this->doctorUser->assignRole('doctor');

        $this->hospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deptId = DB::table('departments')->insertGetId([
            'hospital_id' => $this->hospitalId,
            'name' => 'قسم الطوارئ',
            'type' => 'emergency',
            'room_number' => 'ER-1',
            'consultation_fee' => 10000,
            'working_hours_start' => '00:00:00',
            'working_hours_end' => '23:59:59',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $this->deptId,
            'specialization' => 'طب الطوارئ',
            'qualification' => 'بكالوريوس طب وجراحة عامة',
            'license_number' => 'DOC-ER-' . rand(1000, 9999),
            'consultation_fee' => 10000,
            'status' => 'active',
        ]);

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'emergency_contact' => '07700000000',
        ]);

        $this->emergencyService = EmergencyService::create([
            'name' => 'تخطيط قلب ECG',
            'price' => 15000,
            'is_active' => true,
        ]);
    }

    public function test_can_render_emergency_show_page()
    {
        $emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'admission_time' => now(),
            'symptoms' => 'ألم حاد في الصدر',
            'emergency_type' => 'medical',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get(route('emergency.show', $emergency));
        $response->assertStatus(200);
        $response->assertSee('محطة كشف الطوارئ');
        $response->assertSee('إجراءات وتحويل وتخريج الحالة');
    }

    public function test_medical_report_and_discharge_blocked_when_unpaid()
    {
        $emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'admission_time' => now(),
            'symptoms' => 'ألم حاد في الصدر',
            'emergency_type' => 'medical',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'payment_status' => 'pending',
        ]);

        $emergency->services()->attach($this->emergencyService->id);

        // 1. Trying to view medical report while unpaid
        $response = $this->actingAs($this->admin)->get(route('emergency.medical-report', $emergency));
        $response->assertRedirect(route('emergency.show', $emergency->id));
        $response->assertSessionHas('error');

        // 2. Trying to discharge while unpaid
        $dischargeResponse = $this->actingAs($this->admin)->post(route('emergency.discharge', $emergency), [
            'discharge_type' => 'recovered',
            'discharge_notes' => 'المريض بحالة جيدة',
        ]);
        $dischargeResponse->assertSessionHas('error');
        $this->assertDatabaseMissing('emergencies', [
            'id' => $emergency->id,
            'status' => 'discharged',
        ]);

        // 3. Trying to transfer to surgery while unpaid
        $transferResponse = $this->actingAs($this->admin)->post(route('emergency.transfer-to-surgery', $emergency));
        $transferResponse->assertSessionHas('error');
        $this->assertDatabaseMissing('emergencies', [
            'id' => $emergency->id,
            'status' => 'transferred',
        ]);
    }

    public function test_can_cancel_unpaid_active_emergency()
    {
        $emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'admission_time' => now(),
            'symptoms' => 'ألم حاد في الصدر',
            'emergency_type' => 'medical',
            'priority' => 'urgent',
            'status' => 'waiting',
            'payment_status' => 'pending',
        ]);

        $emergency->services()->attach($this->emergencyService->id);

        // create unpaid payment
        $payment = Payment::create([
            'emergency_id' => $emergency->id,
            'patient_id' => $this->patient->id,
            'amount' => 15000,
            'payment_type' => 'emergency',
            'payment_method' => 'cash',
            'receipt_number' => Payment::generateReceiptNumber(),
            'paid_at' => null,
        ]);

        $response = $this->actingAs($this->admin)->post(route('emergency.cancel', $emergency), [
            'cancel_reason' => 'المريض قرر المغادرة دون تلقي العلاج',
        ]);

        $response->assertRedirect(route('emergency.index', ['filter' => 'all']));
        $this->assertDatabaseHas('emergencies', [
            'id' => $emergency->id,
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
        ]);

        // unpaid pending payment is deleted
        $this->assertDatabaseMissing('payments', [
            'id' => $payment->id,
        ]);
    }

    public function test_discharged_emergency_cannot_be_cancelled()
    {
        $emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'admission_time' => now(),
            'symptoms' => 'ألم حاد في الصدر',
            'emergency_type' => 'medical',
            'priority' => 'urgent',
            'status' => 'discharged',
            'discharge_type' => 'recovered',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($this->admin)->post(route('emergency.cancel', $emergency), [
            'cancel_reason' => 'محاولة إلغاء بعد التخريج',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('emergencies', [
            'id' => $emergency->id,
            'status' => 'discharged',
        ]);
    }

    public function test_paid_emergency_cancellation_and_cashier_refund()
    {
        $emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'admission_time' => now(),
            'symptoms' => 'ألم حاد في الصدر',
            'emergency_type' => 'medical',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'payment_status' => 'paid',
        ]);

        $emergency->services()->attach($this->emergencyService->id);

        // Paid payment in cashier
        $paidPayment = Payment::create([
            'emergency_id' => $emergency->id,
            'patient_id' => $this->patient->id,
            'amount' => 15000,
            'payment_type' => 'emergency',
            'payment_method' => 'cash',
            'receipt_number' => Payment::generateReceiptNumber(),
            'paid_at' => now(),
            'cashier_id' => $this->cashierUser->id,
        ]);

        // 1. Cancel paid emergency
        $cancelResponse = $this->actingAs($this->admin)->post(route('emergency.cancel', $emergency), [
            'cancel_reason' => 'تم إلغاء الخدمة بعد السداد لعدم توفر الجهاز',
        ]);

        $cancelResponse->assertRedirect(route('emergency.index', ['filter' => 'all']));
        $cancelResponse->assertSessionHas('warning');

        $emergency->refresh();
        $this->assertEquals('cancelled', $emergency->status);
        $this->assertEquals('cancelled', $emergency->payment_status);

        // Cancelled emergency cannot be opened in show
        $showResponse = $this->actingAs($this->admin)->get(route('emergency.show', $emergency));
        $showResponse->assertRedirect(route('emergency.index', ['filter' => 'all']));
        $showResponse->assertSessionHas('error');

        // 2. Cashier processes the refund
        $refundResponse = $this->actingAs($this->cashierUser)->post(route('cashier.emergency.refund.process', $emergency), [
            'payment_method' => 'cash',
            'notes' => 'إرجاع نقدي للمريض بموجب إلغاء الحالة',
        ]);

        $refundResponse->assertStatus(302);

        // Verify negative refund payment was created
        $this->assertDatabaseHas('payments', [
            'emergency_id' => $emergency->id,
            'amount' => -15000,
            'payment_type' => 'emergency',
        ]);

        // Verify emergency payment status is now refunded
        $emergency->refresh();
        $this->assertEquals('refunded', $emergency->payment_status);
    }

    public function test_doctor_only_sees_their_own_emergencies_in_index_and_show()
    {
        // Second doctor
        $otherDoctorUser = User::factory()->create(['name' => 'د. خالد']);
        $otherDoctorUser->assignRole('doctor');
        $otherDoctor = Doctor::create([
            'user_id' => $otherDoctorUser->id,
            'department_id' => $this->deptId,
            'specialization' => 'طوارئ',
            'qualification' => 'طب عام',
            'license_number' => 'DOC-OTHER-' . rand(1000, 9999),
            'status' => 'active',
        ]);

        $pUser1 = User::factory()->create(['name' => 'المريض أحمد للطبيب الأول']);
        $patient1 = Patient::create(['user_id' => $pUser1->id]);

        $pUser2 = User::factory()->create(['name' => 'المريض سليم للطبيب الثاني']);
        $patient2 = Patient::create(['user_id' => $pUser2->id]);

        // Emergency for first doctor
        $emg1 = Emergency::create([
            'patient_id' => $patient1->id,
            'doctor_id' => $this->doctor->id,
            'admission_time' => now(),
            'symptoms' => 'ألم حاد',
            'emergency_type' => 'medical',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'payment_status' => 'pending',
        ]);

        // Emergency for other doctor
        $emg2 = Emergency::create([
            'patient_id' => $patient2->id,
            'doctor_id' => $otherDoctor->id,
            'admission_time' => now(),
            'symptoms' => 'صداع مستمر',
            'emergency_type' => 'medical',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'payment_status' => 'pending',
        ]);

        // When other doctor accesses index, they only see their own emergency
        $response = $this->actingAs($otherDoctorUser)->get(route('emergency.index', ['filter' => 'today']));
        $response->assertStatus(200);
        $response->assertSee('المريض سليم للطبيب الثاني');
        $response->assertDontSee('المريض أحمد للطبيب الأول');

        // Other doctor cannot view first doctor's emergency in show
        $blockedShow = $this->actingAs($otherDoctorUser)->get(route('emergency.show', $emg1));
        $blockedShow->assertStatus(403);

        // Other doctor can view their own emergency
        $allowedShow = $this->actingAs($otherDoctorUser)->get(route('emergency.show', $emg2));
        $allowedShow->assertStatus(200);
    }
}
