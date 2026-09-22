<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\PharmacyService;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->adminUser->assignRole('admin');
    }

    public function test_admin_can_view_medicines_catalog()
    {
        Medicine::create([
            'national_code' => '01-C00-038',
            'name' => 'Amoxicillin 500mg',
            'generic_name' => 'Amoxicillin',
            'dosage_form' => 'كبسول',
            'strength' => '500mg',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2,
            'cost_price' => 2000,
            'sale_price' => 3000,
            'sub_unit_sale_price' => 1500,
            'is_insurance_covered' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('pharmacy.medicines.index'));

        $response->assertStatus(200);
        $response->assertSee('Amoxicillin 500mg');
        $response->assertSee('01-C00-038');
    }

    public function test_can_create_medicine_with_multi_units_and_opening_batch()
    {
        $response = $this->actingAs($this->adminUser)->post(route('pharmacy.medicines.store'), [
            'name' => 'Ciprofloxacin 500mg',
            'generic_name' => 'Ciprofloxacin',
            'national_code' => '02-A11-005',
            'dosage_form' => 'حبوب',
            'strength' => '500mg',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 1,
            'cost_price' => 1500,
            'sale_price' => 2500,
            'sub_unit_sale_price' => 2500,
            'hi_price' => 2200,
            'is_insurance_covered' => '1',
            'min_stock_alert' => 10,
            'initial_batch_number' => 'LOT-2026-X1',
            'initial_expiry_date' => now()->addMonths(12)->toDateString(),
            'initial_quantity' => 20,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('medicines', ['name' => 'Ciprofloxacin 500mg']);
        $this->assertDatabaseHas('medicine_batches', ['batch_number' => 'LOT-2026-X1', 'current_quantity' => 20]);
    }

    public function test_can_link_and_remove_alternatives()
    {
        $med1 = Medicine::create([
            'name' => 'Panadol Extra',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2,
            'cost_price' => 1000,
            'sale_price' => 2000,
        ]);

        $med2 = Medicine::create([
            'name' => 'Paramol',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2,
            'cost_price' => 800,
            'sale_price' => 1500,
        ]);

        // ربط البديل
        $response = $this->actingAs($this->adminUser)->post(route('pharmacy.medicines.alternatives.add', $med1->id), [
            'alternative_medicine_id' => $med2->id,
            'notes' => 'مكافئ علمي',
        ]);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('medicine_alternatives', [
            'medicine_id' => $med1->id,
            'alternative_medicine_id' => $med2->id,
        ]);

        // فك ربط البديل
        $response2 = $this->actingAs($this->adminUser)->delete(route('pharmacy.medicines.alternatives.remove', [$med1->id, $med2->id]));
        $response2->assertSessionHas('success');
        $this->assertDatabaseMissing('medicine_alternatives', [
            'medicine_id' => $med1->id,
            'alternative_medicine_id' => $med2->id,
        ]);
    }

    public function test_can_manage_pharmacy_services()
    {
        $response = $this->actingAs($this->adminUser)->post(route('pharmacy.services.store'), [
            'name' => 'قياس السكر في الدم',
            'price' => 1500,
            'hi_price' => 1000,
            'notes' => 'فحص شريطي سريع',
        ]);

        $response->assertRedirect(route('pharmacy.services.index'));
        $this->assertDatabaseHas('pharmacy_services', ['name' => 'قياس السكر في الدم']);

        $service = PharmacyService::where('name', 'قياس السكر في الدم')->first();

        // تفعيل / تعطيل
        $this->actingAs($this->adminUser)->post(route('pharmacy.services.toggle', $service->id));
        $this->assertFalse($service->fresh()->is_active);
    }

    public function test_medicine_search_api()
    {
        Medicine::create([
            'name' => 'Augmentin 1g',
            'barcode' => '890123456789',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2,
            'cost_price' => 5000,
            'sale_price' => 8000,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('pharmacy.medicines.search', ['q' => '890123456789']));
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Augmentin 1g']);
    }

    public function test_download_template_csv()
    {
        $response = $this->actingAs($this->adminUser)->get(route('pharmacy.medicines.template'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename="medicines_import_template.csv"');
    }
}
