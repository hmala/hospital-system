<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuMilkType;
use App\Models\NicuService;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NicuGranularPermissionsAndMilkTypesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $receptionist;

    protected User $nicuDoctor;

    protected User $nicuNurse;

    protected IncubatorReservation $activeReservation;

    protected Room $room;

    protected Incubator $incubator;

    protected NicuService $service;

    protected Department $department;

    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $hospital = Hospital::create([
            'name' => 'مستشفى الفيحاء',
            'owner_name' => 'د. حسام',
            'license_number' => 'LIC-100',
            'phone' => '07700000000',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الأطفال والخدج',
            'description' => 'NICU',
            'type' => 'pediatrics',
            'room_number' => '601',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
        ]);

        $this->room = Room::create([
            'room_number' => 'NICU-101',
            'type' => 'nicu',
            'status' => 'available',
            'department_id' => $this->department->id,
            'daily_rate' => 50000,
        ]);

        $this->incubator = Incubator::create([
            'incubator_number' => 'INC-01',
            'room_id' => $this->room->id,
            'status' => Incubator::STATUS_OCCUPIED,
            'notes' => 'حاضنة العناية الفائقة',
            'incubator_type' => 'intensive',
            'features' => ['phototherapy'],
        ]);

        $this->service = NicuService::create([
            'name' => 'حاضنة عناية مركزة + جهاز تنفس',
            'code' => 'NICU_INT_VENT',
            'daily_fee' => 150000,
            'hi_price' => 120000,
            'official_claim_name' => 'العناية المركزة لحديثي الولادة مع التنفس الاصطناعي',
            'is_active' => true,
            'is_hi_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'والدة خديج تجريبي']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'blood_type' => 'O+',
            'emergency_contact_name' => 'أحمد العبيدي',
            'emergency_contact_phone' => '07711112222',
        ]);

        $this->activeReservation = IncubatorReservation::create([
            'incubator_id' => $this->incubator->id,
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'nicu_service_id' => $this->service->id,
            'baby_name' => 'خديج تجريبي نشط',
            'gender' => 'male',
            'birth_weight' => 1800,
            'current_weight' => 1850,
            'gestational_age' => '32 أسبوعاً',
            'admission_time' => '10:00:00',
            'admission_date' => now()->subDays(2)->toDateString(),
            'status' => IncubatorReservation::STATUS_ADMITTED,
        ]);

        $this->admin = User::factory()->create(['name' => 'المدير العام']);
        $this->admin->assignRole('admin');

        $this->receptionist = User::factory()->create(['name' => 'موظف الاستعلامات']);
        $this->receptionist->assignRole('receptionist');

        $this->nicuDoctor = User::factory()->create(['name' => 'طبيب اختصاص الخدج']);
        $this->nicuDoctor->assignRole('nicu_doctor');

        $this->nicuNurse = User::factory()->create(['name' => 'ممرضة الخدج']);
        $this->nicuNurse->assignRole('nicu_nurse');
    }

    /**
     * الاستعلامات تستقبل وتحجز الحاضنات وتطبع الكروت، ولكنها محظورة تماماً عن الإجراءات السريرية والتمريضية
     */
    public function test_receptionist_can_book_and_print_cards_but_cannot_perform_clinical_or_nursing_actions(): void
    {
        // 1. الاستعلامات تستعرض قائمة الخدج وتطبع أسورة المعصم
        $this->actingAs($this->receptionist);

        $resIndex = $this->get(route('nicu.reservations.index'));
        $resIndex->assertOk();

        $wristbandRes = $this->get(route('nicu.print.wristband', $this->activeReservation));
        $wristbandRes->assertOk();

        $cribCardRes = $this->get(route('nicu.print.crib-card', $this->activeReservation));
        $cribCardRes->assertOk();

        // 2. الاستعلامات محظورة عن توثيق العلامات الحيوية (403)
        $vitalsRes = $this->post(route('nicu.vitals.store', $this->activeReservation), [
            'heart_rate' => 140,
            'temperature' => 36.8,
        ]);
        $vitalsRes->assertForbidden();

        // 3. الاستعلامات محظورة عن طلب التحاليل المخبرية (403)
        $labRes = $this->post(route('nicu.lab-orders.store', $this->activeReservation), [
            'tests' => ['CBC'],
            'priority' => 'urgent',
        ]);
        $labRes->assertForbidden();

        // 4. الاستعلامات محظورة عن طلب الأشعة (403)
        $radRes = $this->post(route('nicu.radiology-orders.store', $this->activeReservation), [
            'radiology_types' => ['Chest X-Ray'],
            'priority' => 'urgent',
        ]);
        $radRes->assertForbidden();

        // 5. الاستعلامات محظورة عن كتابة الوصفات الدوائية (403)
        $rxRes = $this->post(route('nicu.prescriptions.store', $this->activeReservation), [
            'item_name' => 'Ampicillin',
            'dose' => '100mg',
        ]);
        $rxRes->assertForbidden();

        // 6. الاستعلامات محظورة عن تعديل البيانات السريرية وعمر الحمل (403)
        $profileRes = $this->post(route('nicu.clinical-profile.update', $this->activeReservation), [
            'birth_weight' => 2000,
            'gestational_age' => '34 أسبوعاً',
        ]);
        $profileRes->assertForbidden();

        // 7. الاستعلامات محظورة عن إتمام الخروج الطبي للطفل (403)
        $dischargeRes = $this->post(route('nicu.discharge', $this->activeReservation), [
            'discharge_type' => 'improved',
        ]);
        $dischargeRes->assertForbidden();
    }

    /**
     * طبيب الخدج يملك الصلاحيات السريرية التامة (تحاليل، أشعة، وصفات، بيانات سريرية)
     */
    public function test_nicu_doctor_can_perform_clinical_orders_and_profile_updates(): void
    {
        $this->actingAs($this->nicuDoctor);

        // 1. طلب فحص مخبري
        $labRes = $this->post(route('nicu.lab-orders.store', $this->activeReservation), [
            'tests' => ['Serum Bilirubin'],
            'priority' => 'urgent',
            'instructions' => 'فحص يرقان فوري',
        ]);
        $labRes->assertSessionHas('success');

        // 2. طلب فحص إشعاعي
        $radRes = $this->post(route('nicu.radiology-orders.store', $this->activeReservation), [
            'radiology_types' => ['Babygram Chest & Abdomen'],
            'priority' => 'urgent',
        ]);
        $radRes->assertSessionHas('success');

        // 3. كتابة وصفة طبية
        $rxRes = $this->post(route('nicu.prescriptions.store', $this->activeReservation), [
            'item_name' => 'Caffeine Citrate',
            'dose' => '10mg/kg',
            'route' => 'IV',
        ]);
        $rxRes->assertSessionHas('success');

        // 4. تحديث البيانات السريرية للولادة
        $profileRes = $this->post(route('nicu.clinical-profile.update', $this->activeReservation), [
            'birth_weight' => 1950,
            'gestational_age' => '33 أسبوعاً',
        ]);
        $profileRes->assertSessionHas('success');
        $this->activeReservation->refresh();
        $this->assertEquals(1950, $this->activeReservation->birth_weight);
    }

    /**
     * ممرضة الخدج توثق العلامات الحيوية والعلاجات وتُحظر عن طلب التحاليل
     */
    public function test_nicu_nurse_can_record_vitals_and_mar_but_cannot_order_lab(): void
    {
        $this->actingAs($this->nicuNurse);

        // 1. تسجيل العلامات الحيوية
        $vitalsRes = $this->post(route('nicu.vitals.store', $this->activeReservation), [
            'temperature' => 37.0,
            'heart_rate' => 135,
            'respiratory_rate' => 45,
            'spo2' => 97,
            'weight' => 1860,
        ]);
        $vitalsRes->assertSessionHas('success');
        $this->assertDatabaseHas('nicu_vitals', [
            'reservation_id' => $this->activeReservation->id,
            'heart_rate' => 135,
        ]);

        // 2. توثيق إعطاء دواء في سجل MAR
        $marRes = $this->post(route('nicu.medications-admin.store', $this->activeReservation), [
            'medication_name' => 'Ampicillin 100mg',
            'route' => 'IV',
            'dose' => '100mg',
        ]);
        $marRes->assertSessionHas('success');

        // 3. الممرضة محظورة عن إصدار طلبات التحاليل المخبرية (403)
        $labRes = $this->post(route('nicu.lab-orders.store', $this->activeReservation), [
            'tests' => ['CBC'],
            'priority' => 'stat',
        ]);
        $labRes->assertForbidden();
    }

    /**
     * إدارة أنواع حليب وتغذية الخدج: الإضافة، التعديل، التفعيل، والحذف
     */
    public function test_milk_types_crud_and_ajax_toggle(): void
    {
        $this->actingAs($this->admin);

        // 1. إنشاء نوع حليب جديد
        $storeRes = $this->post(route('nicu.milk-types.store'), [
            'name' => 'حليب سيميلاك جولد 1 (Similac Gold)',
            'category' => 'حليب أطفال اعتيادي',
            'sort_order' => 15,
            'is_active' => true,
            'notes' => 'للأطفال الخدج المكتملين بعد مرحلة التعافي',
        ]);
        $storeRes->assertRedirect();
        $this->assertDatabaseHas('nicu_milk_types', [
            'name' => 'حليب سيميلاك جولد 1 (Similac Gold)',
            'category' => 'حليب أطفال اعتيادي',
        ]);

        $milkType = NicuMilkType::where('name', 'حليب سيميلاك جولد 1 (Similac Gold)')->firstOrFail();

        // 2. تحديث نوع الحليب
        $updateRes = $this->put(route('nicu.milk-types.update', $milkType), [
            'name' => 'حليب سيميلاك جولد 1 المطور',
            'category' => 'حليب أطفال اعتيادي',
            'sort_order' => 20,
            'notes' => 'محدث مع إضافة البريبايوتك',
        ]);
        $updateRes->assertRedirect();
        $milkType->refresh();
        $this->assertEquals('حليب سيميلاك جولد 1 المطور', $milkType->name);

        // 3. التبديل اللحظي لحالة التفعيل عبر AJAX
        $toggleRes = $this->postJson(route('nicu.milk-types.toggle', $milkType));
        $toggleRes->assertOk()
            ->assertJson([
                'success' => true,
                'is_active' => false,
            ]);
        $milkType->refresh();
        $this->assertFalse((bool) $milkType->is_active);

        // 4. استدعاء API القائمة النشطة
        $activeListRes = $this->getJson(route('nicu.milk-types.active-list'));
        $activeListRes->assertOk()
            ->assertJson(['success' => true]);

        // 5. حذف نوع الحليب
        $destroyRes = $this->delete(route('nicu.milk-types.destroy', $milkType));
        $destroyRes->assertRedirect();
        $this->assertDatabaseMissing('nicu_milk_types', [
            'id' => $milkType->id,
        ]);
    }

    /**
     * الواجهات تستعرض تبويب أنواع الحليب وداتا ليست الرضاعة بالخيارات المسجلة
     */
    public function test_views_render_milk_types_tab_and_feeding_datalists(): void
    {
        $this->actingAs($this->admin);

        // شاشة تسعير الخدمات وباقات الخدج
        $servicesView = $this->get(route('nicu.services.index'));
        $servicesView->assertOk();
        $servicesView->assertSee('قائمة وتصنيفات حليب وتغذية الخدج');
        $servicesView->assertSee('حليب أم طبيعي (EBM)');
        $servicesView->assertSee('حليب خدج صناعي (Pre-term Formula)');

        // إضبارة الطفل السريرية
        $babyChartView = $this->get(route('nicu.show', $this->activeReservation));
        $babyChartView->assertOk();
        $babyChartView->assertSee('id="quickFeedingTypes"', false);
        $babyChartView->assertSee('حليب أم طبيعي (EBM)');
        $babyChartView->assertSee('حليب خدج صناعي (Pre-term Formula)');
        $babyChartView->assertSee('إدارة أنواع الحليب');
    }
}
