<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\LabTest;
use App\Models\UserLabTestGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DoctorLabQuickPackagesTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $doctor;
    protected $visit;
    protected $group;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('doctor', 'web');

        Permission::findOrCreate('manage own visits', 'web');
        Permission::findOrCreate('view own visits', 'web');

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $this->doctorUser = User::create([
            'name' => 'د. حسام علي',
            'email' => 'dr_hussam@hospital.test',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo(['manage own visits', 'view own visits']);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-1234',
            'consultation_fee' => 15000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $patientUser = User::create([
            'name' => 'محمد كريم',
            'email' => 'mohammed_p@hospital.test',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'gender' => 'male',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => $hospital->id,
            'national_id' => '1234567899',
            'phone' => '07711223399',
            'age' => 40,
            'blood_type' => 'A+',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص دوري',
            'status' => 'in_progress',
        ]);

        // إنشاء تحاليل مخبرية
        $test1 = LabTest::create([
            'name' => 'Complete Blood Count',
            'code' => 'CBC',
            'price' => 10000,
            'is_active' => true,
            'main_category' => 'hematology',
            'subcategory' => 'hematology',
        ]);

        $test2 = LabTest::create([
            'name' => 'Fasting Blood Sugar',
            'code' => 'FBS',
            'price' => 5000,
            'is_active' => true,
            'main_category' => 'biochemistry',
            'subcategory' => 'biochemistry',
        ]);

        // إنشاء باقة تحاليل للطبيب
        $this->group = UserLabTestGroup::create([
            'user_id' => $this->doctorUser->id,
            'name' => 'باقة السكر والدم الشاملة',
            'description' => 'باقة دورية',
        ]);

        $this->group->labTests()->attach([$test1->id, $test2->id]);
    }

    /** @test */
    public function user_lab_test_group_has_tests_accessor_and_relation()
    {
        $this->assertEquals(2, $this->group->labTests->count());
        $this->assertEquals(2, $this->group->tests->count());
    }

    /** @test */
    public function doctor_visit_page_renders_quick_package_buttons_with_test_ids()
    {
        $response = $this->actingAs($this->doctorUser)
            ->get(route('doctor.visits.show', $this->visit));

        $response->assertOk();
        $response->assertSee('doc-lab-quick-pkg-btn');
        $response->assertSee('data-test-ids=');
        $response->assertDontSee('data-tests="null"');
        $response->assertSee('باقة السكر والدم الشاملة');
        $response->assertSee('docLabActiveFilterNotice');
        $response->assertSee('docLabFilterSelectedBtn');
    }
}
