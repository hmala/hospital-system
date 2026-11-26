<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgeryTabPermission;
use App\Models\SurgeryTreatment;
use App\Models\User;
use App\Models\UserMedicineGroup;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalStationMedicinePackageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $resident1;

    protected User $resident2;

    protected User $nurse;

    protected Surgery $surgery;

    protected Medicine $med1;

    protected Medicine $med2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        SurgeryTabPermission::seedDefaults();

        $department = Department::first();

        // Admin
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Resident 1
        $this->resident1 = User::factory()->create();
        $this->resident1->assignRole('resident');
        Doctor::create([
            'user_id' => $this->resident1->id,
            'department_id' => $department->id,
            'name' => 'د. أحمد المقيم الأول',
            'phone' => '0770000001',
            'specialization' => 'General Surgery',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-RES-'.uniqid(),
            'type' => 'resident',
            'is_active' => true,
        ]);

        // Resident 2 (Another independent colleague)
        $this->resident2 = User::factory()->create();
        $this->resident2->assignRole('resident');
        Doctor::create([
            'user_id' => $this->resident2->id,
            'department_id' => $department->id,
            'name' => 'د. عمر المقيم الثاني',
            'phone' => '0770000002',
            'specialization' => 'General Surgery',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-RES-'.uniqid(),
            'type' => 'resident',
            'is_active' => true,
        ]);

        // Nurse
        $this->nurse = User::factory()->create();
        $this->nurse->assignRole('nurse');

        // Patient & Surgery
        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 35,
            'gender' => 'male',
            'blood_group' => 'O+',
        ]);

        $room = Room::create([
            'room_number' => 'OR-TEST-'.uniqid(),
            'type' => 'surgery',
            'status' => 'available',
            'price_per_day' => 100,
        ]);

        $theater = \App\Models\OperationTheater::create([
            'name' => 'صالة العمليات الكبرى 1',
            'code' => 'OR-1',
            'is_active' => true,
        ]);

        $this->surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->resident1->doctor->id,
            'department_id' => $department->id,
            'room_id' => $room->id,
            'operation_theater_id' => $theater->id,
            'surgery_name' => 'استئصال المرارة بالناظور',
            'surgery_type' => 'major',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'start_time' => now()->subHour(),
            'room_checkin_at' => now(),
            'status' => 'in_progress',
        ]);

        // Medicines in catalog
        $this->med1 = Medicine::create([
            'name' => 'Ceftriaxone',
            'strength' => '1g',
            'dosage_form' => 'Vial (IV)',
            'price' => 5000,
            'is_active' => true,
        ]);

        $this->med2 = Medicine::create([
            'name' => 'Paracetamol IV',
            'strength' => '1000mg',
            'dosage_form' => 'Infusion bottle',
            'price' => 3000,
            'is_active' => true,
        ]);
    }

    public function test_shared_medicine_packages_appear_to_all_residents_in_clinical_station(): void
    {
        // Create a shared package owned by resident 1
        $sharedPkg = UserMedicineGroup::create([
            'user_id' => $this->resident1->id,
            'name' => 'بروتوكول ما بعد الزائدة المشترك',
            'description' => 'باقة عامة لمضادات ومسكنات ما بعد الجراحة',
            'department' => 'surgery',
            'is_public' => true,
            'is_starred' => true,
            'usage_count' => 10,
        ]);
        $sharedPkg->medicines()->attach($this->med1->id, [
            'dosage' => '1g',
            'dosage_form' => 'Vial',
            'frequency' => 'q12h',
            'duration' => '3',
            'instructions' => 'IV بعد فحص التحسس',
        ]);

        // Login as Resident 2 (a different doctor) and view clinical station treatments tab
        $res = $this->actingAs($this->resident2)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'tab' => 'treatments',
            'phase' => 'post_op',
        ]));

        $res->assertStatus(200);
        $res->assertSee('باقات الأدوية المشتركة للمقيمين');
        $res->assertSee('بروتوكول ما بعد الزائدة المشترك');
        $res->assertSee('Ceftriaxone');
    }

    public function test_resident_can_apply_shared_medicine_package_in_one_click(): void
    {
        $sharedPkg = UserMedicineGroup::create([
            'user_id' => $this->admin->id,
            'name' => 'بروتوكول العمليات العامة',
            'department' => 'surgery',
            'is_public' => true,
            'usage_count' => 5,
        ]);

        $sharedPkg->medicines()->attach($this->med1->id, [
            'dosage' => '1g',
            'dosage_form' => 'Vial',
            'frequency' => 'q12h',
            'duration' => '3',
        ]);

        $sharedPkg->medicines()->attach($this->med2->id, [
            'dosage' => '1000mg',
            'dosage_form' => 'Infusion',
            'frequency' => 'q8h',
            'duration' => '2',
        ]);

        // Resident 2 applies the shared package
        $response = $this->actingAs($this->resident2)->post(route('clinical-station.treatments.apply-package', $this->surgery), [
            'group_id' => $sharedPkg->id,
            'phase' => 'post_op',
            'start_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'post_op',
            'tab' => 'treatments',
        ]));

        // Verify treatments were created for the surgery
        $this->assertDatabaseHas('surgery_treatments', [
            'surgery_id' => $this->surgery->id,
            'medicine_id' => $this->med1->id,
            'dosage' => '1g',
            'timing' => 'q12h',
            'prescribed_by' => $this->resident2->id,
        ]);

        $this->assertDatabaseHas('surgery_treatments', [
            'surgery_id' => $this->surgery->id,
            'medicine_id' => $this->med2->id,
            'dosage' => '1000mg',
            'timing' => 'q8h',
            'prescribed_by' => $this->resident2->id,
        ]);

        // Verify package usage count was incremented
        $this->assertEquals(6, $sharedPkg->fresh()->usage_count);
    }

    public function test_resident_can_save_current_treatments_as_shared_package_for_all_colleagues(): void
    {
        // 1. Manually add treatments for the surgery
        SurgeryTreatment::create([
            'surgery_id' => $this->surgery->id,
            'item_type' => SurgeryTreatment::TYPE_MEDICATION,
            'medicine_id' => $this->med1->id,
            'description' => 'Ceftriaxone 1g',
            'dosage' => '1g',
            'route' => 'IV',
            'timing' => 'q12h',
            'duration_value' => 3,
            'duration_unit' => 'days',
            'start_at' => now(),
            'prescribed_by' => $this->resident1->id,
        ]);

        SurgeryTreatment::create([
            'surgery_id' => $this->surgery->id,
            'item_type' => SurgeryTreatment::TYPE_MEDICATION,
            'medicine_id' => null,
            'description' => 'Omeprazole 40mg IV vial',
            'dosage' => '40mg',
            'route' => 'IV',
            'timing' => 'q24h',
            'duration_value' => 3,
            'duration_unit' => 'days',
            'start_at' => now(),
            'prescribed_by' => $this->resident1->id,
        ]);

        // 2. Resident 1 saves as shared package
        $saveRes = $this->actingAs($this->resident1)->post(route('clinical-station.treatments.save-package', $this->surgery), [
            'name' => 'بروتوكول المرارة والمعدة',
            'description' => 'مضاد حيوي مع حامي معدة وريدي',
            'phase' => 'post_op',
        ]);

        $saveRes->assertSessionHasNoErrors();

        // 3. Verify UserMedicineGroup was created as public and department=surgery
        $this->assertDatabaseHas('user_medicine_groups', [
            'name' => 'بروتوكول المرارة والمعدة',
            'department' => 'surgery',
            'is_public' => true,
            'user_id' => $this->resident1->id,
        ]);

        $newGroup = UserMedicineGroup::where('name', 'بروتوكول المرارة والمعدة')->first();
        $this->assertNotNull($newGroup);
        $this->assertTrue((bool) $newGroup->is_public);
        $this->assertEquals(1, $newGroup->medicines()->count());
        $this->assertEquals(1, $newGroup->customItems()->count());

        // 4. Resident 2 immediately sees this new shared package in their station
        $res2 = $this->actingAs($this->resident2)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'tab' => 'treatments',
            'phase' => 'post_op',
        ]));

        $res2->assertStatus(200);
        $res2->assertSee('بروتوكول المرارة والمعدة');
    }

    public function test_nurse_without_prescribe_permission_cannot_apply_or_save_packages(): void
    {
        $sharedPkg = UserMedicineGroup::create([
            'user_id' => $this->admin->id,
            'name' => 'باقة سريرية',
            'is_public' => true,
        ]);

        // Nurse tries to apply package -> denied
        $applyRes = $this->actingAs($this->nurse)->post(route('clinical-station.treatments.apply-package', $this->surgery), [
            'group_id' => $sharedPkg->id,
        ]);
        $applyRes->assertSessionHas('error');

        // Nurse tries to save package -> denied
        $saveRes = $this->actingAs($this->nurse)->post(route('clinical-station.treatments.save-package', $this->surgery), [
            'name' => 'باقة تمريض',
        ]);
        $saveRes->assertSessionHas('error');
    }
}
