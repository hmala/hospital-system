<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LabTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class LabTestActivationAndBulkControlTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('view lab tests', 'web');
        Permission::findOrCreate('edit lab tests', 'web');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo(['view lab tests', 'edit lab tests']);
    }

    public function test_quick_toggle_inverts_is_active_via_ajax()
    {
        $test = LabTest::create([
            'name' => 'CBC Test Quick Toggle',
            'code' => 'CBC_QT',
            'price' => 10000,
            'is_active' => true,
            'is_hi_active' => true,
            'hi_price' => 8000,
        ]);

        // Toggle from active to inactive
        $response = $this->actingAs($this->admin)->postJson(route('lab-tests.quick-toggle', $test), [
            'field' => 'is_active',
            'value' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_active' => false,
            'field' => 'is_active',
        ]);
        $this->assertDatabaseHas('lab_tests', [
            'id' => $test->id,
            'is_active' => false,
        ]);

        // Toggle back to active
        $response2 = $this->actingAs($this->admin)->postJson(route('lab-tests.quick-toggle', $test), [
            'field' => 'is_active',
            'value' => 1,
        ]);

        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('lab_tests', [
            'id' => $test->id,
            'is_active' => true,
        ]);
    }

    public function test_quick_toggle_inverts_is_hi_active_via_ajax()
    {
        $test = LabTest::create([
            'name' => 'Lipid Profile HI Toggle',
            'code' => 'LIP_HI',
            'price' => 15000,
            'is_active' => true,
            'is_hi_active' => true,
            'hi_price' => 12000,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('lab-tests.quick-toggle', $test), [
            'field' => 'is_hi_active',
            'value' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_hi_active' => false,
        ]);
        $this->assertDatabaseHas('lab_tests', [
            'id' => $test->id,
            'is_hi_active' => false,
            'is_active' => true,
        ]);
    }

    public function test_bulk_toggle_status_updates_multiple_tests()
    {
        $test1 = LabTest::create(['name' => 'Test Bulk 1', 'is_active' => true]);
        $test2 = LabTest::create(['name' => 'Test Bulk 2', 'is_active' => true]);
        $test3 = LabTest::create(['name' => 'Test Bulk 3', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->postJson(route('lab-tests.bulk-toggle-status'), [
            'ids' => [$test1->id, $test2->id],
            'field' => 'is_active',
            'status' => 'inactive',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 2,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('lab_tests', ['id' => $test1->id, 'is_active' => false]);
        $this->assertDatabaseHas('lab_tests', ['id' => $test2->id, 'is_active' => false]);
        $this->assertDatabaseHas('lab_tests', ['id' => $test3->id, 'is_active' => true]);
    }

    public function test_bulk_disable_non_insurance_disables_unpriced_tests_only()
    {
        // 2 tests without insurance price
        $uncovered1 = LabTest::create(['name' => 'Non Insurance 1', 'price' => 5000, 'hi_price' => null, 'is_active' => true]);
        $uncovered2 = LabTest::create(['name' => 'Non Insurance 2', 'price' => 6000, 'hi_price' => 0, 'is_active' => true]);
        // 1 test with insurance price
        $covered = LabTest::create(['name' => 'Insured Test', 'price' => 20000, 'hi_price' => 18000, 'is_active' => true, 'is_hi_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('lab-tests.bulk-disable-non-insurance'), [
            'action' => 'general_disable',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('lab_tests', ['id' => $uncovered1->id, 'is_active' => false]);
        $this->assertDatabaseHas('lab_tests', ['id' => $uncovered2->id, 'is_active' => false]);
        $this->assertDatabaseHas('lab_tests', ['id' => $covered->id, 'is_active' => true]);
    }

    public function test_deactivated_test_disappears_from_stations_active_query()
    {
        $activeTest = LabTest::create(['name' => 'Active Urine Test', 'is_active' => true]);
        $disabledTest = LabTest::create(['name' => 'Disabled Stool Test', 'is_active' => false]);

        $stationTests = LabTest::where('is_active', true)->pluck('id')->toArray();

        $this->assertContains($activeTest->id, $stationTests);
        $this->assertNotContains($disabledTest->id, $stationTests);
    }
}
