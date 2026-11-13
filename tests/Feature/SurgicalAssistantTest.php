<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\SurgicalAssistant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgicalAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
    }

    public function test_authorized_user_can_view_surgical_assistants_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        SurgicalAssistant::create([
            'name' => 'علياء عبدالجبار',
            'phone' => '07700000001',
            'notes' => 'مساعد جراح صالة العمليات',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('surgical-assistants.index'));

        $response->assertStatus(200);
        $response->assertSee('دليل وإدارة مساعدي الجراحين');
        $response->assertSee('علياء عبدالجبار');
    }

    public function test_user_can_create_surgical_assistant(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('surgical-assistants.store'), [
            'name' => 'حكم علاء',
            'phone' => '07700000005',
            'notes' => 'كادر عمليات',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('surgical-assistants.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('surgical_assistants', [
            'name' => 'حكم علاء',
            'phone' => '07700000005',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_name_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        SurgicalAssistant::create(['name' => 'ليث رحيم', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('surgical-assistants.store'), [
            'name' => 'ليث رحيم',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_update_surgical_assistant(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $assistant = SurgicalAssistant::create(['name' => 'علي عبدالله', 'is_active' => true]);

        $response = $this->actingAs($admin)->put(route('surgical-assistants.update', $assistant), [
            'name' => 'علي عبدالله المحدث',
            'phone' => '07800000000',
            'notes' => 'مساعد جراح رئيسي',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('surgical-assistants.index'));
        $assistant->refresh();
        $this->assertEquals('علي عبدالله المحدث', $assistant->name);
        $this->assertEquals('07800000000', $assistant->phone);
    }

    public function test_user_can_toggle_assistant_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $assistant = SurgicalAssistant::create(['name' => 'محمد فالح', 'is_active' => true]);

        $response = $this->actingAs($admin)->patch(route('surgical-assistants.toggle-status', $assistant));

        $response->assertRedirect();
        $assistant->refresh();
        $this->assertFalse($assistant->is_active);

        // Toggle back
        $this->actingAs($admin)->patch(route('surgical-assistants.toggle-status', $assistant));
        $assistant->refresh();
        $this->assertTrue($assistant->is_active);
    }

    public function test_user_can_delete_surgical_assistant(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $assistant = SurgicalAssistant::create(['name' => 'مساعد مؤقت', 'is_active' => true]);

        $response = $this->actingAs($admin)->delete(route('surgical-assistants.destroy', $assistant));

        $response->assertRedirect(route('surgical-assistants.index'));
        $this->assertDatabaseMissing('surgical_assistants', ['name' => 'مساعد مؤقت']);
    }

    public function test_surgery_show_renders_assistants_searchable_dropdown(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        SurgicalAssistant::create(['name' => 'مضاء علي', 'is_active' => true]);

        $dept = Department::first();
        $patient = Patient::create(['user_id' => User::factory()->create()->id]);
        $doctor = Doctor::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'د. جراح',
            'type' => 'surgeon',
            'specialization' => 'جراحة عامة',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-1234',
            'phone' => '07700000000',
            'department_id' => $dept->id,
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'department_id' => $dept->id,
            'doctor_id' => $doctor->id,
            'surgery_name' => 'عملية جراحية',
            'surgery_type' => 'جراحة',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin)->get(route('surgeries.show', $surgery));

        $response->assertStatus(200);
        $response->assertSee('surgicalAssistantsMenu');
        $response->assertSee('assistantAutocompleteWrapper');
        $response->assertSee('مضاء علي');
        $response->assertSee('دليل المساعدين');
    }
}
