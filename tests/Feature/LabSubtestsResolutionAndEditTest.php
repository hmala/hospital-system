<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LabTest;
use App\Models\LabTestSubTest;
use App\Models\Request as ServiceRequest;
use App\Models\Visit;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class LabSubtestsResolutionAndEditTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage lab tests', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit lab tests', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view lab tests', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_can_update_result_entry_type_via_lab_test_edit_form()
    {
        $test = LabTest::firstOrCreate(
            ['code' => 'TEST_MODE'],
            [
                'name' => 'Test Mode Lab Test',
                'price' => 5000,
                'is_active' => true,
                'result_entry_type' => 'single_value',
            ]
        );

        $response = $this->actingAs($this->admin)->put(route('lab-tests.update', $test), [
            'name' => $test->name,
            'code' => $test->code,
            'price' => 5000,
            'result_entry_type' => 'sub_tests',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('lab-tests.index'));
        $test->refresh();
        $this->assertEquals('sub_tests', $test->result_entry_type);
    }

    public function test_lab_show_resolves_subtests_when_requested_by_code()
    {
        $labStaffRole = Role::firstOrCreate(['name' => 'lab_staff', 'guard_name' => 'web']);
        $labStaff = User::factory()->create();
        $labStaff->assignRole('lab_staff');

        $parent = LabTest::where('code', 'GSE')->first();
        if (!$parent) {
            $parent = LabTest::create([
                'name' => 'General Stool Examination',
                'code' => 'GSE',
                'price' => 5000,
                'result_entry_type' => 'sub_tests',
                'is_active' => true,
            ]);
            LabTestSubTest::create([
                'lab_test_id' => $parent->id,
                'name' => 'Color',
                'sort_order' => 1,
            ]);
        }

        $hospital = \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الكفاءات'],
            [
                'owner_name' => 'Admin',
                'phone' => '07700000000',
                'address' => 'Baghdad',
                'license_number' => 'LIC-12345',
            ]
        );
        $dept = \App\Models\Department::firstOrCreate(
            ['name' => 'المختبر'],
            [
                'hospital_id' => $hospital->id,
                'type' => 'laboratory',
                'room_number' => '101',
                'consultation_fee' => 0,
                'working_hours_start' => '08:00:00',
                'working_hours_end' => '20:00:00',
                'is_active' => true,
            ]
        );

        $patientUser = User::factory()->create(['role' => 'patient']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);
        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $dept->id,
            'visit_type' => 'consultation',
            'chief_complaint' => 'Routine lab check',
            'status' => 'pending',
            'visit_date' => now()->toDateString(),
            'visit_time' => now()->toTimeString(),
        ]);

        $mockReq = ServiceRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'فحص الغائط العام GSE',
            'status' => 'pending',
            'details' => ['tests' => ['GSE']],
        ]);

        $this->admin->givePermissionTo('view lab tests');

        $response = $this->actingAs($this->admin)->get(route('lab.show', $mockReq));
        $response->assertStatus(200);
        $response->assertSee('subtest-item-row');
        $response->assertDontSee('name="test_results[GSE][value]"');
    }
}
