<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuService;
use App\Models\NicuServiceLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\NicuBillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NicuInsuranceSettlementAndClaimsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $cashierUser;

    protected Doctor $doctor;

    protected Patient $patient;

    protected Incubator $incubator;

    protected NicuService $service;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'hospital_admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['name' => 'المدير المالي']);
        $this->admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'مستشفى الفيحاء',
            'owner_name' => 'د. حسام',
            'license_number' => 'LIC-100',
            'phone' => '07700000000',
            'address' => 'بغداد',
        ]);

        $dept = Department::firstOrCreate([
            'name' => 'قسم الأطفال والخدج',
            'hospital_id' => $hospital->id,
            'type' => 'pediatrics',
            'room_number' => '201',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. حسام استشاري خديج']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'حديثي ولادة',
            'qualification' => 'بورد',
            'license_number' => 'LIC-777',
            'consultation_fee' => 25000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'والدة الخديج']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'phone' => '07700000002',
            'gender' => 'female',
        ]);

        $this->incubator = Incubator::create([
            'incubator_number' => 'INC-01',
            'room_number' => 'NICU-1',
            'status' => 'occupied',
            'daily_fee' => 100000,
        ]);

        $this->service = NicuService::create([
            'name' => 'حاضنة دعم تنفسي وسريري متقدم CPAP',
            'code' => 'cpap_advanced',
            'daily_fee' => 200000,
            'hi_price' => 180000,
            'is_hi_active' => true,
            'is_active' => true,
        ]);
    }

    /**
     * اختبار احتساب الأيام الحية تلقائياً ومصفوفة عمر الحمل (33 أسبوعاً لمدة 7 أيام)
     */
    public function test_automatic_stay_days_and_gestational_matrix_calculation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04 14:00:00'));

        // دخول قبل 7 أيام، عمر الحمل 33 أسبوعاً (شريحة 32 إلى < 34 أسبوعاً)
        // أيام 1 إلى 5: 75% ضمان و 25% مريض
        // أيام 6 و 7: 50% ضمان و 50% مريض
        $admissionDate = now()->subDays(7)->setTime(10, 0); // 7 أيام فندقية كاملة
        $res = IncubatorReservation::create([
            'patient_id' => $this->patient->id,
            'baby_name' => 'طفل زينب',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'admission_date' => $admissionDate->toDateString(),
            'admission_time' => '10:00:00',
            'status' => 'admitted',
            'insurance_type' => 'hi',
            'policy_category' => 'E',
            'gestational_age' => '33 أسبوعاً',
        ]);

        // إنشاء سجل خدمة أولي مفتوح
        NicuServiceLog::create([
            'reservation_id' => $res->id,
            'nicu_service_id' => $this->service->id,
            'daily_fee' => $this->service->getEffectiveFee('hi'), // 180000
            'start_time' => $admissionDate,
            'end_time' => null,
            'days_count' => 1,
            'total_cost' => 180000,
        ]);

        $billingService = app(NicuBillingService::class);
        $stayDetails = $billingService->calculateStayDetails($res);

        // التحقق من حساب 7 أيام حية تلقائياً دون الحاجة لتدخل يدوي
        $this->assertEquals(7, $stayDetails['calculated_days']);
        $this->assertEquals(7 * 180000, $stayDetails['total_service_cost']); // 1,260,000

        // حساب الحصص الرياضية:
        // أيام 1-5: 5 * 180,000 = 900,000 -> الضمان (75% = 675,000) | المريض (25% = 225,000)
        // أيام 6-7: 2 * 180,000 = 360,000 -> الضمان (50% = 180,000) | المريض (50% = 180,000)
        // إجمالي الضمان المطالب به = 675,000 + 180,000 = 855,000 د.ع
        // إجمالي حصة المريض = 225,000 + 180,000 = 405,000 د.ع
        $this->assertEquals(855000, $stayDetails['insurance_share_total']);
        $this->assertEquals(405000, $stayDetails['patient_share_total']);

        // التحقق من تحديث الحقول على الحجز نفسه
        $res->refresh();
        $this->assertEquals(855000, (float) $res->insurance_share_amount);
        $this->assertEquals(405000, (float) $res->patient_share_amount);

        Carbon::setTestNow();
    }

    /**
     * اختبار انعكاس مطالبات الخدج في خزينة المستشفى المركزية
     */
    public function test_hospital_treasury_reflects_nicu_insurance_claims(): void
    {
        $admissionDate = now()->subDays(3)->setTime(10, 0);
        $res = IncubatorReservation::create([
            'patient_id' => $this->patient->id,
            'baby_name' => 'طفل مريم',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'admission_date' => $admissionDate->toDateString(),
            'admission_time' => '10:00:00',
            'status' => 'admitted',
            'insurance_type' => 'hi',
            'policy_category' => 'E',
            'gestational_age' => '35 أسبوعاً',
            'insurance_share_amount' => 540000,
            'patient_share_amount' => 180000,
        ]);

        $resView = $this->actingAs($this->admin)->get(route('treasury.index'));
        $resView->assertStatus(200);
        $resView->assertSee('مطالبات الضمان (الفترة)');
        $resView->assertSee(number_format(540000));
    }

    /**
     * اختبار كشوفات حسابات الخدج بالكاشير وإبراز الفرق والمطالبة
     */
    public function test_cashier_nicu_statements_displays_insurance_claim_and_gestational_age(): void
    {
        $admissionDate = now()->subDays(2)->setTime(10, 0);
        $res = IncubatorReservation::create([
            'patient_id' => $this->patient->id,
            'baby_name' => 'خديج فاطمة',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'admission_date' => $admissionDate->toDateString(),
            'admission_time' => '10:00:00',
            'status' => 'admitted',
            'insurance_type' => 'hi',
            'policy_category' => 'E',
            'gestational_age' => '31 أسبوعاً',
        ]);

        NicuServiceLog::create([
            'reservation_id' => $res->id,
            'nicu_service_id' => $this->service->id,
            'daily_fee' => $this->service->getEffectiveFee('hi'),
            'start_time' => $admissionDate,
            'end_time' => null,
            'days_count' => 1,
            'total_cost' => 180000,
        ]);

        // استدعاء حساب التصفية
        app(NicuBillingService::class)->calculateStayDetails($res);

        $response = $this->actingAs($this->admin)->get(route('cashier.nicu.statements'));
        $response->assertStatus(200);
        $response->assertSee('مطالبة الضمان (تطلبها المستشفى)');
        $response->assertSee('خديج فاطمة');
        $response->assertSee('31 أسبوعاً');
        $response->assertSee('تطلبها المستشفى');
    }

    /**
     * اختبار لوحة تحليلات محاسب الخدج ومصفوفة فئات عمر الحمل الوزارية
     */
    public function test_accountant_nicu_analytics_displays_gestational_matrix_and_claims(): void
    {
        $res = IncubatorReservation::create([
            'patient_id' => $this->patient->id,
            'baby_name' => 'خديج نور',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'admission_date' => now()->toDateString(),
            'admission_time' => '10:00:00',
            'status' => 'admitted',
            'insurance_type' => 'hi',
            'policy_category' => 'E',
            'gestational_age' => '27 أسبوعاً',
            'insurance_share_amount' => 450000,
            'patient_share_amount' => 150000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('accountant.nicu.analytics'));
        $response->assertStatus(200);
        $response->assertSee('تصفيات ومطالبات الضمان الصحي للخدج وفق مصفوفة عمر الحمل الوزارية');
        $response->assertSee('< 28 أسبوعاً (خداجة شديدة - تغطية مستمرة)');
        $response->assertSee(number_format(450000));
    }
}
