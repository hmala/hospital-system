<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorCommissionSetting;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\MedicalDevice;
use App\Models\OperationTheater;
use App\Models\Patient;
use App\Models\RadiologyType;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgicalOperation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryCashierDevicesAndRoomWaitingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $cashierUser;

    private User $accountantUser;

    private User $nurseUser;

    private User $residentUser;

    private User $receptionUser;

    private User $labStaff;

    private User $radiologyStaff;

    private Department $dept;

    private Doctor $surgeon;

    private Doctor $consultant;

    private Patient $patient;

    private Room $regularRoom;

    private Room $vipRoom;

    private OperationTheater $theater;

    private SurgicalOperation $operation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'د. خالد',
            'phone' => '07701234567',
            'address' => 'بغداد',
            'license_number' => 'LIC-TEST-99',
        ]);

        $this->dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '101',
            'consultation_fee' => 50000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 25,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create(['name' => 'مدير النظام']);
        $this->admin->assignRole('admin');

        $this->cashierUser = User::factory()->create(['name' => 'أمين الصندوق']);
        $this->cashierUser->assignRole('cashier');
        $this->cashierUser->givePermissionTo(['view doctor profits']);

        $this->accountantUser = User::factory()->create(['name' => 'محاسب العمليات']);
        $this->accountantUser->assignRole('accountant');

        $this->nurseUser = User::factory()->create(['name' => 'ممرضة الردهة']);
        $this->nurseUser->assignRole('nurse');

        $this->residentUser = User::factory()->create(['name' => 'الطبيب المقيم']);
        $this->residentUser->assignRole('doctor');
        $this->residentUser->givePermissionTo([
            'view resident station',
            'view surgeries',
        ]);

        $this->receptionUser = User::factory()->create(['name' => 'موظف الاستعلامات']);
        $this->receptionUser->assignRole('receptionist');
        $this->receptionUser->givePermissionTo([
            'view surgeries',
            'create surgeries',
            'edit surgeries',
            'assign surgery room',
            'view occupancy',
        ]);

        $this->labStaff = User::factory()->create(['name' => 'فني المختبر']);
        $this->labStaff->assignRole('lab_staff');

        $this->radiologyStaff = User::factory()->create(['name' => 'فني الأشعة']);
        $this->radiologyStaff->assignRole('radiology_staff');

        $surgeonUser = User::factory()->create(['name' => 'د. سيف الجراح']);
        $surgeonUser->assignRole('doctor');
        $this->surgeon = Doctor::create([
            'user_id' => $surgeonUser->id,
            'department_id' => $this->dept->id,
            'specialization' => 'جراحة عامة',
            'phone' => '07711111111',
            'qualification' => 'Board',
            'license_number' => 'LIC-SURG-101',
            'type' => 'surgeon',
            'is_active' => true,
        ]);

        DoctorCommissionSetting::create([
            'doctor_id' => $this->surgeon->id,
            'surgery_fee_percentage' => 50,
            'additional_operation_percentage' => 50,
            'medical_device_percentage' => 0,
            'consultation_fee_percentage' => 50,
            'lab_fee_percentage' => 0,
            'radiology_fee_percentage' => 0,
            'is_active' => true,
        ]);

        $consultantUser = User::factory()->create(['name' => 'د. نبيل الاستشاري']);
        $consultantUser->assignRole('doctor');
        $this->consultant = Doctor::create([
            'user_id' => $consultantUser->id,
            'department_id' => $this->dept->id,
            'specialization' => 'باطنية',
            'phone' => '07722222222',
            'qualification' => 'MD',
            'license_number' => 'LIC-CONS-202',
            'type' => 'consultant',
            'consultation_fee' => 40000,
            'is_available_today' => true,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'علي حسن المريض', 'phone' => '07733333333']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'patient_id' => 'PAT-301-01',
            'name' => 'علي حسن المريض',
            'gender' => 'male',
            'age' => 38,
            'phone' => '07733333333',
            'file_number' => 'FILE-301-01',
        ]);

        $this->regularRoom = Room::create([
            'room_number' => '301',
            'room_type' => 'regular',
            'room_purpose' => 'beds',
            'floor' => 'الطابق الثالث',
            'daily_fee' => 0,
            'status' => 'available',
            'beds_count' => 1,
            'is_active' => true,
        ]);

        $this->vipRoom = Room::create([
            'room_number' => '401',
            'room_type' => 'vip',
            'room_purpose' => 'beds',
            'floor' => 'الطابق الرابع',
            'daily_fee' => 100000,
            'status' => 'available',
            'beds_count' => 1,
            'is_active' => true,
        ]);

        $this->theater = OperationTheater::create([
            'name' => 'صالة 1',
            'code' => 'OR-1',
            'is_active' => true,
        ]);

        $this->operation = SurgicalOperation::create([
            'name' => 'استئصال مرارة بالمنظار',
            'code' => 'LAP-CHOLE-01',
            'category' => 'major',
            'department_id' => $this->dept->id,
            'fee' => 3000000,
            'is_active' => true,
        ]);
    }

    public function test_medical_device_decoupled_from_patient_cashier_and_settled_with_surgeon_only(): void
    {
        $device = MedicalDevice::create([
            'name' => 'جهاز منظار ليزري',
            'type' => 'laparoscopy',
            'price' => 5000000,
            'usage_price' => 200000,
            'status' => 'active',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->surgeon->id,
            'department_id' => $this->dept->id,
            'room_id' => $this->regularRoom->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'surgery_category' => 'major',
            'surgery_fee' => 3000000,
            'surgery_fee_paid' => false,
            'surgery_fee_paid_amount' => 0,
            'payment_status' => 'pending',
            'billing_status' => 'ready',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'in_progress',
        ]);

        // إرفاق جهاز طبي داخل العملية
        $addResponse = $this->actingAs($this->admin)->post(route('surgeries.addDevice', $surgery), [
            'device_ids' => [$device->id],
        ]);
        $addResponse->assertRedirect();
        $addResponse->assertSessionHas('success');

        $surgery->refresh();
        // لا يتعطل كاشير المريض أبداً بسبب الجهاز الطبي
        $this->assertEquals('ready', $surgery->billing_status);
        $this->assertEquals('pending_review', $surgery->devices_billing_status);
        $this->assertEquals(200000.0, (float) $surgery->getDevicesTotalFee());
        $this->assertEquals(3000000.0, (float) $surgery->getTotalSurgeryPatientFee());

        // يظهر للمريض في كاشير العمليات بالمبلغ الفعلي (3,000,000) دون سعر الجهاز
        $cashierIndex = $this->actingAs($this->cashierUser)->get(route('cashier.surgeries.index'));
        $cashierIndex->assertStatus(200);
        $cashierIndex->assertSee('علي حسن المريض');

        $paymentForm = $this->actingAs($this->cashierUser)->get(route('cashier.surgeries.payment.form', $surgery));
        $paymentForm->assertStatus(200);
        $paymentForm->assertSee('3,000,000');

        // يظهر للمحاسب في قائمة مراجعة وتسوية الأجهزة الطبية
        $reviewIndex = $this->actingAs($this->accountantUser)->get(route('accountant.surgeries.index'));
        $reviewIndex->assertStatus(200);
        $reviewIndex->assertSee('علي حسن المريض');

        // المحاسب يعتمد تسوية الجهاز بـ 250,000 د.ع على حساب الجراح فقط
        $confirmResponse = $this->actingAs($this->accountantUser)->post(route('accountant.surgeries.confirm', $surgery), [
            'device_prices' => [
                $device->id => 250000,
            ],
        ]);
        $confirmResponse->assertRedirect(route('accountant.surgeries.index'));

        $surgery->refresh();
        $this->assertEquals('reviewed', $surgery->devices_billing_status);
        $this->assertEquals(3000000.0, (float) $surgery->surgery_fee);

        // خصم مبلغ الجهاز الطبي من مستحق الجراح في المحاسبة: 3,000,000 ناقص 250,000 = 2,750,000
        $surgeonFinancials = $surgery->calculateSurgeonFinancials();
        $this->assertEquals(250000.0, (float) $surgeonFinancials['devices_deductions']);
        $this->assertEquals(2750000.0, (float) $surgeonFinancials['net_earned']);

        // عند تسديد رسم العملية بالكاشير يُرحّل القيد مع خصم الجهاز من حساب الجراح فقط
        $this->actingAs($this->cashierUser)->post(route('cashier.surgeries.payment.process', $surgery), [
            'payment_method' => 'cash',
            'amount' => 3000000,
            'pay_surgery' => '1',
            'package_included_submitted' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        // يظهر تفاصيل الخصم في كشف حساب الجراح بكاشير العمليات
        $doctorAccountView = $this->actingAs($this->cashierUser)->get(route('cashier.surgeries.doctor-account', $this->surgeon));
        $doctorAccountView->assertStatus(200);
        $doctorAccountView->assertSee('250,000');
    }

    public function test_inpatient_post_op_requests_pass_through_sync_cashier_and_enforce_discharge_gate(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->surgeon->id,
            'department_id' => $this->dept->id,
            'room_id' => $this->regularRoom->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'surgery_category' => 'major',
            'surgery_fee' => 3000000,
            'surgery_fee_paid' => true,
            'surgery_fee_paid_amount' => 3000000,
            'room_fee' => 0,
            'room_fee_paid_amount' => 0,
            'payment_status' => 'paid',
            'billing_status' => 'ready',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'room_checkin_at' => now()->subHours(2),
            'room_checkin_by' => $this->nurseUser->id,
            'status' => 'completed',
            'completed_at' => now()->subHour(),
        ]);
        $this->regularRoom->update(['status' => 'occupied']);

        $labTest = LabTest::create([
            'name' => 'تحليل وظائف كبد',
            'code' => 'LFT-01',
            'price' => 25000,
            'is_active' => true,
        ]);

        $radType = RadiologyType::create([
            'name' => 'أشعة صدر رقمية',
            'code' => 'CXR-01',
            'base_price' => 45000,
            'is_active' => true,
        ]);

        // المقيم يطلب تحليل + أشعة + استشارة بعد العملية لمريض مسدد للعملية بالكامل مسبقاً
        $this->actingAs($this->residentUser)->post(route('clinical-station.lab-tests.store', $surgery), [
            'lab_test_ids' => [$labTest->id],
        ])->assertRedirect();

        $this->actingAs($this->residentUser)->post(route('clinical-station.radiology-tests.store', $surgery), [
            'radiology_type_id' => $radType->id,
        ])->assertRedirect();

        $this->actingAs($this->residentUser)->post(route('clinical-station.consultations.store', $surgery), [
            'doctor_id' => $this->consultant->id,
            'specialty' => 'باطنية',
            'reason' => 'تقييم حمى بعد العملية',
            'priority' => 'urgent',
            'fee' => 40000,
        ])->assertRedirect();

        // بمجرد إضافة الطلبات تتحول حالة دفع العملية من paid إلى partial وتظهر في الكاشير فوراً
        $surgery->refresh();
        $this->assertEquals('partial', $surgery->payment_status);

        $generalCashier = $this->actingAs($this->cashierUser)->get(route('cashier.index'));
        $generalCashier->assertStatus(200);
        $generalCashier->assertSee('كاشير العمليات والرقود');

        $surgeryCashier = $this->actingAs($this->cashierUser)->get(route('cashier.surgeries.index'));
        $surgeryCashier->assertStatus(200);
        $surgeryCashier->assertSee('علي حسن المريض');

        // التحاليل الجديدة في استمارة الدفع تكون منفصلة افتراضياً (غير مشمولة بالباقة بـ 0 د.ع)
        $paymentForm = $this->actingAs($this->cashierUser)->get(route('cashier.surgeries.payment.form', $surgery));
        $paymentForm->assertStatus(200);
        $paymentForm->assertSee('تحليل وظائف كبد');
        $paymentForm->assertSee('25,000');
        $paymentForm->assertSee('45,000');
        $paymentForm->assertSee('40,000');
        $breakdown = $surgery->calculatePendingItemsBreakdown();
        $this->assertTrue($breakdown['has_unpaid_items']);
        $this->assertEquals(1, $breakdown['pending_lab_count']);
        $this->assertEquals(1, $breakdown['pending_radiology_count']);
        $this->assertEquals(1, $breakdown['pending_consultations_count']);

        // سياسة المرور المباشر: المختبر والأشعة ينفذان الطلبات مباشرة قبل الدفع مع شارة يُحاسب قبل التخريج
        $labIndex = $this->actingAs($this->labStaff)->get(route('staff.surgery-lab-tests.index'));
        $labIndex->assertStatus(200);
        $labIndex->assertSee('بانتظار الكاشير');
        $labIndex->assertSee('يمر للفحص ويُحاسب في الكاشير قبل التخريج');

        $radIndex = $this->actingAs($this->radiologyStaff)->get(route('staff.surgery-radiology-tests.index'));
        $radIndex->assertStatus(200);
        $radIndex->assertSee('يُحاسب قبل التخريج');

        $surgeryLab = $surgery->labTests()->first();
        $this->actingAs($this->labStaff)->put(route('staff.surgery-lab-tests.update-all', $surgery), [
            'action' => 'complete',
            'test_results' => [
                $surgeryLab->id => [
                    'test_id' => $surgeryLab->id,
                    'value' => 'Normal',
                    'unit' => 'U/L',
                ],
            ],
        ])->assertRedirect();
        $this->assertEquals('completed', $surgeryLab->fresh()->status);

        // بوابة التخريج: منع التمريض والاستقبال من تخريج المريض قبل تسديد الـ 110,000 د.ع بالكاشير
        $blockedCheckout = $this->actingAs($this->nurseUser)->post(route('nursing-station.room-checkout', $surgery));
        $blockedCheckout->assertRedirect();
        $blockedCheckout->assertSessionHas('error');
        $this->assertNull($surgery->fresh()->discharged_at);
        $this->assertEquals('occupied', $this->regularRoom->fresh()->status);

        $blockedDischarge = $this->actingAs($this->receptionUser)->post(route('surgeries.discharge', $surgery));
        $blockedDischarge->assertRedirect();
        $blockedDischarge->assertSessionHas('error');
        $this->assertNull($surgery->fresh()->discharged_at);

        // تسديد المتبقي في الكاشير (الفحوصات + الاستشارة + الخدمات الإضافية الافتراضية الملحقة)
        $surgeryRad = $surgery->radiologyTests()->first();
        $surgeryCons = $surgery->consultations()->first();
        $extraIds = $surgery->extraServices()->where('payment_status', '!=', 'paid')->pluck('id')->all();

        $payResponse = $this->actingAs($this->cashierUser)->post(route('cashier.surgeries.payment.process', $surgery), [
            'payment_method' => 'cash',
            'amount' => 165000,
            'package_included_submitted' => '1',
            'pay_lab_tests' => [$surgeryLab->id],
            'pay_radiology_tests' => [$surgeryRad->id],
            'pay_consultations' => [$surgeryCons->id],
            'pay_extra_services' => $extraIds,
        ]);
        $payResponse->assertRedirect();
        $payResponse->assertSessionHas('success');
        $this->assertSame([], $surgery->fresh()->getFinancialClearanceSummary()['reasons']);
        $this->assertEquals('paid', $surgery->fresh()->payment_status);

        // بعد السداد الكامل ينجح التخريج وتتحرر الغرفة
        $okCheckout = $this->actingAs($this->nurseUser)->post(route('nursing-station.room-checkout', $surgery));
        $okCheckout->assertRedirect();
        $okCheckout->assertSessionHas('success');

        $surgery->refresh();
        $this->assertNotNull($surgery->discharged_at);
        $this->assertEquals('available', $this->regularRoom->fresh()->status);

        // أرشيف محطة المقيم يعرض الحالات المكتملة/المخرجة حتى لو لم يضغط إكمال pre_op يدوياً
        $residentIndex = $this->actingAs($this->residentUser)->get(route('resident-station.index'));
        $residentIndex->assertStatus(200);
        $residentIndex->assertSee('علي حسن المريض');
    }

    public function test_or_completion_locks_restart_and_creates_post_op_station(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->surgeon->id,
            'department_id' => $this->dept->id,
            'room_id' => $this->regularRoom->id,
            'operation_theater_id' => $this->theater->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'surgery_category' => 'major',
            'surgery_fee' => 3000000,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'in_progress',
        ]);

        // بدء وإنهاء صالة العمليات
        $this->actingAs($this->admin)->post(route('surgeries.complete', $surgery))->assertRedirect();

        $surgery->refresh();
        $this->assertEquals('completed', $surgery->status);
        $this->assertDatabaseHas('resident_stations', [
            'surgery_id' => $surgery->id,
            'phase' => 'pre_op',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('resident_stations', [
            'surgery_id' => $surgery->id,
            'phase' => 'post_op',
        ]);

        // منع إعادة بدء العملية أو إعادتها للانتظار أو إلغائها أو حذفها بعد إكمالها
        $this->actingAs($this->admin)->post(route('surgeries.start', $surgery))->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('surgeries.return-to-waiting', $surgery))->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('surgeries.cancel', $surgery))->assertSessionHas('error');
        $this->actingAs($this->admin)->delete(route('surgeries.destroy', $surgery))->assertSessionHas('error');

        $this->assertEquals('completed', $surgery->fresh()->status);
    }

    public function test_room_waiting_mode_allows_booking_without_room_and_permission_gated_assignment(): void
    {
        // حجز عملية بوضع انتظار الغرفة (بدون room_id)
        $storeResponse = $this->actingAs($this->receptionUser)->post(route('surgeries.store'), [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->surgeon->id,
            'room_waiting_mode' => '1',
            'room_id' => '',
            'expected_stay_days' => 1,
            'surgery_category' => 'major',
            'surgical_operation_id' => $this->operation->id,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '11:00',
            'referring_doctor_name' => 'د. عيادة خارجية',
            'custom_surgery_fee' => '3000000',
        ]);
        $storeResponse->assertRedirect();
        $storeResponse->assertSessionHasNoErrors();

        $surgery = Surgery::latest('id')->first();
        $this->assertNotNull($surgery);
        $this->assertNull($surgery->room_id);
        $this->assertEquals(0.0, (float) $surgery->room_fee);

        // المريض يبقى ظاهراً في شاشات التمريض والمقيم والاستعلامات رغم اختيار فلتر طابق محدد
        $nursingView = $this->actingAs($this->nurseUser)->get(route('nursing-station.index', ['floor' => 'الطابق الثالث']));
        $nursingView->assertStatus(200);
        $nursingView->assertSee('علي حسن المريض');
        $nursingView->assertSee('وضع انتظار الغرفة');

        $residentView = $this->actingAs($this->residentUser)->get(route('resident-station.index', ['floor' => 'الطابق الثالث']));
        $residentView->assertStatus(200);
        $residentView->assertSee('علي حسن المريض');

        $occupancyView = $this->actingAs($this->receptionUser)->get(route('inquiry.occupancy'));
        $occupancyView->assertStatus(200);
        $occupancyView->assertSee('علي حسن المريض');

        // مستخدم لا يملك صلاحية assign surgery room يُرفض بـ 403
        $unauthorizedUser = User::factory()->create(['name' => 'موظف بدون صلاحية غرفة']);
        $unauthorizedUser->assignRole('cashier');
        $this->actingAs($unauthorizedUser)->post(route('surgeries.assign-room', $surgery), [
            'room_id' => $this->vipRoom->id,
        ])->assertStatus(403);

        // الموظف المخوّل يخصص غرفة VIP مع دخول فوري فيتحدث السعر وحالة الكاشير
        $assignResponse = $this->actingAs($this->receptionUser)->post(route('surgeries.assign-room', $surgery), [
            'room_id' => $this->vipRoom->id,
            'checkin_immediately' => '1',
        ]);
        $assignResponse->assertRedirect();
        $assignResponse->assertSessionHas('success');

        $surgery->refresh();
        $this->assertEquals($this->vipRoom->id, $surgery->room_id);
        $this->assertNotNull($surgery->room_checkin_at);
        $this->assertEquals(100000.0, (float) $surgery->room_fee);
        $this->assertEquals('occupied', $this->vipRoom->fresh()->status);
    }
}
