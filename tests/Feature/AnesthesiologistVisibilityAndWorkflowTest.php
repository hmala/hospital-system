<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnesthesiologistVisibilityAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
    }

    protected function createDoctor(User $user, Department $dept, string $type, string $spec): Doctor
    {
        $doctor = new Doctor;
        $doctor->user_id = $user->id;
        $doctor->department_id = $dept->id;
        $doctor->phone = '07700000000';
        $doctor->specialization = $spec;
        $doctor->qualification = 'MBBS';
        $doctor->license_number = 'LIC-'.$user->id;
        $doctor->experience_years = 5;
        $doctor->bio = 'Doctor';
        $doctor->consultation_fee = 10000;
        $doctor->max_patients_per_day = 20;
        $doctor->is_active = true;
        $doctor->type = $type;
        $doctor->save();

        return $doctor;
    }

    public function test_anesthesiologist_sees_assigned_surgery_in_anesthesia_station_and_surgeries_index(): void
    {
        $dept = Department::first();

        // Create Surgeon
        $surgeonUser = User::factory()->create(['name' => 'الجراح اسيل']);
        $surgeonUser->assignRole('doctor');
        $surgeonDoctor = $this->createDoctor($surgeonUser, $dept, 'surgeon', 'جراحة عامة');

        // Create Anesthesiologist
        $anesthesiaUser = User::factory()->create(['name' => 'المخدر احمد']);
        $anesthesiaUser->assignRole('التخدير');
        $anesthesiaDoctor = $this->createDoctor($anesthesiaUser, $dept, 'anesthesiologist', 'تخدير');

        // Create Patient & Surgery with pending surgeon station
        $patientUser = User::factory()->create(['name' => 'المريض حسام']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'department_id' => $dept->id,
            'doctor_id' => $surgeonDoctor->id,
            'anesthesiologist_id' => $anesthesiaDoctor->id,
            'surgery_name' => 'عملية جراحية عامة',
            'surgery_type' => 'جراحة',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        // Create pending surgeon station (not completed)
        $surgery->surgeonStation()->create([
            'surgeon_id' => $surgeonDoctor->id,
            'status' => 'pending',
        ]);

        // 1. Anesthesiologist accesses /anesthesia (anesthesia-station.index)
        $response = $this->actingAs($anesthesiaUser)->get(route('anesthesia-station.index'));
        $response->assertStatus(200);
        $response->assertSee('المريض حسام');

        // 2. Anesthesiologist accesses /surgeries (surgeries.index)
        $responseSurgeries = $this->actingAs($anesthesiaUser)->get(route('surgeries.index'));
        $responseSurgeries->assertStatus(200);
        $responseSurgeries->assertSee('المريض حسام');

        // 3. Anesthesiologist accesses /surgeries/{surgery}
        $responseDetail = $this->actingAs($anesthesiaUser)->get(route('surgeries.show', $surgery));
        $responseDetail->assertStatus(200);
        $responseDetail->assertSee('المريض حسام');
    }

    public function test_saving_team_timing_creates_and_syncs_anesthesia_station(): void
    {
        $dept = Department::first();

        $surgeonUser = User::factory()->create();
        $surgeonUser->assignRole('doctor');
        $surgeonDoctor = $this->createDoctor($surgeonUser, $dept, 'surgeon', 'جراحة عامة');

        $anesthesiaUser = User::factory()->create();
        $anesthesiaUser->assignRole('التخدير');
        $anesthesiaDoctor = $this->createDoctor($anesthesiaUser, $dept, 'anesthesiologist', 'تخدير');

        $orStaff = User::factory()->create();
        $orStaff->assignRole('surgery_staff');

        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'department_id' => $dept->id,
            'doctor_id' => $surgeonDoctor->id,
            'surgery_name' => 'عملية تجريبية',
            'surgery_type' => 'جراحة',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '11:00:00',
            'status' => 'scheduled',
        ]);

        $this->assertNull($surgery->anesthesiaStation);

        // Surgery staff saves detail_team_timing assigning the anesthesiologist
        $response = $this->actingAs($orStaff)->patch(route('surgeries.updateDetails', $surgery), [
            'tab' => 'detail_team_timing',
            'anesthesiologist_id' => $anesthesiaDoctor->id,
            'start_time' => '11:00',
            'end_time' => '12:00',
        ]);

        $response->assertSessionHasNoErrors();
        $surgery->refresh();

        $this->assertEquals($anesthesiaDoctor->id, $surgery->anesthesiologist_id);
        $this->assertNotNull($surgery->anesthesiaStation);
        $this->assertEquals($anesthesiaDoctor->id, $surgery->anesthesiaStation->anesthesiologist_id);
    }
}
