<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\IcuAdmission;
use App\Models\IcuBed;
use App\Models\IcuMedicalOrder;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IcuModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $icuDoctorUser;

    protected Doctor $doctor;

    protected Patient $patient;

    protected IcuBed $bed;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء الصلاحيات
        $permissions = [
            'view icu radar',
            'manage icu beds',
            'admit icu patients',
            'record icu vitals',
            'record icu fluid balance',
            'manage icu medical orders',
            'manage icu care plans',
            'discharge icu patients',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($permissions);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->icuDoctorUser = User::factory()->create();
        $doctorRole = Role::firstOrCreate(['name' => 'icu_doctor']);
        $doctorRole->syncPermissions($permissions);
        $this->icuDoctorUser->assignRole('icu_doctor');

        $hospital = \App\Models\Hospital::create([
            'name' => 'المستشفى التخصصي',
            'owner_name' => 'المدير العام',
            'license_number' => 'HOSP-ICU-1',
            'phone' => '07700000000',
            'address' => 'بغداد',
        ]);

        $dept = \App\Models\Department::firstOrCreate(
            ['name' => 'وحدة العناية المركزة'],
            [
                'hospital_id' => $hospital->id,
                'type' => 'other',
                'room_number' => 'ICU-100',
                'consultation_fee' => 25000,
                'working_hours_start' => '08:00',
                'working_hours_end' => '20:00',
                'is_active' => true,
            ]
        );

        $this->doctor = Doctor::create([
            'user_id' => $this->icuDoctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'عناية مركزة وتخدير',
            'qualification' => 'استشاري عناية حرجة',
            'license_number' => 'ICU-DOC-101',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'زيدون عمار علي']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'phone' => '07700000002',
            'gender' => 'male',
        ]);

        $this->bed = IcuBed::create([
            'bed_number' => '1',
            'bed_type' => IcuBed::TYPE_GENERAL,
            'room_number' => 'ICU-301',
            'floor' => 'الطابق الثالث - العناية المركزة',
            'status' => IcuBed::STATUS_AVAILABLE,
            'daily_price' => 150000.00,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_icu_radar()
    {
        $response = $this->actingAs($this->admin)->get(route('icu.index'));

        $response->assertStatus(200);
        $response->assertSee('رادار وحدة العناية المركزة');
        $response->assertSee('سرير #1');
        $response->assertSee('شاغر ومتاح');
    }

    public function test_unauthorized_user_cannot_access_icu_radar()
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)->get(route('icu.index'));
        $response->assertStatus(403);
    }

    public function test_can_search_patients_for_icu_admission_ajax()
    {
        $response = $this->actingAs($this->admin)->getJson(route('icu.search-patients', ['query' => 'زيدون']));
        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->patient->id,
            ]);
    }

    public function test_admit_page_renders_with_instant_search_and_bed_tiles()
    {
        $response = $this->actingAs($this->admin)->get(route('icu.admissions.create'));
        $response->assertStatus(200)
            ->assertSee('patientSearchInput')
            ->assertSee('doctorSearchInput')
            ->assertSee('bed-tile')
            ->assertSee('اختيار سرير العناية المركزة والإقامة');
    }

    public function test_admit_page_preselects_patient_when_patient_id_is_passed()
    {
        $response = $this->actingAs($this->admin)->get(route('icu.admissions.create', ['patient_id' => $this->patient->id]));
        $response->assertStatus(200)
            ->assertSee('زيدون عمار علي')
            ->assertSee('تغيير المريض');
    }

    public function test_inquiry_create_page_displays_icu_admission_card()
    {
        $response = $this->actingAs($this->admin)->get(route('inquiry.create', ['patient_id' => $this->patient->id]));
        $response->assertStatus(200)
            ->assertSee('حجز عناية مركزة (ICU)')
            ->assertSee(route('icu.admissions.create', ['patient_id' => $this->patient->id]));
    }

    public function test_can_admit_patient_to_icu_and_bed_status_becomes_occupied()
    {
        $admissionData = [
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'admission_source' => 'emergency',
            'admission_date' => now()->format('Y-m-d H:i:s'),
            'primary_diagnosis' => 'Severe Sepsis with Septic Shock',
            'icd10_code' => 'R65.21',
            'initial_gcs' => 11,
            'is_ventilated' => 1,
            'is_isolated' => 0,
            'is_insured' => 1,
        ];

        $response = $this->actingAs($this->admin)->post(route('icu.admissions.store'), $admissionData);

        $this->assertDatabaseHas('icu_admissions', [
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'primary_diagnosis' => 'Severe Sepsis with Septic Shock',
            'status' => IcuAdmission::STATUS_ACTIVE,
            'is_ventilated' => true,
        ]);

        // تأكيد تحول حالة السرير إلى مشغول
        $this->assertEquals(IcuBed::STATUS_OCCUPIED, $this->bed->fresh()->status);

        // تأكيد تدوين تقييم GCS الأولي
        $this->assertDatabaseHas('icu_vital_signs', [
            'gcs' => 11,
        ]);

        $admission = IcuAdmission::first();
        $response->assertRedirect(route('icu.show', $admission->id));
    }

    public function test_can_record_hourly_vitals_and_auto_calculate_map()
    {
        $admission = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'admission_source' => 'emergency',
            'admission_date' => now(),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'primary_diagnosis' => 'ARDS',
        ]);

        $vitalData = [
            'gcs' => 10,
            'heart_rate' => 95,
            'systolic_bp' => 120,
            'diastolic_bp' => 80,
            'mean_arterial_pressure' => null, // سيحسب تلقائياً (120 + 2*80)/3 = 93
            'respiratory_rate' => 20,
            'temperature' => 37.4,
            'spo2' => 96,
            'oxygen_device' => 'Mechanical Ventilator',
            'ventilator_mode' => 'SIMV',
            'fio2' => 45,
            'peep' => 6.0,
            'notes' => 'تحسن نسبي في نسبة الأكسجين',
        ];

        $response = $this->actingAs($this->admin)->post(route('icu.vitals.store', $admission->id), $vitalData);
        $response->assertRedirect(route('icu.show', $admission->id));

        $this->assertDatabaseHas('icu_vital_signs', [
            'icu_admission_id' => $admission->id,
            'gcs' => 10,
            'heart_rate' => 95,
            'mean_arterial_pressure' => 93,
            'ventilator_mode' => 'SIMV',
        ]);

        $this->assertTrue($admission->fresh()->is_ventilated);
    }

    public function test_can_record_hourly_fluid_balance_and_auto_calculate_net_balance()
    {
        $admission = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'admission_source' => 'emergency',
            'admission_date' => now(),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'primary_diagnosis' => 'Hypovolemic Shock',
        ]);

        $fluidData = [
            'intake_iv_fluids' => 500,
            'intake_blood_products' => 250,
            'intake_enteral_feeding' => 100,
            'intake_medications' => 50, // total intake = 900
            'output_urine' => 300,
            'output_drains' => 100,
            'output_nasogastric' => 50,
            'output_other' => 0, // total output = 450
            'notes' => 'محلول نورمال سلاين + كيس دم كامل',
        ];

        $response = $this->actingAs($this->admin)->post(route('icu.fluid-balance.store', $admission->id), $fluidData);
        $response->assertRedirect(route('icu.show', $admission->id));

        $this->assertDatabaseHas('icu_fluid_balances', [
            'icu_admission_id' => $admission->id,
            'intake_total' => 900,
            'output_total' => 450,
            'net_balance' => 450, // 900 - 450
        ]);
    }

    public function test_can_create_and_complete_medical_orders()
    {
        $admission = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'admission_source' => 'emergency',
            'admission_date' => now(),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'primary_diagnosis' => 'Septic Shock',
        ]);

        $orderData = [
            'order_type' => 'medication',
            'order_text' => 'Start Noradrenaline infusion at 5 mcg/min titrate MAP > 65',
            'priority' => 'stat',
        ];

        $response = $this->actingAs($this->admin)->post(route('icu.orders.store', $admission->id), $orderData);
        $response->assertRedirect(route('icu.show', $admission->id));

        $order = IcuMedicalOrder::where('icu_admission_id', $admission->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(IcuMedicalOrder::PRIORITY_STAT, $order->priority);
        $this->assertEquals(IcuMedicalOrder::STATUS_PENDING, $order->status);

        // إنجاز الأمر من قبل التمريض/الطبيب
        $completeResponse = $this->actingAs($this->admin)->patch(route('icu.orders.complete', $order->id));
        $this->assertEquals(IcuMedicalOrder::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);
    }

    public function test_can_discharge_patient_and_bed_becomes_sterilizing_then_available()
    {
        $admission = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'admission_source' => 'emergency',
            'admission_date' => now()->subDays(3),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'primary_diagnosis' => 'Post-op Cardiac Monitoring',
        ]);

        $this->bed->update(['status' => IcuBed::STATUS_OCCUPIED]);

        // تخريج المريض
        $dischargeData = [
            'discharge_destination' => 'جناح الرقود العادي (Step-down Ward)',
            'discharge_summary' => 'تحسن الحالة واستقرار العلامات الحيوية وفصل جهاز التنفس بنجاح',
        ];

        $response = $this->actingAs($this->admin)->post(route('icu.admissions.discharge', $admission->id), $dischargeData);
        $response->assertRedirect(route('icu.index'));

        $this->assertEquals(IcuAdmission::STATUS_DISCHARGED, $admission->fresh()->status);
        $this->assertNotNull($admission->fresh()->discharge_date);

        // السرير يتحول إلى قيد التعقيم
        $this->assertEquals(IcuBed::STATUS_STERILIZING, $this->bed->fresh()->status);

        // إتمام التعقيم
        $sterilizeResponse = $this->actingAs($this->admin)->post(route('icu.beds.sterilized', $this->bed->id));
        $this->assertEquals(IcuBed::STATUS_AVAILABLE, $this->bed->fresh()->status);
    }

    public function test_icu_dossier_renders_lab_and_radiology_tabs_and_preserves_tab_state()
    {
        $admission = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'admission_source' => 'emergency',
            'admission_date' => now(),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'primary_diagnosis' => 'Severe Sepsis',
        ]);

        $response = $this->actingAs($this->admin)->get(route('icu.show', ['admission' => $admission->id, 'tab' => 'labTabPane']));

        $response->assertStatus(200);
        $response->assertSee('icuLabOrderForm');
        $response->assertSee('icuRadOrderForm');
        $response->assertSee('icuLabSearchInput');
        $response->assertSee('icuRadSearchInput');
        $response->assertSee('labTabPane');
        $response->assertSee('radiologyTabPane');
    }

    public function test_can_order_lab_tests_from_icu_dossier_with_stat_priority()
    {
        $admission = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'admission_source' => 'emergency',
            'admission_date' => now(),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'primary_diagnosis' => 'Acute Respiratory Distress Syndrome',
        ]);

        $labTest = \App\Models\LabTest::create([
            'name' => 'Arterial Blood Gases (ABG)',
            'code' => 'ABG-TEST',
            'price' => 20000,
            'category' => 'Blood Gas',
            'is_active' => true,
        ]);

        $orderPayload = [
            'lab_test_ids' => [$labTest->id],
            'custom_tests' => ['Lactate Rapid'],
            'priority' => 'stat',
            'doctor_notes' => 'عينة شريانية فورية للإنعاش',
            '_active_tab' => 'labTabPane',
        ];

        $response = $this->actingAs($this->admin)->post(route('icu.lab-orders.store', $admission->id), $orderPayload);

        $response->assertRedirect(route('icu.show', $admission->id));
        $response->assertSessionHas('active_tab', 'labTabPane');

        // التحقق من إنشاء الطلب في جدول requests المركزي
        $this->assertDatabaseHas('requests', [
            'icu_admission_id' => $admission->id,
            'type' => 'lab',
            'status' => 'pending',
        ]);

        // التحقق من إنشاء أوامر العناية في جدول icu_medical_orders
        $this->assertDatabaseHas('icu_medical_orders', [
            'icu_admission_id' => $admission->id,
            'order_type' => 'lab',
            'lab_test_id' => $labTest->id,
            'priority' => 'stat',
        ]);

        $this->assertDatabaseHas('icu_medical_orders', [
            'icu_admission_id' => $admission->id,
            'order_type' => 'lab',
            'item_name' => 'Lactate Rapid',
            'priority' => 'stat',
        ]);
    }

    public function test_can_order_radiology_exams_from_icu_dossier_with_stat_priority()
    {
        $admission = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'admission_source' => 'emergency',
            'admission_date' => now(),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'primary_diagnosis' => 'Pneumothorax Suspicion',
        ]);

        $radType = \App\Models\RadiologyType::create([
            'name' => 'Portable Chest X-Ray',
            'code' => 'CXR-PORT-TEST',
            'base_price' => 25000,
            'is_active' => true,
        ]);

        $orderPayload = [
            'radiology_type_ids' => [$radType->id],
            'custom_radiology' => ['Bedside FAST Ultrasound'],
            'priority' => 'stat',
            'doctor_notes' => 'أشعة صدر وسونار طوارئ بجانب السرير',
            '_active_tab' => 'radiologyTabPane',
        ];

        $response = $this->actingAs($this->admin)->post(route('icu.radiology-orders.store', $admission->id), $orderPayload);

        $response->assertRedirect(route('icu.show', $admission->id));
        $response->assertSessionHas('active_tab', 'radiologyTabPane');

        // التحقق من إنشاء الطلب في جدول requests المركزي
        $this->assertDatabaseHas('requests', [
            'icu_admission_id' => $admission->id,
            'type' => 'radiology',
            'status' => 'pending',
        ]);

        // التحقق من إنشاء بنود الأشعة في جدول icu_medical_orders
        $this->assertDatabaseHas('icu_medical_orders', [
            'icu_admission_id' => $admission->id,
            'order_type' => 'radiology',
            'radiology_type_id' => $radType->id,
            'priority' => 'stat',
        ]);

        $this->assertDatabaseHas('icu_medical_orders', [
            'icu_admission_id' => $admission->id,
            'order_type' => 'radiology',
            'item_name' => 'Bedside FAST Ultrasound',
            'priority' => 'stat',
        ]);
    }

    public function test_submitting_vitals_preserves_active_tab_state()
    {
        $admission = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'admission_source' => 'emergency',
            'admission_date' => now(),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'primary_diagnosis' => 'Hypertensive Crisis',
        ]);

        $vitalData = [
            'gcs' => 15,
            'heart_rate' => 88,
            'systolic_bp' => 140,
            'diastolic_bp' => 90,
            'spo2' => 98,
            '_active_tab' => 'vitalsTabPane',
        ];

        $response = $this->actingAs($this->admin)->post(route('icu.vitals.store', $admission->id), $vitalData);

        $response->assertRedirect(route('icu.show', $admission->id));
        $response->assertSessionHas('active_tab', 'vitalsTabPane');
    }
}
