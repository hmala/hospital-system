<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\HealthInsuranceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PatientCompletionAndQuickUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private HealthInsuranceCategory $categoryH;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and permissions
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view patients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit patients', 'guard_name' => 'web']);
        $role->givePermissionTo(['view patients', 'edit patients']);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('admin');

        $this->categoryH = HealthInsuranceCategory::create([
            'id' => 8,
            'code' => 'H',
            'name' => 'الفئة H',
            'description' => 'موظفو الدولة',
            'is_active' => true,
        ]);
    }

    public function test_patients_index_displays_kpi_cards_and_completion_filter(): void
    {
        $response = $this->actingAs($this->admin)->get(route('patients.index'));

        $response->assertStatus(200);
        $response->assertSee('إجمالي المرضى بالمنظومة');
        $response->assertSee('بانتظار إكمال البيانات');
        $response->assertSee('سجلات مكتملة ومدققة');
        $response->assertSee('حالة اكتمال الملف');
    }

    public function test_filtering_by_completion_status_incomplete(): void
    {
        // 1. Incomplete patient (missing phone and insurance number)
        $user1 = User::factory()->create([
            'name' => 'مريض ناقص البيانات',
            'phone' => null,
            'gender' => null,
            'role' => 'patient',
        ]);
        $p1 = Patient::create([
            'user_id' => $user1->id,
            'insurance_type' => 'hi',
            'health_insurance_category_id' => 8,
            'covered_by_insurance' => 1,
            'copay_percentage' => 15.00,
        ]);

        // 2. Complete patient
        $user2 = User::factory()->create([
            'name' => 'مريض كامل البيانات',
            'phone' => '07701234567',
            'gender' => 'male',
            'role' => 'patient',
        ]);
        $p2 = Patient::create([
            'user_id' => $user2->id,
            'national_id' => '199012345678',
            'insurance_type' => 'hi',
            'insurance_card_no' => 'HIA-999',
            'health_insurance_category_id' => 8,
            'covered_by_insurance' => 1,
            'copay_percentage' => 15.00,
        ]);

        $response = $this->actingAs($this->admin)->get(route('patients.index', [
            'completion_status' => 'incomplete'
        ]));

        $response->assertStatus(200);
        $response->assertSee('مريض ناقص البيانات');
        $response->assertDontSee('مريض كامل البيانات');
    }

    public function test_quick_update_endpoint_updates_patient_and_user_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'أحمد حسن علي',
            'phone' => null,
            'gender' => null,
            'role' => 'patient',
        ]);
        $patient = Patient::create([
            'user_id' => $user->id,
            'insurance_type' => 'hi',
            'health_insurance_category_id' => 8,
            'covered_by_insurance' => 1,
            'copay_percentage' => 15.00,
        ]);

        $payload = [
            'phone' => '07801122334',
            'gender' => 'male',
            'insurance_number' => 'CARD-12345',
            'national_id' => '200012345678',
            'date_of_birth' => '1995-05-15',
        ];

        $response = $this->actingAs($this->admin)->patchJson(
            route('patients.quick-update', $patient),
            $payload
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'patient' => [
                'id' => $patient->id,
                'phone' => '07801122334',
                'gender' => 'male',
                'insurance_number' => 'CARD-12345',
                'national_id' => '200012345678',
                'date_of_birth' => '1995-05-15',
            ]
        ]);

        $user->refresh();
        $this->assertEquals('07801122334', $user->phone);
        $this->assertEquals('male', $user->gender);
        $this->assertEquals('1995-05-15', \Carbon\Carbon::parse($user->date_of_birth)->format('Y-m-d'));

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'national_id' => '200012345678',
            'insurance_card_no' => 'CARD-12345',
        ]);
    }

    public function test_quick_update_calculates_dob_from_age_input(): void
    {
        $user = User::factory()->create([
            'name' => 'سارة كريم',
            'phone' => null,
            'gender' => null,
            'role' => 'patient',
        ]);
        $patient = Patient::create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($this->admin)->patchJson(
            route('patients.quick-update', $patient),
            [
                'phone' => '07705544332',
                'gender' => 'female',
                'age' => 30,
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'patient' => [
                'id' => $patient->id,
                'age' => 30,
            ]
        ]);

        $user->refresh();
        $this->assertNotNull($user->date_of_birth);
        $this->assertEquals(30, $patient->fresh()->age);
    }
}
