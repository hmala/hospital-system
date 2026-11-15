<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NurseDashboardAndLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'view nursing station', 'guard_name' => 'web']);
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

        Location::create([
            'name' => 'مخزن الطابق الخامس',
            'type' => 'sub',
            'code' => 'LOC-NURSE-TEST',
            'is_active' => true,
        ]);
    }

    public function test_nurse_user_redirects_to_nursing_station_from_dashboard(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $response = $this->actingAs($nurse)->get(route('dashboard'));

        $response->assertRedirect(route('nursing-station.index'));
    }

    public function test_nurse_user_redirects_to_nursing_station_from_root(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $response = $this->actingAs($nurse)->get('/');

        $response->assertRedirect(route('nursing-station.index'));
    }

    public function test_nurse_login_redirect_target_is_nursing_station(): void
    {
        $controller = new \App\Http\Controllers\Auth\LoginController;

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $this->actingAs($nurse);

        $this->assertEquals(route('nursing-station.index'), $controller->redirectTo());
    }
}
