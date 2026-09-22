<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyBatchTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $medicine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->adminUser->assignRole('admin');

        $this->medicine = Medicine::create([
            'name' => 'Ceftriaxone 1g Vial',
            'main_unit' => 'فيال',
            'sub_unit' => 'فيال',
            'sub_units_count' => 1,
            'cost_price' => 3000,
            'sale_price' => 5000,
        ]);
    }

    public function test_can_view_fefo_batches_dashboard()
    {
        MedicineBatch::create([
            'medicine_id' => $this->medicine->id,
            'batch_number' => 'LOT-CEF-001',
            'expiry_date' => now()->addDays(60)->toDateString(), // قريبة انتهاء
            'initial_quantity' => 100,
            'current_quantity' => 80,
            'purchase_price' => 3000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('pharmacy.batches.index'));

        $response->assertStatus(200);
        $response->assertSee('LOT-CEF-001');
        $response->assertSee('Ceftriaxone 1g Vial');
        $response->assertSee('تنبيه: قريبة الانتهاء');
    }

    public function test_can_supply_new_batch()
    {
        $response = $this->actingAs($this->adminUser)->post(route('pharmacy.batches.store'), [
            'medicine_id' => $this->medicine->id,
            'batch_number' => 'LOT-CEF-002',
            'expiry_date' => now()->addMonths(18)->toDateString(),
            'initial_quantity' => 50,
            'purchase_price' => 3200,
            'supplier_name' => 'شركة أدوية سامراء',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('pharmacy.batches.index'));
        $this->assertDatabaseHas('medicine_batches', [
            'batch_number' => 'LOT-CEF-002',
            'current_quantity' => 50,
            'supplier_name' => 'شركة أدوية سامراء',
        ]);
    }

    public function test_can_quarantine_and_reactivate_batch()
    {
        $batch = MedicineBatch::create([
            'medicine_id' => $this->medicine->id,
            'batch_number' => 'LOT-CEF-003',
            'expiry_date' => now()->addYear()->toDateString(),
            'initial_quantity' => 30,
            'current_quantity' => 30,
            'purchase_price' => 3000,
            'status' => 'active',
        ]);

        // حجر الوجبة
        $response = $this->actingAs($this->adminUser)->post(route('pharmacy.batches.status', $batch->id), [
            'status' => 'quarantined',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals('quarantined', $batch->fresh()->status);

        // إلغاء الحجر وإعادة التفعيل
        $response2 = $this->actingAs($this->adminUser)->post(route('pharmacy.batches.status', $batch->id), [
            'status' => 'active',
        ]);
        $response2->assertSessionHas('success');
        $this->assertEquals('active', $batch->fresh()->status);
    }

    public function test_can_filter_expiring_soon_batches()
    {
        // وجبة تنتهي بعد 30 يوم
        MedicineBatch::create([
            'medicine_id' => $this->medicine->id,
            'batch_number' => 'EXPIRING-SOON-LOT',
            'expiry_date' => now()->addDays(30)->toDateString(),
            'initial_quantity' => 20,
            'current_quantity' => 20,
            'purchase_price' => 3000,
            'status' => 'active',
        ]);

        // وجبة تنتهي بعد سنتين
        MedicineBatch::create([
            'medicine_id' => $this->medicine->id,
            'batch_number' => 'LONG-TERM-LOT',
            'expiry_date' => now()->addYears(2)->toDateString(),
            'initial_quantity' => 20,
            'current_quantity' => 20,
            'purchase_price' => 3000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('pharmacy.batches.index', ['status' => 'expiring_soon']));

        $response->assertStatus(200);
        $response->assertSee('EXPIRING-SOON-LOT');
        $response->assertDontSee('LONG-TERM-LOT');
    }
}
