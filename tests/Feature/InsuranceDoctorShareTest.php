<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ConsultationRevenue;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorCommissionSetting;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InsuranceDoctorShareTest extends TestCase
{
    use RefreshDatabase;

    protected Hospital $hospital;
    protected Department $department;
    protected User $cashier;
    protected Doctor $doctor;
    protected Patient $patient;

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

        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'الاستشاريون',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 35000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'is_active' => true,
        ]);

        Permission::findOrCreate('process consultation payments');

        $this->cashier = User::factory()->create();
        $this->cashier->givePermissionTo('process consultation payments');

        $doctorUser = User::factory()->create(['name' => 'دكتور فحص']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'استشاري باطنية',
            'qualification' => 'بورد عربي',
            'license_number' => 'DOC-' . uniqid(),
            'consultation_fee' => 40000,
            'hi_price' => 25000,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'محمد حميد']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'محمد حميد',
            'phone' => '07700000000',
            'insurance_type' => 'hi',
            'copay_percentage' => 10,
        ]);
    }

    public function test_cashier_blocks_payment_if_doctor_has_no_hi_fixed_amount()
    {
        // Commission setting without hi_fixed_amount
        DoctorCommissionSetting::create([
            'doctor_id' => $this->doctor->id,
            'commission_type' => 'fixed',
            'fixed_amount' => 30000,
            'hi_fixed_amount' => null,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => now(),
            'appointment_time' => '10:00:00',
            'consultation_fee' => 40000,
            'insurance_type' => 'hi',
            'payment_status' => 'pending',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->cashier)
            ->post(route('cashier.payment.process', $appointment), [
                'payment_method' => 'cash',
                'amount' => 2500,
                'insurance_type' => 'hi',
                'copay_percentage' => 10,
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('payments', [
            'appointment_id' => $appointment->id,
        ]);
    }

    public function test_cashier_allows_payment_and_calculates_correct_shares_when_hi_fixed_amount_is_set()
    {
        // Commission setting with hi_fixed_amount = 15000
        DoctorCommissionSetting::create([
            'doctor_id' => $this->doctor->id,
            'commission_type' => 'fixed',
            'fixed_amount' => 30000,
            'hi_fixed_amount' => 15000,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => now(),
            'appointment_time' => '10:00:00',
            'consultation_fee' => 40000,
            'insurance_type' => 'hi',
            'payment_status' => 'pending',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->cashier)
            ->post(route('cashier.payment.process', $appointment), [
                'payment_method' => 'cash',
                'amount' => 2500,
                'insurance_type' => 'hi',
                'copay_percentage' => 10,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payments', [
            'appointment_id' => $appointment->id,
            'total_amount' => 25000,
        ]);

        $this->assertDatabaseHas('consultation_revenues', [
            'appointment_id' => $appointment->id,
            'total_amount' => 25000,
            'doctor_share' => 15000,
            'hospital_share' => 10000,
            'doctor_percentage' => 60,
        ]);
    }

    public function test_admin_can_save_all_commission_settings()
    {
        Permission::findOrCreate('manage doctor commissions');
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage doctor commissions');

        $response = $this->actingAs($admin)
            ->postJson(route('admin.doctor-commission-settings.save'), [
                'save_mode' => 'all',
                'doctor_id' => [$this->doctor->id],
                'fixed_amount' => [32000],
                'hi_fixed_amount' => [18000],
                'consultation_fee' => [45000],
                'hi_price' => [25000],
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('doctor_commission_settings', [
            'doctor_id' => $this->doctor->id,
            'fixed_amount' => 32000,
            'hi_fixed_amount' => 18000,
        ]);

        $this->assertDatabaseHas('doctors', [
            'id' => $this->doctor->id,
            'consultation_fee' => 45000,
            'hi_price' => 25000,
        ]);
    }
}
