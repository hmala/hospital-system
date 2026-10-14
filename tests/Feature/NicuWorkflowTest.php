<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Room;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuService;
use App\Models\NicuServiceLog;
use App\Models\NicuVital;
use App\Models\NicuConsultation;
use App\Models\NicuMedicalOrder;
use App\Services\NicuBillingService;
use Database\Seeders\NicuServicesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Carbon\Carbon;

class NicuWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Room $nicuRoom;
    protected Incubator $incubator;
    protected Patient $babyPatient;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(NicuServicesSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');

        $hospitalId = \Illuminate\Support\Facades\DB::table('hospitals')->insertGetId([
            'name' => 'مستشفى الشفاء التخصصي',
            'owner_name' => 'د. أحمد',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-NICU-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $departmentId = \Illuminate\Support\Facades\DB::table('departments')->insertGetId([
            'hospital_id' => $hospitalId,
            'name' => 'قسم الأطفال وحديثي الولادة والخدج',
            'type' => 'pediatrics',
            'room_number' => 'NICU-1',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $departmentId,
            'specialization' => 'أخصائي طب الأطفال وحديثي الولادة',
            'qualification' => 'بورد عربي في طب الأطفال وحديثي الولادة',
            'license_number' => 'DOC-NICU-' . uniqid(),
            'consultation_fee' => 25000,
        ]);

        $this->nicuRoom = Room::create([
            'room_number' => 'ط6-خدج',
            'room_type' => 'incubator',
            'room_purpose' => 'incubators',
            'floor' => 'الطابق السادس',
            'daily_fee' => 0,
            'status' => 'available',
        ]);

        $this->incubator = Incubator::create([
            'incubator_number' => '1',
            'incubator_type' => 'normal',
            'status' => 'available',
            'room_id' => $this->nicuRoom->id,
            'daily_fee' => 200000,
            'is_active' => true,
        ]);

        $babyUser = User::factory()->create(['name' => 'طفل فاطمة علي']);
        $this->babyPatient = Patient::create([
            'user_id' => $babyUser->id,
            'gender' => 'male',
            'date_of_birth' => now()->subDays(2),
        ]);
    }

    public function test_nicu_services_seeder_creates_all_8_packages(): void
    {
        $this->assertEquals(8, NicuService::count());

        $ventService = NicuService::where('code', 'incubator_ventilator')->first();
        $this->assertNotNull($ventService);
        $this->assertEquals(360000.00, (float)$ventService->daily_fee);

        $o2Service = NicuService::where('code', 'incubator_o2')->first();
        $this->assertNotNull($o2Service);
        $this->assertEquals(200000.00, (float)$o2Service->daily_fee);

        $cpapService = NicuService::where('code', 'incubator_cpap')->first();
        $this->assertNotNull($cpapService);
        $this->assertEquals(250000.00, (float)$cpapService->daily_fee);

        $outVentService = NicuService::where('code', 'incubator_ventilator_out')->first();
        $this->assertNotNull($outVentService);
        $this->assertEquals(300000.00, (float)$outVentService->daily_fee);
    }

    public function test_admin_can_view_nicu_live_board(): void
    {
        $response = $this->actingAs($this->admin)->get(route('nicu.index'));
        $response->assertStatus(200);
        $response->assertSee('حاضنة رقم 1');
        $response->assertSee('لوحة قسم الخدج والحاضنات');
    }

    public function test_admitting_baby_creates_reservation_and_initial_service_log(): void
    {
        $ventService = NicuService::where('code', 'incubator_ventilator')->first();

        $response = $this->actingAs($this->admin)->post(route('incubator-reservations.store'), [
            'patient_id' => $this->babyPatient->id,
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $ventService->id,
            'doctor_id' => $this->doctor->id,
            'birth_weight' => 1400,
            'gestational_age' => '31 أسبوع',
            'admission_source' => 'c_section',
            'admission_date' => now()->toDateString(),
            'admission_time' => '08:30',
        ]);

        $reservation = IncubatorReservation::where('patient_id', $this->babyPatient->id)->first();
        $this->assertNotNull($reservation);
        $response->assertRedirect(route('nicu.show', $reservation));

        $this->assertEquals(1400, $reservation->current_weight);
        $this->assertEquals('c_section', $reservation->admission_source);
        $this->assertNotNull($reservation->camera_token);

        // الحاضنة أصبحت مشغولة
        $this->assertEquals(Incubator::STATUS_OCCUPIED, $this->incubator->fresh()->status);

        // وجود سجل خدمة أولي
        $this->assertDatabaseHas('nicu_service_logs', [
            'reservation_id' => $reservation->id,
            'nicu_service_id' => $ventService->id,
            'daily_fee' => 360000.00,
        ]);
    }

    public function test_billing_service_applies_6_hour_grace_period(): void
    {
        $service = new NicuBillingService();

        // دخول الساعة 08:30 صباحاً (أقل من 6 ساعات قبل 12:00 ظهراً)
        $admission = Carbon::create(2026, 10, 11, 8, 30);
        $discharge = Carbon::create(2026, 10, 12, 14, 0); // خروج الساعة 14:00 (أقل من 6 ساعات بعد 12:00 ظهراً)

        $result = $service->calculateBillableDays($admission, $discharge);

        $this->assertTrue($result['admission_grace']);
        $this->assertTrue($result['discharge_grace']);
        $this->assertEquals(1, $result['days']);
    }

    public function test_switching_nicu_service_creates_new_service_log(): void
    {
        $ventService = NicuService::where('code', 'incubator_ventilator')->first();
        $o2Service = NicuService::where('code', 'incubator_o2')->first();

        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل الاختبار',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $ventService->id,
            'admission_date' => now()->subDay(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $this->actingAs($this->admin)->post(route('nicu.switch-service', $reservation), [
            'nicu_service_id' => $o2Service->id,
            'reason' => 'تحسن التنفس وفصل الفنتليتر والتحويل إلى أكسجين',
        ]);

        $this->assertEquals($o2Service->id, $reservation->fresh()->nicu_service_id);

        $this->assertDatabaseHas('nicu_service_logs', [
            'reservation_id' => $reservation->id,
            'nicu_service_id' => $o2Service->id,
            'daily_fee' => 200000.00,
            'reason_for_switch' => 'تحسن التنفس وفصل الفنتليتر والتحويل إلى أكسجين',
        ]);
    }

    public function test_recording_vitals_updates_current_weight(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل الاختبار',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'birth_weight' => 1500,
            'current_weight' => 1500,
            'status' => 'admitted',
        ]);

        $response = $this->actingAs($this->admin)->post(route('nicu.vitals.store', $reservation), [
            'temperature' => 36.8,
            'incubator_temperature' => 34.0,
            'heart_rate' => 142,
            'respiratory_rate' => 44,
            'spo2' => 97,
            'weight' => 1540,
            'feeding_type' => 'حليب أم (EBM)',
            'feeding_amount_ml' => 15,
        ]);

        $response->assertRedirect();
        $this->assertEquals(1540, $reservation->fresh()->current_weight);
        $this->assertEquals(40, $reservation->fresh()->weight_difference);

        $this->assertDatabaseHas('nicu_vitals', [
            'reservation_id' => $reservation->id,
            'weight' => 1540,
            'spo2' => 97,
        ]);
    }

    public function test_requesting_and_completing_consultation(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل الاختبار',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $this->actingAs($this->admin)->post(route('nicu.consultations.store', $reservation), [
            'consultation_type' => 'internal',
            'doctor_id' => $this->doctor->id,
            'specialty' => 'قلب أطفال',
            'fee' => 35000,
            'reason' => 'فحص نفخة قلبية مشتبه بها',
        ]);

        $consultation = NicuConsultation::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($consultation);
        $this->assertEquals(NicuConsultation::STATUS_REQUESTED, $consultation->status);

        // إكمال الاستشارة
        $this->actingAs($this->admin)->patch(route('nicu.consultations.complete', $consultation), [
            'findings' => 'إيكو طبيعي مع استمرار القناة الشريانية PDA صغيرة في طور الانغلاق',
            'recommendations' => 'متابعة دورية بعد 5 أيام دون حاجة لتدخل دوائي',
        ]);

        $this->assertEquals(NicuConsultation::STATUS_COMPLETED, $consultation->fresh()->status);
        $this->assertNotNull($consultation->fresh()->completed_at);
    }

    public function test_requesting_medical_order_and_submitting_result(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل الاختبار',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $this->actingAs($this->admin)->post(route('nicu.orders.store', $reservation), [
            'order_type' => 'lab',
            'item_name' => 'غازات الدم الشرياني ABG',
            'priority' => 'stat',
            'price' => 15000,
            'instructions' => 'عاجل جداً من الشريان الكعبري',
        ]);

        $order = NicuMedicalOrder::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(NicuMedicalOrder::STATUS_PENDING, $order->status);

        // إكمال النتيجة
        $this->actingAs($this->admin)->patch(route('nicu.orders.complete', $order), [
            'result' => 'pH: 7.36, pCO2: 42, pO2: 85, HCO3: 23',
            'price' => 15000,
        ]);

        $this->assertEquals(NicuMedicalOrder::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertStringContainsString('pH: 7.36', $order->fresh()->result);
    }

    public function test_discharge_puts_incubator_into_sterilizing_state_and_can_be_marked_ready(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل الاختبار',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now()->subDays(2),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $this->incubator->update(['status' => Incubator::STATUS_OCCUPIED]);

        $response = $this->actingAs($this->admin)->post(route('nicu.discharge', $reservation), [
            'discharge_type' => 'improved',
            'discharge_notes' => 'الطفل مستقر والرضاعة ممتازة وخروج لحضن الأم',
        ]);

        $response->assertRedirect(route('nicu.statement', $reservation));

        $this->assertEquals(IncubatorReservation::STATUS_DISCHARGED, $reservation->fresh()->status);
        $this->assertEquals('improved', $reservation->fresh()->discharge_type);

        // الحاضنة تحولت إلى قيد التعقيم والتطهير
        $this->assertEquals(Incubator::STATUS_STERILIZING, $this->incubator->fresh()->status);

        // تأكيد التعقيم
        $this->actingAs($this->admin)->post(route('nicu.incubators.sterilized', $this->incubator));
        $this->assertEquals(Incubator::STATUS_AVAILABLE, $this->incubator->fresh()->status);
    }

    public function test_public_baby_cam_token_view_works(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل الاختبار',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
            'camera_token' => 'test-secure-token-12345',
            'camera_active' => true,
        ]);

        // الزائر أو الأب يفتح الرابط بدون تسجيل دخول
        $response = $this->get(route('nicu.baby-cam', ['token' => 'test-secure-token-12345']));
        $response->assertStatus(200);
        $response->assertSee('طفل الاختبار');
        $response->assertSee('كاميرا الاطمئنان - قسم الخدج');
        $response->assertSee('الحمد لله على سلامته.. طفلكم في أيدٍ أمينة');
    }

    public function test_viewing_accumulated_statement(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل الاختبار',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now()->subDays(2),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $response = $this->actingAs($this->admin)->get(route('nicu.statement', $reservation));
        $response->assertStatus(200);
        $response->assertSee('كشف حساب الإقامة والرعاية السريرية التفصيلي');
        $response->assertSee('طفل الاختبار');
    }

    public function test_admin_can_view_nicu_services_settings(): void
    {
        $response = $this->actingAs($this->admin)->get(route('nicu.services.index'));
        $response->assertStatus(200);
        $response->assertSee('إعدادات وتسعير باقات وخدمات الخدج');
        $response->assertSee('حاضنة + جهاز تنفس اصطناعي (Ventilator)');
        $response->assertSee('360,000');
    }

    public function test_admin_can_update_nicu_service_price(): void
    {
        $service = NicuService::where('code', 'incubator_ventilator')->first();

        $response = $this->actingAs($this->admin)->put(route('nicu.services.update', $service), [
            'name' => 'حاضنة + جهاز تنفس اصطناعي (محدث)',
            'daily_fee' => 380000,
            'description' => 'تسعيرة محدثة',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('nicu.services.index'));
        $this->assertEquals(380000, $service->fresh()->daily_fee);
        $this->assertEquals('حاضنة + جهاز تنفس اصطناعي (محدث)', $service->fresh()->name);
    }

    public function test_admin_can_toggle_nicu_service_status(): void
    {
        $service = NicuService::where('code', 'incubator_o2')->first();
        $this->assertTrue((bool)$service->is_active);

        $response = $this->actingAs($this->admin)->post(route('nicu.services.toggle', $service));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_active' => false]);
        $this->assertFalse((bool)$service->fresh()->is_active);
    }

    public function test_rapid_service_switching_within_15_mins_updates_existing_log(): void
    {
        $ventService = NicuService::where('code', 'incubator_ventilator')->first();
        $o2Service = NicuService::where('code', 'incubator_o2')->first();
        $cpapService = NicuService::where('code', 'incubator_cpap')->first();

        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل التبديل السريع',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $ventService->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        NicuServiceLog::create([
            'reservation_id' => $reservation->id,
            'nicu_service_id' => $ventService->id,
            'start_time' => now(),
            'daily_fee' => $ventService->daily_fee,
        ]);

        // First switch to O2
        $this->actingAs($this->admin)->post(route('nicu.switch-service', $reservation), [
            'nicu_service_id' => $o2Service->id,
            'reason' => 'تحويل أولي',
        ]);

        // Second switch 2 minutes later to CPAP (simulating user changing mind or rapid switch)
        $this->actingAs($this->admin)->post(route('nicu.switch-service', $reservation), [
            'nicu_service_id' => $cpapService->id,
            'reason' => 'تعديل فوري للخدمة',
        ]);

        $this->assertEquals($cpapService->id, $reservation->fresh()->nicu_service_id);

        // Should not have created 3 active billings; it updates the open log in-place
        $logs = NicuServiceLog::where('reservation_id', $reservation->id)->whereNull('end_time')->get();
        $this->assertCount(1, $logs);
        $this->assertEquals($cpapService->id, $logs->first()->nicu_service_id);
    }

    public function test_admin_can_view_nicu_reservations_registry(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'رضيع السجل السريري',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '09:00',
            'status' => 'admitted',
            'birth_weight' => 1600,
            'current_weight' => 1650,
        ]);

        // Visit index
        $response = $this->actingAs($this->admin)->get(route('nicu.reservations.index'));
        $response->assertStatus(200);
        $response->assertSee('سجل وإضبارات الخدج وحجوزات الحاضنات');
        $response->assertSee('رضيع السجل السريري');

        // Test search
        $searchResponse = $this->actingAs($this->admin)->get(route('nicu.reservations.index', ['search' => 'رضيع السجل']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('رضيع السجل السريري');

        // Test status filter
        $filterResponse = $this->actingAs($this->admin)->get(route('nicu.reservations.index', ['status' => 'admitted']));
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('رضيع السجل السريري');
    }
}

