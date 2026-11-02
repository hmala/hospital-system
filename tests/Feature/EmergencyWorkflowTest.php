<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Emergency;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $doctorUser;

    protected $doctor;

    protected $emergency;

    protected $medicine;

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
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'د. حسام',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-LIC-'.uniqid(),
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
            'specialization' => 'طب الطوارئ والحوادث',
            'qualification' => 'دكتوراه طب طوارئ',
            'consultation_fee' => 10000,
            'license_number' => 'DOC-ER-'.rand(1000, 9999),
            'status' => 'active',
        ]);

        $patientUser = User::factory()->create(['name' => 'مريض طوارئ تجريبي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'medical_number' => 'MED-ER-'.rand(1000, 9999),
            'birth_date' => '1995-05-15',
            'gender' => 'male',
            'blood_group' => 'O+',
        ]);

        $this->medicine = Medicine::create([
            'name' => 'Paracetamol 500mg IV',
            'generic_name' => 'Paracetamol',
            'dosage_form' => 'Infusion/Vial',
            'strength' => '500mg',
            'price' => 3000,
            'is_active' => true,
        ]);

        $this->emergency = Emergency::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'admission_time' => now(),
            'priority' => 'yellow',
            'emergency_type' => 'medical',
            'symptoms' => 'ألم حاد ومغص كلوي',
            'status' => 'waiting',
            'payment_status' => 'paid',
        ]);
    }

    public function test_emergency_create_screen_renders_with_live_patient_search()
    {
        $response = $this->actingAs($this->admin)->get(route('emergency.create'));

        $response->assertStatus(200);
        $response->assertSee('patientSearchInput');
        $response->assertSee('ابحث باسم المريض');
        $response->assertDontSee('<select class="form-select @error(\'patient_id\')', false);
    }

    public function test_doctor_visiting_emergency_create_is_auto_assigned_as_responsible_doctor()
    {
        $response = $this->actingAs($this->doctorUser)->get(route('emergency.create'));

        $response->assertStatus(200);
        $response->assertSee($this->doctorUser->name);
        $response->assertSee('value="'.$this->doctor->id.'"', false);
        $response->assertDontSee('<select class="form-select @error(\'doctor_id\')', false);
    }

    public function test_emergency_search_patients_ajax_returns_matching_results()
    {
        $response = $this->actingAs($this->admin)->getJson(route('emergency.search-patients', ['query' => 'تجريبي']));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'مريض طوارئ تجريبي',
        ]);
    }

    public function test_emergency_index_displays_clean_consultation_button()
    {
        $response = $this->actingAs($this->admin)->get(route('emergency.index'));

        $response->assertStatus(200);
        $response->assertSee('فتح ملف الكشف');
        $response->assertSee(route('emergency.show', $this->emergency));
    }

    public function test_emergency_show_renders_workstation_with_clinical_tabs()
    {
        $response = $this->actingAs($this->admin)->get(route('emergency.show', $this->emergency));

        $response->assertStatus(200);
        $response->assertSee('طلب استشارة');
        $response->assertSee('#consultationModal');
        $response->assertSee(route('emergency.create-consultation', $this->emergency));
        $response->assertSee('العلامات الحيوية');
        $response->assertSee('التشخيص والخدمات');
        $response->assertSee('المختبر والأشعة');
        $response->assertSee('الأدوية وعلاج الطوارئ');
        $response->assertDontSee('المالية والسجل');
        $response->assertSee('Paracetamol 500mg IV');
    }

    public function test_submitting_emergency_treatments_updates_emergency()
    {
        $response = $this->actingAs($this->admin)->post(route('emergency.treatments.store', $this->emergency), [
            'prescribed_medications' => [
                [
                    'medicine_id' => $this->medicine->id,
                    'medicine_name' => 'Paracetamol 500mg IV',
                    'dosage_form' => 'Infusion/Vial',
                    'frequency' => 'عند اللزوم (PRN)',
                    'instructions' => 'يؤخذ وريدياً ببطء',
                ],
            ],
            'treatment_notes' => 'تم إعطاء الجرعة الإسعافية الأولى',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('emergency_treatments', [
            'emergency_id' => $this->emergency->id,
            'treatment_type' => 'drip',
        ]);

        $this->emergency->refresh();
        $this->assertEquals('in_progress', $this->emergency->status);
    }

    public function test_emergency_discharge_workflow()
    {
        $response = $this->actingAs($this->admin)->post(route('emergency.discharge', $this->emergency), [
            'discharge_type' => 'recovered',
        ]);

        $response->assertRedirect();
        $this->emergency->refresh();
        $this->assertEquals('discharged', $this->emergency->status);
        $this->assertEquals('recovered', $this->emergency->discharge_type);
    }

    public function test_emergency_discharge_transferred_to_consultation_workflow()
    {
        $response = $this->actingAs($this->admin)->post(route('emergency.discharge', $this->emergency), [
            'discharge_type' => 'transferred_to_consultation',
            'discharge_notes' => 'تحويل إلى العيادة الاستشارية الباطنية للمتابعة',
        ]);

        $response->assertRedirect();
        $this->emergency->refresh();
        $this->assertEquals('discharged', $this->emergency->status);
        $this->assertEquals('transferred_to_consultation', $this->emergency->discharge_type);
        $this->assertEquals('تحويل إلى العيادة الاستشارية الباطنية للمتابعة', $this->emergency->discharge_notes);
    }

    public function test_nursing_request_status_update()
    {
        $visit = \App\Models\Visit::create([
            'patient_id' => $this->emergency->patient_id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->doctor->department_id,
            'visit_date' => now()->toDateString(),
            'visit_time' => now()->toTimeString(),
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص روتيني وسوائل',
            'status' => 'completed',
        ]);

        $nursingRequest = \App\Models\Request::create([
            'visit_id' => $visit->id,
            'type' => 'nursing',
            'description' => 'طلب خدمات تمريضية: إعطاء سائل سول',
            'status' => 'pending',
            'payment_status' => 'paid',
            'details' => ['nursing_service_names' => ['إعطاء سائل سول']],
        ]);

        $response = $this->actingAs($this->admin)->put(route('emergency.nursing-request.update', $nursingRequest), [
            'status' => 'in_progress',
        ]);

        $response->assertRedirect();
        $nursingRequest->refresh();
        $this->assertEquals('in_progress', $nursingRequest->status);

        $response2 = $this->actingAs($this->admin)->put(route('emergency.nursing-request.update', $nursingRequest), [
            'status' => 'completed',
        ]);

        $response2->assertRedirect();
        $nursingRequest->refresh();
        $this->assertEquals('completed', $nursingRequest->status);

        // تختفي من حالات اليوم النشطة
        $todayView = $this->actingAs($this->admin)->get(route('emergency.index', ['filter' => 'today']));
        $todayView->assertDontSee('إعطاء سائل سول');

        // تظهر في تبويب المغادرين / المكتملين
        $dischargedView = $this->actingAs($this->admin)->get(route('emergency.index', ['filter' => 'discharged']));
        $dischargedView->assertSee('إعطاء سائل سول');
    }

    public function test_requesting_emergency_consultation_attaches_emergency_service_and_schedules_appointment()
    {
        // 1. إنشاء خدمة الاستشارة بسعر 30,000
        $service = \App\Models\EmergencyService::firstOrCreate(
            ['id' => 115],
            [
                'name' => 'استشارة اخصائي من العيادة الاستشارية',
                'price' => 30000,
                'category' => 'طوارئ عامة',
                'is_active' => true,
            ]
        );

        // 2. إنشاء طبيب استشاري
        $consultantUser = User::factory()->create(['name' => 'د. أحمد الباطني']);
        $consultantUser->assignRole('doctor');

        $consultantDoctor = Doctor::create([
            'user_id' => $consultantUser->id,
            'department_id' => $this->doctor->department_id,
            'specialization' => 'أمراض باطنية وقلبية',
            'qualification' => 'بورد عربي باطنية',
            'type' => 'consultant',
            'consultation_fee' => 30000,
            'license_number' => 'DOC-CON-'.rand(1000, 9999),
            'status' => 'active',
            'is_active' => true,
            'is_available_today' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('emergency.create-consultation', $this->emergency), [
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '12:00',
            'doctor_id' => $consultantDoctor->id,
            'notes' => 'المريض يعاني من ألم صدري حاد ومطلوب استشارة باطنية فورية',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // تحقق من إلحاق خدمة الاستشارة للطوارئ بكمية 1
        $this->assertDatabaseHas('emergency_emergency_service', [
            'emergency_id' => $this->emergency->id,
            'emergency_service_id' => $service->id,
            'quantity' => 1,
        ]);

        // تحقق من إنشاء الموعد للطبيب الاستشاري برسم 0 (محسوب عبر خدمة الطوارئ)
        $this->assertDatabaseHas('appointments', [
            'emergency_id' => $this->emergency->id,
            'doctor_id' => $consultantDoctor->id,
            'consultation_fee' => 0,
            'status' => 'scheduled',
        ]);

        // تحقق من انعكاس الخدمة (30,000 د.ع) في فاتورة الطوارئ الموحدة
        $this->emergency->refresh();
        $this->assertNotNull($this->emergency->payment);
        $this->assertEquals(30000, (float) $this->emergency->payment->amount);
    }
}
