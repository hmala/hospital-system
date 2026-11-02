<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockTransferRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FloorCustodianStockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
    }

    public function test_floor_nurse_sees_locked_destination_sub_store_on_create_page(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);
        $subFloor2 = Location::create(['name' => 'ردهة الطابق الثاني', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');

        $response = $this->actingAs($nurseMaryam)->get(route('stock-transfers.create'));

        $response->assertStatus(200);
        $response->assertSee('المخزن الفرعي المستلم (عهدتك المعتمدة)');
        $response->assertSee('ردهة الطابق الثالث');
        $response->assertSee('محدد تلقائياً');
        $response->assertSee('type="hidden" name="to_location_id" value="'.$subFloor3->id.'"', false);
        // Ensure no open select dropdown is displayed for destination
        $response->assertDontSee('<select name="to_location_id"', false);
    }

    public function test_floor_nurse_cannot_tamper_to_request_for_another_location(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);
        $subFloor2 = Location::create(['name' => 'ردهة الطابق الثاني', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');

        $product = Product::create([
            'name' => 'باراسيتامول فيال',
            'code' => 'MED-001',
            'unit' => 'فيال',
        ]);
        StockBatch::create([
            'product_id' => $product->id,
            'location_id' => $mainLocation->id,
            'internal_barcode' => 'BATCH-TEST-001',
            'cost_price' => 1000,
            'initial_qty' => 100,
            'current_qty' => 100,
            'expiry_date' => now()->addYear(),
            'received_at' => now(),
        ]);

        // Attempt to forge request for subFloor2 instead of Maryam's floor 3
        $response = $this->actingAs($nurseMaryam)->post(route('stock-transfers.requests.store'), [
            'to_location_id' => $subFloor2->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 5],
            ],
        ]);

        $response->assertSessionHasErrors('to_location_id');
        $this->assertEquals(0, StockTransferRequest::count());
    }

    public function test_floor_nurse_can_successfully_submit_request_for_assigned_sub_store(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');

        $product = Product::create([
            'name' => 'كانولا زرقاء',
            'code' => 'CAN-001',
            'unit' => 'قطعة',
        ]);
        StockBatch::create([
            'product_id' => $product->id,
            'location_id' => $mainLocation->id,
            'internal_barcode' => 'BATCH-TEST-002',
            'cost_price' => 500,
            'initial_qty' => 50,
            'current_qty' => 50,
            'expiry_date' => now()->addYear(),
            'received_at' => now(),
        ]);

        $response = $this->actingAs($nurseMaryam)->post(route('stock-transfers.requests.store'), [
            'to_location_id' => $subFloor3->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 10],
            ],
        ]);

        $response->assertRedirect(route('stock-transfers.requests.index'));
        $this->assertDatabaseHas('stock_transfer_requests', [
            'to_location_id' => $subFloor3->id,
            'from_location_id' => $mainLocation->id,
            'status' => 'pending',
            'requested_by' => $nurseMaryam->id,
        ]);
    }

    public function test_floor_nurse_only_sees_her_floor_requests_in_requests_index(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);
        $subFloor2 = Location::create(['name' => 'ردهة الطابق الثاني', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');

        $otherUser = User::factory()->create(['name' => 'ممرض الطابق الثاني', 'location_id' => $subFloor2->id]);
        $otherUser->assignRole('nurse');

        // Request for Floor 3
        $reqFloor3 = StockTransferRequest::create([
            'from_location_id' => $mainLocation->id,
            'to_location_id' => $subFloor3->id,
            'requested_by' => $nurseMaryam->id,
            'items' => [['product_id' => 1, 'qty' => 5]],
            'status' => 'pending',
        ]);

        // Request for Floor 2
        $reqFloor2 = StockTransferRequest::create([
            'from_location_id' => $mainLocation->id,
            'to_location_id' => $subFloor2->id,
            'requested_by' => $otherUser->id,
            'items' => [['product_id' => 1, 'qty' => 8]],
            'status' => 'pending',
        ]);

        $response = $this->actingAs($nurseMaryam)->get(route('stock-transfers.requests.index'));

        $response->assertStatus(200);
        $response->assertSee('ردهة الطابق الثالث');
        $this->assertTrue($response->viewData('requests')->contains('id', $reqFloor3->id));
        $this->assertFalse($response->viewData('requests')->contains('id', $reqFloor2->id));
        $response->assertDontSee('>#'.$reqFloor2->id.'<', false);
    }

    public function test_floor_nurse_cannot_view_or_approve_other_floor_requests(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);
        $subFloor2 = Location::create(['name' => 'ردهة الطابق الثاني', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');

        $otherUser = User::factory()->create(['name' => 'ممرض آخر']);
        $otherUser->assignRole('nurse');

        $reqFloor2 = StockTransferRequest::create([
            'from_location_id' => $mainLocation->id,
            'to_location_id' => $subFloor2->id,
            'requested_by' => $otherUser->id,
            'items' => [['product_id' => 1, 'qty' => 8]],
            'status' => 'pending',
        ]);

        // Cannot view other floor's request
        $response = $this->actingAs($nurseMaryam)->get(route('stock-transfers.requests.show', $reqFloor2));
        $response->assertStatus(403);

        // Cannot approve request
        $approveResponse = $this->actingAs($nurseMaryam)->post(route('stock-transfers.requests.approve', $reqFloor2));
        $approveResponse->assertStatus(403);
    }
}
