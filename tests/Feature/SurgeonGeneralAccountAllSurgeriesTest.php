<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurgeonGeneralAccountAllSurgeriesTest extends TestCase
{
    use RefreshDatabase;

    protected User $generalSurgeonUser;

    protected Doctor $surgeonDoctor;

    protected Department $department;

    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'الجراح', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view surgeon station', 'guard_name' => 'web']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-001',
            'phone' => '07700000000',
            'email' => 'info@alkafaat.com',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '101',
            'consultation_fee' => 50000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 30,
            'is_active' => true,
        ]);

        // المستخدم الموحد: حساب الجراحين العام
        $this->generalSurgeonUser = User::factory()->create([
            'name' => 'حساب الجراحين العام',
            'email' => 'gh@hospital.com',
        ]);
        $this->generalSurgeonUser->assignRole('الجراح');
        $this->generalSurgeonUser->givePermissionTo('view surgeon station');

        // جراح حقيقي
        $docUser = User::factory()->create(['name' => 'د. حيدر المعموري']);
        $this->surgeonDoctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'phone' => '07712345678',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-SURG-01',
            'type' => 'surgeon',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'مريض قيد الانتظار']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'file_number' => 'PAT-100',
            'gender' => 'male',
            'age' => 35,
        ]);
    }

    public function test_general_surgeon_account_sees_all_active_surgeries_regardless_of_ot_status(): void
    {
        // عملية 1: في صالة العمليات قيد الإجراء (بدون إكمال OT)
        $surgery1 = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->surgeonDoctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'استئصال مرارة بالناظور',
            'surgery_name' => 'استئصال مرارة بالناظور',
            'status' => 'in_progress',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
        ]);

        // عملية 2: مجدولة وفي الانتظار لم تدخل الصالة بعد
        $surgery2 = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => null, // بانتظار التعيين
            'department_id' => $this->department->id,
            'surgery_type' => 'ترميم فتق إربي',
            'surgery_name' => 'ترميم فتق إربي',
            'status' => 'waiting',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '11:00:00',
        ]);

        // عملية 3: ملغاة (يجب ألا تظهر)
        $surgery3 = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->surgeonDoctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية ملغاة تجريبية',
            'surgery_name' => 'عملية ملغاة تجريبية',
            'status' => 'cancelled',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '12:00:00',
        ]);

        $response = $this->actingAs($this->generalSurgeonUser)
            ->get(route('surgeon-station.index'));

        $response->assertStatus(200);
        $response->assertSee('صالة العمليات - الحساب الموحد لكافة الجراحين');
        $response->assertSee('استئصال مرارة بالناظور');
        $response->assertSee('ترميم فتق إربي');
        $response->assertDontSee('عملية ملغاة تجريبية');
        $response->assertSee('بانتظار التعيين');
    }

    public function test_general_surgeon_account_can_filter_unassigned_surgeries(): void
    {
        // عملية مسندة لطبيب
        Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->surgeonDoctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية د. حيدر',
            'surgery_name' => 'عملية د. حيدر',
            'status' => 'scheduled',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
        ]);

        // عملية غير مسندة
        Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => null,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية بدون جراح معين',
            'surgery_name' => 'عملية بدون جراح معين',
            'status' => 'waiting',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '11:00:00',
        ]);

        $response = $this->actingAs($this->generalSurgeonUser)
            ->get(route('surgeon-station.index', ['doctor_id' => 'unassigned']));

        $response->assertStatus(200);
        $response->assertSee('عملية بدون جراح معين');
        $response->assertDontSee('عملية د. حيدر');
    }

    public function test_general_surgeon_account_can_access_station_show_without_ot_completed(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->surgeonDoctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية فحص سريري',
            'surgery_name' => 'عملية فحص سريري',
            'status' => 'in_progress',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:30:00',
        ]);

        // يمكن فتح المحطة دون حجب 403 أو إعادة توجيه لخطأ إتمام الصالة
        $response = $this->actingAs($this->generalSurgeonUser)
            ->get(route('surgeon-station.show', $surgery));

        $response->assertStatus(200);
        $response->assertSee('محطة الجراح - العملية رقم '.$surgery->id);
    }

    public function test_general_surgeon_account_redirects_to_station_on_login(): void
    {
        $this->actingAs($this->generalSurgeonUser)
            ->get('/')
            ->assertRedirect(route('surgeon-station.index'));

        $this->actingAs($this->generalSurgeonUser)
            ->get('/dashboard')
            ->assertRedirect(route('surgeon-station.index'));
    }
}
