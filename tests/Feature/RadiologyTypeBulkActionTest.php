<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RadiologyType;
use App\Models\RadiologyRequest;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Hospital;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RadiologyTypeBulkActionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('manage radiology types', 'web');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('manage radiology types');
    }

    public function test_radiology_types_index_displays_insurance_pricing()
    {
        $type = RadiologyType::create([
            'name' => 'فحص سونار بطن وحوض',
            'code' => 'RAD-US-01',
            'subcategory' => 'سونار',
            'base_price' => 35000,
            'moi_price' => 25000,
            'is_moi_active' => true,
            'hi_price' => 20000,
            'is_hi_active' => true,
            'estimated_duration' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('radiology.types.index'));
        $response->assertStatus(200);
        $response->assertSee('فحص سونار بطن وحوض');
        $response->assertSee('35,000');
        $response->assertSee('20,000');
        $response->assertSee('25,000');
    }

    public function test_bulk_delete_deletes_unlinked_types()
    {
        $type1 = RadiologyType::create([
            'name' => 'أشعة سينية صدر 1',
            'code' => 'RAD-X1',
            'subcategory' => 'أشعة',
            'base_price' => 15000,
            'estimated_duration' => 10,
        ]);

        $type2 = RadiologyType::create([
            'name' => 'أشعة سينية صدر 2',
            'code' => 'RAD-X2',
            'subcategory' => 'أشعة',
            'base_price' => 15000,
            'estimated_duration' => 10,
        ]);

        $response = $this->actingAs($this->admin)->post(route('radiology.types.bulk-delete'), [
            'ids' => [$type1->id, $type2->id]
        ]);

        $response->assertRedirect(route('radiology.types.index'));
        $this->assertDatabaseMissing('radiology_types', ['id' => $type1->id]);
        $this->assertDatabaseMissing('radiology_types', ['id' => $type2->id]);
    }

    public function test_bulk_toggle_status_activates_and_deactivates_types()
    {
        $type1 = RadiologyType::create([
            'name' => 'سونار كلى',
            'code' => 'RAD-US-02',
            'subcategory' => 'سونار',
            'base_price' => 25000,
            'estimated_duration' => 15,
            'is_active' => true,
        ]);

        $type2 = RadiologyType::create([
            'name' => 'سونار غدة درقية',
            'code' => 'RAD-US-03',
            'subcategory' => 'سونار',
            'base_price' => 25000,
            'estimated_duration' => 15,
            'is_active' => true,
        ]);

        // Bulk deactivate
        $response = $this->actingAs($this->admin)->post(route('radiology.types.bulk-toggle-status'), [
            'ids' => [$type1->id, $type2->id],
            'status' => 'inactive'
        ]);

        $response->assertRedirect(route('radiology.types.index'));
        $this->assertFalse((bool)$type1->fresh()->is_active);
        $this->assertFalse((bool)$type2->fresh()->is_active);

        // Bulk activate
        $response = $this->actingAs($this->admin)->post(route('radiology.types.bulk-toggle-status'), [
            'ids' => [$type1->id, $type2->id],
            'status' => 'active'
        ]);

        $response->assertRedirect(route('radiology.types.index'));
        $this->assertTrue((bool)$type1->fresh()->is_active);
        $this->assertTrue((bool)$type2->fresh()->is_active);
    }
}
