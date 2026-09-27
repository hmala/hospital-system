<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\RoleManagementController;
use PHPUnit\Framework\Attributes\Test;

class RolePermissionsMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        // Core roles checked in views/dashboard
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'consultation_receptionist', 'guard_name' => 'web']);

        // Representative permissions for each of the 9 categories
        $samples = [
            'inquiry.create.radiology.ultrasound',
            'view inquiries',
            'view doctors',
            'manage consultant availability',
            'view cashier',
            'process consultation payments',
            'view radiology',
            'process radiology requests',
            'view lab tests',
            'process lab requests',
            'view pharmacy',
            'process pharmacy requests',
            'view emergencies',
            'manage emergency services',
            'view surgeries',
            'manage rooms',
            'manage users',
            'manage roles',
        ];

        foreach ($samples as $sample) {
            Permission::firstOrCreate(['name' => $sample, 'guard_name' => 'web']);
        }
    }

    #[Test]
    public function admin_can_view_role_edit_screen_with_9_operational_cards()
    {
        $admin = User::factory()->create(['email' => 'admin_test@hospital.com']);
        $admin->assignRole('admin');

        $targetRole = Role::firstOrCreate(['name' => 'test_operational_role', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->get(route('roles.edit', $targetRole));

        $response->assertStatus(200);
        $response->assertSee('الاستعلامات والحجوزات');
        $response->assertSee('العيادات والاستشارية ومحطة الأطباء');
        $response->assertSee('الصندوق والمالية');
        $response->assertSee('الأشعة والسونار والإيكو');
        $response->assertSee('المختبر والتحاليل الطبية');
        $response->assertSee('الصيدلية والمخزن الطبي');
        $response->assertSee('قسم الطوارئ');
        $response->assertSee('العمليات الجراحية والرقود');
        $response->assertSee('إدارة النظام والإعدادات');
    }

    #[Test]
    public function all_permissions_are_mapped_without_leaving_unmapped_groups()
    {
        $controller = app(RoleManagementController::class);
        $moduleKeys = array_keys(RoleManagementController::getModuleOrder());

        $permissions = Permission::all();
        $this->assertNotEmpty($permissions);

        foreach ($permissions as $p) {
            $reflector = new \ReflectionMethod(RoleManagementController::class, 'permissionGroup');
            $reflector->setAccessible(true);
            $group = $reflector->invoke($controller, $p->name);

            $this->assertContains($group, $moduleKeys, "Permission [{$p->name}] mapped to unknown group [{$group}]");
        }
    }

    #[Test]
    public function cashier_section_is_hidden_from_receptionist_who_lacks_view_cashier_permission()
    {
        $receptionist = User::factory()->create(['email' => 'reception_test@hospital.com']);
        $role = Role::findByName('consultation_receptionist');
        $receptionist->assignRole($role);

        $role->syncPermissions(['view inquiries', 'inquiry.create.radiology.ultrasound']);

        $response = $this->actingAs($receptionist)->get(route('roles.index'));
        $this->assertFalse($receptionist->can('view cashier'));
    }
}
