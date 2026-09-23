<?php

namespace Tests\Feature;

use App\Models\LabTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LabTestPricingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_view_lab_test_pricing_settings_page()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        LabTest::create([
            'name' => 'Complete Blood Count',
            'code' => 'CBC_TEST',
            'main_category' => 'المختبر',
            'subcategory' => 'Haematology',
            'price' => 15000,
            'hi_price' => 5000,
            'moi_price' => 4000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('lab-tests.pricing-settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Complete Blood Count');
        $response->assertSee('Haematology');
        $response->assertSee('15,000');
    }

    public function test_can_save_single_lab_test_pricing_row_via_ajax()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $test = LabTest::create([
            'name' => 'Fasting Blood Sugar',
            'code' => 'FBS_TEST',
            'main_category' => 'المختبر',
            'subcategory' => 'Biochemistry',
            'price' => 10000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->postJson(route('lab-tests.pricing-settings.save'), [
            'save_mode' => 'row',
            'target_id' => $test->id,
            'test_id' => [0 => $test->id],
            'price' => [0 => '12,000'],
            'hi_price' => [0 => '6,000'],
            'moi_price' => [0 => '5,000'],
            'subcategory' => [0 => 'Clinical Chemistry'],
            'is_active' => [0 => 1],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $test->refresh();
        $this->assertEquals(12000, (int) $test->price);
        $this->assertEquals(6000, (int) $test->hi_price);
        $this->assertEquals(5000, (int) $test->moi_price);
        $this->assertEquals('Clinical Chemistry', $test->subcategory);
    }

    public function test_can_bulk_save_all_lab_tests()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $test1 = LabTest::create([
            'name' => 'Test 1',
            'code' => 'T1',
            'main_category' => 'المختبر',
            'subcategory' => 'Cat A',
            'price' => 5000,
        ]);

        $test2 = LabTest::create([
            'name' => 'Test 2',
            'code' => 'T2',
            'main_category' => 'المختبر',
            'subcategory' => 'Cat B',
            'price' => 8000,
        ]);

        $response = $this->actingAs($admin)->post(route('lab-tests.pricing-settings.save'), [
            'save_mode' => 'all',
            'test_id' => [
                0 => $test1->id,
                1 => $test2->id,
            ],
            'price' => [
                0 => '6,000',
                1 => '9,500',
            ],
            'hi_price' => [
                0 => '2,000',
                1 => '3,000',
            ],
            'moi_price' => [
                0 => '1,500',
                1 => '2,500',
            ],
            'subcategory' => [
                0 => 'Cat A Updated',
                1 => 'Cat B Updated',
            ],
            'is_active' => [
                0 => 1,
                1 => 1,
            ],
        ]);

        $response->assertRedirect();

        $test1->refresh();
        $test2->refresh();

        $this->assertEquals(6000, (int) $test1->price);
        $this->assertEquals('Cat A Updated', $test1->subcategory);

        $this->assertEquals(9500, (int) $test2->price);
        $this->assertEquals('Cat B Updated', $test2->subcategory);
    }

    public function test_can_rename_category_for_all_related_tests()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $t1 = LabTest::create([
            'name' => 'Test Alpha',
            'code' => 'TA',
            'main_category' => 'المختبر',
            'subcategory' => 'OldCategory',
            'price' => 10000,
        ]);

        $t2 = LabTest::create([
            'name' => 'Test Beta',
            'code' => 'TB',
            'main_category' => 'المختبر',
            'subcategory' => 'OldCategory',
            'price' => 20000,
        ]);

        $response = $this->actingAs($admin)->post(route('lab-tests.pricing-settings.rename-category'), [
            'old_category' => 'OldCategory',
            'new_category' => 'NewCategoryName',
        ]);

        $response->assertRedirect();

        $t1->refresh();
        $t2->refresh();

        $this->assertEquals('NewCategoryName', $t1->subcategory);
        $this->assertEquals('NewCategoryName', $t2->subcategory);
    }
}
