<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\FinancialTransaction;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class HospitalTreasuryDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perms = ['view treasury', 'manage treasury', 'view account statements'];
        foreach ($perms as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $adminRole->givePermissionTo($perm);
        }
    }

    public function test_admin_can_access_treasury_with_three_tabs_and_department_stats()
    {
        $admin = User::factory()->create(['email' => 'admin_treasury@hospital.com']);
        $admin->assignRole('admin');

        FinancialTransaction::create([
            'transaction_type' => 'hospital_revenue',
            'category' => 'consultation',
            'voucher_type' => 'inflow',
            'voucher_number' => 'V-TEST-1',
            'amount' => 50000,
            'currency' => 'IQD',
            'payment_method' => 'cash',
            'description' => 'كشفية عيادة استشارية',
            'performed_at' => now(),
        ]);

        FinancialTransaction::create([
            'transaction_type' => 'hospital_revenue',
            'category' => 'radiology',
            'voucher_type' => 'inflow',
            'voucher_number' => 'V-TEST-2',
            'amount' => 75000,
            'currency' => 'IQD',
            'payment_method' => 'card',
            'description' => 'فحص إيكو وسونار',
            'performed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('treasury.index'));

        $response->assertStatus(200);
        $response->assertSee('المخططات البيانية والتحليل المالي');
        $response->assertSee('جدول إيرادات ومصروفات الأقسام');
        $response->assertSee('سجل القيود والوصولات التفصيلية');
        $response->assertSee('العيادات الاستشارية');
        $response->assertSee('الأشعة والسونار والإيكو');
        $response->assertSee('50,000');
        $response->assertSee('75,000');
    }

    public function test_unauthorized_user_is_blocked_from_treasury()
    {
        $user = User::factory()->create(['email' => 'guest_user@hospital.com']);
        $response = $this->actingAs($user)->get(route('treasury.index'));
        $response->assertStatus(403);
    }
}
