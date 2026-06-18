<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Surgery;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Visit;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_surgery_details(): void
    {
        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Create a department
        $department = Department::create([
            'name' => 'Surgery Department',
            'code' => 'SURG',
            'is_active' => true,
        ]);

        // Create doctor user and doctor profile
        $doctorUser = User::create([
            'name' => 'Dr. Ahmad',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctorUser->assignRole('doctor');

        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'phone' => '12345678',
            'specialization' => 'General Surgery',
            'qualification' => 'MBBS',
            'license_number' => 'LIC123',
            'experience_years' => 10,
            'bio' => 'Surgeon',
            'consultation_fee' => 50000,
            'max_patients_per_day' => 10,
            'is_active' => true,
        ]);

        // Create patient user and profile
        $patientUser = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 30,
            'gender' => 'male',
            'blood_group' => 'A+',
        ]);

        // Create an admin user to access the pages
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        // Create a visit
        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'doctor_id' => $doctor->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => now()->toTimeString(),
            'visit_type' => 'surgery',
            'status' => 'pending_payment',
        ]);

        // Create a surgery
        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'visit_id' => $visit->id,
            'surgery_type' => 'Appendectomy',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now()->toTimeString(),
            'status' => 'scheduled',
            'surgery_fee' => 100000,
        ]);

        // Access the page as admin
        $response = $this->actingAs($admin)->get(route('surgeries.show', $surgery));
        $response->assertStatus(200);
        $response->assertSee('تفاصيل العملية');
    }

    public function test_can_update_surgery_details(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $department = Department::create([
            'name' => 'Surgery Department',
            'code' => 'SURG',
            'is_active' => true,
        ]);

        $doctorUser = User::create([
            'name' => 'Dr. Ahmad',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctorUser->assignRole('doctor');

        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'phone' => '12345678',
            'specialization' => 'General Surgery',
            'qualification' => 'MBBS',
            'license_number' => 'LIC123',
            'experience_years' => 10,
            'bio' => 'Surgeon',
            'consultation_fee' => 50000,
            'max_patients_per_day' => 10,
            'is_active' => true,
        ]);

        $patientUser = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 30,
            'gender' => 'male',
            'blood_group' => 'A+',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'doctor_id' => $doctor->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => now()->toTimeString(),
            'visit_type' => 'surgery',
            'status' => 'pending_payment',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'visit_id' => $visit->id,
            'surgery_type' => 'Appendectomy',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now()->toTimeString(),
            'status' => 'scheduled',
            'surgery_fee' => 100000,
        ]);

        $response = $this->actingAs($admin)->patch(route('surgeries.updateDetails', $surgery), [
            'diagnosis' => 'Acute Appendicitis',
            'anesthesia_type' => 'general',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'estimated_duration_minutes' => 90,
            'post_op_notes' => 'Patient stable, recovery initiated.',
            'prescribed_medications' => [
                'surgery_treatments' => [
                    $surgery->id => [
                        [
                            'description' => 'Paracetamol',
                            'dosage' => '500mg',
                            'timing' => 'Every 6 hours',
                            'duration_value' => 3,
                            'duration_unit' => 'days'
                        ]
                    ]
                ]
            ]
        ]);

        $response->assertRedirect(route('surgeries.show', $surgery));
        
        $surgery->refresh();
        $this->assertEquals('Acute Appendicitis', $surgery->diagnosis);
        $this->assertEquals('general', $surgery->anesthesia_type);
        $this->assertEquals(90, $surgery->estimated_duration);
        $this->assertCount(1, $surgery->surgeryTreatments);
        $this->assertEquals('Paracetamol', $surgery->surgeryTreatments->first()->description);
    }
}
