<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseDuplicateAndBatchSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);

        Location::firstOrCreate([
            'name' => 'المخزن الرئيسي',
            'type' => 'main',
        ]);
    }

    public function test_purchase_creation_supports_multiple_lines_for_same_product_with_independent_batches()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $supplier = Supplier::create(['name' => 'شركة التوريد']);
        $product = Product::create([
            'name' => 'شاش طبي',
            'code' => 'MED-GAUZE-01',
            'category' => 'مستلزمات',
            'unit' => 'قطعة',
            'is_perishable' => false,
        ]);

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-DUP-TEST-01',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 10,
                    'cost_price' => 100,
                ],
                [
                    'product_id' => $product->id,
                    'qty' => 20,
                    'cost_price' => 120,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('purchases', [
            'invoice_number' => 'INV-DUP-TEST-01',
        ]);

        $purchase = Purchase::where('invoice_number', 'INV-DUP-TEST-01')->first();
        $response->assertRedirect(route('purchases.show', $purchase->id));
        $this->assertCount(2, $purchase->items);

        foreach ($purchase->items as $item) {
            $this->assertEquals($product->id, $item->product_id);
            $this->assertNotNull($item->stockBatch);
            $this->assertEquals($product->id, $item->stockBatch->product_id);
            $this->assertEquals($item->id, $item->stockBatch->purchase_item_id);
        }
    }

    public function test_purchase_creation_succeeds_and_creates_matching_stock_batches()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $supplier = Supplier::create(['name' => 'شركة التوريد 2']);
        $prod1 = Product::create([
            'name' => 'كانيولا خضراء',
            'code' => 'CAN-GRN-01',
            'category' => 'مستلزمات',
            'unit' => 'قطعة',
            'is_perishable' => false,
        ]);
        $prod2 = Product::create([
            'name' => 'كانيولا زرقاء',
            'code' => 'CAN-BLU-01',
            'category' => 'مستلزمات',
            'unit' => 'قطعة',
            'is_perishable' => false,
        ]);

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-VALID-SYNC-01',
            'items' => [
                [
                    'product_id' => $prod1->id,
                    'qty' => 50,
                    'cost_price' => 500,
                ],
                [
                    'product_id' => $prod2->id,
                    'qty' => 75,
                    'cost_price' => 600,
                ],
            ],
        ]);

        $response->assertRedirect();
        $purchase = Purchase::where('invoice_number', 'INV-VALID-SYNC-01')->firstOrFail();

        $this->assertCount(2, $purchase->items);

        foreach ($purchase->items as $item) {
            $this->assertNotNull($item->stockBatch);
            $this->assertEquals($item->product_id, $item->stockBatch->product_id, 'StockBatch product_id must match PurchaseItem product_id');
            $this->assertEquals($item->qty, $item->stockBatch->current_qty);
        }
    }
}
