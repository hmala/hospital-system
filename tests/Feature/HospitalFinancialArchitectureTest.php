<?php

namespace Tests\Feature;

use App\Models\Accounting\Account;
use App\Models\Accounting\CostCenter;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Accounting\FinancialRoutingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalFinancialArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $fy = \App\Models\Accounting\FiscalYear::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'السنة المالية 2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_closed' => false,
                'company_id' => 1,
            ]
        );

        \App\Models\Accounting\FiscalPeriod::firstOrCreate(
            ['id' => 1],
            [
                'fiscal_year_id' => $fy->id,
                'period_number' => 1,
                'name' => 'الفترة 1',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_closed' => false,
            ]
        );

        CostCenter::firstOrCreate(['code' => '111'], ['name' => 'صالة العمليات الرئيسية (1)', 'is_active' => true]);
        CostCenter::firstOrCreate(['code' => '13'], ['name' => 'قسم الخدج والحاضنات (NICU)', 'is_active' => true]);

        Hospital::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'مستشفى الفيحاء الأهلي',
                'owner_name' => 'المدير العام',
                'phone' => '07700000000',
                'address' => 'البصرة',
                'license_number' => 'LIC-12345',
            ]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'financial_tester@faiha.test'],
            [
                'name' => 'مدير مالي للاختبار',
                'password' => bcrypt('password'),
            ]
        );
        $this->admin->assignRole('admin');
    }

    /**
     * التحقق من سلامة شجرة الحسابات الجديدة والصناديق والذمم
     */
    public function test_chart_of_accounts_branches_are_properly_seeded_and_configured(): void
    {
        // الصناديق النقدية التحليلية
        $cashGen = Account::where('code', '134111')->first();
        $this->assertNotNull($cashGen);
        $this->assertEquals('debit', $cashGen->nature);
        $this->assertTrue($cashGen->is_postable);

        $cashHi = Account::where('code', '134112')->first();
        $this->assertNotNull($cashHi);
        $this->assertEquals('debit', $cashHi->nature);
        $this->assertTrue($cashHi->is_postable);

        $cashMoi = Account::where('code', '134113')->first();
        $this->assertNotNull($cashMoi);
        $this->assertEquals('debit', $cashMoi->nature);
        $this->assertTrue($cashMoi->is_postable);

        // ذمم ومطالبات الجهات الحكومية
        $recHi = Account::where('code', '132111')->first();
        $this->assertNotNull($recHi);
        $this->assertEquals('debit', $recHi->nature);
        $this->assertTrue($recHi->is_postable);

        $recMoi = Account::where('code', '132112')->first();
        $this->assertNotNull($recMoi);
        $this->assertEquals('debit', $recMoi->nature);
        $this->assertTrue($recMoi->is_postable);

        // حساب وسيط المقبوضات
        $clearing = Account::where('code', '2326111')->first();
        $this->assertNotNull($clearing);
        $this->assertEquals('credit', $clearing->nature);
        $this->assertTrue($clearing->is_postable);

        // فروع إيرادات الأشعة والخدمات
        $radXray = Account::where('code', '4312131')->first();
        $radSonar = Account::where('code', '4312132')->first();
        $radMri = Account::where('code', '4312133')->first();
        $radCt = Account::where('code', '4312134')->first();
        $radEcho = Account::where('code', '4312135')->first();

        $this->assertNotNull($radXray);
        $this->assertNotNull($radSonar);
        $this->assertNotNull($radMri);
        $this->assertNotNull($radCt);
        $this->assertNotNull($radEcho);

        // حساب مصروف المواد الطبية
        $medExp = Account::where('code', '3263')->first();
        $this->assertNotNull($medExp);
        $this->assertTrue($medExp->is_postable);
    }

    /**
     * التحقق من ترقية جدول departments وإدراج الأقسام الطبية الرئيسية
     */
    public function test_departments_have_financial_fields_and_nicu_is_registered(): void
    {
        $nicu = Department::where('name', 'like', '%الخدج%')->first();
        $this->assertNotNull($nicu);
        $this->assertEquals('NICU', $nicu->room_number);
        $this->assertNotNull($nicu->cost_center_id);
        $this->assertNotNull($nicu->revenue_account_id);
        $this->assertEquals('قسم الخدج والحاضنات (NICU)', $nicu->costCenter?->name);

        $rad = Department::where('name', 'like', '%الأشعة%')->first();
        $this->assertNotNull($rad);
        $this->assertNotNull($rad->cost_center_id);
        $this->assertNotNull($rad->revenue_account_id);

        $or = Department::where('name', 'like', '%صالات العمليات%')->first();
        $this->assertNotNull($or);
        $this->assertNotNull($or->cost_center_id);
        $this->assertNotNull($or->revenue_account_id);

        $inpatient = Department::where('name', 'like', '%الرقود%')->first();
        $this->assertNotNull($inpatient);
        $this->assertNotNull($inpatient->cost_center_id);
        $this->assertNotNull($inpatient->revenue_account_id);
    }

    /**
     * التحقق من إضافة وتعديل قسم جديد مع ربطه المالي
     */
    public function test_can_create_and_update_department_with_cost_center_and_revenue_account(): void
    {
        $cc = CostCenter::firstOrCreate(['code' => '16'], ['name' => 'قسم العلاج الطبيعي', 'is_active' => true]);
        $rev = Account::where('code', '431218')->first();

        // 1. إنشاء قسم جديد
        $response = $this->actingAs($this->admin)->post(route('departments.store'), [
            'name' => 'قسم العلاج الطبيعي والتأهيل',
            'type' => 'other',
            'room_number' => 'PT-101',
            'consultation_fee' => 25000,
            'cost_center_id' => $cc->id,
            'revenue_account_id' => $rev?->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('departments.admin'));

        $dept = Department::where('name', 'قسم العلاج الطبيعي والتأهيل')->first();
        $this->assertNotNull($dept);
        $this->assertEquals($cc->id, $dept->cost_center_id);
        $this->assertEquals($rev?->id, $dept->revenue_account_id);

        // 2. تعديل القسم
        $updateResponse = $this->actingAs($this->admin)->put(route('departments.update', $dept), [
            'name' => 'قسم العلاج الطبيعي والتأهيل المطور',
            'type' => 'other',
            'room_number' => 'PT-202',
            'consultation_fee' => 30000,
            'cost_center_id' => $cc->id,
            'revenue_account_id' => $rev?->id,
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('departments.admin'));
        $dept->refresh();
        $this->assertEquals('قسم العلاج الطبيعي والتأهيل المطور', $dept->name);
        $this->assertEquals('PT-202', $dept->room_number);
        $this->assertEquals(30000, $dept->consultation_fee);
    }

    /**
     * التحقق من واجهة إدارة الأقسام والربط المالي
     */
    public function test_departments_admin_screen_loads_and_displays_financial_links(): void
    {
        $response = $this->actingAs($this->admin)->get(route('departments.admin'));
        $response->assertStatus(200);
        $response->assertSee('الربط المالي والمحاسبي');
        $response->assertSee('قسم الخدج وحاضنات الأطفال (NICU)');
    }

    /**
     * التحقق من توليد قيد المقبوضات النقدي الثلاثي لمريض الضمان الصحي
     */
    public function test_financial_routing_service_records_split_insurance_payment(): void
    {
        $patient = Patient::first() ?? Patient::create([
            'user_id' => $this->admin->id,
            'name' => 'علي حسن محمد',
        ]);
        $payment = Payment::create([
            'patient_id' => $patient->id,
            'receipt_number' => 'TEST-HI-'.rand(1000, 9999),
            'amount' => 100000.00,
            'total_amount' => 100000.00,
            'patient_share' => 15000.00,
            'insurance_share' => 85000.00,
            'insurance_type' => 'health_insurance',
            'copay_percentage' => 15.00,
            'paid_at' => now(),
        ]);

        $service = new FinancialRoutingService;
        $entry = $service->recordPaymentReceipt($payment, $this->admin->id);

        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());
        $this->assertEquals('posted', $entry->status);

        // التحقق من الأسطر
        $lines = $entry->lines;
        $this->assertCount(3, $lines);

        // سطر كاش الضمان 134112
        $cashLine = $lines->where('account_id', Account::where('code', '134112')->value('id'))->first();
        $this->assertNotNull($cashLine);
        $this->assertEquals(15000.00, (float) $cashLine->debit);

        // سطر ذمة الضمان 132111
        $recLine = $lines->where('account_id', Account::where('code', '132111')->value('id'))->first();
        $this->assertNotNull($recLine);
        $this->assertEquals(85000.00, (float) $recLine->debit);

        // سطر الوسيط 2326111
        $clearingLine = $lines->where('account_id', Account::where('code', '2326111')->value('id'))->first();
        $this->assertNotNull($clearingLine);
        $this->assertEquals(100000.00, (float) $clearingLine->credit);
    }

    /**
     * التحقق من توليد قيد استهلاك المواد الطبية على 3263
     */
    public function test_financial_routing_service_records_material_consumption(): void
    {
        $orLocation = Location::where('name', 'like', '%صالة العمليات%')->first()
            ?? Location::firstOrCreate(['name' => 'صالة العمليات 1', 'type' => 'sub', 'cost_center_id' => CostCenter::where('code', '111')->value('id')]);

        $service = new FinancialRoutingService;
        $entry = $service->recordMaterialConsumption(
            locationId: $orLocation->id,
            totalCost: 175000.00,
            description: 'استهلاك مستلزمات جراحية'
        );

        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());

        $medLine = $entry->lines->where('account_id', Account::where('code', '3263')->value('id'))->first();
        $this->assertNotNull($medLine);
        $this->assertEquals(175000.00, (float) $medLine->debit);
        $this->assertEquals($orLocation->cost_center_id, $medLine->cost_center_id);
    }

    /**
     * التحقق من توليد قيد فاتورة المورد على 23214
     */
    public function test_financial_routing_service_records_supplier_invoice(): void
    {
        $supplier = Supplier::first() ?? Supplier::create(['name' => 'مكتب الدواء التخصصي']);

        $service = new FinancialRoutingService;
        $entry = $service->recordSupplierInvoice(
            supplierId: $supplier->id,
            invoiceNumber: 'INV-2026-99',
            totalAmount: 2500000.00,
            receivedDate: '2026-11-27',
            notes: 'أدوية ومستلزمات صيدلية'
        );

        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());

        $supLine = $entry->lines->where('account_id', Account::where('code', '23214')->value('id'))->first();
        $this->assertNotNull($supLine);
        $this->assertEquals(2500000.00, (float) $supLine->credit);
    }
}
