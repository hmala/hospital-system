<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\HealthInsuranceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PatientInsuranceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $hiCategory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user and permissions
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create patients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit patients', 'guard_name' => 'web']);
        $role->givePermissionTo(['create patients', 'edit patients']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->hiCategory = HealthInsuranceCategory::create([
            'name' => 'الفئة الأولى',
            'code' => 'A',
            'is_active' => true,
            'sort_order' => 1
        ]);
    }

    public function test_can_create_patient_with_cash_none_insurance()
    {
        $response = $this->actingAs($this->admin)->post(route('patients.store'), [
            'name' => 'مريض نقدي',
            'phone' => '07700000001',
            'date_of_birth' => '1990-05-15',
            'gender' => 'male',
            'marital_status' => 'أعزب',
            'national_id' => '199000000001',
            'insurance_type' => 'none',
        ]);

        $response->assertRedirect();
        $patient = Patient::where('national_id', '199000000001')->first();
        $this->assertNotNull($patient);
        $this->assertEquals('none', $patient->insurance_type);
        $this->assertEquals(0, $patient->covered_by_insurance);
        $this->assertNull($patient->health_insurance_category_id);
    }

    public function test_can_create_patient_with_moi_insurance()
    {
        $response = $this->actingAs($this->admin)->post(route('patients.store'), [
            'name' => 'مريض قوى الأمن',
            'phone' => '07700000002',
            'date_of_birth' => '1985-02-10',
            'gender' => 'male',
            'marital_status' => 'متزوج',
            'national_id' => '198500000002',
            'insurance_type' => 'moi',
            'insurance_card_no' => 'MOI-123456',
        ]);

        $response->assertRedirect();
        $patient = Patient::where('national_id', '198500000002')->first();
        $this->assertNotNull($patient);
        $this->assertEquals('moi', $patient->insurance_type);
        $this->assertEquals(1, $patient->covered_by_insurance);
        $this->assertEquals('MOI-123456', $patient->insurance_card_no);
        $this->assertNull($patient->health_insurance_category_id);
    }

    public function test_can_create_patient_with_hi_health_insurance()
    {
        $response = $this->actingAs($this->admin)->post(route('patients.store'), [
            'name' => 'مريض ضمان وطني',
            'phone' => '07700000003',
            'date_of_birth' => '1992-11-20',
            'gender' => 'female',
            'marital_status' => 'متزوج',
            'national_id' => '199200000003',
            'insurance_type' => 'hi',
            'health_insurance_category_id' => $this->hiCategory->id,
            'insurance_card_no' => 'HI-987654',
        ]);

        $response->assertRedirect();
        $patient = Patient::where('national_id', '199200000003')->first();
        $this->assertNotNull($patient);
        $this->assertEquals('hi', $patient->insurance_type);
        $this->assertEquals(1, $patient->covered_by_insurance);
        $this->assertEquals($this->hiCategory->id, $patient->health_insurance_category_id);
        $this->assertEquals('HI-987654', $patient->insurance_card_no);
    }

    public function test_can_update_patient_insurance_type()
    {
        // Start as cash
        $user = User::factory()->create(['name' => 'مريض للتعديل']);
        $patient = Patient::create([
            'user_id' => $user->id,
            'national_id' => '199500000004',
            'marital_status' => 'أعزب',
            'insurance_type' => 'none',
            'covered_by_insurance' => 0,
        ]);

        // Update to HI
        $response = $this->actingAs($this->admin)->put(route('patients.update', $patient), [
            'name' => 'مريض للتعديل',
            'phone' => '07700000004',
            'date_of_birth' => '1995-01-01',
            'gender' => 'male',
            'marital_status' => 'متزوج',
            'national_id' => '199500000004',
            'insurance_type' => 'hi',
            'health_insurance_category_id' => $this->hiCategory->id,
            'insurance_card_no' => 'HI-UPDATED-001',
        ]);

        $response->assertRedirect();
        $patient->refresh();
        $this->assertEquals('hi', $patient->insurance_type);
        $this->assertEquals(1, $patient->covered_by_insurance);
        $this->assertEquals($this->hiCategory->id, $patient->health_insurance_category_id);
        $this->assertEquals('HI-UPDATED-001', $patient->insurance_card_no);

        // Update back to cash
        $response2 = $this->actingAs($this->admin)->put(route('patients.update', $patient), [
            'name' => 'مريض للتعديل',
            'phone' => '07700000004',
            'date_of_birth' => '1995-01-01',
            'gender' => 'male',
            'marital_status' => 'متزوج',
            'national_id' => '199500000004',
            'insurance_type' => 'none',
        ]);

        $response2->assertRedirect();
        $patient->refresh();
        $this->assertEquals('none', $patient->insurance_type);
        $this->assertEquals(0, $patient->covered_by_insurance);
        $this->assertNull($patient->health_insurance_category_id);
    }

    public function test_patient_store_with_save_and_book_redirects_to_inquiry_create_with_patient_id()
    {
        $response = $this->actingAs($this->admin)->post(route('patients.store'), [
            'name' => 'مريض حجز استعلامات',
            'phone' => '07700000007',
            'date_of_birth' => '1993-04-12',
            'gender' => 'female',
            'marital_status' => 'أعزب',
            'national_id' => '199300000007',
            'insurance_type' => 'none',
            'action' => 'save_and_book',
        ]);

        $patient = Patient::where('national_id', '199300000007')->first();
        $this->assertNotNull($patient);
        $response->assertRedirect(route('inquiry.create', ['patient_id' => $patient->id]));
        $response->assertSessionHas('success');
    }

    public function test_patient_store_with_save_only_redirects_to_patients_index()
    {
        $response = $this->actingAs($this->admin)->post(route('patients.store'), [
            'name' => 'مريض حفظ فقط',
            'phone' => '07700000008',
            'date_of_birth' => '1989-08-25',
            'gender' => 'male',
            'marital_status' => 'متزوج',
            'national_id' => '198900000008',
            'insurance_type' => 'none',
            'action' => 'save_only',
        ]);

        $patient = Patient::where('national_id', '198900000008')->first();
        $this->assertNotNull($patient);
        $response->assertRedirect(route('patients.index'));
        $response->assertSessionHas('success');
    }
}

