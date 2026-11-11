<?php

namespace Tests\Feature;

use App\Models\CcuPayment;
use App\Models\CcuService;
use App\Models\CcuStayBracket;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Icd10Code;
use App\Models\IcuAdmission;
use App\Models\IcuBed;
use App\Models\IcuMedicalOrder;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\RadiologyType;
use App\Models\User;
use App\Services\CcuBillingService;
use Database\Seeders\CcuTariffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CcuBillingAndCashierTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $doctorUser;

    protected Doctor $doctor;

    protected Patient $patient;

    protected IcuBed $bed;

    protected IcuBed $ccuBed;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'view icu radar',
            'manage icu beds',
            'admit icu patients',
            'record icu vitals',
            'record icu fluid balance',
            'manage icu medical orders',
            'manage icu care plans',
            'discharge icu patients',
            'view cashier',
            'process payments',
            'process refunds',
            'view ccu analytics',
            'view ccu financial movements',
            'view ccu statements',
            'manage ccu services',
            'switch ccu services',
            'edit ccu diagnosis',
            'view treasury',
            'view insurance claims',
            'manage health insurance',
            'view accounting',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($permissions);

        $docRole = Role::firstOrCreate(['name' => 'icu_doctor']);
        $docRole->syncPermissions([
            'view icu radar',
            'manage icu medical orders',
            'manage ccu services',
            'switch ccu services',
            'edit ccu diagnosis',
        ]);

        $this->admin = User::factory()->create(['name' => 'أدمن العناية']);
        $this->admin->assignRole('admin');

        CcuTariffSeeder::seedDefaults();

        $hospital = Hospital::create([
            'name' => 'مستشفى بغداد التخصصي',
            'owner_name' => 'المدير العام',
            'license_number' => 'BAG-CCU-99',
            'phone' => '07700000000',
            'address' => 'بغداد',
        ]);

        $dept = Department::firstOrCreate(
            ['name' => 'العناية المركزة للكبار CCU'],
            [
                'hospital_id' => $hospital->id,
                'type' => 'other',
                'room_number' => 'CCU-100',
                'consultation_fee' => 30000,
                'working_hours_start' => '08:00',
                'working_hours_end' => '20:00',
                'is_active' => true,
            ]
        );

        $this->doctorUser = User::factory()->create(['name' => 'د. حيدر العناية']);
        $this->doctorUser->assignRole('icu_doctor');

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'عناية مركزة وتخدير',
            'qualification' => 'بورد عناية مركزة',
            'license_number' => 'CCU-DOC-99',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'سعد كريم علي', 'phone' => '07719998888']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'phone' => '07719998888',
            'gender' => 'male',
            'national_id' => '198801019999',
        ]);

        $this->bed = IcuBed::create([
            'bed_number' => '10',
            'bed_type' => IcuBed::TYPE_GENERAL,
            'room_number' => 'ICU-R10',
            'floor' => 'الطابق الثاني',
            'status' => IcuBed::STATUS_AVAILABLE,
            'daily_price' => 175000.00,
            'is_active' => true,
        ]);

        $this->ccuBed = IcuBed::create([
            'bed_number' => '11',
            'bed_type' => IcuBed::TYPE_CCU,
            'room_number' => 'CCU-R11',
            'floor' => 'الطابق الثاني',
            'status' => IcuBed::STATUS_AVAILABLE,
            'daily_price' => 200000.00,
            'is_active' => true,
        ]);
    }

    public function test_reception_admits_without_diagnosis_and_doctor_sets_bilingual_icd10_and_gcs_in_dossier(): void
    {
        Icd10Code::create([
            'code' => 'R65.21',
            'description' => 'Severe sepsis with septic shock',
            'description_ar' => 'تسمم الدم الشديد مع صدمة إنتانية',
            'category' => 'Infectious',
        ]);

        $ventService = CcuService::where('code', 'ventilator_support')->firstOrFail();

        // 1. الاستعلامات تدخل المريض بدون تشخيص سريري أو GCS أو ICD-10
        $resp = $this->actingAs($this->admin)->post(route('icu.admissions.store'), [
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'ccu_service_id' => $ventService->id,
            'admission_source' => 'emergency',
            'admission_date' => now()->format('Y-m-d\TH:i'),
            'is_insured' => 1,
            'policy_category' => 'E',
        ]);

        $admission = IcuAdmission::latest('id')->firstOrFail();
        $resp->assertRedirect(route('icu.show', $admission->id));
        $this->assertNull($admission->primary_diagnosis);
        $this->assertEquals($ventService->id, $admission->ccu_service_id);
        $this->assertTrue((bool) $admission->is_ventilated);

        // 2. البحث الذكي ثنائي اللغة (عربي / إنجليزي) عن ICD-10 يعيد النتيجة للطبيب
        $searchAr = $this->actingAs($this->doctorUser)->get(route('doctor.icd10.search', ['q' => 'صدمة']));
        $searchAr->assertStatus(200)->assertJsonFragment(['code' => 'R65.21']);

        $searchEn = $this->actingAs($this->doctorUser)->get(route('doctor.icd10.search', ['q' => 'sepsis']));
        $searchEn->assertStatus(200)->assertJsonFragment(['code' => 'R65.21']);

        // 3. الطبيب يحدد التشخيص الأولي و ICD-10 و GCS داخل طبلة المريض
        $diagResp = $this->actingAs($this->doctorUser)->post(route('ccu.admissions.initial-diagnosis.update', $admission->id), [
            'primary_diagnosis' => 'Severe sepsis with septic shock (تسمم الدم الشديد)',
            'icd10_code' => 'R65.21',
            'initial_gcs' => 9,
        ]);

        $diagResp->assertRedirect();
        $admission->refresh();
        $this->assertEquals('R65.21', $admission->icd10_code);
        $this->assertEquals(9, $admission->initial_gcs);
        $this->assertDatabaseHas('icu_vital_signs', [
            'icu_admission_id' => $admission->id,
            'gcs' => 9,
        ]);
    }

    public function test_official_moh_progressive_stay_brackets_and_annual_30_day_limit_across_admissions(): void
    {
        $ventService = CcuService::where('code', 'ventilator_support')->firstOrFail();
        $billing = app(CcuBillingService::class);

        // رقود أول لمدة 7 أيام على الفنتليتر (750,000/يوم):
        // الأيام 1..5 => 75% ضمان (562,500) / 25% مريض (187,500)
        // الأيام 6..7 => 50% ضمان (375,000) / 50% مريض (375,000)
        $admDate = now()->subDays(7)->setTime(10, 0); // ضمن السماحية الصباحية
        $discDate = now()->setTime(11, 0); // قبل الـ 12 ظهراً => 7 أيام بالضبط

        $adm1 = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'ccu_service_id' => $ventService->id,
            'admission_source' => 'emergency',
            'admission_date' => $admDate,
            'discharge_date' => $discDate,
            'status' => IcuAdmission::STATUS_DISCHARGED,
            'is_ventilated' => true,
            'is_insured' => true,
            'insurance_type' => 'hi',
            'policy_category' => 'E',
        ]);

        $d1 = $billing->calculateStayDetails($adm1);

        $this->assertEquals(7, $d1['days_count']);
        $this->assertEquals(5 * 562500 + 2 * 375000, $d1['insurance_grand_total']); // 3,562,500
        $this->assertEquals(5 * 187500 + 2 * 375000, $d1['patient_grand_total']);   // 1,687,500

        // اختبار تراكم السقف السنوي (30 يوم): إذا كانت لديه إضبارة سابقة 22 يوماً وإضبارة حالية 3 أيام => أيام 28..30 (شريحة 10% ضمان)
        // سنحدث الإضبارة الأولى لتمتد 28 يوماً ثم نفتح إضبارة ثانية لـ 4 أيام (29، 30 @ 10% و 31، 32 @ 0%)
        $adm1->update([
            'admission_date' => now()->subDays(34)->setTime(10, 0),
            'discharge_date' => now()->subDays(6)->setTime(11, 0), // 28 يوم في نفس السنة
        ]);

        $adm2 = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->ccuBed->id,
            'doctor_id' => $this->doctor->id,
            'ccu_service_id' => $ventService->id,
            'admission_source' => 'emergency',
            'admission_date' => now()->subDays(4)->setTime(10, 0),
            'discharge_date' => now()->setTime(11, 0), // 4 أيام -> سنوياً 29, 30, 31, 32
            'status' => IcuAdmission::STATUS_DISCHARGED,
            'is_ventilated' => true,
            'is_insured' => true,
            'insurance_type' => 'hi',
            'policy_category' => 'E',
        ]);

        $d2 = $billing->calculateStayDetails($adm2);

        $this->assertEquals(28, $d2['prior_annual_used_days']);
        $this->assertEquals(4, $d2['days_count']);
        // يوم 29 و 30 => 10% من 750,000 = 75,000 لكل يوم (المجموع 150,000 للضمان)، يوم 31 و 32 => 0% ضمان
        $this->assertEquals(150000.0, $d2['insurance_grand_total']);
        $this->assertEquals(2 * 675000 + 2 * 750000, $d2['patient_grand_total']); // 2,850,000
    }

    public function test_bundled_labs_show_zero_with_original_price_preserved_and_one_click_toggle_updates_totals(): void
    {
        $ventService = CcuService::where('code', 'ventilator_support')->firstOrFail();
        $billing = app(CcuBillingService::class);

        $labTest = LabTest::create([
            'main_category' => 'كيمياء سريرية',
            'subcategory' => 'إنزيمات القلب',
            'code' => 'TROP-I',
            'name' => 'Troponin I Quantitative',
            'price' => 50000,
            'hi_price' => 40000,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $adm = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'ccu_service_id' => $ventService->id,
            'admission_source' => 'emergency',
            'admission_date' => now(),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'is_ventilated' => true,
            'is_insured' => true,
            'insurance_type' => 'hi',
            'policy_category' => 'E',
        ]);

        $order = IcuMedicalOrder::create([
            'icu_admission_id' => $adm->id,
            'doctor_id' => $this->doctor->id,
            'order_type' => 'lab',
            'order_text' => $labTest->name,
            'item_name' => $labTest->name,
            'lab_test_id' => $labTest->id,
            'price' => 40000,
            'original_price' => 40000,
            'billing_mode' => 'auto',
            'priority' => 'stat',
            'status' => 'pending',
            'ordered_by' => $this->doctorUser->id,
            'ordered_at' => now(),
        ]);

        // الحالة الافتراضية على باقة 750,000: التحليل مشمول = 0 على المريض، والقيمة الأصلية محفوظة للمطالبة
        $detailsBundled = $billing->calculateStayDetails($adm);
        $this->assertEquals(0.0, $detailsBundled['total_lab_cost']);
        $this->assertEquals(0.0, $detailsBundled['patient_lab_share']);
        $this->assertEquals(40000.0, $detailsBundled['bundled_reference_total']);
        $this->assertEquals(187500.0, $detailsBundled['patient_grand_total']); // فقط حصة المريض 25% من اليوم الأول
        $this->assertEquals(562500.0, $detailsBundled['insurance_grand_total']);

        // ضغطة زر 1: تحويل التحليل إلى احتساب منفرد (Extra)
        $toggleExtra = $this->actingAs($this->doctorUser)->post(route('cashier.ccu.orders.toggle-billing', $order->id), [
            'billing_mode' => 'extra',
        ]);
        $toggleExtra->assertRedirect();

        $detailsExtra = $billing->calculateStayDetails($adm->fresh());
        $this->assertEquals(0.0, $detailsExtra['bundled_reference_total']);
        $this->assertEquals(40000.0, $detailsExtra['total_lab_cost']);
        $this->assertEquals(10000.0, $detailsExtra['patient_lab_share']);   // 25% من 40,000
        $this->assertEquals(30000.0, $detailsExtra['insurance_lab_share']); // 75% من 40,000
        $this->assertEquals(197500.0, $detailsExtra['patient_grand_total']);
        $this->assertEquals(592500.0, $detailsExtra['insurance_grand_total']);

        // ضغطة زر 2: إرجاعه مشمولاً بالباقة (0 د.ع)
        $this->actingAs($this->doctorUser)->post(route('cashier.ccu.orders.toggle-billing', $order->id), [
            'billing_mode' => 'bundled',
        ])->assertRedirect();

        $detailsBack = $billing->calculateStayDetails($adm->fresh());
        $this->assertEquals(40000.0, $detailsBack['bundled_reference_total']);
        $this->assertEquals(187500.0, $detailsBack['patient_grand_total']);
    }

    public function test_cash_custom_pricing_with_separate_bed_and_custom_addons_and_radiology_billing(): void
    {
        $cashSepService = CcuService::where('code', 'cash_bed_only_separate')->firstOrFail();
        $billing = app(CcuBillingService::class);

        $radType = RadiologyType::create([
            'main_category' => 'الأشعة السينية',
            'subcategory' => 'صدر',
            'name' => 'Portable Chest X-Ray ICU',
            'code' => 'CXR-PORT',
            'base_price' => 35000,
            'hi_price' => 25000,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        // مريض كاش يدفع سعر السرير فقط (175,000) + خدمات إضافية مخصصة + أشعة
        $adm = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id, // daily_price = 175,000
            'doctor_id' => $this->doctor->id,
            'ccu_service_id' => $cashSepService->id,
            'admission_source' => 'ward',
            'admission_date' => now(),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'is_insured' => false,
            'insurance_type' => 'none',
            'pricing_mode' => 'bed_plus_services',
        ]);

        // إضافة خدمة مخصصة من طبلة/كاشير العناية (تمريض خاص 50,000 × 2)
        $this->actingAs($this->admin)->post(route('ccu.admissions.addon-service', $adm->id), [
            'custom_name' => 'جهاز دعم تنفسي ومضخة خاصة (كاش)',
            'unit_price' => 50000,
            'quantity' => 2,
        ])->assertRedirect();

        IcuMedicalOrder::create([
            'icu_admission_id' => $adm->id,
            'doctor_id' => $this->doctor->id,
            'order_type' => 'radiology',
            'order_text' => $radType->name,
            'item_name' => $radType->name,
            'radiology_type_id' => $radType->id,
            'price' => 35000,
            'original_price' => 35000,
            'billing_mode' => 'auto',
            'priority' => 'urgent',
            'status' => 'pending',
            'ordered_by' => $this->doctorUser->id,
            'ordered_at' => now(),
        ]);

        $d = $billing->calculateStayDetails($adm->fresh());

        $this->assertFalse($d['is_insured']);
        $this->assertEquals(175000.0, $d['total_stay_service_cost']); // سعر السرير لليوم الأول
        $this->assertEquals(100000.0, $d['total_addon_cost']);        // 50,000 × 2
        $this->assertEquals(35000.0, $d['total_radiology_cost']);     // تسعيرة الكاش base_price
        $this->assertEquals(310000.0, $d['patient_grand_total']);
        $this->assertEquals(0.0, $d['insurance_grand_total']);
    }

    public function test_full_ccu_cashier_payment_refund_claims_analytics_and_treasury_views(): void
    {
        $ventService = CcuService::where('code', 'ventilator_support')->firstOrFail();

        $adm = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'ccu_service_id' => $ventService->id,
            'admission_source' => 'emergency',
            'admission_date' => now(),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'primary_diagnosis' => 'ARDS Respiratory Failure',
            'icd10_code' => 'J80',
            'is_ventilated' => true,
            'is_insured' => true,
            'insurance_type' => 'hi',
            'policy_category' => 'E',
        ]);

        // 1. دفع سلفة أمانات أكبر من حصة المريض (حصة المريض 187,500، دفع 300,000 => فائض 112,500)
        $payResp = $this->actingAs($this->admin)->post(route('cashier.ccu.payment', $adm->id), [
            'amount' => 300000,
            'payment_method' => 'cash',
            'payment_type' => CcuPayment::TYPE_DEPOSIT,
        ]);
        $payment1 = CcuPayment::latest('id')->firstOrFail();
        $payResp->assertRedirect(route('cashier.ccu.receipt', $payment1->id));

        $this->assertDatabaseHas('financial_transactions', [
            'related_type' => CcuPayment::class,
            'related_id' => $payment1->id,
            'category' => 'icu',
            'voucher_type' => 'inflow',
            'amount' => 300000,
        ]);

        // 2. إرجاع فائض الأمانات (112,500)
        $refundResp = $this->actingAs($this->admin)->post(route('cashier.ccu.payment', $adm->id), [
            'amount' => 112500,
            'payment_method' => 'cash',
            'payment_type' => CcuPayment::TYPE_REFUND,
        ]);
        $refundPay = CcuPayment::latest('id')->firstOrFail();
        $refundResp->assertRedirect(route('cashier.ccu.receipt', $refundPay->id));

        // 3. تحديث تحصيل الضمان الجزئي والتحقق من الباقي بذمة الضمان (562,500 - 200,000 = 362,500)
        $this->actingAs($this->admin)->post(route('cashier.ccu.hia-collection', $adm->id), [
            'hia_collected_amount' => 200000,
        ])->assertRedirect();

        $afterDetails = app(CcuBillingService::class)->calculateStayDetails($adm->fresh());
        $this->assertEquals(0.0, $afterDetails['net_balance']);
        $this->assertEquals(362500.0, $afterDetails['hia_remaining_balance']);

        // 4. التحكم الداينمك بالأسعار والشرائح والإعدادات
        $bracket = CcuStayBracket::firstOrFail();
        $this->actingAs($this->admin)->put(route('ccu.brackets.update', $bracket->id), [
            'name' => $bracket->name,
            'policy_category' => 'ALL',
            'min_day' => $bracket->min_day,
            'max_day' => $bracket->max_day,
            'hia_percent' => 80,
            'is_active' => 1,
        ])->assertRedirect();
        $this->assertEquals(80.0, (float) $bracket->fresh()->hia_percent);

        // 5. التحقق من عرض كافة شاشات CCU والكاشير والمحاسبة والخزينة والمطالبات 200 OK
        $this->actingAs($this->admin)->get(route('ccu.services.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('cashier.ccu.payment.form', $adm->id))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('cashier.ccu.receipt', $payment1->id))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('cashier.ccu.receipt.print', $payment1->id))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('cashier.ccu.itemized-receipt.print', $adm->id))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('cashier.ccu.statements'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('cashier.ccu.financial-movements'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('cashier.index'))->assertStatus(200)->assertSee('العناية المركزة (CCU)');
        $this->actingAs($this->admin)->get(route('accountant.ccu.analytics'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('treasury.index'))->assertStatus(200)->assertSee('العناية المركزة للكبار');
        $this->actingAs($this->admin)->get(route('insurance.claims.index'))->assertStatus(200)->assertSee('مطالبات العناية المركزة للكبار');
    }
}
