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

class EmergencyStandardReportDataTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $doctorUser;
    protected $doctor;
    protected $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->doctorUser = User::factory()->create();
        $this->doctorUser->assignRole('doctor');

        $hospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-LIC-' . uniqid(),
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
            'specialization' => 'طوارئ',
            'qualification' => 'MBChB',
            'consultation_fee' => 10000,
            'license_number' => 'DOC-ER-999',
            'status' => 'active',
        ]);

        $patientUser = User::factory()->create(['name' => 'علي حسن أحمد', 'phone' => '07712345678']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'emergency_contact' => '07700000000',
        ]);
    }

    public function test_can_create_emergency_with_arrival_mode_and_referred_from()
    {
        $response = $this->actingAs($this->admin)->post(route('emergency.store'), [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'priority' => 'critical',
            'arrival_mode' => 'إسعاف المستشفى (الوصول)',
            'referred_from' => 'مستشفى اليرموك التعليمي',
            'gcs' => '15/15',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('emergencies', [
            'patient_id' => $this->patient->id,
            'priority' => 'critical',
            'arrival_mode' => 'إسعاف المستشفى (الوصول)',
            'referred_from' => 'مستشفى اليرموك التعليمي',
            'gcs' => '15/15',
        ]);
    }

    public function test_can_update_vitals_including_gcs()
    {
        $emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'emergency_type' => 'medical',
            'symptoms' => 'أعراض طارئة',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'admission_time' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('emergency.update-vitals', $emergency), [
            'blood_pressure' => '110/70',
            'heart_rate' => 84,
            'temperature' => 37.2,
            'respiratory_rate' => 18,
            'oxygen_saturation' => 99,
            'gcs' => '14/15',
        ]);

        $response->assertRedirect();

        $emergency->refresh();
        $this->assertEquals('14/15', $emergency->gcs);
    }

    public function test_can_update_medical_with_hpi_and_medico_legal_notes()
    {
        $emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'emergency_type' => 'medical',
            'symptoms' => 'أعراض طارئة',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'admission_time' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('emergency.update-medical', $emergency), [
            'symptoms' => 'ألم حاد في الصدر مع ضيق تنفس',
            'hpi' => 'بدأت الأعراض قبل ساعتين بعد مجهود بدني مع تعرق بارد',
            'diagnosis' => 'اشتباه ذبحة صدرية غير مستقرة',
            'medico_legal_notes' => 'حالة مرضية باطنية طبيعية - لا توجد أي شبهة جنائية',
            'notes' => 'تم وضع المريض تحت المراقبة اللحظية وتخطيط القلب',
        ]);

        $response->assertRedirect();

        $emergency->refresh();
        $this->assertEquals('ألم حاد في الصدر مع ضيق تنفس', $emergency->symptoms);
        $this->assertEquals('بدأت الأعراض قبل ساعتين بعد مجهود بدني مع تعرق بارد', $emergency->hpi);
        $this->assertEquals('اشتباه ذبحة صدرية غير مستقرة', $emergency->diagnosis);
        $this->assertEquals('حالة مرضية باطنية طبيعية - لا توجد أي شبهة جنائية', $emergency->medico_legal_notes);
        $this->assertEquals('تم وضع المريض تحت المراقبة اللحظية وتخطيط القلب', $emergency->notes);
    }

    public function test_discharge_records_destination_and_recommendations()
    {
        $emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'emergency_type' => 'medical',
            'symptoms' => 'أعراض طارئة',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'admission_time' => now(),
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($this->admin)->post(route('emergency.discharge', $emergency), [
            'discharge_type' => 'recovered',
            'discharge_destination' => 'خروج للمنزل',
            'discharge_recommendations' => 'مراجعة العيادة الاستشارية للباطنية بعد 3 أيام',
            'discharge_notes' => 'المريض مستقر تماماً',
        ]);

        $response->assertRedirect();

        $emergency->refresh();
        $this->assertEquals('discharged', $emergency->status);
        $this->assertEquals('recovered', $emergency->discharge_type);
        $this->assertEquals('خروج للمنزل', $emergency->discharge_destination);
        $this->assertEquals('مراجعة العيادة الاستشارية للباطنية بعد 3 أيام', $emergency->discharge_recommendations);
    }
}
