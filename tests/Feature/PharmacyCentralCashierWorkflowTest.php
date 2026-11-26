<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PharmacySale;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Database\Seeders\HealthInsuranceCategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PharmacyCentralCashierWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected $cashierWithPharmacyUser;

    protected $cashierConsultationOnlyUser;

    protected $medicine;

    protected $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(HealthInsuranceCategorySeeder::class);

        // Ensure permissions exist
        Permission::findOrCreate('process pharmacy payments');
        Permission::findOrCreate('view cashier pharmacy');

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->adminUser->assignRole('admin');

        // كاشير يمتلك صلاحية قبض الصيدلية
        $this->cashierWithPharmacyUser = User::factory()->create(['role' => 'cashier']);
        $this->cashierWithPharmacyUser->givePermissionTo(['view cashier', 'process pharmacy payments', 'view cashier pharmacy']);

        // كاشير استشارية فقط (بدون صيدلية)
        $this->cashierConsultationOnlyUser = User::factory()->create(['role' => 'cashier']);
        $this->cashierConsultationOnlyUser->givePermissionTo(['view cashier', 'process consultation payments']);

        $this->medicine = Medicine::create([
            'name' => 'Paracetamol 500mg',
            'generic_name' => 'Paracetamol',
            'national_code' => '01-PARA-001',
            'barcode' => '628100111222',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2,
            'cost_price' => 1000,
            'sale_price' => 2000,
            'sub_unit_sale_price' => 1000,
            'hi_price' => 1800,
            'is_insurance_covered' => true,
        ]);

        $this->batch = MedicineBatch::create([
            'medicine_id' => $this->medicine->id,
            'batch_number' => 'LOT-PARA-2026',
            'expiry_date' => now()->addMonths(12)->toDateString(),
            'initial_quantity' => 100,
            'current_quantity' => 100,
            'current_sub_units' => 0,
            'purchase_price' => 1000,
            'status' => 'active',
        ]);
    }

    public function test_pharmacy_sale_routes_to_central_cashier_as_pending_cashier()
    {
        $payload = [
            'sale_type' => 'direct_otc',
            'patient_name' => 'مريض مباشر',
            'insurance_type' => 'none',
            'payment_route' => 'central_cashier',
            'is_held' => 0,
            'items' => [
                [
                    'item_type' => 'medicine',
                    'medicine_id' => $this->medicine->id,
                    'unit_type' => 'main_unit',
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->postJson(route('pharmacy.pos.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('pharmacy_sales', [
            'patient_name' => 'مريض مباشر',
            'total_amount' => 4000,
            'patient_share' => 4000,
            'payment_status' => 'pending_cashier',
            'payment_route' => 'central_cashier',
            'dispensing_status' => 'pending',
        ]);
    }

    public function test_cashier_with_permission_can_view_pharmacy_tab_and_pending_sales()
    {
        $sale = PharmacySale::create([
            'invoice_number' => 'PH-20261125-0010',
            'patient_name' => 'مريض الفحص الكاشيري',
            'sale_type' => 'direct_otc',
            'total_amount' => 5000,
            'patient_share' => 5000,
            'insurance_share' => 0,
            'payment_status' => 'pending_cashier',
            'payment_route' => 'central_cashier',
            'is_held' => false,
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->cashierWithPharmacyUser)->get(route('cashier.index'));

        $response->assertStatus(200);
        $response->assertSee('الصيدلية والعلاج');
        $response->assertSee('PH-20261125-0010');
        $response->assertSee('مريض الفحص الكاشيري');
    }

    public function test_cashier_without_permission_cannot_see_pharmacy_tab()
    {
        PharmacySale::create([
            'invoice_number' => 'PH-20261125-0020',
            'patient_name' => 'مريض غير مرئي للموظف المقيد',
            'sale_type' => 'direct_otc',
            'total_amount' => 3000,
            'patient_share' => 3000,
            'insurance_share' => 0,
            'payment_status' => 'pending_cashier',
            'payment_route' => 'central_cashier',
            'is_held' => false,
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->cashierConsultationOnlyUser)->get(route('cashier.index'));

        $response->assertStatus(200);
        $response->assertDontSee('tab-pharmacy-btn');
        $response->assertDontSee('PH-20261125-0020');
    }

    public function test_cashier_can_process_pharmacy_payment_and_issue_receipt()
    {
        $sale = PharmacySale::create([
            'invoice_number' => 'PH-20261125-0030',
            'patient_name' => 'مريض الدفع والتسديد',
            'sale_type' => 'direct_otc',
            'total_amount' => 4000,
            'patient_share' => 4000,
            'insurance_share' => 0,
            'payment_status' => 'pending_cashier',
            'payment_route' => 'central_cashier',
            'is_held' => false,
            'user_id' => $this->adminUser->id,
        ]);

        // 1. فتح شاشة تسديد الصيدلية
        $formResponse = $this->actingAs($this->cashierWithPharmacyUser)
            ->get(route('cashier.pharmacy.payment.form', $sale->id));
        $formResponse->assertStatus(200);
        $formResponse->assertSee('PH-20261125-0030');

        // 2. معالجة الدفع وتسجيل الإيصال
        $processResponse = $this->actingAs($this->cashierWithPharmacyUser)
            ->post(route('cashier.pharmacy.payment.process', $sale->id), [
                'payment_method' => 'cash',
                'amount' => 4000,
                'notes' => 'تسديد نقدي كامل لصيدلية المستشفى',
            ]);

        $processResponse->assertRedirect();

        // 3. التحقق من السجلات المالية
        $payment = Payment::where('pharmacy_sale_id', $sale->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(4000, (float) $payment->amount);
        $this->assertEquals('pharmacy', $payment->payment_type);

        $this->assertEquals('paid', $sale->fresh()->payment_status);
        $this->assertEquals($payment->id, $sale->fresh()->payment_id);
    }

    public function test_prescription_handoff_after_cashier_payment()
    {
        $patientUser = User::factory()->create(['name' => 'مريض الوصفة']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1995-05-15',
        ]);

        $prescription = Prescription::create([
            'prescription_number' => 'RX-20261125-9999',
            'patient_id' => $patient->id,
            'doctor_id' => null,
            'status' => 'pending',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->medicine->id,
            'quantity' => 1,
            'unit_type' => 'main_unit',
            'status' => 'pending',
        ]);

        // الصيدلي يقوم بتجهيز الوصفة وتحويلها للكاشير
        $dispenseResponse = $this->actingAs($this->adminUser)
            ->postJson(route('pharmacy.pos.prescriptions.dispense', $prescription->id), [
                'items' => [],
            ]);
        $dispenseResponse->assertStatus(200);

        $sale = PharmacySale::where('patient_id', $patient->id)->first();
        $this->assertNotNull($sale);
        $this->assertEquals('pending_cashier', $sale->payment_status);

        // التحقق من حالة الوصفة في الرادار (بانتظار الكاشير)
        $queueResponse = $this->actingAs($this->adminUser)->getJson(route('pharmacy.pos.pending-prescriptions'));
        $queueResponse->assertStatus(200);
        $queueData = $queueResponse->json('prescriptions');
        $this->assertEquals('pending_cashier', $queueData[0]['stage']);

        // الكاشير يسدد الفاتورة
        $this->actingAs($this->cashierWithPharmacyUser)
            ->post(route('cashier.pharmacy.payment.process', $sale->id), [
                'payment_method' => 'cash',
                'amount' => $sale->patient_share,
            ]);

        // الآن الرادار يظهر مدفوعة وجاهزة للتسليم
        $queueResponsePaid = $this->actingAs($this->adminUser)->getJson(route('pharmacy.pos.pending-prescriptions'));
        $queuePaidData = $queueResponsePaid->json('prescriptions');
        $this->assertEquals('paid_ready_to_dispense', $queuePaidData[0]['stage']);

        // الصيدلي يؤكد تسليم الأدوية للمريض
        $handoffResponse = $this->actingAs($this->adminUser)
            ->postJson(route('pharmacy.pos.sales.handoff', $sale->id));
        $handoffResponse->assertStatus(200);
        $handoffResponse->assertJson(['success' => true]);

        $this->assertEquals('dispensed', $sale->fresh()->dispensing_status);
        $this->assertEquals('dispensed', $prescription->fresh()->status);
    }

    public function test_cannot_handoff_unpaid_sale()
    {
        $sale = PharmacySale::create([
            'invoice_number' => 'PH-20261125-0040',
            'patient_name' => 'مريض لم يدفع',
            'sale_type' => 'direct_otc',
            'total_amount' => 2000,
            'patient_share' => 2000,
            'insurance_share' => 0,
            'payment_status' => 'pending_cashier',
            'payment_route' => 'central_cashier',
            'dispensing_status' => 'pending',
            'is_held' => false,
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('pharmacy.pos.sales.handoff', $sale->id));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }
}
