<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DoctorReferralToAnotherDoctorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'view own visits']);
        Permission::firstOrCreate(['name' => 'manage own visits']);
        Permission::firstOrCreate(['name' => 'view visits']);
        Permission::firstOrCreate(['name' => 'manage consultant availability']);
    }

    public function test_doctor_visit_page_always_renders_referral_button_when_in_progress(): void
    {
        $hospital = Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'is_active' => true,
        ]);

        $docUser1 = User::factory()->create(['name' => 'د. قيصر', 'role' => 'doctor']);
        $docUser1->givePermissionTo(['view own visits', 'manage own visits']);
        $doctor1 = Doctor::create([
            'user_id' => $docUser1->id,
            'department_id' => $dept->id,
            'type' => 'consultant',
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-001',
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $docUser2 = User::factory()->create(['name' => 'د. قمر', 'role' => 'doctor']);
        $doctor2 = Doctor::create([
            'user_id' => $docUser2->id,
            'department_id' => $dept->id,
            'type' => 'consultant',
            'specialization' => 'أسنان',
            'qualification' => 'بورد',
            'license_number' => 'DOC-002',
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'نبأ كريم']);
        $patient = Patient::create(['user_id' => $patientUser->id]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor1->id,
            'department_id' => $dept->id,
            'visit_date' => today(),
            'visit_time' => '10:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص سريري',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($docUser1)->get(route('doctor.visits.show', $visit));

        $response->assertStatus(200);
        $response->assertSee('id="referDoctorButton"', false);
        $response->assertSee('id="referDoctorPanel"', false);
        $response->assertSee('id="refer_doctor_search"', false);
        $response->assertSee('id="referDoctorSuggestions"', false);
        $response->assertSee('id="selectedDoctorCard"', false);
        $response->assertSee($doctor2->specialization);
    }

    public function test_referral_transfers_patient_and_resets_queue_status_to_confirmed(): void
    {
        $hospital = Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'is_active' => true,
        ]);

        $docUser1 = User::factory()->create(['name' => 'د. قيصر', 'role' => 'doctor']);
        $docUser1->givePermissionTo(['view own visits', 'manage own visits']);
        $doctor1 = Doctor::create([
            'user_id' => $docUser1->id,
            'department_id' => $dept->id,
            'type' => 'consultant',
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-001',
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $docUser2 = User::factory()->create(['name' => 'د. قمر', 'role' => 'doctor']);
        $doctor2 = Doctor::create([
            'user_id' => $docUser2->id,
            'department_id' => $dept->id,
            'type' => 'surgeon',
            'specialization' => 'جراحة عامة',
            'qualification' => 'بورد',
            'license_number' => 'DOC-002',
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'نبأ كريم']);
        $patient = Patient::create(['user_id' => $patientUser->id]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor1->id,
            'department_id' => $dept->id,
            'appointment_date' => today(),
            'status' => 'in_consultation',
            'called_at' => now(),
            'queue_number' => 1,
            'consultation_fee' => 15000,
            'duration' => 15,
            'payment_status' => 'paid',
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor1->id,
            'department_id' => $dept->id,
            'appointment_id' => $appointment->id,
            'visit_date' => today(),
            'visit_time' => '10:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص سريري',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($docUser1)->put(route('doctor.visits.refer', $visit), [
            'doctor_id' => $doctor2->id,
        ]);

        $response->assertRedirect(route('doctor.visits.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'doctor_id' => $doctor2->id,
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'doctor_id' => $doctor2->id,
            'status' => 'confirmed',
            'called_at' => null,
        ]);
    }
}
