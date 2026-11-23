<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\LocationCustodianHistory;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NursingSubStoreGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء الأدوار والصلاحيات
        $perm = Permission::firstOrCreate(['name' => 'manage nursing sub stores', 'guard_name' => 'web']);
        $headNurseRole = Role::firstOrCreate(['name' => 'head_nurse', 'guard_name' => 'web']);
        $headNurseRole->givePermissionTo($perm);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])->givePermissionTo($perm);
        Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
    }

    public function test_head_nurse_and_admin_can_access_nursing_sub_stores_dashboard(): void
    {
        $headNurse = User::factory()->create(['name' => 'مشرف التمريض خليل']);
        $headNurse->assignRole('head_nurse');

        $subStore1 = Location::create(['name' => 'ردهة الطابق الثاني', 'type' => 'sub']);
        $subStore2 = Location::create(['name' => 'ردهة الطابق الرابع', 'type' => 'sub']);

        // مخازن لا تخص التمريض يجب استبعادها
        $orStore = Location::create(['name' => 'صالة العمليات 1', 'type' => 'sub']);
        $nicuStore = Location::create(['name' => 'الخدج', 'type' => 'sub']);
        $labStore = Location::create(['name' => 'المختبر', 'type' => 'sub']);

        $response = $this->actingAs($headNurse)->get(route('nursing-sub-stores.index'));

        $response->assertStatus(200);
        $response->assertSee('رقابة مخازن وعُهد ردهات التمريض');
        $response->assertSee('ردهة الطابق الثاني');
        $response->assertSee('ردهة الطابق الرابع');
        $response->assertSee('المخازن الفرعية النشطة');

        // التحقق من أن المخازن غير التابعة للتمريض تم استبعادها من قائمة المخازن
        $response->assertViewHas('subStores', function ($stores) use ($subStore1, $subStore2, $orStore, $nicuStore, $labStore) {
            return $stores->contains('id', $subStore1->id)
                && $stores->contains('id', $subStore2->id)
                && ! $stores->contains('id', $orStore->id)
                && ! $stores->contains('id', $nicuStore->id)
                && ! $stores->contains('id', $labStore->id);
        });
    }

    public function test_regular_nurse_or_unauthorized_user_gets_forbidden(): void
    {
        $regularNurse = User::factory()->create(['name' => 'ممرض اعتيادي']);
        $regularNurse->assignRole('nurse');

        $response = $this->actingAs($regularNurse)->get(route('nursing-sub-stores.index'));
        $response->assertStatus(403);
    }

    public function test_head_nurse_can_assign_and_change_sub_store_custodian_with_audit_log(): void
    {
        $headNurse = User::factory()->create(['name' => 'مشرف التمريض أحمد']);
        $headNurse->assignRole('head_nurse');

        $subStore = Location::create(['name' => 'ردهة الطابق الرابع', 'type' => 'sub']);

        $nurseAli = User::factory()->create(['name' => 'الممرض علي']);
        $nurseAli->assignRole('nurse');

        // تعيين علي مسؤولاً عن عهدة ردهة الطابق الرابع
        $assignResponse = $this->actingAs($headNurse)->post(route('nursing-sub-stores.assign-custodian', $subStore), [
            'custodian_id' => $nurseAli->id,
            'sync_user_location' => true,
            'notes' => 'تكليف أولي بالمسؤولية لنوبة الصباح',
        ]);

        $assignResponse->assertRedirect(route('nursing-sub-stores.index'));
        $assignResponse->assertSessionHas('success');

        $subStore->refresh();
        $this->assertEquals($nurseAli->id, $subStore->custodian_id);

        $nurseAli->refresh();
        $this->assertEquals($subStore->id, $nurseAli->location_id);

        // التحقق من الأرشيف وسجل التدقيق
        $this->assertDatabaseHas('location_custodian_histories', [
            'location_id' => $subStore->id,
            'user_id' => $nurseAli->id,
            'assigned_by' => $headNurse->id,
            'notes' => 'تكليف أولي بالمسؤولية لنوبة الصباح',
        ]);

        // تغيير المسؤول إلى ممرض آخر (سامي)
        $nurseSami = User::factory()->create(['name' => 'الممرض سامي']);
        $nurseSami->assignRole('nurse');

        $changeResponse = $this->actingAs($headNurse)->post(route('nursing-sub-stores.assign-custodian', $subStore), [
            'custodian_id' => $nurseSami->id,
            'sync_user_location' => true,
            'notes' => 'تبديل نوبة واستلام العهدة',
        ]);

        $changeResponse->assertRedirect(route('nursing-sub-stores.index'));

        $subStore->refresh();
        $this->assertEquals($nurseSami->id, $subStore->custodian_id);

        $this->assertCount(2, LocationCustodianHistory::where('location_id', $subStore->id)->get());
    }

    public function test_head_nurse_can_view_sub_store_details_and_consumption_movements(): void
    {
        $headNurse = User::factory()->create(['name' => 'مشرف التمريض يوسف']);
        $headNurse->assignRole('head_nurse');

        $subStore = Location::create(['name' => 'ردهة الطابق الخامس', 'type' => 'sub']);
        $nurse = User::factory()->create(['name' => 'ممرض ردهة', 'location_id' => $subStore->id]);
        $subStore->update(['custodian_id' => $nurse->id]);

        $product = Product::create(['name' => 'شاش معقم', 'code' => 'GAU-01', 'unit' => 'باكيت']);
        $batch = StockBatch::create([
            'product_id' => $product->id,
            'location_id' => $subStore->id,
            'internal_barcode' => 'B01-TEST',
            'current_qty' => 50,
            'initial_qty' => 50,
            'cost_price' => 10,
            'received_at' => now(),
            'expiry_date' => now()->addYear(),
        ]);

        StockMovement::create([
            'batch_id' => $batch->id,
            'from_location_id' => $subStore->id,
            'quantity' => 5,
            'type' => 'dispense',
            'user_id' => $nurse->id,
            'notes' => 'استهلاك مريض غرفة 102',
        ]);

        $response = $this->actingAs($headNurse)->get(route('nursing-sub-stores.show', $subStore));

        $response->assertStatus(200);
        $response->assertSee('ردهة الطابق الخامس');
        $response->assertSee('شاش معقم');
        $response->assertSee('50'); // Active qty
        $response->assertSee('-5'); // Dispensed qty
        $response->assertSee('ممرض ردهة');
    }
}
