<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\RadiologyType;
use App\Models\Surgery;
use App\Models\SurgeryRadiologyTest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryRadiologyDoctorSpecialtyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function createHospital(): \App\Models\Hospital
    {
        return \App\Models\Hospital::first() ?? \App\Models\Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'المدير العام',
            'phone' => '07780507060',
            'address' => 'بغداد - الحارثية',
            'license_number' => 'LIC-'.uniqid(),
        ]);
    }

    protected function createDepartment(string $name, int $hospitalId, string $type = 'other'): Department
    {
        return Department::create([
            'name' => $name,
            'hospital_id' => $hospitalId,
            'type' => $type,
            'room_number' => '101',
            'consultation_fee' => 0,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
        ]);
    }

    protected function createDoctor(User $user, int $deptId, string $specialization, string $type = 'consultant'): Doctor
    {
        return Doctor::create([
            'user_id' => $user->id,
            'department_id' => $deptId,
            'specialization' => $specialization,
            'type' => $type,
            'qualification' => 'MBChB',
            'license_number' => 'DOC-'.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createSurgery(int $patientId, int $doctorId, int $deptId): Surgery
    {
        return Surgery::create([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'department_id' => $deptId,
            'surgery_name' => 'عملية جراحية تجريبية',
            'surgery_type' => 'Major',
            'surgery_fee' => 500000,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => 'in_progress',
        ]);
    }

    public function test_can_store_surgery_radiology_test_with_assigned_doctor()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $hospital = $this->createHospital();
        $dept = $this->createDepartment('الأشعة والتصوير', $hospital->id);
        $doctorUser = User::factory()->create(['name' => 'د. حسام السونار']);
        $sonarDoctor = $this->createDoctor($doctorUser, $dept->id, 'سونار');

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);

        $surgery = $this->createSurgery($patient->id, $sonarDoctor->id, $dept->id);

        $radType = RadiologyType::create([
            'name' => 'سونار البطن والحوض',
            'code' => 'US-01',
            'main_category' => 'السونار',
            'base_price' => 50000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('clinical-station.radiology-tests.store', $surgery), [
            'radiology_type_id' => $radType->id,
            'doctor_id' => $sonarDoctor->id,
            'notes' => 'فحص مستعجل بعد العملية',
            'priority' => 'urgent',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('surgery_radiology_tests', [
            'surgery_id' => $surgery->id,
            'radiology_type_id' => $radType->id,
            'doctor_id' => $sonarDoctor->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $test = SurgeryRadiologyTest::where('surgery_id', $surgery->id)->first();
        $this->assertNotNull($test);
        $this->assertEquals($sonarDoctor->id, $test->doctor_id);
        $this->assertEquals('د. حسام السونار', $test->doctor->user->name);
    }

    public function test_clinical_station_passes_filtered_sonar_and_echo_doctors()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $hospital = $this->createHospital();
        $dept = $this->createDepartment('الأشعة', $hospital->id);
        $deptHeart = $this->createDepartment('القلبية', $hospital->id, 'cardiology');

        $sonarUser = User::factory()->create(['name' => 'د. سارة سونار']);
        $sonarDoc = $this->createDoctor($sonarUser, $dept->id, 'سونار');

        $echoUser = User::factory()->create(['name' => 'د. علاء إيكو']);
        $echoDoc = $this->createDoctor($echoUser, $deptHeart->id, 'الجراحة القلبية');

        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1995-01-01',
        ]);

        $surgery = $this->createSurgery($patient->id, $sonarDoc->id, $dept->id);

        $response = $this->actingAs($admin)->get(route('clinical-station.show', ['surgery' => $surgery->id, 'tab' => 'tests']));
        $response->assertOk();
        $response->assertViewHas('sonarDoctors');
        $response->assertViewHas('echoDoctors');

        $sonarDoctors = $response->viewData('sonarDoctors');
        $echoDoctors = $response->viewData('echoDoctors');

        $this->assertTrue($sonarDoctors->contains('id', $sonarDoc->id));
        $this->assertFalse($sonarDoctors->contains('id', $echoDoc->id));

        $this->assertTrue($echoDoctors->contains('id', $echoDoc->id));
        $this->assertFalse($echoDoctors->contains('id', $sonarDoc->id));
    }
}
