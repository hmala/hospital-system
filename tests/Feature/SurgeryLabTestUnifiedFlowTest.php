<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurgeryLabTestUnifiedFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Department $dept;

    protected User $labStaff;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'lab_staff', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view lab tests', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage surgery lab tests', 'guard_name' => 'web']);

        $hospital = Hospital::firstOrCreate(
            ['name' => 'مستشفى الفرات'],
            [
                'owner_name' => 'د. صاحب المستشفى',
                'phone' => '07700000000',
                'address' => 'بغداد',
                'license_number' => 'HOSP-123',
            ]
        );

        $this->dept = Department::firstOrCreate(
            ['name' => 'قسم الجراحة العامة'],
            [
                'hospital_id' => $hospital->id,
                'type' => 'surgery',
                'room_number' => '101',
                'consultation_fee' => 25000,
                'working_hours_start' => '08:00',
                'working_hours_end' => '16:00',
            ]
        );

        $this->labStaff = User::factory()->create(['name' => 'فني المختبر']);
        $this->labStaff->assignRole('lab_staff');
        $this->labStaff->givePermissionTo(['view lab tests', 'manage surgery lab tests']);
    }

    public function test_booked_surgery_without_lab_tests_appears_in_pending_selection_tab(): void
    {
        $room302 = Room::create([
            'room_number' => '302',
            'room_type' => 'vip',
            'floor' => 'الطابق الثالث',
            'daily_fee' => 100000,
            'status' => 'occupied',
            'beds_count' => 1,
            'has_bathroom' => true,
            'has_tv' => true,
            'has_ac' => true,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'علي حسين الفت']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '12345678901234',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. جراح']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'specialization' => 'جراحة عامة',
            'department_id' => $this->dept->id,
            'qualification' => 'MBChB',
            'license_number' => 'DOC-'.uniqid(),
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->dept->id,
            'room_id' => $room302->id,
            'surgery_type' => 'رفع البواسير بالليزر',
            'surgery_category' => 'جراحة عامة',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'referring_doctor_name' => 'د. محول',
            'custom_surgery_fee' => 500000,
            'status' => 'scheduled',
            'expected_stay_days' => 1,
        ]);

        $response = $this->actingAs($this->labStaff)->get(route('staff.surgery-lab-tests.index'));

        $response->assertStatus(200);
        $response->assertSee('علي حسين الفت');
        $response->assertSee('غرفة 302');
        $response->assertSee('الطابق الثالث');
        $response->assertSee('تحديد باقة التحاليل');
        $this->assertEquals('pending_selection', $response->viewData('activeTab'));
        $this->assertEquals(1, $response->viewData('pendingSelectionCount'));
    }

    public function test_creating_selection_redirects_to_show_page_to_pick_tests(): void
    {
        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '12345678901235',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. جراح']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'specialization' => 'جراحة عامة',
            'department_id' => $this->dept->id,
            'qualification' => 'MBChB',
            'license_number' => 'DOC-'.uniqid(),
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->dept->id,
            'surgery_type' => 'عملية جراحية',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->labStaff)
            ->post(route('staff.surgery-lab-tests.create-selection', $surgery));

        $test = $surgery->fresh()->labTests()->first();
        $this->assertNotNull($test);
        $this->assertNull($test->lab_test_id);
        $response->assertRedirect(route('staff.surgery-lab-tests.show', $test));
        $this->assertNull($test->lab_test_id);
        $response->assertRedirect(route('staff.surgery-lab-tests.show', $test));
    }

    public function test_lab_main_page_shows_banner_and_counter_for_pending_surgery_selection(): void
    {
        $patientUser = User::factory()->create(['name' => 'مريض عملية']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '12345678901236',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. جراح']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'specialization' => 'جراحة عامة',
            'department_id' => $this->dept->id,
            'qualification' => 'MBChB',
            'license_number' => 'DOC-'.uniqid(),
        ]);

        Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->dept->id,
            'surgery_type' => 'عملية جراحية',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->labStaff)->get(route('lab.index'));

        $response->assertStatus(200);
        $response->assertSee('عمليات جراحية محجوزة بانتظار تحديد باقة التحاليل');
        $this->assertEquals(1, $response->viewData('pendingSurgerySelectionCount'));
    }
}
