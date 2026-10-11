<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ConsultationRevenue;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorCommissionSetting;
use App\Models\DoctorDue;
use App\Models\DoctorFinancialAccount;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConsultantRevenueManualAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected Hospital $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hospital = Hospital::create([
            'name' => 'مستشفى الفيحاء',
            'email' => 'info@test.com',
            'owner_name' => 'المدير العام',
            'phone' => '0770000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-' . uniqid(),
        ]);
    }

    protected function createDepartment(): Department
    {
        return Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'الاستشاريون',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 35000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'is_active' => true,
        ]);
    }

    protected function createDoctor(Department $department): Doctor
    {
        $doctorUser = User::factory()->create();
        return Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'استشاري جراحة',
            'qualification' => 'بورد عربي',
            'license_number' => 'DOC-' . uniqid(),
            'consultation_fee' => 35000,
            'is_active' => true,
        ]);
    }

    protected function createPatient(): Patient
    {
        $patientUser = User::factory()->create(['name' => 'محمد حميد']);
        return Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'محمد حميد',
            'phone' => '07700000000',
        ]);
    }

    protected function createAppointment(Patient $patient, Doctor $doctor, Department $department): Appointment
    {
        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'appointment_date' => today(),
            'status' => 'pending',
            'queue_number' => 1,
            'consultation_fee' => 35000,
        ]);
    }

    public function test_payment_observer_does_not_guess_seventy_percent(): void
    {
        $department = $this->createDepartment();
        $doctor = $this->createDoctor($department);
        $patient = $this->createPatient();
        $appointment = $this->createAppointment($patient, $doctor, $department);

        $payment = Payment::create([
            'payment_type' => 'appointment',
            'appointment_id' => $appointment->id,
            'amount' => 35000,
            'total_amount' => 35000,
            'patient_id' => $patient->id,
            'payment_method' => 'cash',
            'paid_at' => now(),
            'receipt_number' => 'REC-TEST-NO70',
        ]);

        $revenue = ConsultationRevenue::where('payment_id', $payment->id)->first();
        $this->assertNotNull($revenue);
        // حصة الطبيب 0 وحصة المستشفى 35,000 (لا توجد نسبة 70% تخمينية أبداً)
        $this->assertEquals(0.00, (float)$revenue->doctor_share);
        $this->assertEquals(35000.00, (float)$revenue->hospital_share);
    }

    public function test_admin_can_manually_adjust_doctor_and_hospital_shares(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $department = $this->createDepartment();
        $doctor = $this->createDoctor($department);
        $patient = $this->createPatient();
        $appointment = $this->createAppointment($patient, $doctor, $department);

        // إنشاء عمولة = 20,000
        DoctorCommissionSetting::create([
            'doctor_id' => $doctor->id,
            'commission_type' => 'fixed',
            'fixed_amount' => 20000,
            'is_active' => true,
        ]);

        $payment = Payment::create([
            'payment_type' => 'appointment',
            'appointment_id' => $appointment->id,
            'amount' => 35000,
            'total_amount' => 35000,
            'patient_id' => $patient->id,
            'payment_method' => 'cash',
            'paid_at' => now(),
            'receipt_number' => 'REC-TEST-ADJUST',
        ]);

        $revenue = ConsultationRevenue::where('payment_id', $payment->id)->first();
        $this->assertEquals(20000.00, (float)$revenue->doctor_share);
        $this->assertEquals(15000.00, (float)$revenue->hospital_share);

        // الآدمن يقوم بتعديل حصة الطبيب يدوياً إلى 25,000 د.ع
        $response = $this->actingAs($admin)
            ->postJson(route('consultant-availability.financial-movements.update-shares', $revenue->id), [
                'doctor_share' => 25000,
                'reason' => 'تصحيح يدوي للعمولة الثابتة المتفق عليها',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'doctor_share' => 25000.00,
                'hospital_share' => 10000.00,
            ]);

        // التحقق من تحديث سجل الكشفية
        $revenue->refresh();
        $this->assertEquals(25000.00, (float)$revenue->doctor_share);
        $this->assertEquals(10000.00, (float)$revenue->hospital_share);
        $this->assertEquals(71.43, (float)$revenue->doctor_percentage);

        // التحقق من تحديث المستحق المالي للطبيب
        $due = DoctorDue::where('payment_id', $payment->id)->first();
        $this->assertEquals(25000.00, (float)$due->amount);

        // التحقق من تحديث رصيد الطبيب
        $account = DoctorFinancialAccount::where('doctor_id', $doctor->id)->first();
        $this->assertEquals(25000.00, (float)$account->balance);
        $this->assertEquals(25000.00, (float)$account->total_earned);

        // التحقق من توثيق الحركة في سجل الرقابة والنشاطات
        $this->assertDatabaseHas('user_activities', [
            'action' => 'update_financial_shares',
            'category' => 'treasury',
            'is_sensitive' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_adjust_financial_shares(): void
    {
        $user = User::factory()->create();

        $department = $this->createDepartment();
        $doctor = $this->createDoctor($department);
        $patient = $this->createPatient();
        $appointment = $this->createAppointment($patient, $doctor, $department);

        $payment = Payment::create([
            'payment_type' => 'appointment',
            'appointment_id' => $appointment->id,
            'amount' => 35000,
            'total_amount' => 35000,
            'patient_id' => $patient->id,
            'payment_method' => 'cash',
            'paid_at' => now(),
            'receipt_number' => 'REC-TEST-FORBID',
        ]);

        $revenue = ConsultationRevenue::where('payment_id', $payment->id)->first();

        $response = $this->actingAs($user)
            ->postJson(route('consultant-availability.financial-movements.update-shares', $revenue->id), [
                'doctor_share' => 25000,
            ]);

        $response->assertForbidden();
    }
}
