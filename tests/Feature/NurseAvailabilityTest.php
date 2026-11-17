<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\NurseAvailability;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\HospitalSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NurseAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $headNurse;
    protected User $nurse1;
    protected User $nurse2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(HospitalSeeder::class);
        $this->seed(DepartmentSeeder::class);

        $department = Department::first();

        // 1. Admin
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // 2. Head Nurse
        $this->headNurse = User::factory()->create(['name' => 'مشرف التمريض']);
        $this->headNurse->assignRole('head_nurse');

        // 3. Regular Nurses
        $this->nurse1 = User::factory()->create(['name' => 'ممرض أحمد']);
        $this->nurse1->assignRole('nurse');

        $this->nurse2 = User::factory()->create(['name' => 'ممرضة فاطمة']);
        $this->nurse2->assignRole('nurse');
    }

    public function test_admin_and_head_nurse_can_access_nurse_availability_index(): void
    {
        // Admin
        $responseAdmin = $this->actingAs($this->admin)->get(route('nurse-availability.index'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('جدول توفر ودوام التمريض اليومي');
        $responseAdmin->assertSee('ممرض أحمد');
        $responseAdmin->assertSee('ممرضة فاطمة');

        // Head Nurse
        $responseHeadNurse = $this->actingAs($this->headNurse)->get(route('nurse-availability.index'));
        $responseHeadNurse->assertStatus(200);
        $responseHeadNurse->assertSee('جدول توفر ودوام التمريض اليومي');
    }

    public function test_regular_nurse_without_permission_cannot_access_index(): void
    {
        // Mark nurse1 as available today so middleware passes and controller authorization runs
        NurseAvailability::create([
            'user_id' => $this->nurse1->id,
            'date' => today(),
            'is_available' => true,
        ]);

        $response = $this->actingAs($this->nurse1)->get(route('nurse-availability.index'));
        $response->assertStatus(403);
    }

    public function test_manager_can_toggle_nurse_availability_via_ajax(): void
    {
        // Set available
        $response = $this->actingAs($this->headNurse)->postJson(route('nurse-availability.update-availability', $this->nurse1), [
            'is_available' => 1,
            'date' => today()->toDateString(),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_available' => true,
            'status_text' => 'متاح بالدوام',
        ]);

        $this->assertDatabaseHas('nurse_availabilities', [
            'user_id' => $this->nurse1->id,
            'is_available' => 1,
        ]);

        // Toggle to off duty
        $responseOff = $this->actingAs($this->headNurse)->postJson(route('nurse-availability.update-availability', $this->nurse1), [
            'is_available' => 0,
            'date' => today()->toDateString(),
        ]);

        $responseOff->assertStatus(200);
        $responseOff->assertJson([
            'success' => true,
            'is_available' => false,
            'status_text' => 'خارج الدوام',
        ]);

        $this->assertDatabaseHas('nurse_availabilities', [
            'user_id' => $this->nurse1->id,
            'is_available' => 0,
        ]);
    }

    public function test_manager_can_assign_daily_floor_to_nurse(): void
    {
        $response = $this->actingAs($this->headNurse)->postJson(route('nurse-availability.update-floor', $this->nurse1), [
            'assigned_floor' => 'الطابق الرابع',
            'date' => today()->toDateString(),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'assigned_floor' => 'الطابق الرابع',
        ]);

        $this->assertDatabaseHas('nurse_availabilities', [
            'user_id' => $this->nurse1->id,
            'assigned_floor' => 'الطابق الرابع',
        ]);
    }

    public function test_manager_can_bulk_update_nurses(): void
    {
        $response = $this->actingAs($this->admin)->post(route('nurse-availability.bulk-update'), [
            'action' => 'set_all_available',
            'date' => today()->toDateString(),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('nurse_availabilities', [
            'user_id' => $this->nurse1->id,
            'is_available' => 1,
        ]);

        $this->assertDatabaseHas('nurse_availabilities', [
            'user_id' => $this->nurse2->id,
            'is_available' => 1,
        ]);
    }

    public function test_available_nurse_can_login_successfully(): void
    {
        // Mark nurse1 as available today
        NurseAvailability::create([
            'user_id' => $this->nurse1->id,
            'date' => today(),
            'is_available' => true,
            'assigned_floor' => 'الطابق الثاني',
        ]);

        $password = 'secret-password-123';
        $this->nurse1->update(['password' => bcrypt($password)]);

        $response = $this->post(route('login'), [
            'email' => $this->nurse1->email,
            'password' => $password,
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->nurse1);
    }

    public function test_off_duty_nurse_is_blocked_from_logging_in(): void
    {
        // Mark nurse2 as off duty today
        NurseAvailability::create([
            'user_id' => $this->nurse2->id,
            'date' => today(),
            'is_available' => false,
        ]);

        $password = 'secret-password-123';
        $this->nurse2->update(['password' => bcrypt($password)]);

        $response = $this->post(route('login'), [
            'email' => $this->nurse2->email,
            'password' => $password,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_is_never_blocked_by_nurse_schedule(): void
    {
        $password = 'secret-admin-pass';
        $this->admin->update(['password' => bcrypt($password)]);

        $response = $this->post(route('login'), [
            'email' => $this->admin->email,
            'password' => $password,
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->admin);
    }
}
