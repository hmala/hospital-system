<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePharmacyInventorySeparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = [
            'view pharmacy',
            'create medicines',
            'import medicines',
            'manage inventory',
            'view products',
            'manage nursing sub stores',
            'manage surgery store',
        ];

        foreach ($perms as $p) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
    }

    public function test_roles_edit_page_has_separated_pharmacy_and_inventory_modules(): void
    {
        $admin = User::factory()->create(['name' => 'مدير النظام']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->assignRole($adminRole);

        $pharmacyRole = Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->get(route('roles.edit', $pharmacyRole));

        $response->assertStatus(200);

        // تأكيد وجود قسم الصيدلية وقسم المستودع كقسمين منفصلين تماماً
        $response->assertSee('الصيدلية ومخزن الأدوية');
        $response->assertSee('المستودع والمخزن الرئيسي للمستشفى');

        // تأكيد وجود عناصر الصيدلية
        $response->assertSee('عرض شاشة الصيدلية ونقطة البيع (POS)');
        $response->assertSee('إضافة دواء أو مستلزم جديد للصيدلية');
        $response->assertSee('استيراد وتحديث الأدوية والوجبات عبر ملف إكسل');

        // تأكيد وجود عناصر المستودع العام
        $response->assertSee('إدارة المستودع والمخزن الطبي العام للمستشفى');
        $response->assertSee('عرض دليل مواد ومستلزمات المخزن العام');
        $response->assertSee('رقابة مخازن وعُهد ردهات التمريض وتعيين المسؤولين');
        $response->assertSee('رقابة مخزن وعُهد صالات العمليات الجراحية');
    }
}
