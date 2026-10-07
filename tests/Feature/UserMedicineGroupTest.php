<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\User;
use App\Models\UserMedicineGroup;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMedicineGroupTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctor;
    protected User $otherDoctor;
    protected Medicine $medicine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->doctor = User::factory()->create();
        $this->doctor->assignRole('doctor');

        $this->otherDoctor = User::factory()->create();
        $this->otherDoctor->assignRole('doctor');

        $this->medicine = Medicine::create([
            'name' => 'Amoxicillin 500mg',
            'generic_name' => 'Amoxicillin',
            'national_code' => '01-C00-038',
            'barcode' => '628100999001',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2,
            'cost_price' => 2000,
            'sale_price' => 3000,
            'is_active' => true,
        ]);
    }

    public function test_doctor_can_view_index(): void
    {
        $response = $this->actingAs($this->doctor)->get(route('medicine-groups.index'));

        $response->assertStatus(200);
    }

    public function test_doctor_can_create_group(): void
    {
        $response = $this->actingAs($this->doctor)->post(route('medicine-groups.store'), [
            'name' => 'مجموعتي المفضلة',
            'description' => 'وصف تجريبي',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('user_medicine_groups', [
            'name' => 'مجموعتي المفضلة',
            'user_id' => $this->doctor->id,
        ]);
    }

    public function test_doctor_can_edit_own_group_and_see_medicines_data(): void
    {
        $group = UserMedicineGroup::create([
            'user_id' => $this->doctor->id,
            'name' => 'مجموعة التحرير',
        ]);

        $response = $this->actingAs($this->doctor)->get(route('medicine-groups.edit', $group));

        $response->assertStatus(200);
        $response->assertSee('مجموعة التحرير');
    }

    public function test_doctor_can_search_medicines_via_ajax(): void
    {
        $response = $this->actingAs($this->doctor)->get(route('medicine-groups.search-medicines', ['q' => 'Amox']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Amoxicillin 500mg']);
    }

    public function test_search_medicines_requires_minimum_two_characters(): void
    {
        $response = $this->actingAs($this->doctor)->get(route('medicine-groups.search-medicines', ['q' => 'a']));

        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    public function test_doctor_can_update_group_medicines_with_dosage_details(): void
    {
        $group = UserMedicineGroup::create([
            'user_id' => $this->doctor->id,
            'name' => 'مجموعة التحديث',
        ]);

        $response = $this->actingAs($this->doctor)->put(route('medicine-groups.update', $group), [
            'medicine_ids' => [$this->medicine->id],
            'dosage_form' => [$this->medicine->id => 'tablet'],
            'dosage' => [$this->medicine->id => '500mg'],
            'frequency' => [$this->medicine->id => '2'],
            'duration' => [$this->medicine->id => '7 أيام'],
            'instructions' => [$this->medicine->id => 'بعد الأكل'],
        ]);

        $response->assertRedirect(route('medicine-groups.edit', $group));

        $group->refresh();
        $this->assertCount(1, $group->medicines);
        $this->assertEquals('500mg', $group->medicines->first()->pivot->dosage);
        $this->assertEquals('بعد الأكل', $group->medicines->first()->pivot->instructions);
    }

    public function test_doctor_cannot_edit_another_doctors_group(): void
    {
        $group = UserMedicineGroup::create([
            'user_id' => $this->doctor->id,
            'name' => 'مجموعة محمية',
        ]);

        $response = $this->actingAs($this->otherDoctor)->get(route('medicine-groups.edit', $group));

        $response->assertStatus(403);
    }

    public function test_doctor_cannot_delete_another_doctors_group(): void
    {
        $group = UserMedicineGroup::create([
            'user_id' => $this->doctor->id,
            'name' => 'مجموعة محمية للحذف',
        ]);

        $response = $this->actingAs($this->otherDoctor)->delete(route('medicine-groups.destroy', $group));

        $response->assertStatus(403);
        $this->assertDatabaseHas('user_medicine_groups', ['id' => $group->id]);
    }

    public function test_doctor_can_delete_own_group(): void
    {
        $group = UserMedicineGroup::create([
            'user_id' => $this->doctor->id,
            'name' => 'مجموعة للحذف',
        ]);

        $response = $this->actingAs($this->doctor)->delete(route('medicine-groups.destroy', $group));

        $response->assertRedirect();
        $this->assertDatabaseMissing('user_medicine_groups', ['id' => $group->id]);
    }

    public function test_doctor_can_save_group_directly_from_visit_ajax(): void
    {
        $response = $this->actingAs($this->doctor)->postJson(route('medicine-groups.save-from-visit'), [
            'name' => 'باقة نزلات البرد السريعة',
            'description' => 'كورس علاجي',
            'is_public' => false,
            'medications' => [
                [
                    'medicine_id' => $this->medicine->id,
                    'name' => $this->medicine->name,
                    'type' => 'tablet',
                    'dosage' => '500mg',
                    'frequency' => '3',
                    'duration' => '5 أيام',
                    'instructions' => 'بعد الأكل',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);
        $response->assertJsonFragment(['name' => 'باقة نزلات البرد السريعة']);

        $this->assertDatabaseHas('user_medicine_groups', [
            'name' => 'باقة نزلات البرد السريعة',
            'user_id' => $this->doctor->id,
            'is_public' => 0,
        ]);
    }

    public function test_doctor_can_reorder_groups(): void
    {
        $g1 = UserMedicineGroup::create(['user_id' => $this->doctor->id, 'name' => 'Group 1', 'sort_order' => 0]);
        $g2 = UserMedicineGroup::create(['user_id' => $this->doctor->id, 'name' => 'Group 2', 'sort_order' => 1]);

        $response = $this->actingAs($this->doctor)->postJson(route('medicine-groups.reorder'), [
            'orders' => [
                ['id' => $g1->id, 'sort_order' => 5],
                ['id' => $g2->id, 'sort_order' => 0],
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);

        $g1->refresh();
        $g2->refresh();
        $this->assertEquals(5, $g1->sort_order);
        $this->assertEquals(0, $g2->sort_order);
    }

    public function test_doctor_can_toggle_group_star(): void
    {
        $group = UserMedicineGroup::create([
            'user_id' => $this->doctor->id,
            'name' => 'باقة قابلة للتمييز',
            'is_starred' => false,
        ]);

        $response = $this->actingAs($this->doctor)->postJson(route('medicine-groups.toggle-star', $group));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_starred' => true]);

        $group->refresh();
        $this->assertTrue($group->is_starred);

        // Toggle back to false
        $response2 = $this->actingAs($this->doctor)->postJson(route('medicine-groups.toggle-star', $group));
        $response2->assertStatus(200);
        $response2->assertJson(['success' => true, 'is_starred' => false]);

        $group->refresh();
        $this->assertFalse($group->is_starred);
    }

    public function test_doctor_can_increment_group_usage(): void
    {
        $group = UserMedicineGroup::create([
            'user_id' => $this->doctor->id,
            'name' => 'باقة نشطة',
            'usage_count' => 5,
        ]);

        $response = $this->actingAs($this->doctor)->postJson(route('medicine-groups.increment-usage', $group));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'usage_count' => 6]);

        $group->refresh();
        $this->assertEquals(6, $group->usage_count);
    }

    public function test_doctor_can_decrement_group_usage(): void
    {
        $group = UserMedicineGroup::create([
            'user_id' => $this->doctor->id,
            'name' => 'باقة نشطة للإلغاء',
            'usage_count' => 5,
        ]);

        $response = $this->actingAs($this->doctor)->postJson(route('medicine-groups.decrement-usage', $group));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'usage_count' => 4]);

        $group->refresh();
        $this->assertEquals(4, $group->usage_count);
    }

    public function test_doctor_can_reset_single_group_usage(): void
    {
        $group = UserMedicineGroup::create([
            'user_id' => $this->doctor->id,
            'name' => 'باقة لتصفير الفردي',
            'usage_count' => 12,
        ]);

        $response = $this->actingAs($this->doctor)->postJson(route('medicine-groups.reset-single-usage', $group));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'usage_count' => 0]);

        $group->refresh();
        $this->assertEquals(0, $group->usage_count);
    }

    public function test_doctor_can_reset_all_groups_usage(): void
    {
        $g1 = UserMedicineGroup::create(['user_id' => $this->doctor->id, 'name' => 'G1', 'usage_count' => 8]);
        $g2 = UserMedicineGroup::create(['user_id' => $this->doctor->id, 'name' => 'G2', 'usage_count' => 15]);

        $response = $this->actingAs($this->doctor)->post(route('medicine-groups.reset-usage'));

        $response->assertRedirect();
        $g1->refresh();
        $g2->refresh();
        $this->assertEquals(0, $g1->usage_count);
        $this->assertEquals(0, $g2->usage_count);
    }

    public function test_guest_cannot_access_medicine_groups(): void
    {
        $response = $this->get(route('medicine-groups.index'));

        $response->assertRedirect(route('login'));
    }
}
