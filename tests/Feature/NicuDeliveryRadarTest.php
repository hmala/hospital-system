<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\OperationTheater;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\SurgicalOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NicuDeliveryRadarTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $nicuNurse;

    protected $patientUser;

    protected $hospital;

    protected $department;

    protected $doctor;

    protected $theater;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء الصلاحيات والأدوار الأساسية للاختبار بسرعة
        $viewRadarPerm = Permission::firstOrCreate(['name' => 'view nicu delivery radar', 'guard_name' => 'web']);
        $manageRadarPerm = Permission::firstOrCreate(['name' => 'manage nicu delivery radar settings', 'guard_name' => 'web']);
        $manageSurgOpsPerm = Permission::firstOrCreate(['name' => 'manage surgical operations', 'guard_name' => 'web']);
        $viewSurgOpsPerm = Permission::firstOrCreate(['name' => 'view surgical operations', 'guard_name' => 'web']);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([$viewRadarPerm, $manageRadarPerm, $manageSurgOpsPerm, $viewSurgOpsPerm]);

        $nicuNurseRole = Role::firstOrCreate(['name' => 'nicu_nurse', 'guard_name' => 'web']);
        $nicuNurseRole->givePermissionTo([$viewRadarPerm]);

        $patientRole = Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->nicuNurse = User::factory()->create();
        $this->nicuNurse->assignRole('nicu_nurse');

        $this->patientUser = User::factory()->create();
        $this->patientUser->assignRole('patient');

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الفرات الأهلي',
            'owner_name' => 'د. محمد',
            'license_number' => 'LIC-100',
            'phone' => '07700000000',
            'email' => 'hospital@example.com',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'عيادة النساء والتوليد',
            'type' => 'obstetrics',
            'room_number' => '102',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'code' => 'OBGYN01',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. سارة التوليد']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'نسائية وتوليد',
            'qualification' => 'بورد نسائية وتوليد',
            'license_number' => 'DOC-OBGYN-'.uniqid(),
            'consultation_fee' => 25000,
            'is_active' => true,
        ]);

        $this->theater = OperationTheater::create([
            'name' => 'صالة الولادة والعمليات 1',
            'room_number' => 'OR-101',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_nicu_delivery_radar()
    {
        $response = $this->get(route('nicu.delivery-radar.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_patient_without_permission_cannot_access_nicu_delivery_radar()
    {
        $response = $this->actingAs($this->patientUser)
            ->get(route('nicu.delivery-radar.index'));

        $response->assertStatus(403);
    }

    public function test_nicu_nurse_can_access_radar_and_view_scheduled_deliveries_with_theater()
    {
        // 1. عملية قيصرية مشمولة بالرادار
        $cSectionOp = SurgicalOperation::create([
            'name' => 'القيصرية الثانية المجدولة',
            'category' => 'النسائيه والتوليد',
            'fee' => 0,
            'is_active' => true,
            'show_in_nicu_radar' => true,
        ]);

        // 2. عملية عامة غير مشمولة بالرادار (مثل استئصال المرارة)
        $choleOp = SurgicalOperation::create([
            'name' => 'استئصال المرارة بالناظور',
            'category' => 'الجراحة العامة',
            'fee' => 0,
            'is_active' => true,
            'show_in_nicu_radar' => false,
        ]);

        $patient1 = Patient::create([
            'user_id' => User::factory()->create(['name' => 'زينب علي كريم'])->id,
            'file_number' => 'FN-1001',
            'gender' => 'female',
        ]);

        $patient2 = Patient::create([
            'user_id' => User::factory()->create(['name' => 'عمر خالد حسن'])->id,
            'file_number' => 'FN-1002',
            'gender' => 'male',
        ]);

        // إنشاء العملية القيصرية المرتبطة بالصالة
        $cSectionSurgery = Surgery::create([
            'patient_id' => $patient1->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgical_operation_id' => $cSectionOp->id,
            'surgery_type' => $cSectionOp->name,
            'surgery_category' => 'النسائيه والتوليد',
            'scheduled_date' => today()->format('Y-m-d'),
            'scheduled_time' => '10:00:00',
            'status' => 'in_progress',
            'operation_theater_id' => $this->theater->id,
            'expected_stay_days' => 2,
            'referring_doctor_name' => 'طبيب محيل',
        ]);

        // إنشاء العملية الأخرى في نفس اليوم
        $otherSurgery = Surgery::create([
            'patient_id' => $patient2->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgical_operation_id' => $choleOp->id,
            'surgery_type' => $choleOp->name,
            'surgery_category' => 'الجراحة العامة',
            'scheduled_date' => today()->format('Y-m-d'),
            'scheduled_time' => '11:00:00',
            'status' => 'scheduled',
            'expected_stay_days' => 1,
            'referring_doctor_name' => 'طبيب محيل',
        ]);

        $response = $this->actingAs($this->nicuNurse)
            ->get(route('nicu.delivery-radar.index', ['date' => today()->format('Y-m-d')]));

        $response->assertStatus(200);
        $response->assertSee('زينب علي كريم');
        $response->assertSee('القيصرية الثانية المجدولة');
        $response->assertSee('صالة الولادة والعمليات 1');
        $response->assertSee('جارية الآن داخل الصالة');

        // التأكد من عدم ظهور العملية غير المشمولة
        $response->assertDontSee('استئصال المرارة بالناظور');
        $response->assertDontSee('عمر خالد حسن');
    }

    public function test_admin_can_access_radar_settings_and_toggle_operations()
    {
        $op = SurgicalOperation::create([
            'name' => 'ولادة طبيعية مع إشراف خدر',
            'category' => 'النسائيه والتوليد',
            'fee' => 0,
            'is_active' => true,
            'show_in_nicu_radar' => false,
        ]);

        // 1. فتح شاشة الإعدادات
        $response = $this->actingAs($this->admin)
            ->get(route('nicu.delivery-radar.settings'));

        $response->assertStatus(200);
        $response->assertSee('ولادة طبيعية مع إشراف خدر');
        $response->assertSee('إعدادات العمليات المشمولة برادار الخدج');

        // 2. إرسال تبديل فوري لتفعيل العملية بالرادار
        $toggleResponse = $this->actingAs($this->admin)
            ->postJson(route('nicu.delivery-radar.toggle', $op));

        $toggleResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'show_in_nicu_radar' => true,
                'operation_id' => $op->id,
            ]);

        $this->assertDatabaseHas('surgical_operations', [
            'id' => $op->id,
            'show_in_nicu_radar' => 1,
        ]);

        // 3. إرسال تبديل لإلغاء التفعيل
        $toggleOffResponse = $this->actingAs($this->admin)
            ->postJson(route('nicu.delivery-radar.toggle', $op));

        $toggleOffResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'show_in_nicu_radar' => false,
            ]);

        $this->assertDatabaseHas('surgical_operations', [
            'id' => $op->id,
            'show_in_nicu_radar' => 0,
        ]);
    }

    public function test_creating_surgical_operation_with_radar_flag()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('surgical-operations.store'), [
                'name' => 'عملية قيصرية طارئة جديدة',
                'category' => 'النسائيه والتوليد',
                'is_active' => 1,
                'show_in_nicu_radar' => 1,
            ]);

        $response->assertRedirect(route('surgical-operations.index'));

        $this->assertDatabaseHas('surgical_operations', [
            'name' => 'عملية قيصرية طارئة جديدة',
            'show_in_nicu_radar' => 1,
        ]);
    }
}
