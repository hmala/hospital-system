<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InquiryInsuranceDataIncompleteTest extends TestCase
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
        $this->receptionist = User::factory()->create(['name' => 'موظف استعلامات الاستشارية']);
        $this->receptionist->assignRole($role);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'email' => 'info@alkafaat.test',
            'owner_name' => 'المدير العام',
            'phone' => '0770000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-' . uniqid(),
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
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'is_active' => true,
        ]);

        \App\Models\ServiceType::firstOrCreate(
            ['name' => 'checkup'],
            ['label' => 'كشف طبي', 'is_active' => true]
        );

        $doctorUser = User::factory()->create([
            'name' => 'د. حسام الاستشاري',
            'email' => 'dr.hussam@alkafaat.test',
        ]);
        $doctorRole = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $doctorUser->assignRole($doctorRole);

        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'hospital_id' => $this->hospital->id,
            'department_id' => $this->department->id,
            'specialization' => 'باطنية',
            'qualification' => 'استشاري',
            'license_number' => 'DOC-' . uniqid(),
            'consultation_fee' => 25000,
            'doctor_share_percentage' => 70,
            'hospital_share_percentage' => 30,
            'type' => 'consultant',
            'is_active' => true,
            'is_available_today' => true,
        ]);
    }

    public function test_inquiry_create_view_locks_insurance_when_patient_data_is_incomplete(): void
    {
        // مريض ضمان بدون بطاقة موحدة وبدون تاريخ ميلاد
        $patientUser = User::factory()->create([
            'name' => 'أحمد صاحب النواقص',
            'phone' => '07701234567',
            'gender' => 'male',
            'date_of_birth' => null, // مفقود
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => $this->hospital->id,
            'insurance_type' => 'hi',
            'insurance_card_no' => 'HI-998877',
            'national_id' => null, // مفقود
        ]);

        $this->assertFalse($patient->isInsuranceDataComplete());
        $missing = $patient->getMissingInsuranceFields();
        $this->assertArrayHasKey('national_id', $missing);
        $this->assertArrayHasKey('date_of_birth', $missing);

        $response = $this->actingAs($this->receptionist)
            ->get(route('inquiry.create', ['patient_id' => $patient->id]));

        $response->assertStatus(200);
        // التحقق من ظهور شارة القفل والتنبيه
        $response->assertSee('مقفل 🔒 (نواقص بملف الضمان)');
        $response->assertSee('البطاقة الموحدة / الرقم الوطني');
        $response->assertSee('تاريخ الميلاد');
        $response->assertSee('disabled');
    }

    public function test_inquiry_store_blocks_insurance_booking_when_patient_data_is_incomplete(): void
    {
        // مريض ضمان ناقص البيانات
        $patientUser = User::factory()->create([
            'name' => 'سعدون عبد الرضا',
            'phone' => null, // مفقود
            'gender' => 'male',
            'date_of_birth' => null, // مفقود
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => $this->hospital->id,
            'insurance_type' => 'moi',
            'insurance_card_no' => 'MOI-12345',
            'national_id' => '198512345678',
        ]);

        $this->assertFalse($patient->isInsuranceDataComplete());

        // محاولة الحجز تحت مظلة الضمان
        $response = $this->actingAs($this->receptionist)
            ->post(route('inquiry.store'), [
                'patient_id' => $patient->id,
                'request_type' => ['checkup'],
                'doctor_id' => $this->doctor->id,
                'appointment_date' => Carbon::today()->toDateString(),
                'apply_insurance' => '1',
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('بيانات المريض غير مكتملة لدى شعبة الضمان', session('error'));
        $this->assertStringContainsString('رقم الهاتف', session('error'));
        $this->assertStringContainsString('تاريخ الميلاد', session('error'));
    }

    public function test_inquiry_store_allows_cash_booking_even_if_insurance_data_is_incomplete(): void
    {
        // نفس المريض الناقص البيانات ولكن يتم حجزه نقداً (كاش)
        $patientUser = User::factory()->create([
            'name' => 'سعدون عبد الرضا',
            'phone' => '07701112233',
            'gender' => 'male',
            'date_of_birth' => null, // مفقود
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => $this->hospital->id,
            'insurance_type' => 'moi',
            'insurance_card_no' => 'MOI-12345',
            'national_id' => null, // مفقود
        ]);

        // الحجز نقداً (apply_insurance = 0)
        $response = $this->actingAs($this->receptionist)
            ->post(route('inquiry.store'), [
                'patient_id' => $patient->id,
                'request_type' => ['checkup'],
                'doctor_id' => $this->doctor->id,
                'appointment_date' => Carbon::today()->toDateString(),
                'apply_insurance' => '0',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
        ]);
    }

    public function test_inquiry_store_allows_insurance_booking_when_all_5_fields_are_complete(): void
    {
        // مريض ببيانات مكتملة 100%
        $patientUser = User::factory()->create([
            'name' => 'علي كامل البيانات',
            'phone' => '07709988776',
            'gender' => 'male',
            'date_of_birth' => '1990-05-15',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => $this->hospital->id,
            'insurance_type' => 'hi',
            'insurance_card_no' => 'HI-554433',
            'national_id' => '199012345678',
        ]);

        $this->assertTrue($patient->isInsuranceDataComplete());
        $this->assertEmpty($patient->getMissingInsuranceFields());

        // الحجز تحت مظلة الضمان
        $response = $this->actingAs($this->receptionist)
            ->post(route('inquiry.store'), [
                'patient_id' => $patient->id,
                'request_type' => ['checkup'],
                'doctor_id' => $this->doctor->id,
                'appointment_date' => Carbon::today()->toDateString(),
                'apply_insurance' => '1',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
        ]);
    }

    public function test_inquiry_can_send_urgent_notification_to_insurance_staff_for_incomplete_patient(): void
    {
        $insuranceRole = Role::firstOrCreate(['name' => 'thaman', 'guard_name' => 'web']);
        $insuranceOfficer = User::factory()->create(['name' => 'موظف شعبة الضمان']);
        $insuranceOfficer->assignRole($insuranceRole);

        // مريض ناقص تاريخ الميلاد والبطاقة الموحدة
        $patientUser = User::factory()->create([
            'name' => 'طارق محتاج تدقيق',
            'phone' => '07705544332',
            'gender' => 'male',
            'date_of_birth' => null,
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => $this->hospital->id,
            'insurance_type' => 'hi',
            'insurance_card_no' => 'HI-112233',
            'national_id' => null,
        ]);

        $response = $this->actingAs($this->receptionist)
            ->postJson(route('inquiry.notify-insurance-incomplete'), [
                'patient_id' => $patient->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // التحقق من وصول الإشعار لموظف الضمان
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $insuranceOfficer->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $insuranceOfficer->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('طلب إكمال نواقص عاجل', $notification->data['title']);
        $this->assertStringContainsString('طارق محتاج تدقيق', $notification->data['message']);
        $this->assertStringContainsString('open_quick=' . $patient->id, $notification->data['url']);
    }
}
