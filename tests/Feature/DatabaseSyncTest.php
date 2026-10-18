<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'hospital_admin']);

        $this->admin = User::factory()->create(['name' => 'المشرف العام']);
        $this->admin->assignRole('admin');

        $this->regularUser = User::factory()->create(['name' => 'مستخدم عادي']);
    }

    public function test_admin_can_access_database_sync_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('system.database-sync.index'));
        $response->assertOk();
        $response->assertSee('مركز سحب ومزامنة قاعدة البيانات');
        $response->assertSee('Hostinger MySQL');
        $response->assertSee('srv1687.hstgr.io');
    }

    public function test_unauthorized_user_cannot_access_database_sync(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('system.database-sync.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_test_remote_connection_via_ajax(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('system.database-sync.test'));
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertEquals(106, $response->json('tables_count'));
    }

    public function test_access_is_blocked_in_production_environment(): void
    {
        // محاكاة بيئة الإنتاج
        $this->app['env'] = 'production';

        $response = $this->actingAs($this->admin)->get(route('system.database-sync.index'));
        $response->assertNotFound();
    }

    public function test_pull_is_blocked_when_local_and_remote_database_names_match(): void
    {
        config(['database.connections.mysql.database' => 'u175868711_her2020']);
        config(['database.connections.remote_hostinger.database' => 'u175868711_her2020']);

        $syncService = app(\App\Services\DatabaseSyncService::class);

        try {
            $syncService->pullFromRemote();
            $this->fail('Expected exception was not thrown.');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('u175868711_her2020', $e->getMessage());
        }
    }
}
