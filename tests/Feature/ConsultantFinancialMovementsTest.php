<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ConsultationRevenue;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConsultantFinancialMovementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Doctor $doctor;
    protected Department $department;
    protected Hospital $hospital;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create(['name' => 'Admin User']);
        $this->adminUser->assignRole($adminRole);

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
            'name' => 'العيادات الاستشارية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. استشاري جراحة']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'qualification' => 'بورد عربي',
            'license_number' => 'DOC-' . uniqid(),
            'consultation_fee' => 25000,
            'type' => 'consultant',
            'is_active' => true,
        ]);
    }

    public function test_financial_movements_page_loads_with_date_filters_without_sql_ambiguity()
    {
        $patientUser = User::factory()->create(['name' => 'مريض نقدي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'مريض نقدي',
            'gender' => 'male',
            'birth_date' => '1990-01-01',
            'phone' => '07711111111',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => now()->toDateString(),
            'status' => 'completed',
            'payment_status' => 'paid',
            'consultation_fee' => 25000,
        ]);

        $payment = Payment::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'amount' => 25000,
            'total_amount' => 25000,
            'insurance_share' => 0,
            'status' => 'completed',
            'payment_method' => 'cash',
            'paid_at' => now(),
            'receipt_number' => 'REC-1001',
        ]);

        ConsultationRevenue::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'payment_id' => $payment->id,
            'total_amount' => 25000,
            'doctor_share' => 20000,
            'hospital_share' => 5000,
            'doctor_percentage' => 80,
            'payment_method' => 'cash',
            'movement_type' => 'payment',
            'paid_at' => now(),
            'receipt_number' => 'REC-1001',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('consultant-availability.financial-movements', [
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertSee('25,000');
        $response->assertSee('REC-1001');
    }

    public function test_financial_movements_displays_actual_patient_paid_amount_for_insurance()
    {
        $patientUser = User::factory()->create(['name' => 'مريضة ضمان صحي']);
        $insurancePatient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'مريضة ضمان صحي',
            'gender' => 'female',
            'birth_date' => '1985-05-15',
            'phone' => '07722222222',
        ]);

        $insuranceAppointment = Appointment::create([
            'patient_id' => $insurancePatient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => now()->toDateString(),
            'status' => 'completed',
            'payment_status' => 'paid',
            'insurance_type' => 'hi',
            'consultation_fee' => 25000,
        ]);

        // Patient paid 2,500 cash copay, insurance covered 22,500
        $payment = Payment::create([
            'appointment_id' => $insuranceAppointment->id,
            'patient_id' => $insurancePatient->id,
            'amount' => 2500,
            'total_amount' => 25000,
            'insurance_share' => 22500,
            'insurance_type' => 'hi',
            'status' => 'completed',
            'payment_method' => 'cash',
            'paid_at' => now(),
            'receipt_number' => 'REC-INS-2001',
        ]);

        ConsultationRevenue::create([
            'appointment_id' => $insuranceAppointment->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'payment_id' => $payment->id,
            'total_amount' => 25000,
            'doctor_share' => 20000,
            'hospital_share' => 5000,
            'doctor_percentage' => 80,
            'payment_method' => 'cash',
            'movement_type' => 'payment',
            'paid_at' => now(),
            'receipt_number' => 'REC-INS-2001',
        ]);

        // Filter by insurance
        $response = $this->actingAs($this->adminUser)->get(route('consultant-availability.financial-movements', [
            'insurance_status' => 'insurance',
        ]));

        $response->assertStatus(200);
        // It must display the 2,500 patient copay prominently
        $response->assertSee('2,500');
        $response->assertSee('REC-INS-2001');
        $response->assertSee('ضمان صحي');

        // Filter by cash only: should not find REC-INS-2001
        $responseCash = $this->actingAs($this->adminUser)->get(route('consultant-availability.financial-movements', [
            'insurance_status' => 'cash',
        ]));
        $responseCash->assertStatus(200);
        $responseCash->assertDontSee('REC-INS-2001');
    }

    public function test_export_financial_movements_works_with_filters()
    {
        $response = $this->actingAs($this->adminUser)->get(route('consultant-availability.financial-movements.export', [
            'from_date' => now()->subDays(7)->toDateString(),
            'to_date' => now()->toDateString(),
            'insurance_status' => 'all',
        ]));

        $response->assertStatus(200);
    }
}
