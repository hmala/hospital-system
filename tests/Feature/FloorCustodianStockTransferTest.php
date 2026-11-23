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
        Role::firstOrCreate(['name' => 'surgery_staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'head_nurse', 'guard_name' => 'web']);
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

    public function test_floor_nurse_can_view_edit_page_for_her_pending_stock_request(): void
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

        $req = StockTransferRequest::create([
            'from_location_id' => $mainLocation->id,
            'to_location_id' => $subFloor3->id,
            'requested_by' => $nurseMaryam->id,
            'items' => [['product_id' => $product->id, 'qty' => 5]],
            'status' => 'pending',
        ]);

        $response = $this->actingAs($nurseMaryam)->get(route('stock-transfers.requests.edit', $req));

        $response->assertStatus(200);
        $response->assertSee('تعديل طلبية تعزيز المخزون #'.$req->id);
        $response->assertSee('كانولا زرقاء');
        $response->assertSee('حفظ تعديلات الطلبية');
    }

    public function test_floor_nurse_can_successfully_update_her_pending_request(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');

        $product1 = Product::create([
            'name' => 'كانولا زرقاء',
            'code' => 'CAN-001',
            'unit' => 'قطعة',
        ]);
        $product2 = Product::create([
            'name' => 'سلاين نورمل',
            'code' => 'SAL-001',
            'unit' => 'فيال',
        ]);

        StockBatch::create([
            'product_id' => $product1->id,
            'location_id' => $mainLocation->id,
            'internal_barcode' => 'BATCH-TEST-003',
            'cost_price' => 500,
            'initial_qty' => 50,
            'current_qty' => 50,
            'expiry_date' => now()->addYear(),
            'received_at' => now(),
        ]);
        StockBatch::create([
            'product_id' => $product2->id,
            'location_id' => $mainLocation->id,
            'internal_barcode' => 'BATCH-TEST-004',
            'cost_price' => 1200,
            'initial_qty' => 30,
            'current_qty' => 30,
            'expiry_date' => now()->addYear(),
            'received_at' => now(),
        ]);

        $req = StockTransferRequest::create([
            'from_location_id' => $mainLocation->id,
            'to_location_id' => $subFloor3->id,
            'requested_by' => $nurseMaryam->id,
            'items' => [['product_id' => $product1->id, 'qty' => 5]],
            'status' => 'pending',
        ]);

        $updateResponse = $this->actingAs($nurseMaryam)->put(route('stock-transfers.requests.update', $req), [
            'items' => [
                ['product_id' => $product1->id, 'qty' => 12],
                ['product_id' => $product2->id, 'qty' => 6],
            ],
        ]);

        $updateResponse->assertRedirect(route('stock-transfers.requests.show', $req));
        $updateResponse->assertSessionHas('success');

        $req->refresh();
        $this->assertCount(2, $req->items);
        $this->assertEquals(12, $req->items[0]['qty']);
        $this->assertEquals(6, $req->items[1]['qty']);
    }

    public function test_floor_nurse_cannot_edit_approved_or_rejected_request(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');

        $product = Product::create([
            'name' => 'مادة اختبار',
            'code' => 'TST-001',
            'unit' => 'قطعة',
        ]);

        $req = StockTransferRequest::create([
            'from_location_id' => $mainLocation->id,
            'to_location_id' => $subFloor3->id,
            'requested_by' => $nurseMaryam->id,
            'items' => [['product_id' => $product->id, 'qty' => 5]],
            'status' => 'approved',
        ]);

        // Attempt to access edit page
        $response = $this->actingAs($nurseMaryam)->get(route('stock-transfers.requests.edit', $req));
        $response->assertRedirect(route('stock-transfers.requests.show', $req));

        // Attempt to submit update
        $updateResponse = $this->actingAs($nurseMaryam)->put(route('stock-transfers.requests.update', $req), [
            'items' => [['product_id' => $product->id, 'qty' => 20]],
        ]);
        $updateResponse->assertRedirect(route('stock-transfers.requests.show', $req));

        $req->refresh();
        $this->assertEquals(5, $req->items[0]['qty']);
    }

    public function test_floor_nurse_can_cancel_her_pending_request(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');

        $req = StockTransferRequest::create([
            'from_location_id' => $mainLocation->id,
            'to_location_id' => $subFloor3->id,
            'requested_by' => $nurseMaryam->id,
            'items' => [['product_id' => 1, 'qty' => 5]],
            'status' => 'pending',
        ]);

        $deleteResponse = $this->actingAs($nurseMaryam)->delete(route('stock-transfers.requests.destroy', $req));

        $deleteResponse->assertRedirect(route('stock-transfers.requests.index'));
        $this->assertDatabaseMissing('stock_transfer_requests', [
            'id' => $req->id,
        ]);
    }

    public function test_floor_nurse_cannot_edit_or_delete_other_floors_request(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);
        $subFloor2 = Location::create(['name' => 'ردهة الطابق الثاني', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');

        $otherUser = User::factory()->create(['name' => 'ممرض آخر', 'location_id' => $subFloor2->id]);
        $otherUser->assignRole('nurse');

        $otherReq = StockTransferRequest::create([
            'from_location_id' => $mainLocation->id,
            'to_location_id' => $subFloor2->id,
            'requested_by' => $otherUser->id,
            'items' => [['product_id' => 1, 'qty' => 5]],
            'status' => 'pending',
        ]);

        // Attempt edit
        $editResponse = $this->actingAs($nurseMaryam)->get(route('stock-transfers.requests.edit', $otherReq));
        $editResponse->assertStatus(403);

        // Attempt update
        $updateResponse = $this->actingAs($nurseMaryam)->put(route('stock-transfers.requests.update', $otherReq), [
            'items' => [['product_id' => 1, 'qty' => 10]],
        ]);
        $updateResponse->assertStatus(403);

        // Attempt delete
        $deleteResponse = $this->actingAs($nurseMaryam)->delete(route('stock-transfers.requests.destroy', $otherReq));
        $deleteResponse->assertStatus(403);
    }

    public function test_warehouse_manager_cannot_edit_or_delete_sub_store_requests(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');

        $warehouseManager = User::factory()->create([
            'name' => 'أمين المستودع الرئيسي',
            'location_id' => $mainLocation->id,
        ]);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage inventory', 'guard_name' => 'web']);
        $warehouseManager->givePermissionTo('manage inventory');

        $req = StockTransferRequest::create([
            'from_location_id' => $mainLocation->id,
            'to_location_id' => $subFloor3->id,
            'requested_by' => $nurseMaryam->id,
            'items' => [['product_id' => 1, 'qty' => 5]],
            'status' => 'pending',
        ]);

        // Warehouse manager views requests list: sees 'عرض', but DOES NOT see 'تعديل' or 'إلغاء' buttons for floor 3
        $indexResponse = $this->actingAs($warehouseManager)->get(route('stock-transfers.requests.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee(route('stock-transfers.requests.show', $req));
        $indexResponse->assertDontSee(route('stock-transfers.requests.edit', $req));
        $indexResponse->assertDontSee('title="إلغاء وحذف الطلبية"', false);

        // Warehouse manager views show page: sees approve/reject buttons, but DOES NOT see edit or cancel
        $showResponse = $this->actingAs($warehouseManager)->get(route('stock-transfers.requests.show', $req));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('الموافقة على الطلب وصرف المواد');
        $showResponse->assertSee('رفض الطلب');
        $showResponse->assertDontSee(route('stock-transfers.requests.edit', $req));
        $showResponse->assertDontSee('إلغاء الطلبية');

        // Warehouse manager attempting to visit edit URL is forbidden (403)
        $editResponse = $this->actingAs($warehouseManager)->get(route('stock-transfers.requests.edit', $req));
        $editResponse->assertStatus(403);

        // Warehouse manager attempting to update is forbidden (403)
        $updateResponse = $this->actingAs($warehouseManager)->put(route('stock-transfers.requests.update', $req), [
            'items' => [['product_id' => 1, 'qty' => 20]],
        ]);
        $updateResponse->assertStatus(403);

        // Warehouse manager attempting to delete is forbidden (403)
        $deleteResponse = $this->actingAs($warehouseManager)->delete(route('stock-transfers.requests.destroy', $req));
        $deleteResponse->assertStatus(403);
    }

    public function test_sub_store_can_request_items_exceeding_main_stock_and_main_warehouse_can_adjust_quantity(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $subFloor4 = Location::create(['name' => 'ردهة الطابق الرابع', 'type' => 'sub']);

        $nurseAli = User::factory()->create([
            'name' => 'علي الممرض',
            'location_id' => $subFloor4->id,
        ]);
        $nurseAli->assignRole('nurse');

        $warehouseManager = User::factory()->create([
            'name' => 'أمين المستودع المركزي',
        ]);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage inventory', 'guard_name' => 'web']);
        $warehouseManager->givePermissionTo('manage inventory');

        $product = Product::create([
            'name' => 'محلول ملحي نورمال سلاين',
            'code' => 'SAL-500',
            'unit' => 'قنينة',
        ]);

        // Main warehouse only has 15 bottles
        StockBatch::create([
            'product_id' => $product->id,
            'location_id' => $mainLocation->id,
            'internal_barcode' => 'BATCH-SAL-001',
            'cost_price' => 1000,
            'initial_qty' => 15,
            'current_qty' => 15,
            'expiry_date' => now()->addYear(),
            'received_at' => now(),
        ]);

        // 1. Nurse requests 40 bottles (exceeds available 15)
        $storeResponse = $this->actingAs($nurseAli)->post(route('stock-transfers.requests.store'), [
            'to_location_id' => $subFloor4->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 40],
            ],
        ]);

        $storeResponse->assertRedirect(route('stock-transfers.requests.index'));
        $storeResponse->assertSessionHas('success');

        $transferRequest = StockTransferRequest::first();
        $this->assertNotNull($transferRequest);
        $this->assertEquals('pending', $transferRequest->status);
        $this->assertEquals(40, $transferRequest->items[0]['original_qty']);
        $this->assertEquals(40, $transferRequest->items[0]['qty']);

        // 2. Warehouse manager views request show page: sees shortage alert and available qty
        $managerShow = $this->actingAs($warehouseManager)->get(route('stock-transfers.requests.show', $transferRequest));
        $managerShow->assertStatus(200);
        $managerShow->assertSee('المطلوب أكثر من المتوفر');
        $managerShow->assertSee('15'); // Available in main

        // 3. Warehouse manager approves with modified qty = 15
        $approveResponse = $this->actingAs($warehouseManager)->post(route('stock-transfers.requests.approve', $transferRequest), [
            'approved_items' => [
                0 => [
                    'qty' => 15,
                    'admin_notes' => 'تم الصرف حسب الرصيد المتوفر بالمخزن الرئيسي',
                ],
            ],
        ]);

        $approveResponse->assertRedirect(route('stock-transfers.requests.show', $transferRequest));
        $approveResponse->assertSessionHas('success');

        $transferRequest->refresh();
        $this->assertEquals('approved', $transferRequest->status);
        $this->assertEquals(40, $transferRequest->items[0]['original_qty']);
        $this->assertEquals(15, $transferRequest->items[0]['qty']);
        $this->assertTrue($transferRequest->items[0]['modified_by_admin']);
        $this->assertEquals('تم الصرف حسب الرصيد المتوفر بالمخزن الرئيسي', $transferRequest->items[0]['admin_notes']);

        // Main warehouse stock is now 0, and sub-location stock is now 15
        $this->assertEquals(0, StockBatch::where('location_id', $mainLocation->id)->sum('current_qty'));
        $this->assertEquals(15, StockBatch::where('location_id', $subFloor4->id)->sum('current_qty'));

        // 4. Nurse views request: sees modified banner, original qty, dispensed qty, and note
        $nurseShow = $this->actingAs($nurseAli)->get(route('stock-transfers.requests.show', $transferRequest));
        $nurseShow->assertStatus(200);
        $nurseShow->assertSee('تنبيه: تم تعديل كمية المواد المصروفة من قِبل موظف المخزن الرئيسي');
        $nurseShow->assertSee('تم تعديل الكمية من المخزن الرئيسي');
        $nurseShow->assertSee('تم الصرف حسب الرصيد المتوفر بالمخزن الرئيسي');

        // 5. Nurse views index: sees 'تم تعديل الكمية' badge and 'بانتظار استلام القسم'
        $nurseIndex = $this->actingAs($nurseAli)->get(route('stock-transfers.requests.index'));
        $nurseIndex->assertStatus(200);
        $nurseIndex->assertSee('تم تعديل الكمية');
        $nurseIndex->assertSee('بانتظار استلام القسم');

        // 6. Nurse confirms receipt of modified quantities
        $receiveResponse = $this->actingAs($nurseAli)->post(route('stock-transfers.requests.receive', $transferRequest), [
            'received_notes' => 'تم استلام وفحص الـ 15 أمبول في ردهة الطابق الرابع',
        ]);
        $receiveResponse->assertRedirect(route('stock-transfers.requests.show', $transferRequest));
        $receiveResponse->assertSessionHas('success');

        $transferRequest->refresh();
        $this->assertEquals('completed', $transferRequest->status);
        $this->assertEquals($nurseAli->id, $transferRequest->received_by);
        $this->assertNotNull($transferRequest->received_at);
        $this->assertEquals('تم استلام وفحص الـ 15 أمبول في ردهة الطابق الرابع', $transferRequest->received_notes);

        // 7. Nurse views show and index: sees 'مستلم ومودع بالعهدة'
        $completedShow = $this->actingAs($nurseAli)->get(route('stock-transfers.requests.show', $transferRequest));
        $completedShow->assertStatus(200);
        $completedShow->assertSee('مستلم ومودع بالعهدة');
        $completedShow->assertSee('تم استلام وفحص الـ 15 أمبول في ردهة الطابق الرابع');
    }

    public function test_surgery_staff_can_confirm_receipt_and_unrelated_nurse_is_forbidden(): void
    {
        $mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $orLocation = Location::create(['name' => 'صالة العمليات الكبرى', 'type' => 'sub']);
        $otherFloor = Location::create(['name' => 'ردهة الطابق الأول', 'type' => 'sub']);

        $surgeryNurse = User::factory()->create([
            'name' => 'ممرض العمليات سامي',
            'location_id' => $orLocation->id,
        ]);
        $surgeryNurse->assignRole('surgery_staff');

        $otherNurse = User::factory()->create([
            'name' => 'ممرضة ردهة أخرى',
            'location_id' => $otherFloor->id,
        ]);
        $otherNurse->assignRole('nurse');

        $product = Product::create([
            'name' => 'خيط جراحي معقم',
            'code' => 'SURG-001',
            'unit' => 'خيط',
        ]);

        $transferRequest = StockTransferRequest::create([
            'from_location_id' => $mainLocation->id,
            'to_location_id' => $orLocation->id,
            'requested_by' => $surgeryNurse->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'original_qty' => 20,
                    'qty' => 10,
                    'modified_by_admin' => true,
                    'admin_notes' => 'صرف 10 خيوط فقط حسب المتاح',
                ],
            ],
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // Unrelated nurse cannot confirm receipt
        $forbiddenResponse = $this->actingAs($otherNurse)->post(route('stock-transfers.requests.receive', $transferRequest));
        $forbiddenResponse->assertStatus(403);

        // Surgery nurse confirms receipt
        $confirmResponse = $this->actingAs($surgeryNurse)->post(route('stock-transfers.requests.receive', $transferRequest), [
            'received_notes' => 'تم استلام الخيوط الجراحية في صالة العمليات الكبرى',
        ]);
        $confirmResponse->assertRedirect(route('stock-transfers.requests.show', $transferRequest));

        $transferRequest->refresh();
        $this->assertEquals('completed', $transferRequest->status);
        $this->assertEquals($surgeryNurse->id, $transferRequest->received_by);
        $this->assertEquals('تم استلام الخيوط الجراحية في صالة العمليات الكبرى', $transferRequest->received_notes);
    }
}
