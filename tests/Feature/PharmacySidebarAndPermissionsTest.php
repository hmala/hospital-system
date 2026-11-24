<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PharmacySidebarAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'view pharmacy',
            'manage medicines',
            'create medicines',
            'edit medicines',
            'delete medicines',
            'manage pharmacy services',
            'import medicines',
            'dispense medications',
            'process pharmacy requests',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $pharmacyRole = Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);
        $pharmacyRole->givePermissionTo($permissions);
    }

    public function test_pharmacy_staff_sees_all_seven_pharmacy_sidebar_links(): void
    {
        $pharmacyUser = User::factory()->create(['name' => 'صيدلاني مجيد', 'email' => 'pharmacist_test@hospital.com']);
        $pharmacyUser->assignRole('pharmacy_staff');

        $response = $this->actingAs($pharmacyUser)->get(route('pharmacy.pos.index'));

        $response->assertStatus(200);

        // تأكيد ظهور كافة الروابط الـ 7 في القائمة الجانبية
        $response->assertSee('نقطة البيع والصرف (POS)');
        $response->assertSee('سجل مبيعات الصيدلية');
        $response->assertSee('تتبع الشحنات والصلاحية (FEFO)');
        $response->assertSee('دليل الأدوية والبدائل');
        $response->assertSee('إضافة دواء / مستلزم جديد');
        $response->assertSee('الخدمات الصيدلانية');
        $response->assertSee('استيراد وتحديث الأدوية');
    }

    public function test_pharmacy_staff_can_access_create_and_import_medicine_routes(): void
    {
        $pharmacyUser = User::factory()->create(['name' => 'صيدلاني علي']);
        $pharmacyUser->assignRole('pharmacy_staff');

        $createResponse = $this->actingAs($pharmacyUser)->get(route('pharmacy.medicines.create'));
        $createResponse->assertStatus(200);

        $importResponse = $this->actingAs($pharmacyUser)->get(route('pharmacy.medicines.import'));
        $importResponse->assertStatus(200);
    }
}
