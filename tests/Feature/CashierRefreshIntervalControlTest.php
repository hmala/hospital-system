<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierRefreshIntervalControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_page_renders_refresh_interval_control_and_manual_refresh_button(): void
    {
        $role = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'view cashier', 'guard_name' => 'web']);
        $role->givePermissionTo($perm);

        $user = User::factory()->create();
        $user->assignRole('cashier');

        $response = $this->actingAs($user)->get(route('cashier.index'));

        $response->assertStatus(200);
        $response->assertSee('refreshIntervalSelect');
        $response->assertSee('manualRefreshBtn');
        $response->assertSee('live-indicator');
        $response->assertSee('30000');
        $response->assertSee('30 ثانية');
        $response->assertSee('إيقاف التلقائي');
    }
}
