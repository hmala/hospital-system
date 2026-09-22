<?php

namespace Tests\Feature;

use App\Models\HealthInsuranceCategory;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\PharmacySale;
use App\Models\PharmacyService;
use App\Models\User;
use Database\Seeders\HealthInsuranceCategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyPosTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $medicine;
    protected $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(HealthInsuranceCategorySeeder::class);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->adminUser->assignRole('admin');

        $this->medicine = Medicine::create([
            'name' => 'Amoxicillin 500mg',
            'generic_name' => 'Amoxicillin',
            'national_code' => '01-C00-038',
            'barcode' => '628100999001',
            'sub_barcode' => '628100999002',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2,
            'cost_price' => 2000,
            'sale_price' => 3000,
            'sub_unit_sale_price' => 1500,
            'hi_price' => 2500,
            'is_insurance_covered' => true,
        ]);

        $this->batch = MedicineBatch::create([
            'medicine_id' => $this->medicine->id,
            'batch_number' => 'LOT-AMX-2026',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'initial_quantity' => 50,
            'current_quantity' => 50,
            'current_sub_units' => 0,
            'purchase_price' => 2000,
            'status' => 'active',
        ]);
    }

    public function test_can_view_pos_screen()
    {
        $response = $this->actingAs($this->adminUser)->get(route('pharmacy.pos.index'));
        $response->assertStatus(200);
        $response->assertSee('نقطة بيع وصرف الصيدلية');
    }

    public function test_pos_search_by_barcode_returns_medicine_with_batches_and_alternatives()
    {
        $response = $this->actingAs($this->adminUser)->getJson(route('pharmacy.pos.search', ['q' => '628100999001']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Amoxicillin 500mg']);
        $response->assertJsonStructure([
            'medicines' => [
                '*' => [
                    'id', 'name', 'sale_price', 'sub_unit_sale_price', 'total_stock', 'earliest_batch', 'alternatives'
                ]
            ],
            'services'
        ]);
    }

    public function test_can_process_cash_sale_and_deduct_batch_stock()
    {
        $payload = [
            'sale_type' => 'direct_otc',
            'patient_name' => 'مريض كاش',
            'insurance_type' => 'none',
            'payment_route' => 'pharmacy_cashier',
            'is_held' => 0,
            'items' => [
                [
                    'item_type' => 'medicine',
                    'medicine_id' => $this->medicine->id,
                    'unit_type' => 'main_unit',
                    'quantity' => 2,
                ]
            ]
        ];

        $response = $this->actingAs($this->adminUser)->postJson(route('pharmacy.pos.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('pharmacy_sales', [
            'patient_name' => 'مريض كاش',
            'total_amount' => 6000,
            'patient_share' => 6000,
            'insurance_share' => 0,
            'payment_status' => 'paid',
        ]);

        // تم خصم علبتين من الوجبة: 50 - 2 = 48
        $this->assertEquals(48, $this->batch->fresh()->current_quantity);
    }

    public function test_can_process_health_insurance_sale_with_copay_calculation()
    {
        $categoryE = HealthInsuranceCategory::where('code', 'E')->first(); // استقطاع 25%

        $payload = [
            'sale_type' => 'prescription',
            'patient_name' => 'مريض ضمان',
            'insurance_type' => 'health_insurance',
            'health_insurance_category_id' => $categoryE->id,
            'copay_percentage' => 25.0,
            'payment_route' => 'pharmacy_cashier',
            'is_held' => 0,
            'items' => [
                [
                    'item_type' => 'medicine',
                    'medicine_id' => $this->medicine->id,
                    'unit_type' => 'main_unit',
                    'quantity' => 1,
                ]
            ]
        ];

        $response = $this->actingAs($this->adminUser)->postJson(route('pharmacy.pos.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // السعر المعتمد للضمان 2500، استقطاع 25% = 625، وحصة الضمان 1875
        $this->assertDatabaseHas('pharmacy_sales', [
            'patient_name' => 'مريض ضمان',
            'total_amount' => 2500,
            'patient_share' => 625,
            'insurance_share' => 1875,
            'claim_status' => 'pending',
        ]);
    }

    public function test_can_hold_bill_and_resume_it()
    {
        $payload = [
            'sale_type' => 'direct_otc',
            'patient_name' => 'زبون معلق',
            'insurance_type' => 'none',
            'payment_route' => 'pharmacy_cashier',
            'is_held' => 1,
            'items' => [
                [
                    'item_type' => 'medicine',
                    'medicine_id' => $this->medicine->id,
                    'unit_type' => 'main_unit',
                    'quantity' => 1,
                ]
            ]
        ];

        $response = $this->actingAs($this->adminUser)->postJson(route('pharmacy.pos.store'), $payload);
        $response->assertStatus(200);

        $heldSale = PharmacySale::where('patient_name', 'زبون معلق')->first();
        $this->assertTrue($heldSale->is_held);

        // استئناف الفاتورة
        $resumeResponse = $this->actingAs($this->adminUser)->getJson(route('pharmacy.pos.held.resume', $heldSale->id));
        $resumeResponse->assertStatus(200);
        $resumeResponse->assertJson(['success' => true]);
        $resumeResponse->assertJsonFragment(['patient_name' => 'زبون معلق']);
    }

    public function test_can_print_receipt_and_view_sales_history()
    {
        $sale = PharmacySale::create([
            'invoice_number' => 'PH-20260920-9999',
            'patient_name' => 'مريض تجريبي',
            'sale_type' => 'direct_otc',
            'total_amount' => 3000,
            'patient_share' => 3000,
            'insurance_share' => 0,
            'user_id' => $this->adminUser->id,
        ]);

        $printResponse = $this->actingAs($this->adminUser)->get(route('pharmacy.pos.sales.print', $sale->id));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('PH-20260920-9999');

        $historyResponse = $this->actingAs($this->adminUser)->get(route('pharmacy.pos.sales.history'));
        $historyResponse->assertStatus(200);
        $historyResponse->assertSee('PH-20260920-9999');
    }
}
