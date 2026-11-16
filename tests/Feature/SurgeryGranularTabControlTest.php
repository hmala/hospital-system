<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\SurgeryTabPermission;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryGranularTabControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected Surgery $surgery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $hospital = Hospital::create([
            'name' => 'مستشفى الفيحاء الأهلي',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-001',
            'phone' => '07700000000',
            'email' => 'info@hospital.com',
            'address' => 'بغداد',
        ]);

        $department = Department::create([
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

        $this->staffUser = User::factory()->create(['name' => 'Surgery Staff User', 'email' => 'staff_user_test@hospital.com']);
        $this->staffUser->assignRole('surgery_staff');

        $doctorUser = User::factory()->create(['name' => 'Dr. Lead Surgeon', 'email' => 'lead_surgeon_test@hospital.com']);
        $doctorUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'General Surgery',
            'phone' => '07712345678',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-SURG-01',
            'type' => 'surgeon',
        ]);

        $patientUser = User::factory()->create(['name' => 'Patient Yaseen']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => $hospital->id,
            'first_name' => 'ياسين',
            'last_name' => 'علي',
            'gender' => 'male',
            'birth_date' => '1990-01-01',
            'phone' => '07701112233',
            'blood_type' => 'O+',
        ]);

        $location = Location::create([
            'hospital_id' => $hospital->id,
            'name' => 'صالة العمليات الكبرى 1',
            'type' => 'sub',
            'is_active' => true,
        ]);

        $this->surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'surgery_name' => 'استئصال الزائدة الدودية',
            'surgery_type' => 'major',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'in_progress',
            'estimated_duration' => 60,
        ]);
    }

    public function test_default_permissions_render_active_elements(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->get(route('surgeries.show', $this->surgery));

        $response->assertStatus(200);
        $response->assertSee('التشخيص: لم يُشخص بعد');
        $response->assertSee('التخدير: لم يُدخل بعد');
        $response->assertSee('صالة العمليات الكبرى 1');
        $response->assertSee('جدول العلاج:');
    }

    public function test_hiding_detail_badges_removes_header_badges(): void
    {
        SurgeryTabPermission::updateOrCreate(
            ['role_name' => 'surgery_staff', 'tab_key' => 'detail_badges'],
            ['access_level' => SurgeryTabPermission::ACCESS_HIDDEN]
        );

        $response = $this->actingAs($this->staffUser)
            ->get(route('surgeries.show', $this->surgery));

        $response->assertStatus(200);
        $response->assertDontSee('title="انقر للانتقال لقسم التشخيص"', false);
        $response->assertDontSee('title="انقر للانتقال لتفاصيل التخدير"', false);
    }

    public function test_hiding_timing_location_removes_location_and_duration_buttons(): void
    {
        SurgeryTabPermission::updateOrCreate(
            ['role_name' => 'surgery_staff', 'tab_key' => 'detail_timing_location'],
            ['access_level' => SurgeryTabPermission::ACCESS_HIDDEN]
        );
        SurgeryTabPermission::updateOrCreate(
            ['role_name' => 'surgery_staff', 'tab_key' => 'detail_team_timing'],
            ['access_level' => SurgeryTabPermission::ACCESS_HIDDEN]
        );

        $response = $this->actingAs($this->staffUser)
            ->get(route('surgeries.show', $this->surgery));

        $response->assertStatus(200);
        $response->assertDontSee('id="locationDropdownWrapper"', false);
        $response->assertDontSee('id="durationDropdownWrapper"', false);
    }

    public function test_hiding_medications_removes_treatments_dropdown(): void
    {
        SurgeryTabPermission::updateOrCreate(
            ['role_name' => 'surgery_staff', 'tab_key' => 'detail_medications'],
            ['access_level' => SurgeryTabPermission::ACCESS_HIDDEN]
        );
        SurgeryTabPermission::updateOrCreate(
            ['role_name' => 'surgery_staff', 'tab_key' => 'detail_diagnosis'],
            ['access_level' => SurgeryTabPermission::ACCESS_HIDDEN]
        );

        $response = $this->actingAs($this->staffUser)
            ->get(route('surgeries.show', $this->surgery));

        $response->assertStatus(200);
        $response->assertDontSee('id="treatmentsDropdownWrapper"', false);
    }

    public function test_hiding_devices_removes_medical_devices_card(): void
    {
        SurgeryTabPermission::updateOrCreate(
            ['role_name' => 'surgery_staff', 'tab_key' => 'detail_devices'],
            ['access_level' => SurgeryTabPermission::ACCESS_HIDDEN]
        );
        SurgeryTabPermission::updateOrCreate(
            ['role_name' => 'surgery_staff', 'tab_key' => 'detail_supplies'],
            ['access_level' => SurgeryTabPermission::ACCESS_HIDDEN]
        );

        $response = $this->actingAs($this->staffUser)
            ->get(route('surgeries.show', $this->surgery));

        $response->assertStatus(200);
        $response->assertDontSee('الأجهزة الطبية المستخدمة:');
    }
}
