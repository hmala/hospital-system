<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorVisitsArchiveSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_doctor_can_search_and_filter_visits_in_archive(): void
    {
        $hospital = Hospital::first() ?? Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'د. الإدارة',
            'email' => 'hosp@test.com',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-12345',
        ]);

        $dept = Department::create([
            'name' => 'العيادة الباطنية',
            'hospital_id' => $hospital->id,
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $doctorUser = User::factory()->create([
            'name' => 'د. حسام علي',
            'email' => 'dr.hossam@example.com',
            'role' => 'doctor',
        ]);
        $doctorUser->assignRole('doctor');

        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'باطنية وقلبية',
            'qualification' => 'بورد / ماجستير',
            'license_number' => 'DOC-1234',
            'phone' => '07701234567',
            'consultation_fee' => 30000,
        ]);

        // Patient 1: Ahmed
        $patientUser1 = User::factory()->create([
            'name' => 'أحمد سمير التميمي',
            'email' => 'ahmed@test.com',
            'phone' => '07711111111',
            'role' => 'patient',
        ]);
        $patient1 = Patient::create([
            'user_id' => $patientUser1->id,
            'file_number' => 'FILE-1001',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
        ]);

        // Patient 2: Omar
        $patientUser2 = User::factory()->create([
            'name' => 'عمر خالد الربيعي',
            'email' => 'omar@test.com',
            'phone' => '07722222222',
            'role' => 'patient',
        ]);
        $patient2 = Patient::create([
            'user_id' => $patientUser2->id,
            'national_id' => 'FILE-2002',
            'gender' => 'male',
        ]);

        // Visit 1: Completed for Ahmed on 2026-11-10
        Visit::create([
            'patient_id' => $patient1->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'visit_date' => '2026-11-10',
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'status' => 'completed',
            'chief_complaint' => 'صداع مزمن وارتفاع ضغط',
        ]);

        // Visit 2: Waiting/Incomplete for Omar on 2026-11-15
        Visit::create([
            'patient_id' => $patient2->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'visit_date' => '2026-11-15',
            'visit_time' => '11:00:00',
            'visit_type' => 'checkup',
            'status' => 'waiting',
            'chief_complaint' => 'ألم حاد في البطن',
        ]);

        // 1. Visit index page without search - displays instant inputs and visits
        $response = $this->actingAs($doctorUser)->get(route('doctor.visits.index'));
        $response->assertStatus(200);
        $response->assertSee('id="archiveSearchInput"', false);
        $response->assertSee('id="archiveStatusSelect"', false);
        $response->assertSee('id="archiveDateFrom"', false);
        $response->assertSee('id="archiveDateTo"', false);
        $response->assertSee('أحمد سمير التميمي');
        $response->assertSee('عمر خالد الربيعي');

        // Verify data-search-text and data attributes exist for instant client-side filtering
        $response->assertSee('data-search-text=', false);
        $response->assertSee('data-status="completed"', false);
        $response->assertSee('data-date="2026-11-10"', false);

        // 2. Server search for Ahmed specifically in archive
        $searchResponse = $this->actingAs($doctorUser)->get(route('doctor.visits.index', [
            'tab' => 'archive',
            'search' => 'أحمد سمير',
        ]));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('أحمد سمير التميمي');
        $searchResponse->assertDontSee('عمر خالد الربيعي');

        // 3. Search by file number FILE-2002
        $fileResponse = $this->actingAs($doctorUser)->get(route('doctor.visits.index', [
            'tab' => 'archive',
            'search' => 'FILE-2002',
        ]));
        $fileResponse->assertStatus(200);
        $fileResponse->assertSee('عمر خالد الربيعي');
        $fileResponse->assertDontSee('أحمد سمير التميمي');

        // 4. Filter by status: completed
        $statusResponse = $this->actingAs($doctorUser)->get(route('doctor.visits.index', [
            'tab' => 'archive',
            'status' => 'completed',
        ]));
        $statusResponse->assertStatus(200);
        $statusResponse->assertSee('أحمد سمير التميمي');
        $statusResponse->assertDontSee('عمر خالد الربيعي');

        // 5. Filter by date range (2026-11-14 to 2026-11-20)
        $dateResponse = $this->actingAs($doctorUser)->get(route('doctor.visits.index', [
            'tab' => 'archive',
            'date_from' => '2026-11-14',
            'date_to' => '2026-11-20',
        ]));
        $dateResponse->assertStatus(200);
        $dateResponse->assertSee('عمر خالد الربيعي');
        $dateResponse->assertDontSee('أحمد سمير التميمي');
    }
}
