<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockTransferRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FloorStockReturnWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'view nursing station', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage inventory', 'guard_name' => 'web']);
    }

    public function test_floor_nurse_sees_scoped_floor_batches_on_return_create_page(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $floor3Location = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);
        $floor2Location = Location::create(['name' => 'ردهة الطابق الثاني', 'type' => 'sub']);

        $nurse = User::factory()->create([
            'name' => 'ممرض الطابق الثالث',
            'location_id' => $floor3Location->id,
        ]);
        $nurse->assignRole('nurse');

        $prod1 = Product::create(['name' => 'ترامادول أمبول', 'code' => 'TRAM-01', 'unit' => 'أمبول']);
        $prod2 = Product::create(['name' => 'سيروم ملحي', 'code' => 'SALINE-01', 'unit' => 'قنينة']);

        // Batch in Floor 3 (available)
        $batchFloor3 = StockBatch::create([
            'product_id' => $prod1->id,
            'location_id' => $floor3Location->id,
            'internal_barcode' => 'BATCH-F3-001',
            'cost_price' => 2000,
            'initial_qty' => 10,
            'current_qty' => 8,
            'expiry_date' => now()->addMonths(6),
            'received_at' => now(),
        ]);

        // Batch in Floor 2 (should NOT appear for Floor 3 nurse)
        $batchFloor2 = StockBatch::create([
            'product_id' => $prod2->id,
            'location_id' => $floor2Location->id,
            'internal_barcode' => 'BATCH-F2-999',
            'cost_price' => 1500,
            'initial_qty' => 20,
            'current_qty' => 20,
            'expiry_date' => now()->addMonths(12),
            'received_at' => now(),
        ]);

        $response = $this->actingAs($nurse)->get(route('stock-transfers.returns.create', [
            'from_location_id' => $floor3Location->id,
            'batch_id' => $batchFloor3->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('إرجاع مواد وعُهدة إلى المخزن الرئيسي');
        $response->assertSee('ردهة الطابق الثالث');
        $response->assertSee('ترامادول أمبول');
        $response->assertSee('BATCH-F3-001');
        // Floor 2 batch must not be listed in Floor 3's available return options
        $response->assertDontSee('BATCH-F2-999');
    }

    public function test_floor_nurse_can_submit_multi_item_return_request_with_reasons(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $floor3Location = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurse = User::factory()->create([
            'name' => 'ممرض الطابق الثالث',
            'location_id' => $floor3Location->id,
        ]);
        $nurse->assignRole('nurse');

        $prod1 = Product::create(['name' => 'ترامادول أمبول', 'code' => 'TRAM-01', 'unit' => 'أمبول']);
        $prod2 = Product::create(['name' => 'كانيولا وريدية 20G', 'code' => 'CAN-20', 'unit' => 'قطعة']);

        $batch1 = StockBatch::create([
            'product_id' => $prod1->id,
            'location_id' => $floor3Location->id,
            'internal_barcode' => 'BATCH-TRAM-1',
            'cost_price' => 2000,
            'initial_qty' => 10,
            'current_qty' => 10,
            'expiry_date' => now()->addMonths(6),
            'received_at' => now(),
        ]);

        $batch2 = StockBatch::create([
            'product_id' => $prod2->id,
            'location_id' => $floor3Location->id,
            'internal_barcode' => 'BATCH-CAN-2',
            'cost_price' => 500,
            'initial_qty' => 50,
            'current_qty' => 30,
            'expiry_date' => now()->addYear(),
            'received_at' => now(),
        ]);

        $payload = [
            'from_location_id' => $floor3Location->id,
            'to_location_id' => $mainLocation->id,
            'from_nursing' => 1,
            'items' => [
                [
                    'product_id' => $prod1->id,
                    'batch_id' => $batch1->id,
                    'qty' => 4,
                    'reason' => 'إلغاء حجز العملية الجراحية',
                    'notes' => 'المريض خرج بدون إجراء التداخل',
                ],
                [
                    'product_id' => $prod2->id,
                    'batch_id' => $batch2->id,
                    'qty' => 10,
                    'reason' => 'فائض عن حاجة المريض / خروج المريض',
                    'notes' => 'فائض من طبلة الرقود',
                ],
            ],
        ];

        $response = $this->actingAs($nurse)->post(route('stock-transfers.returns.store'), $payload);

        $response->assertRedirect(route('nursing-station.index', ['location_id' => $floor3Location->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_transfer_requests', [
            'from_location_id' => $floor3Location->id,
            'to_location_id' => $mainLocation->id,
            'requested_by' => $nurse->id,
            'status' => 'pending',
        ]);

        $createdRequest = StockTransferRequest::latest('id')->first();
        $this->assertCount(2, $createdRequest->items);
        $this->assertEquals(4, $createdRequest->items[0]['qty']);
        $this->assertEquals('إلغاء حجز العملية الجراحية', $createdRequest->items[0]['reason']);
        $this->assertEquals(10, $createdRequest->items[1]['qty']);
    }

    public function test_return_request_fails_if_quantity_exceeds_available_floor_stock(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $floor3Location = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurse = User::factory()->create([
            'name' => 'ممرض الطابق الثالث',
            'location_id' => $floor3Location->id,
        ]);
        $nurse->assignRole('nurse');

        $prod = Product::create(['name' => 'ترامادول أمبول', 'code' => 'TRAM-01', 'unit' => 'أمبول']);

        $batch = StockBatch::create([
            'product_id' => $prod->id,
            'location_id' => $floor3Location->id,
            'internal_barcode' => 'BATCH-TRAM-1',
            'cost_price' => 2000,
            'initial_qty' => 5,
            'current_qty' => 5,
            'expiry_date' => now()->addMonths(6),
            'received_at' => now(),
        ]);

        // Attempting to return 10 when only 5 is available in this batch
        $payload = [
            'from_location_id' => $floor3Location->id,
            'to_location_id' => $mainLocation->id,
            'items' => [
                [
                    'product_id' => $prod->id,
                    'batch_id' => $batch->id,
                    'qty' => 10,
                    'reason' => 'فائض',
                ],
            ],
        ];

        $response = $this->actingAs($nurse)->post(route('stock-transfers.returns.store'), $payload);

        $response->assertSessionHasErrors(['qty']);
        $this->assertEquals(0, StockTransferRequest::count());
    }

    public function test_main_warehouse_admin_approves_return_and_stock_is_restored_to_main_warehouse(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $floor3Location = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurse = User::factory()->create([
            'name' => 'ممرض الطابق الثالث',
            'location_id' => $floor3Location->id,
        ]);
        $nurse->assignRole('nurse');

        $warehouseManager = User::factory()->create(['name' => 'أمين المخزن الرئيسي']);
        $warehouseManager->assignRole('admin');

        $prod = Product::create(['name' => 'ترامادول أمبول', 'code' => 'TRAM-01', 'unit' => 'أمبول']);

        $floorBatch = StockBatch::create([
            'product_id' => $prod->id,
            'location_id' => $floor3Location->id,
            'internal_barcode' => 'BATCH-F3-100',
            'cost_price' => 2000,
            'initial_qty' => 15,
            'current_qty' => 15,
            'expiry_date' => now()->addMonths(8),
            'received_at' => now(),
        ]);

        $transferRequest = StockTransferRequest::create([
            'from_location_id' => $floor3Location->id,
            'to_location_id' => $mainLocation->id,
            'requested_by' => $nurse->id,
            'status' => 'pending',
            'items' => [
                [
                    'product_id' => $prod->id,
                    'batch_id' => $floorBatch->id,
                    'qty' => 5,
                    'reason' => 'إلغاء حجز العملية الجراحية',
                ],
            ],
        ]);

        // Manager approves the return
        $response = $this->actingAs($warehouseManager)->post(route('stock-transfers.requests.approve', $transferRequest));

        $response->assertSessionHasNoErrors();
        $this->assertEquals('approved', $transferRequest->fresh()->status);
        $this->assertEquals($warehouseManager->id, $transferRequest->fresh()->approved_by);

        // Floor batch reduced by 5: 15 - 5 = 10
        $this->assertEquals(10, $floorBatch->fresh()->current_qty);

        // Movement logged
        $this->assertDatabaseHas('stock_movements', [
            'from_location_id' => $floor3Location->id,
            'to_location_id' => $mainLocation->id,
            'quantity' => 5,
            'type' => 'transfer',
        ]);

        // Stock in main warehouse now exists for this batch
        $mainBatches = StockBatch::where('location_id', $mainLocation->id)->where('product_id', $prod->id)->get();
        $this->assertEquals(5, $mainBatches->sum('current_qty'));
    }

    public function test_nursing_station_tab4_renders_instant_search_and_filter_elements(): void
    {
        $floor3Location = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurse = User::factory()->create([
            'name' => 'ممرض الطابق الثالث',
            'location_id' => $floor3Location->id,
        ]);
        $nurse->givePermissionTo('view nursing station');

        $prod = Product::create([
            'name' => 'باراسيتامول وريدي',
            'code' => 'PARA-IV',
            'category' => 'أدوية',
            'unit' => 'فيال',
        ]);

        StockBatch::create([
            'product_id' => $prod->id,
            'location_id' => $floor3Location->id,
            'internal_barcode' => 'PARA-IV-B1',
            'cost_price' => 1000,
            'initial_qty' => 20,
            'current_qty' => 20,
            'expiry_date' => now()->addYear(),
            'received_at' => now(),
        ]);

        $response = $this->actingAs($nurse)->get(route('nursing-station.index', [
            'location_id' => $floor3Location->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('floorStockSearchInput');
        $response->assertSee('stock-filter-btn');
        $response->assertSee('باراسيتامول وريدي');
        $response->assertSee('إرجاع عهدة للمخزن الرئيسي');
        $response->assertSee(route('stock-transfers.returns.create', [
            'from_location_id' => $floor3Location->id,
            'from_nursing' => 1,
        ]));
    }
}
