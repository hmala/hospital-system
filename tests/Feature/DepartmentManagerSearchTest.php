<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentManagerSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'name' => 'مدير النظام',
            'email' => 'admin@hospital.test',
            'role' => 'admin',
        ]);
        $this->admin->assignRole($adminRole);

        $hospital = Hospital::create([
            'name' => 'مستشفى الاختبار',
            'owner_name' => 'د. المدير',
            'license_number' => 'LIC-DEPT-01',
            'phone' => '07700000001',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الطوارئ',
            'type' => 'emergency',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
            'max_patients_per_day' => 50,
            'is_active' => true,
        ]);
    }

    public function test_can_search_department_managers_by_name_and_email_excluding_patients(): void
    {
        $doctorManager = User::factory()->create([
            'name' => 'د. علي حسين الجبوري',
            'email' => 'ali.jabouri@hospital.test',
            'phone' => '07701112233',
            'role' => 'doctor',
        ]);
        $doctorManager->assignRole('doctor');

        $patientUser = User::factory()->create([
            'name' => 'علي المريض التجريبي',
            'email' => 'ali.patient@hospital.test',
            'role' => 'patient',
        ]);
        $patientUser->assignRole('patient');
        Patient::create([
            'user_id' => $patientUser->id,
            'phone' => '07709998877',
            'gender' => 'male',
        ]);

        // البحث بالاسم
        $this->actingAs($this->admin)
            ->getJson(route('departments.managers.search', ['q' => 'علي']))
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $doctorManager->id,
                'name' => 'د. علي حسين الجبوري',
                'email' => 'ali.jabouri@hospital.test',
            ])
            ->assertJsonMissing([
                'email' => 'ali.patient@hospital.test',
            ]);

        // البحث بالبريد الإلكتروني
        $this->actingAs($this->admin)
            ->getJson(route('departments.managers.search', ['q' => 'ali.jabouri@']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $doctorManager->id);
    }

    public function test_edit_and_create_department_pages_render_searchable_manager_picker_and_save_manager(): void
    {
        $manager = User::factory()->create([
            'name' => 'أ. سارة كريم',
            'email' => 'sara.kareem@hospital.test',
            'role' => 'doctor',
        ]);

        $this->actingAs($this->admin)
            ->get(route('departments.edit', $this->department))
            ->assertStatus(200)
            ->assertSee('departmentManagerPicker')
            ->assertSee('managerSearchInput')
            ->assertSee(route('departments.managers.search'));

        $this->actingAs($this->admin)
            ->get(route('departments.create'))
            ->assertStatus(200)
            ->assertSee('departmentManagerPicker')
            ->assertSee('managerSearchInput');

        $this->actingAs($this->admin)
            ->put(route('departments.update', $this->department), [
                'name' => $this->department->name,
                'type' => $this->department->type,
                'room_number' => $this->department->room_number,
                'consultation_fee' => 25000,
                'working_hours_start' => '08:00',
                'working_hours_end' => '20:00',
                'max_patients_per_day' => 50,
                'manager_id' => $manager->id,
                'is_active' => 1,
            ])
            ->assertRedirect(route('departments.admin'));

        $this->assertEquals($manager->id, $this->department->fresh()->manager_id);
    }

    public function test_unauthorized_user_cannot_search_department_managers(): void
    {
        $unauthorized = User::factory()->create(['role' => 'patient']);
        $unauthorized->assignRole('patient');

        $this->actingAs($unauthorized)
            ->getJson(route('departments.managers.search', ['q' => 'admin']))
            ->assertStatus(403);
    }

    public function test_department_can_be_created_and_updated_without_working_hours_or_max_patients(): void
    {
        $this->actingAs($this->admin)
            ->post(route('departments.store'), [
                'name' => 'قسم الحسابات الإداري',
                'type' => 'other',
                'room_number' => '205',
                'consultation_fee' => 0,
                'working_hours_start' => '',
                'working_hours_end' => '',
                'max_patients_per_day' => '',
                'is_active' => 1,
            ])
            ->assertRedirect(route('departments.admin'));

        $created = Department::where('name', 'قسم الحسابات الإداري')->first();
        $this->assertNotNull($created);
        $this->assertNull($created->working_hours_start);
        $this->assertNull($created->working_hours_end);
        $this->assertNull($created->max_patients_per_day);

        $this->actingAs($this->admin)
            ->put(route('departments.update', $this->department), [
                'name' => $this->department->name,
                'type' => $this->department->type,
                'room_number' => $this->department->room_number,
                'consultation_fee' => 25000,
                'working_hours_start' => '',
                'working_hours_end' => '',
                'max_patients_per_day' => '',
                'is_active' => 1,
            ])
            ->assertRedirect(route('departments.admin'));

        $updated = $this->department->fresh();
        $this->assertNull($updated->working_hours_start);
        $this->assertNull($updated->working_hours_end);
        $this->assertNull($updated->max_patients_per_day);

        // التأكد من عمل صفحة العرض والتعديل بدون أخطاء مع القيم الفارغة
        $this->actingAs($this->admin)
            ->get(route('departments.edit', $updated))
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->get(route('departments.show', $updated))
            ->assertStatus(200)
            ->assertSee('غير محدد');
    }
}
