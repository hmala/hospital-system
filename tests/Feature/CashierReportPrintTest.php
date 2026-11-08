<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierReportPrintTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view cashier reports', 'guard_name' => 'web']);
        $adminRole->givePermissionTo('view cashier reports');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        Hospital::firstOrCreate([
            'name' => 'مستشفى الكفاءات الأهلي',
        ], [
            'owner_name' => 'إدارة المستشفى',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-TEST-001',
        ]);
    }

    public function test_cashier_report_print_renders_with_standard_header_and_coverage_column(): void
    {
        // 1. Create a cash payment
        $cashPayment = Payment::create([
            'amount' => 50000,
            'paid_amount' => 50000,
            'payment_method' => 'cash',
            'payment_type' => 'appointment',
            'status' => 'paid',
            'receipt_number' => 'REC-CASH-101',
            'cashier_id' => $this->admin->id,
            'insurance_type' => 'none',
            'insurance_share' => 0,
            'paid_at' => now(),
        ]);

        // 2. Create an insurance payment
        $insurancePayment = Payment::create([
            'amount' => 10000,
            'paid_amount' => 10000,
            'payment_method' => 'cash',
            'payment_type' => 'appointment',
            'status' => 'paid',
            'receipt_number' => 'REC-INS-202',
            'cashier_id' => $this->admin->id,
            'insurance_type' => 'hi',
            'insurance_card_no' => 'INS-CARD-888',
            'insurance_share' => 40000,
            'paid_at' => now(),
        ]);

        // 3. Request report print
        $response = $this->actingAs($this->admin)->get(route('cashier.report', [
            'range' => 'all',
            'print' => '1',
        ]));

        $response->assertOk();

        // Standardized 3-Zone Hospital Header Assertions
        $response->assertSee('مستشفى الكفاءات الأهلي');
        $response->assertSee("Al-Kafa'at Private Hospital", false);
        $response->assertSee('كشف مقبوضات ووصولات الكاشير المعتمد');
        $response->assertSee('قسم الحسابات المالية والتدقيق');

        // Total repetition prevention CSS check
        $response->assertSee('display: table-row-group;', false);

        // Coverage Column Assertions
        $response->assertSee('نوع التغطية');
        $response->assertSee('REC-CASH-101');
        $response->assertSee('نقدي (كاش)');
        $response->assertSee('REC-INS-202');
        $response->assertSee('الضمان الصحي الوطني');
        $response->assertSee('INS-CARD-888');
        $response->assertSee('حصة الضمان: 40,000 د.ع');

        // Totals Assertions
        $response->assertSee('60,000'); // 50k + 10k total cash collected
        $response->assertSee('40,000'); // total insurance share
    }
}
