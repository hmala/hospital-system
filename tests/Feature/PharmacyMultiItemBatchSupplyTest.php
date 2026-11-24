<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PharmacyMultiItemBatchSupplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = [
            'view pharmacy',
            'dispense medications',
            'manage medicines',
            'create medicines',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
    }

    public function test_pharmacy_staff_can_view_multi_item_batch_supply_page(): void
    {
        $pharmacyUser = User::factory()->create();
        $pharmacyRole = Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);
        $pharmacyRole->syncPermissions(['view pharmacy', 'manage medicines']);
        $pharmacyUser->assignRole($pharmacyRole);

        $response = $this->actingAs($pharmacyUser)->get(route('pharmacy.batches.create'));

        $response->assertStatus(200);
        $response->assertSee('توريد قائمة أدوية وشحنات جديدة للصيدلية');
        $response->assertSee('إضافة دواء آخر للقائمة');
        $response->assertSee('itemsTable');
    }

    public function test_pharmacy_staff_can_store_multi_item_batch_supply_list(): void
    {
        $pharmacyUser = User::factory()->create();
        $pharmacyRole = Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);
        $pharmacyRole->syncPermissions(['view pharmacy', 'manage medicines']);
        $pharmacyUser->assignRole($pharmacyRole);

        $med1 = Medicine::create([
            'name' => 'Paracetamol 500mg',
            'dosage_form' => 'حبوب',
            'strength' => '500mg',
            'main_unit' => 'علبة',
            'cost_price' => 1500,
            'sale_price' => 2000,
            'is_active' => true,
        ]);

        $med2 = Medicine::create([
            'name' => 'Amoxicillin 250mg',
            'dosage_form' => 'شراب',
            'strength' => '250mg',
            'main_unit' => 'زجاجة',
            'cost_price' => 3000,
            'sale_price' => 4500,
            'is_active' => true,
        ]);

        $postData = [
            'supplier_name' => 'شركة بغداد الدوائية',
            'invoice_number' => 'INV-2026-909',
            'received_at' => '2026-11-24',
            'status' => 'active',
            'items' => [
                1 => [
                    'medicine_id' => $med1->id,
                    'batch_number' => 'LOT-PARA-001',
                    'expiry_date' => '2027-12-31',
                    'initial_quantity' => 50,
                    'purchase_price' => 1400.00,
                ],
                2 => [
                    'medicine_id' => $med2->id,
                    'batch_number' => 'LOT-AMOX-002',
                    'expiry_date' => '2028-06-30',
                    'initial_quantity' => 25,
                    'purchase_price' => 2900.00,
                ],
            ],
        ];

        $response = $this->actingAs($pharmacyUser)->post(route('pharmacy.batches.store'), $postData);

        $response->assertRedirect(route('pharmacy.batches.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('medicine_batches', [
            'medicine_id' => $med1->id,
            'batch_number' => 'LOT-PARA-001',
            'initial_quantity' => 50,
            'current_quantity' => 50,
            'purchase_price' => 1400.00,
            'supplier_name' => 'شركة بغداد الدوائية',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('medicine_batches', [
            'medicine_id' => $med2->id,
            'batch_number' => 'LOT-AMOX-002',
            'initial_quantity' => 25,
            'current_quantity' => 25,
            'purchase_price' => 2900.00,
            'supplier_name' => 'شركة بغداد الدوائية',
            'status' => 'active',
        ]);
    }
}
