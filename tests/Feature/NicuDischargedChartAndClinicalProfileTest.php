<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuService;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NicuDischargedChartAndClinicalProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Patient $patientActive;

    protected Patient $patientDischarged;

    protected Incubator $incubator;

    protected NicuService $service;

    protected IncubatorReservation $activeReservation;

    protected IncubatorReservation $dischargedReservation;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'nicu_doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'nicu_nurse', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['name' => 'مشرف الخدج']);
        $this->admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'مستشفى الفيحاء',
            'owner_name' => 'د. حسام',
            'license_number' => 'LIC-100',
            'phone' => '07700000000',
            'address' => 'بغداد',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الأطفال والخدج',
            'description' => 'NICU',
            'type' => 'pediatrics',
            'room_number' => '601',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
        ]);

        $this->incubator = Incubator::create([
            'incubator_number' => 'INC-01',
            'room_number' => '601',
            'status' => 'occupied',
            'model' => 'Drager Isolette 8000',
            'is_active' => true,
            'daily_fee' => 100000,
        ]);

        $this->service = NicuService::create([
            'name' => 'عناية حاضنة متقدمة',
            'code' => 'NICU-ADV',
            'daily_fee' => 150000,
            'is_active' => true,
        ]);

        // Patient 1 (Active)
        $patientUserActive = User::factory()->create(['name' => 'والدة مصطفى أحمد']);
        $this->patientActive = Patient::create([
            'user_id' => $patientUserActive->id,
            'gender' => 'male',
            'blood_type' => 'O+',
            'emergency_contact_name' => 'أحمد العبيدي',
            'emergency_contact_phone' => '07711112222',
            'medical_history' => 'خديج 32 أسبوع',
        ]);

        // Patient 2 (Discharged)
        $patientUserDischarged = User::factory()->create(['name' => 'والدة سليم علي']);
        $this->patientDischarged = Patient::create([
            'user_id' => $patientUserDischarged->id,
            'gender' => 'male',
            'blood_type' => 'A+',
            'emergency_contact_name' => 'علي الموسوي',
            'emergency_contact_phone' => '07733334444',
            'medical_history' => 'خديج 35 أسبوع',
        ]);

        // Active reservation
        $this->activeReservation = IncubatorReservation::create([
            'patient_id' => $this->patientActive->id,
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'admission_date' => now()->subDays(2)->toDateString(),
            'admission_time' => '10:00:00',
            'start_date' => now()->subDays(2),
            'expected_duration' => 7,
            'status' => 'admitted',
            'daily_fee' => 150000,
            'baby_name' => 'طفل مصطفى أحمد',
            'mother_name' => 'والدة مصطفى أحمد',
            'gestational_age' => '32 أسبوع',
            'birth_weight' => 1600,
            'current_weight' => 1620,
        ]);

        // Discharged reservation
        $this->dischargedReservation = IncubatorReservation::create([
            'patient_id' => $this->patientDischarged->id,
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'admission_date' => now()->subDays(10)->toDateString(),
            'admission_time' => '09:00:00',
            'start_date' => now()->subDays(10),
            'discharge_date' => now()->subDay(),
            'discharge_time' => '12:00:00',
            'discharge_type' => 'normal',
            'expected_duration' => 7,
            'actual_duration' => 9,
            'status' => 'discharged',
            'daily_fee' => 150000,
            'baby_name' => 'طفل سليم معافى',
            'mother_name' => 'والدة سليم علي',
            'gestational_age' => '35 أسبوع',
            'birth_weight' => 2100,
            'current_weight' => 2400,
        ]);
    }

    public function test_reservations_index_segregates_active_and_archived_discharged_babies(): void
    {
        // 1. Default (active view) should show active baby and NOT discharged baby
        $response = $this->actingAs($this->admin)
            ->get(route('nicu.reservations.index', ['view' => 'active']));

        $response->assertStatus(200);
        $response->assertSee('طفل مصطفى أحمد');
        $response->assertDontSee('طفل سليم معافى');
        $response->assertSee('الأطفال بالحاضنات (الحالات النشطة)');
        $response->assertSee('السجلات وأرشفة المرضى');

        // 2. Archived view should show discharged baby and NOT active baby
        $archivedResponse = $this->actingAs($this->admin)
            ->get(route('nicu.reservations.index', ['view' => 'archived']));

        $archivedResponse->assertStatus(200);
        $archivedResponse->assertSee('طفل سليم معافى');
        $archivedResponse->assertDontSee('طفل مصطفى أحمد');
        $archivedResponse->assertSee('السجلات وأرشفة المرضى (إضبارات الخدج المخرجة)');
    }

    public function test_registration_form_does_not_contain_birth_weight_or_gestational_age_inputs(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('incubator-reservations.create'));

        $response->assertStatus(200);
        $response->assertDontSee('name="birth_weight"', false);
        $response->assertDontSee('name="gestational_age"', false);
        $response->assertSee('داخل إضبارة الطفل السريرية بمحطة رعاية الخدج');
    }

    public function test_clinical_profile_can_be_updated_on_active_baby(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('nicu.clinical-profile.update', $this->activeReservation), [
                'birth_weight' => 1750,
                'gestational_age' => '34 أسبوع',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->activeReservation->refresh();
        $this->assertEquals(1750, $this->activeReservation->birth_weight);
        $this->assertEquals('34 أسبوع', $this->activeReservation->gestational_age);
    }

    public function test_discharged_baby_chart_is_strictly_read_only(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('nicu.show', $this->dischargedReservation));

        $response->assertStatus(200);

        // Header warning
        $response->assertSee('إضبارة سريرية مؤرشفة ومغلقة (تم تسجيل الخروج الطبي)');
        $response->assertSee('تم تسجيل الخروج الطبي للطفل بتاريخ');

        // Locked notices across tabs
        $response->assertSee('بيانات الولادة وعمر الحمل السريرية');
        $response->assertSee('إضبارة مؤرشفة ومقفلة (للاطلاع فقط)');
        $response->assertSee('سجل العلامات الحيوية مقفل');
        $response->assertSee('سجل إعطاء الأدوية (MAR) مقفل');
        $response->assertSee('طلب الاستشارات الطبية مقفل');
        $response->assertSee('طلب التحاليل المخبرية مقفل');
        $response->assertSee('طلب فحوصات الأشعة مقفل');
        $response->assertSee('صرف وتعديل الوصفات مقفل');
        $response->assertSee('توصيات وملاحظات الرعاية مقفلة');
        $response->assertSee('رفع المرفقات مقفل');

        // Mutation action forms should be hidden
        $response->assertDontSee('btnEditClinicalProfile');
    }

    public function test_backend_strictly_blocks_mutations_on_discharged_reservation(): void
    {
        // 1. Updating clinical profile
        $resp = $this->actingAs($this->admin)
            ->post(route('nicu.clinical-profile.update', $this->dischargedReservation), [
                'birth_weight' => 2500,
                'gestational_age' => '36 أسبوع',
            ]);
        $resp->assertSessionHas('error');

        // 2. Vitals store
        $respVitals = $this->actingAs($this->admin)
            ->post(route('nicu.vitals.store', $this->dischargedReservation), [
                'heart_rate' => 140,
                'temperature' => 36.8,
                'oxygen_saturation' => 98,
            ]);
        $respVitals->assertSessionHas('error');

        // 3. Lab order store
        $respLab = $this->actingAs($this->admin)
            ->post(route('nicu.lab-orders.store', $this->dischargedReservation), [
                'test_names' => ['CBC'],
            ]);
        $respLab->assertSessionHas('error');

        // 4. Radiology order store
        $respRad = $this->actingAs($this->admin)
            ->post(route('nicu.radiology-orders.store', $this->dischargedReservation), [
                'service_ids' => [1],
            ]);
        $respRad->assertSessionHas('error');

        // 5. Prescription store
        $respPresc = $this->actingAs($this->admin)
            ->post(route('nicu.prescriptions.store', $this->dischargedReservation), [
                'medications' => [
                    ['name' => 'Ampicillin', 'dose' => '50mg', 'route' => 'IV', 'frequency' => 'BID', 'duration' => '5 days'],
                ],
            ]);
        $respPresc->assertSessionHas('error');

        // 6. Care schedule store
        $respCare = $this->actingAs($this->admin)
            ->post(route('nicu.care-schedules.store', $this->dischargedReservation), [
                'type' => 'feeding',
                'title' => 'EBM',
                'dose_or_amount' => '20ml',
                'interval_hours' => 3,
                'start_time' => now()->format('Y-m-d H:i:s'),
            ]);
        $respCare->assertSessionHas('error');
    }
}
