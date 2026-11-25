<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\Location;
use App\Models\Medicine;
use App\Models\NicuCareSchedule;
use App\Models\NicuCustomMedicine;
use App\Models\NicuMedicalOrder;
use App\Models\NicuService;
use App\Models\Patient;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\UserMedicineGroup;
use App\Services\NicuBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NicuEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $nicuDoctor;

    protected User $nicuNurse;

    protected User $residentDoctor;

    protected IncubatorReservation $reservation;

    protected Location $mainLocation;

    protected Location $nicuLocation;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = [
            'view incubator reservations',
            'manage nicu clinical',
            'manage nicu nursing',
            'manage nicu prescriptions',
            'record nicu vitals',
            'manage nicu care plans',
            'manage nicu doctor notes',
            'manage nicu store',
            'manage nicu admission history',
            'view medicine groups',
            'create medicine groups',
            'edit medicine groups',
            'delete medicine groups',
            'create stock transfer requests',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $docRole = Role::firstOrCreate(['name' => 'nicu_doctor', 'guard_name' => 'web']);
        $docRole->givePermissionTo($perms);

        $nurseRole = Role::firstOrCreate(['name' => 'nicu_nurse', 'guard_name' => 'web']);
        $nurseRole->givePermissionTo(['view incubator reservations', 'manage nicu nursing', 'record nicu vitals', 'manage nicu store', 'create stock transfer requests']);

        $residentRole = Role::firstOrCreate(['name' => 'resident', 'guard_name' => 'web']);
        $residentRole->givePermissionTo(['view incubator reservations', 'manage nicu prescriptions', 'view medicine groups', 'create medicine groups', 'edit medicine groups']);

        $this->nicuDoctor = User::factory()->create(['name' => 'د. أخصائي خدج', 'role' => 'nicu_doctor']);
        $this->nicuDoctor->assignRole('nicu_doctor');

        $this->nicuNurse = User::factory()->create(['name' => 'ممرضة الخدج', 'role' => 'nicu_nurse']);
        $this->nicuNurse->assignRole('nicu_nurse');

        $this->residentDoctor = User::factory()->create(['name' => 'د. مقيم الخدج', 'role' => 'resident']);
        $this->residentDoctor->assignRole('resident');

        $this->mainLocation = Location::create(['name' => 'المخزن الرئيسي', 'type' => 'main']);
        $this->nicuLocation = Location::create(['name' => 'الخدج', 'type' => 'sub']);

        $hospital = \App\Models\Hospital::create([
            'name' => 'مستشفى الاختبار',
            'owner_name' => 'د. المدير',
            'license_number' => 'LIC-NICU-01',
            'phone' => '07700000000',
            'address' => 'بغداد',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الخدج',
            'type' => 'pediatrics',
            'room_number' => '601',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
            'is_active' => true,
        ]);

        $docUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'أطفال وخدج',
            'qualification' => 'بورد أطفال',
            'license_number' => 'DOC-NICU-01',
            'consultation_fee' => 25000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'أم الطفل التجريبي', 'role' => 'patient']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'phone' => '07700000002',
            'gender' => 'female',
        ]);

        $service = NicuService::create([
            'name' => 'حاضنة عناية فائقة',
            'code' => 'NICU-TEST-1',
            'daily_fee' => 100000,
            'is_active' => true,
        ]);

        $incubator = Incubator::create([
            'incubator_number' => 'INC-01',
            'room_number' => '601',
            'incubator_type' => 'normal',
            'daily_fee' => 100000,
            'status' => 'available',
        ]);

        $this->reservation = IncubatorReservation::create([
            'patient_id' => $patient->id,
            'baby_name' => 'رضيع الاختبار',
            'incubator_id' => $incubator->id,
            'nicu_service_id' => $service->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'admission_date' => now()->toDateString(),
            'admission_time' => '09:00',
            'status' => IncubatorReservation::STATUS_ADMITTED,
        ]);
    }

    public function test_nicu_store_links_to_main_warehouse_and_dispenses_with_zero_financial_impact(): void
    {
        $product = Product::create([
            'name' => 'كانيولا أطفال مقاس 24',
            'code' => 'CAN-24',
            'unit' => 'قطعة',
            'alert_quantity' => 5,
        ]);

        $batch = StockBatch::create([
            'product_id' => $product->id,
            'location_id' => $this->nicuLocation->id,
            'internal_barcode' => 'NICU-BATCH-01',
            'initial_qty' => 20,
            'current_qty' => 20,
            'cost_price' => 1000,
            'received_at' => now(),
            'expiry_date' => now()->addYear()->toDateString(),
        ]);

        // 1. لوحة مخزن الخدج وتكليف أمين العهدة وربطها بالمخزن الرئيسي
        $this->actingAs($this->nicuNurse)
            ->get(route('nicu-store.index'))
            ->assertStatus(200)
            ->assertSee($this->mainLocation->name)
            ->assertSee('طلب تعزيز من المخزن الرئيسي');

        $this->actingAs($this->nicuNurse)
            ->post(route('nicu-store.assign-custodian', $this->nicuLocation), [
                'custodian_id' => $this->nicuNurse->id,
                'sync_user_location' => 1,
                'notes' => 'استلام عهدة الخدج',
            ])
            ->assertRedirect();

        $this->assertEquals($this->nicuNurse->id, $this->nicuLocation->fresh()->custodian_id);

        // 2. إمكانية طلب تعزيز من المخزن الرئيسي
        $this->actingAs($this->nicuNurse)
            ->post(route('stock-transfers.requests.store'), [
                'to_location_id' => $this->nicuLocation->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => 10],
                ],
            ])
            ->assertRedirect(route('stock-transfers.requests.index'));

        $this->assertDatabaseHas('stock_transfer_requests', [
            'from_location_id' => $this->mainLocation->id,
            'to_location_id' => $this->nicuLocation->id,
            'status' => 'pending',
        ]);

        // 3. صرف مستلزم للطفل وتأكد من خصم الرصيد وعدم وجود أي أثر مالي على الفاتورة
        $beforeStay = app(NicuBillingService::class)->calculateStayDetails($this->reservation);

        $this->actingAs($this->nicuNurse)
            ->post(route('nicu.store-dispense.store', $this->reservation), [
                'batch_id' => $batch->id,
                'quantity' => 3,
                'notes' => 'تبديل كانيولا باليد اليمنى',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(17, $batch->fresh()->current_qty);
        $this->assertDatabaseHas('stock_movements', [
            'batch_id' => $batch->id,
            'from_location_id' => $this->nicuLocation->id,
            'incubator_reservation_id' => $this->reservation->id,
            'quantity' => 3,
            'type' => 'issue_to_patient',
        ]);

        $afterStay = app(NicuBillingService::class)->calculateStayDetails($this->reservation->fresh());
        $this->assertEquals($beforeStay['grand_total'], $afterStay['grand_total']);
    }

    public function test_custom_nicu_medicine_auto_saves_reappears_and_can_be_managed(): void
    {
        // كتابة دواء غير موجود بجدول الصيدلية medicines
        $this->actingAs($this->nicuDoctor)
            ->post(route('nicu.prescriptions.store', $this->reservation), [
                'medications' => [
                    [
                        'name' => 'Caffeine Citrate NICU Mix',
                        'dose' => '10 mg',
                        'route' => 'وريدي IV',
                        'frequency' => 'مرة يومياً (OD)',
                        'duration_days' => 5,
                        'instructions' => 'ببطء خلال 30 دقيقة',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nicu_custom_medicines', [
            'name' => 'Caffeine Citrate NICU Mix',
            'default_dose' => '10 mg',
            'default_duration_days' => 5,
            'usage_count' => 1,
        ]);

        // لم يلوث جدول الصيدلية المركزي
        $this->assertDatabaseMissing('medicines', [
            'name' => 'Caffeine Citrate NICU Mix',
        ]);

        // إعادة وصف نفس الدواء تزيد usage_count ولا تكرره
        $this->actingAs($this->nicuDoctor)
            ->post(route('nicu.prescriptions.store', $this->reservation), [
                'medications' => [
                    ['name' => 'caffeine citrate nicu mix', 'dose' => '5 mg'],
                ],
            ])
            ->assertRedirect();

        $this->assertEquals(1, NicuCustomMedicine::count());
        $this->assertEquals(2, NicuCustomMedicine::first()->usage_count);

        // يظهر في شاشة الطفل وفي صفحة إدارة دليل أدوية الخدج
        $this->actingAs($this->nicuDoctor)
            ->get(route('nicu.show', $this->reservation))
            ->assertStatus(200)
            ->assertSee('Caffeine Citrate NICU Mix');

        $this->actingAs($this->nicuDoctor)
            ->get(route('nicu.custom-medicines.index'))
            ->assertStatus(200)
            ->assertSee('Caffeine Citrate NICU Mix');
    }

    public function test_medication_therapy_day_counter_and_stop_action(): void
    {
        $orderDay1Of3 = NicuMedicalOrder::create([
            'reservation_id' => $this->reservation->id,
            'order_type' => NicuMedicalOrder::TYPE_MEDICATION,
            'item_name' => 'Ampicillin',
            'duration_days' => 3,
            'ordered_at' => now(),
            'started_at' => now(),
            'status' => NicuMedicalOrder::STATUS_PENDING,
        ]);

        $this->assertEquals(1, $orderDay1Of3->current_therapy_day);
        $this->assertEquals('Day 1 of 3', $orderDay1Of3->therapy_day_label);
        $this->assertFalse($orderDay1Of3->is_therapy_overdue);

        $orderDay4Overdue = NicuMedicalOrder::create([
            'reservation_id' => $this->reservation->id,
            'order_type' => NicuMedicalOrder::TYPE_MEDICATION,
            'item_name' => 'Gentamicin',
            'duration_days' => 3,
            'ordered_at' => now()->subDays(3),
            'started_at' => now()->subDays(3),
            'status' => NicuMedicalOrder::STATUS_PENDING,
        ]);

        $this->assertEquals(4, $orderDay4Overdue->current_therapy_day);
        $this->assertEquals('Day 4 of 3', $orderDay4Overdue->therapy_day_label);
        $this->assertTrue($orderDay4Overdue->is_therapy_overdue);

        $openEndedSchedule = NicuCareSchedule::create([
            'reservation_id' => $this->reservation->id,
            'type' => 'medication',
            'title' => 'Vitamin K',
            'interval_hours' => 24,
            'start_time' => now()->subDays(2),
            'next_due_at' => now(),
            'is_active' => true,
        ]);

        $this->assertEquals('Day 3', $openEndedSchedule->therapy_day_label);

        // إيقاف العلاج يجمّد العداد
        $this->actingAs($this->nicuDoctor)
            ->post(route('nicu.orders.stop', $orderDay1Of3))
            ->assertRedirect();

        $this->assertNotNull($orderDay1Of3->fresh()->stopped_at);
        $this->assertEquals('bg-secondary text-white', $orderDay1Of3->fresh()->therapy_day_badge_class);
    }

    public function test_nicu_medicine_packs_support_public_and_private_and_arbitrary_counts_isolated_from_outpatient(): void
    {
        $m1 = Medicine::create(['name' => 'Ampicillin 500mg', 'dosage_form' => 'injection', 'is_active' => true]);
        $m2 = Medicine::create(['name' => 'Gentamicin 80mg', 'dosage_form' => 'injection', 'is_active' => true]);

        // 1. طبيب الخدج يحفظ باقة عامة للخدج فيها مزيج من أدوية الصيدلية + أدوية الخدج المخصصة (مثلاً 4 أدوية)
        $resPublic = $this->actingAs($this->nicuDoctor)
            ->postJson(route('medicine-groups.save-from-visit'), [
                'department' => 'nicu',
                'name' => 'بروتوكول التهاب دم الخديج',
                'is_public' => true,
                'medications' => [
                    ['medicine_id' => $m1->id, 'name' => $m1->name, 'dosage' => '50 mg/kg', 'route' => 'وريدي IV', 'duration_days' => 5],
                    ['medicine_id' => $m2->id, 'name' => $m2->name, 'dosage' => '4 mg/kg', 'route' => 'وريدي IV', 'duration_days' => 5],
                    ['name' => 'Aminophylline NICU Loading', 'dosage' => '5 mg/kg', 'route' => 'وريدي IV', 'duration_days' => 3],
                    ['name' => 'Calcium Gluconate 10% NICU', 'dosage' => '2 ml', 'route' => 'وريدي IV'],
                ],
            ]);

        $resPublic->assertStatus(200)->assertJson(['success' => true]);

        // 2. الطبيب المقيم بالخدج يرى الباقة العامة ويستطيع إنشاء باقة خاصة به بعدد أدوية مرن (مثلاً دوائين)
        $resPrivate = $this->actingAs($this->residentDoctor)
            ->postJson(route('medicine-groups.save-from-visit'), [
                'department' => 'nicu',
                'name' => 'باقة المقيم السريعة',
                'is_public' => false,
                'medications' => [
                    ['medicine_id' => $m1->id, 'name' => $m1->name, 'dosage' => '25 mg'],
                    ['name' => 'Dextrose 10% Maintenance', 'dosage' => '60 ml/kg/day'],
                ],
            ]);

        $resPrivate->assertStatus(200)->assertJson(['success' => true]);

        // باقة العيادة الاستشارية العامة لا تظهر في الخدج
        UserMedicineGroup::create([
            'user_id' => $this->nicuDoctor->id,
            'department' => 'general',
            'name' => 'باقة العيادة الاستشارية',
            'is_public' => true,
        ]);

        $nicuShowResponse = $this->actingAs($this->residentDoctor)->get(route('nicu.show', $this->reservation));
        $nicuShowResponse->assertStatus(200);
        $nicuShowResponse->assertSee('بروتوكول التهاب دم الخديج');
        $nicuShowResponse->assertSee('باقة المقيم السريعة');
        $nicuShowResponse->assertDontSee('باقة العيادة الاستشارية');

        // التأكد أن شاشة إدارة الباقات بالخدج معزولة
        $this->actingAs($this->residentDoctor)
            ->get(route('medicine-groups.index', ['department' => 'nicu']))
            ->assertStatus(200)
            ->assertSee('بروتوكول التهاب دم الخديج')
            ->assertSee('باقة المقيم السريعة')
            ->assertDontSee('باقة العيادة الاستشارية');
    }

    public function test_doctor_can_save_large_clinical_note_only_and_pin_in_vitals_section(): void
    {
        $longNote = trim(str_repeat('ملاحظة سريرية مفصلة لمتابعة حالة الخديج واستجابة التنفس. ', 15));

        $this->actingAs($this->nicuDoctor)
            ->post(route('nicu.vitals.store', $this->reservation), [
                'save_mode' => 'note_only',
                'clinical_notes' => $longNote,
                'pin_as_doctor_note' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('nicu_vitals', [
            'reservation_id' => $this->reservation->id,
            'nurse_id' => $this->nicuDoctor->id,
            'clinical_notes' => $longNote,
        ]);

        $this->assertDatabaseHas('nicu_doctor_notes', [
            'incubator_reservation_id' => $this->reservation->id,
            'doctor_id' => $this->nicuDoctor->id,
            'is_pinned' => 1,
            'content' => $longNote,
        ]);

        $this->actingAs($this->nicuDoctor)
            ->get(route('nicu.show', $this->reservation))
            ->assertStatus(200)
            ->assertSee('ملاحظة سريرية مفصلة لمتابعة حالة الخديج');
    }

    public function test_admission_history_tab_permissions_doctor_nurse_and_discharged_lock(): void
    {
        // 1. التبويب الأول يحمل الاسم الجديد ويعرض بطاقة معلومات الدخول والتاريخ المرضي
        $this->actingAs($this->nicuDoctor)
            ->get(route('nicu.show', $this->reservation))
            ->assertStatus(200)
            ->assertSee('باقة الخدمة والتبديل ومعلومات الدخول')
            ->assertSee('معلومات الدخول والتاريخ المرضي');

        // 2. الطبيب يحفظ سبب الدخول + التاريخ المرضي للطفل + التاريخ المرضي للأم
        $this->actingAs($this->nicuDoctor)
            ->post(route('nicu.admission-history.update', $this->reservation), [
                'admission_reason' => 'خداجة مبكرة مع ضائقة تنفسية خفيفة',
                'medical_history' => 'تم الإنعاش الأولي في صالة الولادة ووضع على CPAP',
                'maternal_history' => 'ولادة قيصرية في الأسبوع 32، سكري حمل مسيطر عليه',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('incubator_reservations', [
            'id' => $this->reservation->id,
            'admission_reason' => 'خداجة مبكرة مع ضائقة تنفسية خفيفة',
            'medical_history' => 'تم الإنعاش الأولي في صالة الولادة ووضع على CPAP',
            'maternal_history' => 'ولادة قيصرية في الأسبوع 32، سكري حمل مسيطر عليه',
            'admission_info_updated_by' => $this->nicuDoctor->id,
        ]);

        // 3. التمريض بدون صلاحية manage nicu admission history يرى البيانات للقراءة فقط ويُمنع من التعديل (403)
        $this->actingAs($this->nicuNurse)
            ->get(route('nicu.show', $this->reservation))
            ->assertStatus(200)
            ->assertSee('عرض فقط (صلاحية الطبيب)')
            ->assertSee('خداجة مبكرة مع ضائقة تنفسية خفيفة');

        $this->actingAs($this->nicuNurse)
            ->post(route('nicu.admission-history.update', $this->reservation), [
                'admission_reason' => 'محاولة تعديل بدون صلاحية',
            ])
            ->assertStatus(403);

        // 4. عند منح التمريض صلاحية manage nicu admission history من الأدوار يتمكن من الحفظ بنجاح
        $this->nicuNurse->givePermissionTo('manage nicu admission history');

        $this->actingAs($this->nicuNurse)
            ->post(route('nicu.admission-history.update', $this->reservation), [
                'admission_reason' => 'خداجة مبكرة مع ضائقة تنفسية خفيفة - محدث',
                'medical_history' => 'تم الإنعاش الأولي في صالة الولادة ووضع على CPAP',
                'maternal_history' => 'ولادة قيصرية في الأسبوع 32، سكري حمل مسيطر عليه',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('incubator_reservations', [
            'id' => $this->reservation->id,
            'admission_reason' => 'خداجة مبكرة مع ضائقة تنفسية خفيفة - محدث',
            'admission_info_updated_by' => $this->nicuNurse->id,
        ]);

        // 5. عند خروج الطفل وأرشفة الإضبارة يُقفل التعديل
        $this->reservation->update(['status' => IncubatorReservation::STATUS_DISCHARGED]);

        $this->actingAs($this->nicuDoctor)
            ->post(route('nicu.admission-history.update', $this->reservation), [
                'admission_reason' => 'محاولة بعد الخروج',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('incubator_reservations', [
            'id' => $this->reservation->id,
            'admission_reason' => 'خداجة مبكرة مع ضائقة تنفسية خفيفة - محدث',
        ]);
    }
}
