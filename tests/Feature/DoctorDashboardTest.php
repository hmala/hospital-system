<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\DoctorDue;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class DoctorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_doctor_can_view_executive_dashboard_with_monthly_dues_and_live_queue(): void
    {
        $hospital = \App\Models\Hospital::first() ?? \App\Models\Hospital::create([
            'name' => 'المستشفى العام',
            'owner_name' => 'د. الإدارة',
            'email' => 'hosp@test.com',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-12345',
        ]);
        $dept = Department::create([
            'name' => 'الباطنية',
            'hospital_id' => $hospital->id,
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 30000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);
        $doctorUser = User::factory()->create(['name' => 'د. حسام المحترم']);
        $doctorUser->assignRole('doctor');

        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'phone' => '07701234567',
            'specialization' => 'استشاري باطنية وقسطرة',
            'qualification' => 'بورد عربي / دكتوراه',
            'license_number' => 'DOC-9988',
            'consultation_fee' => 50000,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'أحمد علي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'أحمد علي',
            'phone' => '07709998877',
            'gender' => 'male',
            'birth_date' => '1990-01-01',
            'identification_type' => 'national_id',
            'identification_number' => '1990001122',
        ]);

        // Create an appointment for today
        $appt = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'appointment_date' => today(),
            'appointment_time' => '10:00:00',
            'queue_number' => 1,
            'consultation_fee' => 50000,
            'status' => 'waiting',
            'type' => 'consultation',
        ]);

        // Create Doctor Dues for current month
        DoctorDue::create([
            'doctor_id' => $doctor->id,
            'amount' => 35000,
            'status' => 'paid',
            'paid_at' => now(),
            'created_at' => now(),
        ]);

        DoctorDue::create([
            'doctor_id' => $doctor->id,
            'amount' => 15000,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($doctorUser)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('د. حسام المحترم');
        $response->assertSee('مستحقات الشهر المكتسبة');
        $response->assertSee('50,000'); // total earned
        $response->assertSee('35,000'); // paid
        $response->assertSee('15,000'); // pending
        $response->assertSee('طابور مراجعي اليوم');
        $response->assertSee('أحمد علي');
    }
}
