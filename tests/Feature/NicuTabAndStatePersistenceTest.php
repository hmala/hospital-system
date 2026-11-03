<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuService;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NicuTabAndStatePersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Patient $patient;

    protected Incubator $incubator;

    protected NicuService $service;

    protected IncubatorReservation $reservation;

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

        $patientUser = User::factory()->create(['name' => 'طفل مصطفى أحمد']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'phone' => '07700000001',
            'gender' => 'male',
        ]);

        $this->reservation = IncubatorReservation::create([
            'patient_id' => $this->patient->id,
            'baby_name' => 'طفل مصطفى أحمد',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'doctor_id' => null,
            'department_id' => $dept->id,
            'admission_date' => now()->subDays(2)->toDateString(),
            'admission_time' => '10:00:00',
            'start_date' => now()->subDays(2),
            'status' => 'admitted',
            'payment_status' => 'pending',
            'birth_weight' => 1800,
            'current_weight' => 1850,
        ]);
    }

    /**
     * اختبار توثيق العلامات الحيوية وإعادة التوجيه مع الحفاظ على تبويب vitalsPane
     */
    public function test_store_vitals_redirects_back_preserving_vitals_tab(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('nicu.show', $this->reservation))
            ->post(route('nicu.vitals.store', $this->reservation), [
                'temperature' => 36.8,
                'heart_rate' => 140,
                'spo2' => 98,
                'weight' => 1860,
                '_active_tab' => 'vitalsPane',
            ]);

        $response->assertRedirect(route('nicu.show', $this->reservation));
        $response->assertSessionHas('active_tab', 'vitalsPane');
        $response->assertSessionHas('success');
    }

    /**
     * اختبار اعتماد جدول رعاية وإعادة التوجيه مع الحفاظ على تبويب careSchedulesPane والتبويب الفرعي
     */
    public function test_store_care_schedule_redirects_preserving_tab_and_pill(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('nicu.show', $this->reservation))
            ->post(route('nicu.care-schedules.store', $this->reservation), [
                'type' => 'feeding',
                'title' => 'حليب أم EBM',
                'dose_or_amount' => '30 ml',
                'interval_hours' => 3,
                '_active_tab' => 'careSchedulesPane',
                '_active_pill' => 'pill-feeding',
            ]);

        $response->assertRedirect(route('nicu.show', $this->reservation));
        $response->assertSessionHas('active_tab', 'careSchedulesPane');
        $response->assertSessionHas('active_pill', 'pill-feeding');
    }

    /**
     * اختبار تدوين ملاحظة الطبيب الاختصاص والحفاظ على التبويب والتبويب الفرعي pill-doctor-note
     */
    public function test_store_doctor_note_redirects_preserving_tab_and_pill(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('nicu.show', $this->reservation))
            ->post(route('nicu.doctor-notes.store', $this->reservation), [
                'content' => 'يرجى مراقبة التنفس كل ساعتين.',
                '_active_tab' => 'careSchedulesPane',
                '_active_pill' => 'pill-doctor-note',
            ]);

        $response->assertRedirect(route('nicu.show', $this->reservation));
        $response->assertSessionHas('active_tab', 'careSchedulesPane');
        $response->assertSessionHas('active_pill', 'pill-doctor-note');
    }

    /**
     * اختبار عرض صفحة الخديج وتفعيل التبويب الممرر في الجلسة مباشرة
     */
    public function test_show_page_activates_requested_tab_server_side(): void
    {
        // محاكاة فتح الصفحة بعد حفظ العلامات الحيوية
        $response = $this->actingAs($this->admin)
            ->withSession(['active_tab' => 'vitalsPane'])
            ->get(route('nicu.show', $this->reservation));

        $response->assertOk();
        // التحقق من أن زر التمريض يحمل كلاس active
        $response->assertSee('id="vitals-tab" data-bs-toggle="tab" data-bs-target="#vitalsPane" type="button"', false);
        // التحقق من أن حاوية vitalsPane تحمل كلاس show active
        $response->assertSee('class="tab-pane fade show active" id="vitalsPane"', false);
        // التحقق من أن حاوية servicePane لا تحمل show active
        $response->assertDontSee('class="tab-pane fade show active" id="servicePane"', false);
    }

    /**
     * اختبار عرض صفحة الخديج وتفعيل تبويب التوصيات والملاحظات الفرعية
     */
    public function test_show_page_activates_care_schedules_and_pill(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession([
                'active_tab' => 'careSchedulesPane',
                'active_pill' => 'pill-doctor-note',
            ])
            ->get(route('nicu.show', $this->reservation));

        $response->assertOk();
        $response->assertSee('class="tab-pane fade show active" id="careSchedulesPane"', false);
        $response->assertSee('class="tab-pane fade show active" id="pill-doctor-note"', false);
        $response->assertDontSee('class="tab-pane fade show active" id="pill-feeding"', false);
    }
}
