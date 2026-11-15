<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurgeryStaffDashboardAndLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'surgery_staff', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'view surgeries', 'guard_name' => 'web']);
        $role->givePermissionTo($perm);

        Hospital::firstOrCreate(
            ['name' => 'مستشفى الفرات'],
            [
                'owner_name' => 'د. صاحب المستشفى',
                'phone' => '07700000000',
                'address' => 'بغداد',
                'license_number' => 'HOSP-123',
            ]
        );

        Department::firstOrCreate(
            ['name' => 'قسم الجراحة العامة'],
            [
                'hospital_id' => 1,
                'type' => 'surgery',
                'room_number' => '101',
                'consultation_fee' => 25000,
                'working_hours_start' => '08:00',
                'working_hours_end' => '16:00',
            ]
        );
    }

    public function test_surgery_staff_user_redirects_to_surgeries_from_dashboard(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('surgery_staff');

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertRedirect(route('surgeries.index'));
    }

    public function test_surgery_staff_user_redirects_to_surgeries_from_root(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('surgery_staff');

        $response = $this->actingAs($staff)->get('/');

        $response->assertRedirect(route('surgeries.index'));
    }

    public function test_surgery_staff_login_redirect_target_is_surgeries(): void
    {
        $controller = new \App\Http\Controllers\Auth\LoginController;

        $staff = User::factory()->create();
        $staff->assignRole('surgery_staff');

        $this->actingAs($staff);

        $this->assertEquals(route('surgeries.index'), $controller->redirectTo());
    }

    public function test_surgery_staff_arabic_role_name_also_redirects_to_surgeries(): void
    {
        $role = Role::firstOrCreate(['name' => 'موظف عمليات', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'view surgeries', 'guard_name' => 'web']);
        $role->givePermissionTo($perm);

        $staff = User::factory()->create();
        $staff->assignRole('موظف عمليات');

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertRedirect(route('surgeries.index'));
    }
}
