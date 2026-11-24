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
        $response->assertSee('توريد شحنة أدوية بنظام الوحدات المزدوج');
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

    public function test_pharmacy_staff_can_supply_with_bonus_and_update_medicine_prices(): void
    {
        $pharmacyUser = User::factory()->create();
        $pharmacyRole = Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);
        $pharmacyRole->syncPermissions(['view pharmacy', 'manage medicines']);
        $pharmacyUser->assignRole($pharmacyRole);

        $med = Medicine::create([
            'name' => 'Cataflam 50mg',
            'dosage_form' => 'حبوب',
            'strength' => '50mg',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2,
            'cost_price' => 2000,
            'sale_price' => 3000,
            'sub_unit_sale_price' => 1500,
            'is_active' => true,
        ]);

        // توريد 10 علب بسعر 2000 للعلبة + 2 علب بونص مجاني = 12 علبة مستلمة
        // التكلفة الإجمالية = 20,000 د.ع / 12 علبة = 1,666.67 د.ع للعلبة
        $postData = [
            'supplier_name' => 'مذخر الأمل الدوائي',
            'invoice_number' => 'INV-AMAL-101',
            'received_at' => '2026-11-24',
            'status' => 'active',
            'items' => [
                1 => [
                    'medicine_id' => $med->id,
                    'batch_number' => 'LOT-CAT-99',
                    'expiry_date' => '2028-12-31',
                    'initial_quantity' => 10,
                    'bonus_quantity' => 2,
                    'purchase_price' => 2000.00,
                    'sale_price' => 3500.00,
                    'sub_unit_sale_price' => 1750.00,
                    'update_prices' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($pharmacyUser)->post(route('pharmacy.batches.store'), $postData);

        $response->assertRedirect(route('pharmacy.batches.index'));
        $response->assertSessionHas('success');

        // التأكد من إيداع 12 علبة بالرصيد واحتساب التكلفة الفعلية
        $this->assertDatabaseHas('medicine_batches', [
            'medicine_id' => $med->id,
            'batch_number' => 'LOT-CAT-99',
            'initial_quantity' => 12,
            'current_quantity' => 12,
            'purchase_price' => 1666.67,
        ]);

        // التأكد من تحديث أسعار الدواء في الدليل
        $med->refresh();
        $this->assertEquals(2000.00, $med->cost_price);
        $this->assertEquals(3500.00, $med->sale_price);
        $this->assertEquals(1750.00, $med->sub_unit_sale_price);
    }
}
