<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\LabTest;
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

    public function test_booked_surgery_without_lab_tests_appears_in_unified_table(): void
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
        $response->assertSee('بانتظار تحديد الفحوصات');
        $this->assertEquals(1, $response->viewData('counts')['pending_selection']);
        $this->assertEquals(1, $response->viewData('counts')['all']);
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
    }

    public function test_selecting_tests_creates_tests_and_redirects_to_show_results_interface(): void
    {
        $patientUser = User::factory()->create(['name' => 'مريض فحص الدم']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '12345678901238',
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
            'surgery_type' => 'عملية الزائدة الدودية',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'status' => 'scheduled',
        ]);

        $placeholderTest = $surgery->labTests()->create([
            'lab_test_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $t1 = LabTest::create(['name' => 'تعداد الدم الكامل CBC', 'code' => 'CBC01', 'price' => 15000, 'is_active' => true]);
        $t2 = LabTest::create(['name' => 'سكر الدم العشوائي RBS', 'code' => 'RBS01', 'price' => 5000, 'is_active' => true]);

        $response = $this->actingAs($this->labStaff)->put(route('staff.surgery-lab-tests.select-tests', $placeholderTest), [
            'lab_test_ids' => [$t1->id, $t2->id],
        ]);

        $firstTest = $surgery->fresh()->labTests()->whereNotNull('lab_test_id')->first();
        $this->assertNotNull($firstTest);
        $response->assertRedirect(route('staff.surgery-lab-tests.show', $firstTest));
        $this->assertEquals(2, $surgery->fresh()->labTests()->count());
    }

    public function test_submitting_results_as_draft_and_complete(): void
    {
        $patientUser = User::factory()->create(['name' => 'مريض مسودة واكتمال']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '12345678901239',
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
            'surgery_type' => 'عملية تكميم المعدة',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'status' => 'scheduled',
        ]);

        $t1 = LabTest::create(['name' => 'هيموغلوبين الدم Hb', 'code' => 'HB01', 'price' => 5000, 'is_active' => true]);
        $sTest = $surgery->labTests()->create([
            'lab_test_id' => $t1->id,
            'status' => 'pending',
            'payment_status' => 'paid',
        ]);

        // 1. اختبار الحفظ المؤقت (مسودة)
        $draftResponse = $this->actingAs($this->labStaff)->put(route('staff.surgery-lab-tests.update-all', $surgery), [
            'action' => 'draft',
            'test_results' => [
                $sTest->id => [
                    'test_id' => $sTest->id,
                    'value' => '13.5',
                    'unit' => 'g/dL',
                ],
            ],
            'notes' => 'مسودة أولية',
        ]);

        $draftResponse->assertRedirect(route('staff.surgery-lab-tests.show', $sTest));
        $this->assertEquals('in_progress', $sTest->fresh()->status);
        $this->assertEquals('13.5', $sTest->fresh()->result);
        $this->assertNull($sTest->fresh()->completed_at);

        // 2. اختبار الاعتماد النهائي
        $completeResponse = $this->actingAs($this->labStaff)->put(route('staff.surgery-lab-tests.update-all', $surgery), [
            'action' => 'complete',
            'test_results' => [
                $sTest->id => [
                    'test_id' => $sTest->id,
                    'value' => '13.5',
                    'unit' => 'g/dL',
                ],
            ],
            'notes' => 'تم الاعتماد النهائي',
        ]);

        $completeResponse->assertRedirect(route('staff.surgery-lab-tests.show', $sTest));
        $this->assertEquals('completed', $sTest->fresh()->status);
        $this->assertNotNull($sTest->fresh()->completed_at);
    }

    public function test_appending_extra_tests_to_surgery(): void
    {
        $patientUser = User::factory()->create(['name' => 'مريض إلحاق تحاليل']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '12345678901240',
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
            'surgery_type' => 'عملية استئصال المرارة',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'status' => 'scheduled',
        ]);

        $t1 = LabTest::create(['name' => 'تحليل سكر', 'code' => 'SUG01', 'price' => 5000, 'is_active' => true]);
        $sTest = $surgery->labTests()->create([
            'lab_test_id' => $t1->id,
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $extraTest = LabTest::create(['name' => 'تحليل وظائف كلى', 'code' => 'KID01', 'price' => 12000, 'is_active' => true]);

        $response = $this->actingAs($this->labStaff)->post(route('staff.surgery-lab-tests.append', $surgery), [
            'extra_lab_test_ids' => [$extraTest->id],
        ]);

        $response->assertRedirect(route('staff.surgery-lab-tests.show', $sTest));
        $this->assertEquals(2, $surgery->fresh()->labTests()->count());
        $this->assertDatabaseHas('surgery_lab_tests', [
            'surgery_id' => $surgery->id,
            'lab_test_id' => $extraTest->id,
            'status' => 'pending',
        ]);
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

    public function test_show_page_for_pending_selection_surgery_renders_packages_and_test_selection_identical_to_general_lab(): void
    {
        $patientUser = User::factory()->create(['name' => 'مريض الباقات']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '12345678901241',
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
            'surgery_type' => 'عملية جراحية تجريبية',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'status' => 'scheduled',
        ]);

        $placeholderTest = $surgery->labTests()->create([
            'lab_test_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $package = \App\Models\Package::create([
            'name' => 'باقة تحاليل ما قبل العملية الكاملة',
            'code' => 'PREOP01',
            'price' => 75000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->labStaff)
            ->get(route('staff.surgery-lab-tests.show', $placeholderTest));

        $response->assertStatus(200);
        $response->assertSee('باقات المختبر الطبية (البكجات Packages):');
        $response->assertSee('باقة تحاليل ما قبل العملية الكاملة');
        $response->assertSee('تحديد واختيار التحاليل المطلوبة لهذه العملية');
        $response->assertSee('مريض الباقات');
        $response->assertSee('بيانات المريض والعملية الجراحية');
    }

    public function test_print_page_renders_modern_clinical_report_with_patient_surgery_and_test_details(): void
    {
        $room = Room::create([
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

        $patientUser = User::factory()->create([
            'name' => 'حسام عبد الستار حسين',
            'gender' => 'male',
            'phone' => '07712345678',
        ]);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '12345678901245',
            'age' => 38,
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. احمد اسامة']);
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
            'room_id' => $room->id,
            'surgery_type' => 'عملية استئصال الزائدة الدودية',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'status' => 'scheduled',
        ]);

        $t1 = LabTest::create([
            'name' => 'Complete Blood Count CBC',
            'code' => 'CBC01',
            'main_category' => 'hematology',
            'unit' => 'g/dL',
            'reference_range' => '12.0 - 16.0',
            'price' => 15000,
            'is_active' => true,
        ]);

        $sTest = $surgery->labTests()->create([
            'lab_test_id' => $t1->id,
            'status' => 'completed',
            'result' => '14.2',
            'notes' => 'فحوصات ما قبل الصالة سليمة',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($this->labStaff)
            ->get(route('staff.surgery-lab-tests.print', $sTest));

        $response->assertStatus(200);
        $response->assertSee('SURGERY CLINICAL LABORATORY REPORT');
        $response->assertSee('حسام عبد الستار حسين');
        $response->assertSee('غرفة 302 (الطابق الثالث)');
        $response->assertSee('عملية استئصال الزائدة الدودية');
        $response->assertSee('Complete Blood Count CBC');
        $response->assertSee('14.2');
        $response->assertSee('فحوصات ما قبل الصالة سليمة');
        $response->assertSee('مستشفى الفرات الأهلي');
        $response->assertSee('طباعة التقرير');
        $response->assertSee('ورق مروس (Letterhead)');
    }
}
