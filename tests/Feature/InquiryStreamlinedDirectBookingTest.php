<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InquiryStreamlinedDirectBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $receptionist;

    protected Hospital $hospital;

    protected Department $department;

    protected Department $inquiryDepartment;

    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'radiology_ultrasound', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'radiology_echo', 'guard_name' => 'web']);

        $this->receptionist = User::factory()->create(['name' => 'موظف استعلامات']);
        $this->receptionist->assignRole($role);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'email' => 'info@alkafaat.test',
            'owner_name' => 'المدير العام',
            'phone' => '0770000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-'.uniqid(),
        ]);

        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'العيادات الاستشارية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'is_active' => true,
        ]);

        $this->inquiryDepartment = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'الاستعلامات',
            'type' => 'other',
            'room_number' => '001',
            'consultation_fee' => 0,
            'working_hours_start' => '00:00',
            'working_hours_end' => '23:59',
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. حسام الاستشاري']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'hospital_id' => $this->hospital->id,
            'department_id' => $this->department->id,
            'specialization' => 'باطنية',
            'qualification' => 'استشاري',
            'license_number' => 'DOC-'.uniqid(),
            'consultation_fee' => 25000,
            'doctor_share_percentage' => 70,
            'hospital_share_percentage' => 30,
            'type' => 'consultant',
            'is_active' => true,
            'is_available_today' => true,
        ]);
    }

    public function test_inquiry_create_page_opens_without_patient_id_and_shows_live_search_and_services_on_same_page(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->get(route('inquiry.create'));

        $response->assertOk();
        $response->assertSee('patientLiveSearchInput');
        $response->assertSee('مريض جديد');
        $response->assertSee('لوحة التحويلات والإحالات');
        $response->assertSee('الخدمات الطبية المباشرة');
        $response->assertSee('requestForm');
        $response->assertSee('emptyPatientPrompt');
    }

    public function test_inquiry_search_redirects_directly_to_inquiry_create(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->get(route('inquiry.search'));

        $response->assertRedirect(route('inquiry.create'));
    }

    public function test_inquiry_search_patients_ajax_finds_by_name_phone_and_file_id(): void
    {
        $patientUser = User::factory()->create([
            'name' => 'سارة علي أحمد',
            'phone' => '07712345678',
        ]);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'created_by' => $this->receptionist->id,
            'blood_type' => 'O+',
        ]);

        // البحث بالاسم
        $resName = $this->actingAs($this->receptionist)
            ->getJson(route('inquiry.search.patients', ['query' => 'سارة']));
        $resName->assertOk();
        $resName->assertJsonFragment(['id' => $patient->id]);

        // البحث برقم الهاتف
        $resPhone = $this->actingAs($this->receptionist)
            ->getJson(route('inquiry.search.patients', ['query' => '123456']));
        $resPhone->assertOk();
        $resPhone->assertJsonFragment(['id' => $patient->id]);

        // البحث برقم الملف
        $resId = $this->actingAs($this->receptionist)
            ->getJson(route('inquiry.search.patients', ['query' => (string) $patient->id]));
        $resId->assertOk();
        $resId->assertJsonFragment(['id' => $patient->id]);
    }

    public function test_inquiry_create_page_with_patient_id_renders_booking_form_and_change_patient_button(): void
    {
        $patientUser = User::factory()->create([
            'name' => 'محمد قاسم حسن',
            'phone' => '07809998877',
        ]);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'created_by' => $this->receptionist->id,
            'blood_type' => 'A+',
        ]);

        $response = $this->actingAs($this->receptionist)
            ->get(route('inquiry.create', ['patient_id' => $patient->id]));

        $response->assertOk();
        $response->assertSee('محمد قاسم حسن');
        $response->assertSee('ملف #'.$patient->id);
        $response->assertSee('تغيير المريض');
        $response->assertSee('الخدمات الطبية المباشرة');
        $response->assertSee('requestForm');
    }
}
