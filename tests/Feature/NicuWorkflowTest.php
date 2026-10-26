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
use App\Models\LabTest;
use App\Models\RadiologyType;
use App\Models\Medicine;
use App\Models\UserLabTestGroup;
use App\Models\NicuMedicationAdministration;
use App\Services\NicuBillingService;
use Database\Seeders\NicuServicesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

        // 1. محاولة الخروج الطبي مع وجود رصيد متبقي بذمة الحاضنة يجب أن تُمنع بصمام الأمان المالي
        $blockedResponse = $this->actingAs($this->admin)->post(route('nicu.discharge', $reservation), [
            'discharge_type' => 'improved',
            'discharge_notes' => 'الطفل مستقر وخروج لحضن الأم',
        ]);
        $blockedResponse->assertSessionHas('error');
        $this->assertEquals('admitted', $reservation->fresh()->status);

        // 2. تسوية الرصيد المتبقي بالكامل لدى الكاشير
        $stayDetails = app(\App\Services\NicuBillingService::class)->calculateStayDetails($reservation);
        \App\Models\NicuPayment::create([
            'incubator_reservation_id' => $reservation->id,
            'receipt_number' => \App\Models\NicuPayment::generateReceiptNumber(),
            'cashier_id' => $this->admin->id,
            'amount' => $stayDetails['net_balance'],
            'payment_type' => 'deposit',
            'financial_status' => 'paid',
            'paid_at' => now(),
        ]);

        // 3. الآن بعد تسوية المبلغ كاملاً، يتم اعتماد الخروج بنجاح
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

    public function test_admin_can_update_and_undo_service_log(): void
    {
        $ventService = NicuService::where('code', 'incubator_ventilator')->first();
        $o2Service = NicuService::where('code', 'incubator_o2')->first();

        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل اختبار التعديل',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $ventService->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $log1 = NicuServiceLog::create([
            'reservation_id' => $reservation->id,
            'nicu_service_id' => $ventService->id,
            'daily_fee' => $ventService->daily_fee,
            'start_time' => now()->subDay(),
            'end_time' => now(),
            'days_count' => 1,
            'total_cost' => $ventService->daily_fee,
        ]);

        $log2 = NicuServiceLog::create([
            'reservation_id' => $reservation->id,
            'nicu_service_id' => $o2Service->id,
            'daily_fee' => $o2Service->daily_fee,
            'start_time' => now(),
            'end_time' => null,
            'days_count' => 1,
            'total_cost' => $o2Service->daily_fee,
        ]);

        // Test updating log2
        $cpapService = NicuService::where('code', 'incubator_cpap')->first();
        $response = $this->actingAs($this->admin)->put(route('nicu.service-logs.update', [$reservation, $log2]), [
            'nicu_service_id' => $cpapService->id,
            'reason' => 'تصحيح إلى CPAP',
        ]);
        $response->assertRedirect();
        $this->assertEquals($cpapService->id, $log2->fresh()->nicu_service_id);
        $this->assertEquals($cpapService->id, $reservation->fresh()->nicu_service_id);

        // Test destroying/undoing log2
        $destroyResponse = $this->actingAs($this->admin)->delete(route('nicu.service-logs.destroy', [$reservation, $log2]));
        $destroyResponse->assertRedirect();
        $this->assertDatabaseMissing('nicu_service_logs', ['id' => $log2->id]);
        // Previous log reopened and restored as current service
        $this->assertNull($log1->fresh()->end_time);
        $this->assertEquals($ventService->id, $reservation->fresh()->nicu_service_id);
    }

    public function test_insured_reservation_applies_hi_pricing_and_stores_coverage_details(): void
    {
        $cpapService = NicuService::where('code', 'incubator_cpap')->first();
        $this->assertNotNull($cpapService);
        $this->assertEquals(250000, $cpapService->daily_fee);
        $this->assertEquals(200000, $cpapService->hi_price);

        $response = $this->actingAs($this->admin)->post(route('incubator-reservations.store'), [
            'patient_id' => $this->babyPatient->id,
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $cpapService->id,
            'doctor_id' => $this->doctor->id,
            'admission_date' => now()->format('Y-m-d'),
            'admission_time' => '10:00',
            'birth_weight' => 1800,
            'gestational_age' => '34 أسبوع',
            'admission_source' => 'internal',
            'insurance_type' => 'hi',
            'policy_category' => 'E',
            'insurance_card_no' => 'HIA-998877',
            'encounter_number' => 'ENC-2026-4455',
        ]);

        $response->assertRedirect();
        $reservation = IncubatorReservation::where('patient_id', $this->babyPatient->id)->first();
        $this->assertNotNull($reservation);
        $this->assertEquals('hi', $reservation->insurance_type);
        $this->assertEquals('E', $reservation->policy_category);
        $this->assertEquals('34 أسبوع', $reservation->gestational_age);
        $this->assertEquals('HIA-998877', $reservation->insurance_card_no);
        $this->assertEquals('ENC-2026-4455', $reservation->encounter_number);
        $this->assertTrue($reservation->is_insured);
        $this->assertEquals(34, $reservation->gestational_weeks);

        // Initial service log should use the approved HI fee (200,000)
        $initialLog = $reservation->serviceLogs()->first();
        $this->assertNotNull($initialLog);
        $this->assertEquals(200000, (float)$initialLog->daily_fee);
        $this->assertEquals(200000, (float)$initialLog->total_cost);
    }

    public function test_gestational_age_coverage_tiers_calculation(): void
    {
        $billingService = app(NicuBillingService::class);
        $dailyFee = 200000.0;

        // 1. الفئة الأولى (>= 34 أسبوع)
        // الأيام 1-5: 75% ضمان / 25% مريض
        $day1 = $billingService->getInsuranceCoverageForDay(1, $dailyFee, 35, 'E');
        $this->assertEquals(75.0, $day1['hia_percent']);
        $this->assertEquals(25.0, $day1['patient_percent']);
        $this->assertEquals(150000.0, $day1['hia_share']);
        $this->assertEquals(50000.0, $day1['patient_share']);

        // الأيام 6-10: 50% ضمان / 50% مريض
        $day7 = $billingService->getInsuranceCoverageForDay(7, $dailyFee, 35, 'E');
        $this->assertEquals(50.0, $day7['hia_percent']);
        $this->assertEquals(50.0, $day7['patient_percent']);
        $this->assertEquals(100000.0, $day7['hia_share']);
        $this->assertEquals(100000.0, $day7['patient_share']);

        // تجاوز 10 أيام: 0% ضمان / 100% مريض
        $day11 = $billingService->getInsuranceCoverageForDay(11, $dailyFee, 35, 'E');
        $this->assertEquals(0.0, $day11['hia_percent']);
        $this->assertEquals(100.0, $day11['patient_percent']);
        $this->assertEquals(0.0, $day11['hia_share']);
        $this->assertEquals(200000.0, $day11['patient_share']);

        // 2. الفئة A (إعفاء تام 100%)
        $dayCatA = $billingService->getInsuranceCoverageForDay(12, $dailyFee, 35, 'A');
        $this->assertEquals(100.0, $dayCatA['hia_percent']);
        $this->assertEquals(0.0, $dayCatA['patient_percent']);
        $this->assertEquals(200000.0, $dayCatA['hia_share']);
        $this->assertEquals(0.0, $dayCatA['patient_share']);

        // 3. خداجة شديدة (<28 أسبوع) مستمرة بعد اليوم 16
        $dayExtreme = $billingService->getInsuranceCoverageForDay(18, $dailyFee, 26, 'E');
        $this->assertEquals(10.0, $dayExtreme['hia_percent']);
        $this->assertEquals(90.0, $dayExtreme['patient_percent']);
        $this->assertEquals(20000.0, $dayExtreme['hia_share']);
        $this->assertEquals(180000.0, $dayExtreme['patient_share']);

        // 4. رقود ردهة اعتيادي خارج الخدج (90% ضمان / 10% مريض)
        $dayWard = $billingService->getInsuranceCoverageForDay(3, $dailyFee, null, 'E', 'pediatric_inpatient');
        $this->assertEquals(90.0, $dayWard['hia_percent']);
        $this->assertEquals(10.0, $dayWard['patient_percent']);
        $this->assertEquals(180000.0, $dayWard['hia_share']);
        $this->assertEquals(20000.0, $dayWard['patient_share']);
    }

    public function test_admin_can_update_service_hi_price_and_official_claim_name(): void
    {
        $service = NicuService::where('code', 'incubator_o2')->first();
        $this->assertNotNull($service);

        $response = $this->actingAs($this->admin)->put(route('nicu.services.update', $service), [
            'name' => $service->name,
            'daily_fee' => 210000,
            'hi_price' => 160000,
            'official_claim_name' => 'رقود حديثي الولادة لليوم الواحد شامل الخدمات التمريضية و الفندقية المحدث',
            'is_active' => '1',
            'is_hi_active' => '1',
        ]);

        $response->assertRedirect(route('nicu.services.index'));
        $service->refresh();
        $this->assertEquals(210000, (float)$service->daily_fee);
        $this->assertEquals(160000, (float)$service->hi_price);
        $this->assertEquals('رقود حديثي الولادة لليوم الواحد شامل الخدمات التمريضية و الفندقية المحدث', $service->official_claim_name);
        $this->assertTrue($service->is_hi_active);
    }

    public function test_incubator_types_simplified_to_normal_and_capsule(): void
    {
        // 1. اختبار مسميات وأيقونات النوعين المعتمدين
        $normalInc = Incubator::create([
            'incubator_number' => 'TEST-NORM-01',
            'incubator_type' => Incubator::TYPE_NORMAL,
            'status' => Incubator::STATUS_AVAILABLE,
            'daily_fee' => 0,
            'is_active' => true,
        ]);
        $this->assertEquals('حاضنة', $normalInc->type_name);
        $this->assertEquals('primary', $normalInc->type_color);
        $this->assertEquals('fa-baby', $normalInc->type_icon);

        $capsuleInc = Incubator::create([
            'incubator_number' => 'TEST-CAPS-01',
            'incubator_type' => Incubator::TYPE_CAPSULE,
            'status' => Incubator::STATUS_AVAILABLE,
            'daily_fee' => 0,
            'is_active' => true,
        ]);
        $this->assertEquals('كبسولة علاج ضوئي', $capsuleInc->type_name);
        $this->assertEquals('dark', $capsuleInc->type_color);
        $this->assertEquals('fa-capsules', $capsuleInc->type_icon);

        // 2. اختبار إنشاء حاضنة عبر المتحكم بنوع معتمد
        $storeResponse = $this->actingAs($this->admin)->post(route('incubators.store'), [
            'incubator_number' => 'TEST-NORM-02',
            'incubator_type' => 'normal',
            'daily_fee' => 0,
        ]);
        $storeResponse->assertRedirect(route('incubators.index'));

        // 3. رفض أي نوع قديم ملغي مثل oxygen
        $invalidResponse = $this->actingAs($this->admin)->post(route('incubators.store'), [
            'incubator_number' => 'TEST-OXY-FAIL',
            'incubator_type' => 'oxygen',
            'daily_fee' => 0,
        ]);
        $invalidResponse->assertSessionHasErrors(['incubator_type']);

        // 4. اختبار تحديث الحاضنة إلى كبسولة
        $updateResponse = $this->actingAs($this->admin)->put(route('incubators.update', $normalInc), [
            'incubator_number' => $normalInc->incubator_number,
            'incubator_type' => 'capsule',
            'status' => 'available',
            'daily_fee' => 0,
        ]);
        $updateResponse->assertRedirect(route('incubators.index'));
        $this->assertEquals('capsule', $normalInc->fresh()->incubator_type);

        // 5. التحقق من عرض الواجهة وإحصائيات النوعين
        $indexResponse = $this->actingAs($this->admin)->get(route('incubators.index'));
        $indexResponse->assertOk();
        $indexResponse->assertViewHas('stats', function ($stats) {
            return isset($stats['normal']) && isset($stats['capsule']) && !isset($stats['oxygen']);
        });
    }

    public function test_patient_live_search_and_adult_mother_reservation_with_baby_name(): void
    {
        // 1. إنشاء مريضة بالغة (الأم) بعمر 27 سنة
        $motherUser = \App\Models\User::factory()->create([
            'name' => 'فاطمة حسن علي',
            'phone' => '07701234567',
        ]);
        $motherPatient = \App\Models\Patient::create([
            'user_id' => $motherUser->id,
            'age' => 27,
            'gender' => 'female',
            'phone' => '07701234567',
            'insurance_type' => 'none',
        ]);

        // 2. اختبار البحث المباشر السريع بالاسم وبالهاتف
        $searchByNameResponse = $this->actingAs($this->admin)->getJson(
            route('incubator-reservations.search-patients', ['query' => 'فاطمة'])
        );
        $searchByNameResponse->assertOk();
        $searchByNameResponse->assertJsonFragment(['name' => 'فاطمة حسن علي']);

        $searchByPhoneResponse = $this->actingAs($this->admin)->getJson(
            route('incubator-reservations.search-patients', ['query' => '07701234567'])
        );
        $searchByPhoneResponse->assertOk();
        $searchByPhoneResponse->assertJsonFragment(['name' => 'فاطمة حسن علي']);

        // 3. اختبار الحجز باسم الأم بدون إدخال اسم الطفل (يولد تلقائياً طفل + اسم الأم)
        $inc1 = Incubator::create([
            'incubator_number' => 'INC-SEARCH-01',
            'incubator_type' => 'normal',
            'status' => 'available',
            'daily_fee' => 0,
            'is_active' => true,
        ]);

        $service = NicuService::first();

        $storeResponse1 = $this->actingAs($this->admin)->post(route('incubator-reservations.store'), [
            'patient_id' => $motherPatient->id,
            'incubator_id' => $inc1->id,
            'nicu_service_id' => $service->id,
            'gestational_age' => '35 أسبوع',
            'admission_date' => now()->format('Y-m-d'),
            'admission_time' => '10:00',
        ]);

        $storeResponse1->assertRedirect();
        $res1 = IncubatorReservation::where('incubator_id', $inc1->id)->first();
        $this->assertNotNull($res1);
        $this->assertEquals('طفل فاطمة حسن علي', $res1->baby_name);
        $this->assertEquals($motherPatient->id, $res1->patient_id);

        // 4. اختبار الحجز مع إدخال اسم طفل مخصص صريحاً
        $fatherUser = \App\Models\User::factory()->create([
            'name' => 'كرار حميد جاسم',
            'phone' => '07809876543',
        ]);
        $fatherPatient = \App\Models\Patient::create([
            'user_id' => $fatherUser->id,
            'age' => 32,
            'gender' => 'male',
            'phone' => '07809876543',
            'insurance_type' => 'none',
        ]);

        $inc2 = Incubator::create([
            'incubator_number' => 'INC-SEARCH-02',
            'incubator_type' => 'capsule',
            'status' => 'available',
            'daily_fee' => 0,
            'is_active' => true,
        ]);

        $storeResponse2 = $this->actingAs($this->admin)->post(route('incubator-reservations.store'), [
            'patient_id' => $fatherPatient->id,
            'baby_name' => 'يوسف كرار حميد',
            'incubator_id' => $inc2->id,
            'nicu_service_id' => $service->id,
            'gestational_age' => '31 أسبوع',
            'admission_date' => now()->format('Y-m-d'),
            'admission_time' => '11:00',
        ]);

        $storeResponse2->assertRedirect();
        $res2 = IncubatorReservation::where('incubator_id', $inc2->id)->first();
        $this->assertNotNull($res2);
        $this->assertEquals('يوسف كرار حميد', $res2->baby_name);
        $this->assertEquals($fatherPatient->id, $res2->patient_id);
    }

    public function test_incubator_reservation_create_page_renders_with_searchable_doctor_select(): void
    {
        $response = $this->actingAs($this->admin)->get(route('incubator-reservations.create', [
            'incubator_id' => $this->incubator->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('select2-doctor');
        $response->assertSee('ابحث عن الطبيب المشرف بالاسم أو التخصص');
        $response->assertSee(optional($this->doctor->user)->name);
        $response->assertSee('أخصائي طب الأطفال وحديثي الولادة');
    }

    public function test_internal_consultation_search_box_renders_and_auto_adopts_doctor_specialty(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'رضيع الفحص الاستشاري',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        // 1. فحص الواجهة وظهور مربع البحث وإخفاء التخصص الدقيق في الداخلي
        $response = $this->actingAs($this->admin)->get(route('nicu.show', $reservation));
        $response->assertStatus(200);
        $response->assertSee('id="internalDoctorSearchInput"', false);
        $response->assertSee('id="specialtyDiv"', false);
        $response->assertSee('d-none', false);

        // 2. إرسال استشارة داخلية بدون تخصص دقيق (يتم جلبه تلقائياً من بيانات الطبيب)
        $postInternal = $this->actingAs($this->admin)->post(route('nicu.consultations.store', $reservation), [
            'consultation_type' => 'internal',
            'doctor_id' => $this->doctor->id,
            'fee' => 40000,
            'reason' => 'تقييم فحص استشاري داخلي',
        ]);

        $postInternal->assertRedirect();
        $consultation = NicuConsultation::where('reservation_id', $reservation->id)->latest()->first();
        $this->assertNotNull($consultation);
        $this->assertEquals($this->doctor->id, $consultation->doctor_id);
        $this->assertEquals($this->doctor->specialization, $consultation->specialty);

        // 3. إرسال استشارة خارجية بدون تخصص دقيق يجب أن يفشل بالتحقق
        $postExternal = $this->actingAs($this->admin)->post(route('nicu.consultations.store', $reservation), [
            'consultation_type' => 'external',
            'external_doctor_name' => 'د. سمير الخارجي',
            'fee' => 50000,
            'reason' => 'استشارة جراحية خارجية',
        ]);
        $postExternal->assertSessionHasErrors(['specialty']);
    }

    public function test_doctor_can_order_lab_tests_with_auto_pricing(): void
    {
        $labTest = LabTest::create([
            'name' => 'Complete Blood Count (CBC)',
            'code' => 'CBC-01',
            'price' => 15000,
            'is_active' => true,
        ]);

        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل فحص المختبر',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $response = $this->actingAs($this->admin)->post(route('nicu.lab-orders.store', $reservation), [
            'tests' => ['Complete Blood Count (CBC)', 'Arterial Blood Gases (ABG)'],
            'priority' => 'stat',
            'instructions' => 'سحب كعب قدم عاجل',
        ]);

        $response->assertRedirect();
        $orders = NicuMedicalOrder::where('reservation_id', $reservation->id)
            ->where('order_type', 'lab')
            ->get();

        $this->assertCount(2, $orders);
        $cbcOrder = $orders->firstWhere('item_name', 'Complete Blood Count (CBC)');
        $this->assertNotNull($cbcOrder);
        $this->assertEquals(15000, $cbcOrder->price);
        $this->assertEquals('stat', $cbcOrder->priority);
        $this->assertEquals('سحب كعب قدم عاجل', $cbcOrder->instructions);
    }

    public function test_doctor_can_order_radiology_with_auto_pricing(): void
    {
        $radType = RadiologyType::create([
            'name' => 'Portable Chest X-Ray (Incubator)',
            'code' => 'RAD-CHEST-01',
            'base_price' => 30000,
            'is_active' => true,
        ]);

        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل فحص الأشعة',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $response = $this->actingAs($this->admin)->post(route('nicu.radiology-orders.store', $reservation), [
            'radiology_types' => ['Portable Chest X-Ray (Incubator)'],
            'priority' => 'urgent',
            'instructions' => 'تصوير داخل الحاضنة بدون تحريك',
        ]);

        $response->assertRedirect();
        $order = NicuMedicalOrder::where('reservation_id', $reservation->id)
            ->where('order_type', 'radiology')
            ->first();

        $this->assertNotNull($order);
        $this->assertEquals('Portable Chest X-Ray (Incubator)', $order->item_name);
        $this->assertEquals(30000, $order->price);
        $this->assertEquals('urgent', $order->priority);
    }

    public function test_doctor_can_issue_e_prescription_and_print_rx(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل الوصفة الطبية',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $response = $this->actingAs($this->admin)->post(route('nicu.prescriptions.store', $reservation), [
            'medications' => [
                [
                    'name' => 'Ampicillin 250mg',
                    'dose' => '50 mg/kg',
                    'route' => 'وريدي IV',
                    'frequency' => 'كل 12 ساعة (BID)',
                    'duration' => '5 أيام',
                    'instructions' => 'يُعطى ببطء عبر المضخة',
                ],
                [
                    'name' => 'Gentamicin 20mg',
                    'dose' => '4 mg/kg',
                    'route' => 'وريدي IV',
                    'frequency' => 'مرة يومياً (OD)',
                    'duration' => '3 أيام',
                    'instructions' => 'مراقبة وظائف الكلى',
                ]
            ]
        ]);

        $response->assertRedirect();
        $prescriptions = NicuMedicalOrder::where('reservation_id', $reservation->id)
            ->where('order_type', 'medication')
            ->get();

        $this->assertCount(2, $prescriptions);
        $ampicillin = $prescriptions->firstWhere('item_name', 'Ampicillin 250mg');
        $this->assertNotNull($ampicillin);
        $this->assertStringContainsString('50 mg/kg', $ampicillin->instructions);
        $this->assertStringContainsString('وريدي IV', $ampicillin->instructions);

        // Print RX test
        $printResponse = $this->actingAs($this->admin)->get(route('nicu.print.prescription', $reservation));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Ampicillin 250mg');
        $printResponse->assertSee('Gentamicin 20mg');
        $printResponse->assertSee('وصفة طبية إلكترونية');
    }

    public function test_nurse_can_record_medication_administration_mar_without_affecting_pharmacy(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل توثيق الجرعة MAR',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $response = $this->actingAs($this->admin)->post(route('nicu.medications-admin.store', $reservation), [
            'medication_name' => 'Ampicillin 250mg',
            'route' => 'iv',
            'dose' => '60 mg',
            'given_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'تم الإعطاء في كانيولا اليد اليسرى ببطء، استجابة طبيعية',
        ]);

        $response->assertRedirect();
        $mar = NicuMedicationAdministration::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($mar);
        $this->assertEquals('Ampicillin 250mg', $mar->medication_name);
        $this->assertEquals('iv', $mar->route);
        $this->assertEquals('حقن وريدي (IV)', $mar->route_name);
        $this->assertEquals('danger', $mar->route_badge_color);
        $this->assertEquals($this->admin->id, $mar->nurse_id);

        // التأكد من عدم إنشاء أي طلب فندقي أو صيدلاني مالي في nicu_medical_orders
        $medOrder = NicuMedicalOrder::where('reservation_id', $reservation->id)
            ->where('item_name', 'Ampicillin 250mg')
            ->first();
        $this->assertNull($medOrder, 'Nursing MAR must NEVER generate a medical billing order or inventory request!');
    }

    public function test_nicu_show_renders_all_workstation_tabs_and_mar_table(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل الواجهة السريرية الشاملة',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $response = $this->actingAs($this->admin)->get(route('nicu.show', $reservation));
        $response->assertStatus(200);

        // فحص وجود التبويبات الستة
        $response->assertSee('id="service-tab"', false);
        $response->assertSee('id="vitals-tab"', false);
        $response->assertSee('id="consultations-tab"', false);
        $response->assertSee('id="lab-tab"', false);
        $response->assertSee('id="radiology-tab"', false);
        $response->assertSee('id="prescriptions-tab"', false);

        // فحص وجود عناصر محطة المختبر بنمط الطبيب الاستشاري
        $response->assertSee('id="docLabSearchInput"', false);
        $response->assertSee('id="docLabSelectedTray"', false);

        // فحص وجود عناصر محطة الأشعة بنمط الطبيب الاستشاري
        $response->assertSee('id="docRadSearchInput"', false);
        $response->assertSee('id="docRadSelectedTray"', false);

        // فحص وجود تبويب ورسم معدلات النمو والوزن البياني
        $response->assertSee('id="growth-tab"', false);
        $response->assertSee('id="growthPane"', false);
        $response->assertSee('id="nicuWeightGrowthChart"', false);

        // فحص وجود تبويب المرفقات وسجل المستندات
        $response->assertSee('id="attachments-tab"', false);
        $response->assertSee('id="attachmentsPane"', false);

        // فحص وجود عناصر محطة الوصفة والطباعة
        $response->assertSee('id="prescriptionRowsContainer"', false);
        $response->assertSee('طباعة الوصفة (RX)');

        // فحص وجود تبويب التوصيات وخطة الرعاية
        $response->assertSee('id="care-schedules-tab"', false);
        $response->assertSee('id="careSchedulesPane"', false);

        // فحص وجود نموذج وجدول إعطاء العلاج MAR التمريضي المستقل
        $response->assertSee('توثيق إعطاء جرعة علاجية (MAR)');
        $response->assertSee('سجل إعطاء وتطبيق الأدوية التمريضي (MAR Timeline)');
    }

    public function test_care_schedules_creation_and_quick_administration(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل جدول الرعاية',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        // 1. إضافة جدول رضاعة (كل 3 ساعات)
        $feedingResp = $this->actingAs($this->admin)->post(route('nicu.care-schedules.store', $reservation), [
            'type' => 'feeding',
            'title' => 'حليب سيميلاك نيوشور',
            'dose_or_amount' => '25 ml',
            'route' => 'أنبوب أنفي معدي (NG Tube)',
            'interval_hours' => 3,
            'start_time' => now()->format('Y-m-d H:i:s'),
            'instructions' => 'فحص بقايا المعدة قبل الوجبة',
        ]);
        $feedingResp->assertSessionHas('success');

        $feedingSchedule = $reservation->careSchedules()->where('type', 'feeding')->first();
        $this->assertNotNull($feedingSchedule);
        $this->assertEquals('حليب سيميلاك نيوشور', $feedingSchedule->title);
        $this->assertEquals(3, $feedingSchedule->interval_hours);
        $this->assertTrue($feedingSchedule->is_active);

        // 2. إضافة جدول دواء (أمبيسيلين كل 8 ساعات)
        $medResp = $this->actingAs($this->admin)->post(route('nicu.care-schedules.store', $reservation), [
            'type' => 'medication',
            'title' => 'أمبيسيلين Ampicillin',
            'dose_or_amount' => '50 mg',
            'route' => 'وريدي (IV)',
            'interval_hours' => 8,
            'start_time' => now()->format('Y-m-d H:i:s'),
            'instructions' => 'إعطاء ببطء خلال 30 دقيقة',
        ]);
        $medResp->assertSessionHas('success');

        $medSchedule = $reservation->careSchedules()->where('type', 'medication')->first();
        $this->assertNotNull($medSchedule);
        $this->assertEquals(8, $medSchedule->interval_hours);

        // 3. توثيق إعطاء سريع للجرعة الدوائية (administer quick)
        $adminQuickResp = $this->actingAs($this->admin)->post(route('nicu.care-schedules.administer-quick', $medSchedule));
        $adminQuickResp->assertSessionHas('success');

        $medSchedule->refresh();
        $this->assertNotNull($medSchedule->last_administered_at);
        $this->assertTrue($medSchedule->next_due_at->gt(now()));

        // التأكد من توثيق الجرعة في MAR التمريضي تلقائياً
        $mar = \App\Models\NicuMedicationAdministration::where('reservation_id', $reservation->id)
            ->where('medication_name', 'أمبيسيلين Ampicillin')
            ->first();
        $this->assertNotNull($mar);
        $this->assertNotNull($mar->given_at);
        $this->assertEquals('50 mg', $mar->dose);

        // التأكد من عدم إنشاء أي طلب مخزني أو فواتير صيدلية
        $pharmacyOrder = \App\Models\NicuMedicalOrder::where('reservation_id', $reservation->id)
            ->where('order_type', 'medication')
            ->first();
        $this->assertNull($pharmacyOrder);

        // 4. فحص ظهور التنبيه على اللوحة اللحظية للحاضنات (Nicu Live Board)
        $this->incubator->update(['status' => Incubator::STATUS_OCCUPIED]);
        $liveBoardResp = $this->actingAs($this->admin)->get(route('nicu.index'));
        $liveBoardResp->assertStatus(200);
        $liveBoardResp->assertSee('حليب سيميلاك نيوشور');
        $liveBoardResp->assertSee('أمبيسيلين Ampicillin');
    }

    public function test_nicu_attachments_upload_download_and_deletion_workflow(): void
    {
        Storage::fake('public');

        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل فحص الأرشيف والمرفقات',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $fakeFile = UploadedFile::fake()->create('birth_certificate.pdf', 300, 'application/pdf');

        // 1. رفع المرفق باسم يختاره المستخدم بحرية
        $uploadResp = $this->actingAs($this->admin)->post(route('nicu.attachments.store', $reservation), [
            'title' => 'بيان الولادة الرسمي ومستمسكات الأبوين',
            'file' => $fakeFile,
            'notes' => 'نسخة مصدقة من مستشفى الولادة',
        ]);

        $uploadResp->assertRedirect();
        $uploadResp->assertSessionHas('success');

        $attachment = \App\Models\NicuAttachment::where('incubator_reservation_id', $reservation->id)->first();
        $this->assertNotNull($attachment);
        $this->assertEquals('بيان الولادة الرسمي ومستمسكات الأبوين', $attachment->title);
        $this->assertEquals('birth_certificate.pdf', $attachment->file_name);
        $this->assertEquals($this->babyPatient->id, $attachment->patient_id);
        $this->assertTrue($attachment->isPdf());
        Storage::disk('public')->assertExists($attachment->file_path);

        // 2. التحقق من ظهور المستند في شاشة الخدج وفي تبويب الأرشيف
        $showResp = $this->actingAs($this->admin)->get(route('nicu.show', $reservation));
        $showResp->assertStatus(200);
        $showResp->assertSee('بيان الولادة الرسمي ومستمسكات الأبوين');
        $showResp->assertSee('birth_certificate.pdf');

        // 3. تحميل المرفق
        $downloadResp = $this->actingAs($this->admin)->get(route('nicu.attachments.download', $attachment));
        $downloadResp->assertStatus(200);

        // 4. حذف المرفق
        $deleteResp = $this->actingAs($this->admin)->delete(route('nicu.attachments.destroy', $attachment));
        $deleteResp->assertRedirect();
        $this->assertDatabaseMissing('nicu_attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertMissing($attachment->file_path);
    }

    public function test_specialist_doctor_notes_creation_pinning_and_deletion(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل ملاحظات الاختصاص',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        // 1. تدوين ملاحظة جديدة وتثبيتها كأمر عاجل
        $noteResp = $this->actingAs($this->admin)->post(route('nicu.doctor-notes.store', $reservation), [
            'content' => 'إيقاف الرضاعة فوراً والبدء بمحلول سكري 10% بمعدل 4 مل/ساعة ومراقبة سكر الدم',
            'is_urgent' => true,
            'is_pinned' => true,
        ]);

        $noteResp->assertRedirect();
        $noteResp->assertSessionHas('success');

        $note = \App\Models\NicuDoctorNote::where('incubator_reservation_id', $reservation->id)->first();
        $this->assertNotNull($note);
        $this->assertTrue($note->is_urgent);
        $this->assertTrue($note->is_pinned);
        $this->assertEquals($this->admin->id, $note->doctor_id);

        // 2. التأكد من ظهور شريط التنبيه المثبت في أعلى المحطة السريرية
        $showResp = $this->actingAs($this->admin)->get(route('nicu.show', $reservation));
        $showResp->assertStatus(200);
        $showResp->assertSee('توجيهات وملاحظات الطبيب الاختصاص المثبتة');
        $showResp->assertSee('إيقاف الرضاعة فوراً والبدء بمحلول سكري 10%');

        // 3. إلغاء التثبيت
        $toggleResp = $this->actingAs($this->admin)->post(route('nicu.doctor-notes.toggle-pin', $note));
        $toggleResp->assertRedirect();
        $note->refresh();
        $this->assertFalse($note->is_pinned);

        // 4. حذف الملاحظة
        $deleteResp = $this->actingAs($this->admin)->delete(route('nicu.doctor-notes.destroy', $note));
        $deleteResp->assertRedirect();
        $this->assertDatabaseMissing('nicu_doctor_notes', ['id' => $note->id]);
    }

    public function test_central_lab_attachment_renders_in_nicu_workstation(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل مرفق المختبر',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        // إنشاء طلب مختبر مع مرفق تقرير جهاز الفحص
        $medRequest = \App\Models\Request::create([
            'incubator_reservation_id' => $reservation->id,
            'type' => 'lab',
            'status' => 'completed',
            'description' => 'تحاليل خديج: CBC Blood Profile',
            'details' => [
                'tests' => ['CBC Blood Profile'],
                'priority' => 'normal',
                'attachment' => 'lab_attachments/sysmex_cbc_report.pdf',
                'attachment_name' => 'sysmex_cbc_report.pdf',
                'attachment_mime' => 'application/pdf',
                'test_attachments' => [
                    'CBC Blood Profile' => [
                        'path' => 'lab_attachments/device_curve.png',
                        'name' => 'device_curve.png',
                    ],
                ],
            ],
            'result' => [
                'technician' => 'أحمد المختبري',
                'notes' => 'العينة سليمة وتم التحليل بنجاح',
                'completed_at' => now()->toDateTimeString(),
            ],
        ]);

        $showResp = $this->actingAs($this->admin)->get(route('nicu.show', $reservation));
        $showResp->assertStatus(200);
        $showResp->assertSee('sysmex_cbc_report.pdf');
        $showResp->assertSee('معاينة المرفق');
        $showResp->assertSee('device_curve.png');
    }

    public function test_nicu_roles_and_granular_permissions(): void
    {
        $doctorRole = \Spatie\Permission\Models\Role::findByName('nicu_doctor');
        $nurseRole = \Spatie\Permission\Models\Role::findByName('nicu_nurse');

        $this->assertNotNull($doctorRole);
        $this->assertNotNull($nurseRole);

        // الطبيب يمتلك صلاحيات الأوامر السريرية وملاحظات الاختصاص
        $this->assertTrue($doctorRole->hasPermissionTo('manage nicu doctor notes'));
        $this->assertTrue($doctorRole->hasPermissionTo('manage nicu clinical'));
        $this->assertTrue($doctorRole->hasPermissionTo('manage nicu attachments'));
        $this->assertTrue($doctorRole->hasPermissionTo('view incubator reservations'));

        // التمريض يمتلك صلاحيات محطة التمريض وخطة الرعاية
        $this->assertTrue($nurseRole->hasPermissionTo('manage nicu nursing'));
        $this->assertTrue($nurseRole->hasPermissionTo('manage nicu care plans'));
        $this->assertTrue($nurseRole->hasPermissionTo('manage nicu attachments'));
        $this->assertFalse($nurseRole->hasPermissionTo('manage nicu doctor notes'));

        // اختبار منع مستخدم غير مصرح من تدوين ملاحظات الطبيب
        $unauthorizedUser = User::factory()->create();
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل اختبار الصلاحيات',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $forbiddenResp = $this->actingAs($unauthorizedUser)->post(route('nicu.doctor-notes.store', $reservation), [
            'content' => 'ملاحظة غير مصرح بها',
        ]);
        $forbiddenResp->assertStatus(403);
    }

    public function test_nicu_workstation_renders_user_lab_test_groups_matching_consultant_doctor(): void
    {
        $labTest1 = LabTest::firstOrCreate(['name' => 'فحص بيليروبين كلي ومباشر'], ['code' => 'BIL-TOT', 'price' => 15000]);
        $labTest2 = LabTest::firstOrCreate(['name' => 'تعداد دم كامل خدج'], ['code' => 'CBC-NICU', 'price' => 12000]);

        // باقة مخصصة للخدج
        $nicuGroup = UserLabTestGroup::create([
            'user_id' => $this->admin->id,
            'name' => 'باقة يرقان ومسح دم مخصصة',
            'department' => 'nicu',
        ]);
        $nicuGroup->labTests()->attach([$labTest1->id, $labTest2->id]);

        // باقة استشارية عامة خارجية (يجب ألا تظهر في الخدج إطلاقاً)
        $consultantGroup = UserLabTestGroup::create([
            'user_id' => $this->admin->id,
            'name' => 'باقة جلدية وروماتزم استشارية',
            'department' => 'consultant',
        ]);
        $consultantGroup->labTests()->attach([$labTest1->id]);

        $reservation = IncubatorReservation::create([
            'patient_id' => $this->babyPatient->id,
            'baby_name' => 'طفل اختبار الباقات المخصصة',
            'incubator_id' => $this->incubator->id,
            'admission_date' => now(),
            'admission_time' => '10:00',
            'status' => 'admitted',
        ]);

        $response = $this->actingAs($this->admin)->get(route('nicu.show', $reservation));
        $response->assertStatus(200);

        // رابط إدارة باقات الخدج
        $response->assertSee(route('lab-tests.groups.index', ['department' => 'nicu']));
        $response->assertSee('إدارة باقات الخدج');

        // ظهور باقة الخدج حصراً
        $response->assertSee('باقة يرقان ومسح دم مخصصة');
        $response->assertSee('doc-lab-quick-pkg-btn');
        $response->assertSee('data-test-ids');
        $response->assertSee('docLabActiveFilterNotice');
        $response->assertSee('docLabResetPkgFilterBtn');

        // التأكد القاطع من عزل باقات الاستشارية والعيادات وعدم ظهورها في الخدج
        $response->assertDontSee('باقة جلدية وروماتزم استشارية');
    }

    public function test_lab_test_groups_department_isolation_between_nicu_and_consultant(): void
    {
        // 1. إنشاء باقة خدج عبر POST
        $postResp = $this->actingAs($this->admin)->post(route('lab-tests.groups.store'), [
            'name' => 'باقة خدج تنفسية جديدة',
            'department' => 'nicu',
            'description' => 'باقة متخصصة بالحاضنات',
        ]);
        $postResp->assertRedirect();

        $group = UserLabTestGroup::where('name', 'باقة خدج تنفسية جديدة')->first();
        $this->assertNotNull($group);
        $this->assertEquals('nicu', $group->department);

        // 2. التحقق من فلتر صفحة المجموعات للخدج
        $indexNicuResp = $this->actingAs($this->admin)->get(route('lab-tests.groups.index', ['department' => 'nicu']));
        $indexNicuResp->assertStatus(200);
        $indexNicuResp->assertSee('باقة خدج تنفسية جديدة');
        $indexNicuResp->assertSee('باقات قسم الخدج والحاضنات (NICU)');
    }
}

