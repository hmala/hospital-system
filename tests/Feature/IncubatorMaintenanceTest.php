<?php

namespace Tests\Feature;

use App\Models\Incubator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IncubatorMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $nicuStaff;

    protected User $regularUser;

    protected Incubator $incubator;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'nicu_staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->nicuStaff = User::factory()->create();
        $this->nicuStaff->assignRole('nicu_staff');

        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('patient');

        $this->incubator = Incubator::create([
            'incubator_number' => 'INC-11',
            'incubator_type' => 'normal',
            'status' => Incubator::STATUS_AVAILABLE,
            'daily_fee' => 50000,
            'is_active' => true,
        ]);
    }

    public function test_toggle_maintenance_works_with_patch_method(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('incubators.show', $this->incubator))
            ->patch(route('incubators.toggle-maintenance', $this->incubator));

        $response->assertRedirect(route('incubators.show', $this->incubator));
        $response->assertSessionHas('success');

        $this->assertEquals(Incubator::STATUS_MAINTENANCE, $this->incubator->fresh()->status);
    }

    public function test_toggle_maintenance_works_with_post_method(): void
    {
        $this->incubator->update(['status' => Incubator::STATUS_MAINTENANCE]);

        $response = $this->actingAs($this->nicuStaff)
            ->from(route('incubators.show', $this->incubator))
            ->post(route('incubators.toggle-maintenance', $this->incubator));

        $response->assertRedirect(route('incubators.show', $this->incubator));
        $response->assertSessionHas('success');

        $this->assertEquals(Incubator::STATUS_AVAILABLE, $this->incubator->fresh()->status);
    }

    public function test_toggle_maintenance_denied_for_unauthorized_user(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->patch(route('incubators.toggle-maintenance', $this->incubator));

        $response->assertStatus(403);
    }
}
