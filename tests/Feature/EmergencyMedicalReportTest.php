<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Emergency;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyMedicalReportTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $doctorUser;
    protected $doctor;
    protected $emergency;
    protected $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->doctorUser = User::factory()->create(['name' => 'د. مهند إسماعيل']);
        $this->doctorUser->assignRole('doctor');

        $hospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deptId = DB::table('departments')->insertGetId([
            'hospital_id' => $hospitalId,
            'name' => 'قسم الطوارئ',
            'type' => 'emergency',
            'room_number' => 'ER-1',
            'consultation_fee' => 10000,
            'working_hours_start' => '00:00:00',
            'working_hours_end' => '23:59:59',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $deptId,
            'specialization' => 'طب الطوارئ والحالات الحرجة',
            'qualification' => 'بكالوريوس طب وجراحة عامة',
            'consultation_fee' => 10000,
            'license_number' => 'DOC-ER-' . rand(1000, 9999),
            'status' => 'active',
            'signature' => 'images/signatures/doctor_244.png',
        ]);

        $patientUser = User::factory()->create(['name' => 'أحمد علي حسن']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'emergency_contact' => '07711112222',
        ]);

        $this->emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'admission_time' => now(),
            'emergency_type' => 'medical',
            'priority' => 'urgent',
            'status' => 'discharged',
            'discharge_type' => 'recovered',
            'discharge_notes' => 'المريض مستقر ويراجع بعد 3 أيام',
            'diagnosis' => 'نزلة معوية حادة Acute Gastroenteritis',
            'symptoms' => 'ألم حاد في البطن مع غثيان وارتفاع حرارة',
            'discharge_time' => now(),
        ]);
    }

    public function test_authenticated_user_can_view_emergency_medical_report()
    {
        $response = $this->actingAs($this->admin)->get(route('emergency.medical-report', $this->emergency));

        $response->assertStatus(200);
        $response->assertSee('التقرير الطبي لحالة طوارئ');
        $response->assertSee('مستشفى الكفاءات الأهلي');
        $response->assertSee('أحمد علي حسن');
        $response->assertSee('د. مهند إسماعيل');
        $response->assertSee('نزلة معوية حادة Acute Gastroenteritis');
        $response->assertSee('نسخة المريض (Patient Copy)');
        $response->assertSee('نسخة الإدارة والأرشيف (Administrative & Archive Copy)');
        $response->assertSee('data:image/png;base64');
        $response->assertSee('توقيع د. مهند إسماعيل');
        $response->assertSee('emergency/' . $this->emergency->id . '/verify-report');
    }

    public function test_guest_can_access_verify_report_page_without_login()
    {
        $response = $this->get(route('emergency.verify-report', $this->emergency));

        $response->assertStatus(200);
        $response->assertSee('بوابة التحقق الرقمي المعتمد');
        $response->assertSee('وثيقة رسمية معتمدة وموثقة');
        $response->assertSee('أحمد علي حسن');
        $response->assertSee('د. مهند إسماعيل');
        $response->assertSee('نزلة معوية حادة');
        $response->assertSee('EMR-' . str_pad($this->emergency->id, 6, '0', STR_PAD_LEFT));
    }

    public function test_unauthorized_user_cannot_view_medical_report()
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('patient');

        $response = $this->actingAs($regularUser)->get(route('emergency.medical-report', $this->emergency));

        $response->assertStatus(403);
    }
}
