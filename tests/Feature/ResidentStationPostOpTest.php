<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResidentStationPostOpTest extends TestCase
{
    use RefreshDatabase;

    protected User $residentUser;

    protected Department $department;

    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'resident', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view resident station', 'guard_name' => 'web']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-001',
            'phone' => '07700000000',
            'email' => 'info@alkafaat.com',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '101',
            'consultation_fee' => 50000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 30,
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. حسام الجراح']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'General Surgery',
            'phone' => '12345678',
            'qualification' => 'MBBS',
            'license_number' => 'LIC123',
            'consultation_fee' => 50000,
        ]);

        $this->residentUser = User::factory()->create(['name' => 'المقيم سامر']);
        $this->residentUser->assignRole('resident');
        $this->residentUser->givePermissionTo('view resident station');
    }

    public function test_surgery_transitions_to_resident_post_op_when_operation_theater_completes(): void
    {
        $patientUser = User::factory()->create(['name' => 'مريض ما بعد الصالة']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 45,
            'gender' => 'male',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctor->id,
            'surgery_type' => 'استئصال مرارة',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => 'in_progress',
            'surgery_fee' => 150000,
        ]);

        // Complete operation theater station
        $otStation = $surgery->operationTheaterStation()->create([
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->residentUser)
            ->get(route('resident-station.index'));

        $response->assertStatus(200);

        // Verify patient is in post-op tab, not pre-op
        $postOpSurgeries = $response->viewData('postOpSurgeries');
        $preOpSurgeries = $response->viewData('preOpSurgeries');

        $this->assertTrue($postOpSurgeries->contains('id', $surgery->id));
        $this->assertFalse($preOpSurgeries->contains('id', $surgery->id));
    }
}
