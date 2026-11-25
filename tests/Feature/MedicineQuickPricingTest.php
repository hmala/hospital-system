<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MedicineQuickPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = [
            'view pharmacy',
            'manage medicines',
            'edit medicines',
            'create medicines',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
    }

    public function test_authorized_user_can_quick_update_cash_box_and_strip_prices(): void
    {
        $pharmacyUser = User::factory()->create();
        $pharmacyRole = Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);
        $pharmacyRole->syncPermissions(['view pharmacy', 'edit medicines']);
        $pharmacyUser->assignRole($pharmacyRole);

        $medicine = Medicine::create([
            'name' => 'Amoxicillin 500mg',
            'dosage_form' => 'كبسول',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 3,
            'cost_price' => 2000,
            'sale_price' => 3000,
            'sub_unit_sale_price' => 1000,
            'min_stock_alert' => 5,
        ]);

        $response = $this->actingAs($pharmacyUser)->patchJson(
            route('pharmacy.medicines.quick-price', $medicine->id),
            [
                'sale_price' => 4500,
                'sub_unit_sale_price' => 1500,
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'medicine_id' => $medicine->id,
            'sale_price' => 4500,
            'sub_unit_sale_price' => 1500,
        ]);

        $this->assertDatabaseHas('medicines', [
            'id' => $medicine->id,
            'sale_price' => 4500,
            'sub_unit_sale_price' => 1500,
        ]);
    }

    public function test_sub_unit_price_is_auto_derived_when_omitted(): void
    {
        $pharmacyUser = User::factory()->create();
        $pharmacyRole = Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);
        $pharmacyRole->syncPermissions(['view pharmacy', 'edit medicines']);
        $pharmacyUser->assignRole($pharmacyRole);

        $medicine = Medicine::create([
            'name' => 'Cataflam 50mg',
            'dosage_form' => 'حبوب',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2,
            'cost_price' => 4000,
            'sale_price' => 5000,
            'sub_unit_sale_price' => 2500,
            'min_stock_alert' => 5,
        ]);

        $response = $this->actingAs($pharmacyUser)->patchJson(
            route('pharmacy.medicines.quick-price', $medicine->id),
            [
                'sale_price' => 8000,
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'sale_price' => 8000,
            'sub_unit_sale_price' => 4000, // 8000 / 2
        ]);

        $medicine->refresh();
        $this->assertEquals(8000, $medicine->sale_price);
        $this->assertEquals(4000, $medicine->sub_unit_sale_price);
    }

    public function test_quick_pricing_validation_rejects_negative_prices(): void
    {
        $pharmacyUser = User::factory()->create();
        $pharmacyRole = Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);
        $pharmacyRole->syncPermissions(['view pharmacy', 'edit medicines']);
        $pharmacyUser->assignRole($pharmacyRole);

        $medicine = Medicine::create([
            'name' => 'Panadol Extra',
            'dosage_form' => 'حبوب',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2,
            'cost_price' => 1000,
            'sale_price' => 2000,
            'sub_unit_sale_price' => 1000,
            'min_stock_alert' => 5,
        ]);

        $response = $this->actingAs($pharmacyUser)->patchJson(
            route('pharmacy.medicines.quick-price', $medicine->id),
            [
                'sale_price' => -100,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sale_price']);
    }

    public function test_unauthorized_user_cannot_quick_update_prices(): void
    {
        $plainUser = User::factory()->create();

        $medicine = Medicine::create([
            'name' => 'Aspirin 100mg',
            'dosage_form' => 'حبوب',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 3,
            'cost_price' => 1000,
            'sale_price' => 1500,
            'sub_unit_sale_price' => 500,
            'min_stock_alert' => 5,
        ]);

        $response = $this->actingAs($plainUser)->patchJson(
            route('pharmacy.medicines.quick-price', $medicine->id),
            [
                'sale_price' => 3000,
            ]
        );

        $response->assertStatus(403);
    }

    public function test_medicines_index_renders_quick_pricing_inputs_for_authorized_users(): void
    {
        $pharmacyUser = User::factory()->create();
        $pharmacyRole = Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);
        $pharmacyRole->syncPermissions(['view pharmacy', 'edit medicines']);
        $pharmacyUser->assignRole($pharmacyRole);

        Medicine::create([
            'name' => 'Brufen 400mg',
            'dosage_form' => 'حبوب',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 3,
            'cost_price' => 1500,
            'sale_price' => 2500,
            'sub_unit_sale_price' => 833,
            'min_stock_alert' => 5,
        ]);

        $response = $this->actingAs($pharmacyUser)->get(route('pharmacy.medicines.index'));

        $response->assertStatus(200);
        $response->assertSee('quick-sale-price');
        $response->assertSee('quick-sub-price');
        $response->assertSee('quick-status-badge');
    }
}
