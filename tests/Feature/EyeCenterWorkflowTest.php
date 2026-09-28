<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Eye\EyeAppointment;
use App\Models\Eye\EyeExamination;
use App\Models\Eye\EyeInvestigation;
use App\Models\Eye\EyeInvoice;
use App\Models\Eye\EyeStoreItem;
use App\Models\Eye\EyeStoreMovement;
use App\Models\Eye\EyeSurgery;
use App\Models\Location;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EyeCenterWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $doctorUser;
    protected $doctor;
    protected $patient;
    protected $hospitalId;
    protected $departmentId;
    protected $eyeLocation;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->doctorUser = User::factory()->create(['name' => 'د. فراس استشاري عيون']);
        $this->doctorUser->assignRole('doctor');

        $this->hospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'مستشفى الشفاء التخصصي',
            'owner_name' => 'د. حسام',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-LIC-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->departmentId = DB::table('departments')->insertGetId([
            'hospital_id' => $this->hospitalId,
            'name' => 'مركز وجراحة العيون',
            'type' => 'surgery',
            'room_number' => 'EYE-1',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $this->departmentId,
            'specialization' => 'جراحة العيون والشبكية',
            'qualification' => 'استشاري طب وجراحة العيون (FRCS)',
            'license_number' => 'DOC-EYE-101',
            'consultation_fee' => 25000,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create([
            'name' => 'أحمد سمير علي',
            'phone' => '07712345678',
        ]);

        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '199012345678',
            'gender' => 'male',
            'birth_date' => '1985-05-15',
            'address' => 'بغداد - الكرخ',
            'medical_number' => 'MRN-EYE-' . rand(1000, 9999),
        ]);

        Location::firstOrCreate(
            ['name' => 'المخزن الرئيسي'],
            ['type' => 'main']
        );

        $this->eyeLocation = Location::firstOrCreate(
            ['name' => 'مخزن مركز العيون'],
            ['type' => 'sub']
        );
    }

    /**
     * اختبار مسار الاستعلامات وحجز موعد العيون وتوليد الفاتورة
     */
    public function test_reception_creates_eye_appointment_and_invoice()
    {
        $response = $this->actingAs($this->admin)->post(route('eye.reception.store'), [
            'patient_id'       => $this->patient->id,
            'doctor_id'        => $this->doctor->id,
            'visit_type'       => 'consultation',
            'insurance_type'   => 'cash',
            'chief_complaint'  => 'ضعف مفاجئ في الرؤية بالعين اليمنى',
            'fee_amount'       => 15000,
        ]);

        $response->assertRedirect(route('eye.reception.index'));

        $this->assertDatabaseHas('eye_appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id'  => $this->doctor->id,
            'visit_type' => 'consultation',
            'status'     => 'waiting',
        ]);

        $this->assertDatabaseHas('eye_invoices', [
            'patient_id' => $this->patient->id,
            'status'     => 'pending',
        ]);
    }

    /**
     * اختبار تحديث حالة موعد المريض في طابور العيون (توسيع حدقة)
     */
    public function test_reception_updates_queue_status_to_dilated()
    {
        $appointment = EyeAppointment::create([
            'patient_id'       => $this->patient->id,
            'doctor_id'        => $this->doctor->id,
            'queue_number'     => 1,
            'visit_type'       => 'consultation',
            'status'           => 'waiting',
            'insurance_type'   => 'cash',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('eye.reception.updateStatus', $appointment), [
            'status' => 'dilated',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('eye_appointments', [
            'id'     => $appointment->id,
            'status' => 'dilated',
        ]);
    }

    /**
     * اختبار تسديد فاتورة الكاشير وإغلاق الحساب اليومي
     */
    public function test_cashier_payment_and_daily_reconciliation()
    {
        $invoice = EyeInvoice::create([
            'patient_id'      => $this->patient->id,
            'total_amount'    => 30000,
            'patient_share'   => 5000,
            'insurance_share' => 25000,
            'net_amount'      => 5000,
            'insurance_type'  => 'cash',
            'status'          => 'pending',
        ]);

        // تسديد الفاتورة نقداً
        $payResponse = $this->actingAs($this->admin)->post(route('eye.cashier.pay', $invoice), [
            'payment_method' => 'cash',
            'paid_amount'    => 5000,
        ]);

        $payResponse->assertRedirect(route('eye.cashier.printReceipt', $invoice));

        $this->assertDatabaseHas('eye_invoices', [
            'id'             => $invoice->id,
            'status'         => 'paid',
            'payment_method' => 'cash',
        ]);

        // ترحيل الإيرادات اليومية لخزينة المستشفى المركزية
        $reconResponse = $this->actingAs($this->admin)->post(route('eye.cashier.dailyReconciliation'));
        $reconResponse->assertRedirect();

        $this->assertDatabaseHas('eye_invoices', [
            'id'                       => $invoice->id,
            'reconciled_with_hospital' => 1,
        ]);
    }

    /**
     * اختبار مخزن العيون: التوريد المباشر وطلب النقل من المخزن الرئيسي
     */
    public function test_eye_store_direct_purchase_and_requisition()
    {
        $item = EyeStoreItem::create([
            'item_code'       => 'IOL-TEST-001',
            'name'            => 'عدسة مطوية Alcon AcrySof IQ',
            'category'        => 'iol_lens',
            'diopter'         => 21.5,
            'current_stock'   => 10,
            'min_stock_alert' => 2,
            'unit'            => 'piece',
            'cost_price'      => 45000,
            'selling_price'   => 75000,
        ]);

        // توريد مباشر لمخزن العيون
        $purchaseResponse = $this->actingAs($this->admin)->post(route('eye.store.directPurchase'), [
            'eye_store_item_id' => $item->id,
            'quantity'          => 5,
            'cost_price'        => 45000,
            'notes'             => 'توريد وجبة إضافية',
        ]);

        $purchaseResponse->assertSessionHas('success');

        $this->assertDatabaseHas('eye_store_items', [
            'id'            => $item->id,
            'current_stock' => 15,
        ]);

        $this->assertDatabaseHas('eye_store_movements', [
            'eye_store_item_id' => $item->id,
            'movement_type'     => 'direct_purchase',
            'quantity'          => 5,
            'balance_after'     => 15,
        ]);

        // طلب نقل مستلزمات من المخزن الرئيسي
        $reqResponse = $this->actingAs($this->admin)->post(route('eye.store.transferRequisition'), [
            'items_data' => json_encode([
                ['item_name' => 'شاش معقم', 'quantity' => 10, 'unit' => 'علبة']
            ]),
            'notes' => 'طلب نقل شاش ومحلول ملحي معقم',
        ]);

        $reqResponse->assertSessionHas('success');
        $this->assertDatabaseHas('stock_transfer_requests', [
            'to_location_id' => $this->eyeLocation->id,
        ]);
    }

    /**
     * اختبار تسجيل الفحص السريري الثنائي ومخطط النظارة (OD / OS)
     */
    public function test_clinical_examination_and_glasses_prescription()
    {
        $response = $this->actingAs($this->admin)->post(route('eye.examinations.store'), [
            'patient_id'                => $this->patient->id,
            'doctor_id'                 => $this->doctor->id,
            'chief_complaint'           => 'صعوبة بالقراءة وتشوش رؤية',
            'va_od_unaided'             => '6/18',
            'va_od_corrected'           => '6/6',
            'va_os_unaided'             => '6/12',
            'va_os_corrected'           => '6/6',
            'ref_od_sphere'             => '+1.50',
            'ref_od_cylinder'           => '-0.75',
            'ref_od_axis'               => 90,
            'ref_os_sphere'             => '+1.75',
            'ref_os_cylinder'           => '-0.50',
            'ref_os_axis'               => 85,
            'ref_add'                   => '+2.00',
            'iop_od'                    => 15.5,
            'iop_os'                    => 16.0,
            'iop_method'                => 'Goldmann',
            'slit_lamp_od'              => 'Normal anterior segment',
            'slit_lamp_os'              => 'Normal anterior segment',
            'diagnosis'                 => 'Simple Hyperopic Astigmatism with Presbyopia',
            'has_glasses_prescription' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('eye_examinations', [
            'patient_id'                => $this->patient->id,
            'va_od_corrected'           => '6/6',
            'ref_od_sphere'             => '+1.50',
            'iop_od'                    => 15.5,
            'has_glasses_prescription' => 1,
        ]);

        $exam = EyeExamination::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($exam);

        // فحص راشيتة النظارة
        $printResponse = $this->actingAs($this->admin)->get(route('eye.examinations.printGlasses', $exam));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('+1.50');
        $printResponse->assertSee('Sph');
    }

    /**
     * اختبار طلب فحص بجهاز تشخيصي (OCT) وتحديث النتائج
     */
    public function test_eye_investigation_workflow()
    {
        $createResponse = $this->actingAs($this->admin)->post(route('eye.investigations.store'), [
            'patient_id'         => $this->patient->id,
            'investigation_type' => 'OCT_Macula',
            'eye_target'         => 'OD',
            'findings'           => 'Initial scan findings',
        ]);

        $investigation = EyeInvestigation::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($investigation);
        $createResponse->assertRedirect(route('eye.investigations.show', $investigation));

        $this->assertDatabaseHas('eye_investigations', [
            'patient_id'         => $this->patient->id,
            'investigation_type' => 'OCT_Macula',
            'eye_target'         => 'OD',
            'status'             => 'completed',
        ]);

        // تحديث النتيجة والتشخيص
        $updateResponse = $this->actingAs($this->admin)->post(route('eye.investigations.updateResults', $investigation), [
            'findings'   => 'Cystoid macular edema with subretinal fluid',
            'conclusion' => 'High quality scan achieved',
        ]);

        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('eye_investigations', [
            'id'       => $investigation->id,
            'status'   => 'completed',
            'findings' => 'Cystoid macular edema with subretinal fluid',
        ]);
    }

    /**
     * اختبار حجز عملية ماء أبيض وخصم العدسة تلقائياً من مخزن العيون
     */
    public function test_eye_surgery_booking_auto_dispenses_iol_lens_from_store()
    {
        $iol = EyeStoreItem::create([
            'item_code'       => 'IOL-ZEISS-22',
            'name'            => 'عدسة Zeiss CT ASPHINA',
            'category'        => 'iol_lens',
            'diopter'         => 22.0,
            'current_stock'   => 5,
            'min_stock_alert' => 1,
            'unit'            => 'piece',
            'cost_price'      => 50000,
            'selling_price'   => 85000,
        ]);

        $surgeryResponse = $this->actingAs($this->admin)->post(route('eye.surgeries.store'), [
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'procedure_name'    => 'سحب ماء أبيض مع زراعة عدسة مطوية (Phaco + IOL)',
            'target_eye'        => 'OD',
            'surgery_date'      => Carbon::tomorrow()->format('Y-m-d H:i:s'),
            'anesthesia_type'   => 'تخدير موضعي قطرة (Topical)',
            'iol_item_id'       => $iol->id,
            'iol_power'         => 22.0,
            'iol_serial_number' => 'SN-ZS-8899',
            'operative_notes'   => 'Clear corneal incision 2.2mm, continuous curvilinear capsulorhexis',
        ]);

        $surgery = EyeSurgery::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($surgery);
        $surgeryResponse->assertRedirect(route('eye.surgeries.show', $surgery));

        // التأكد من خصم قطعة واحدة من رصيد مخزن العيون
        $iol->refresh();
        $this->assertEquals(4, $iol->current_stock);

        // التأكد من تسجيل حركة الصرف
        $this->assertDatabaseHas('eye_store_movements', [
            'eye_store_item_id' => $iol->id,
            'movement_type'     => 'surgery_dispense',
            'quantity'          => -1,
            'balance_after'     => 4,
            'surgery_id'        => $surgery->id,
        ]);

        // تحديث حالة العملية إلى مكتملة وتدوين تقرير الجراحة
        $statusResponse = $this->actingAs($this->admin)->patch(route('eye.surgeries.updateStatus', $surgery), [
            'status'          => 'completed',
            'operative_notes' => 'Successful uneventful Phaco with IOL in-the-bag',
            'complications'   => null,
            'postop_plan'     => 'Gutt. Vigamox + Pred Forte every 2 hours',
        ]);

        $statusResponse->assertSessionHas('success');
        $surgery->refresh();
        $this->assertEquals('completed', $surgery->status);
        $this->assertStringContainsString('uneventful', $surgery->operative_notes);
    }

    public function test_eye_doctor_availability_index_and_stats()
    {
        $response = $this->actingAs($this->admin)->get(route('eye.availability.index'));
        $response->assertStatus(200);
        $response->assertSee('توفر أطباء واستشاريي العيون');
        $response->assertSee('د. فراس استشاري عيون');
        $response->assertSee('السبت');
        $response->assertSee('الأحد');
    }

    public function test_eye_doctor_single_and_bulk_availability_toggle()
    {
        // 1. Single toggle
        $updateResponse = $this->actingAs($this->admin)->post(route('eye.availability.update', $this->doctor), [
            'is_available_today' => 1,
        ]);
        $updateResponse->assertSessionHas('success');
        $this->doctor->refresh();
        $this->assertTrue((bool)$this->doctor->is_available_today);

        // 2. Bulk toggle to unavailable
        $bulkResponse = $this->actingAs($this->admin)->post(route('eye.availability.bulkUpdate'), [
            'is_available_today' => 0,
        ]);
        $bulkResponse->assertSessionHas('success');
        $this->doctor->refresh();
        $this->assertFalse((bool)$this->doctor->is_available_today);
    }

    public function test_eye_patient_call_admit_and_dilate_actions()
    {
        $appointment = EyeAppointment::create([
            'appointment_number' => 'EYE-20260928-999',
            'patient_id'         => $this->patient->id,
            'doctor_id'          => $this->doctor->id,
            'visit_type'         => 'consultation',
            'queue_number'       => 15,
            'status'             => 'waiting',
            'insurance_type'     => 'cash',
            'created_by'         => $this->admin->id,
        ]);

        // 1. Call patient
        $callResponse = $this->actingAs($this->admin)->post(route('eye.availability.callPatient', $appointment));
        $callResponse->assertJson([
            'success' => true,
            'queue_number' => 15,
        ]);

        // 2. Dilate pupil
        $dilateResponse = $this->actingAs($this->admin)->post(route('eye.availability.dilatePatient', $appointment));
        $dilateResponse->assertSessionHas('success');
        $appointment->refresh();
        $this->assertEquals('dilated', $appointment->status);

        // 3. Admit patient
        $admitResponse = $this->actingAs($this->admin)->post(route('eye.availability.admitPatient', $appointment));
        $admitResponse->assertSessionHas('success');
        $appointment->refresh();
        $this->assertEquals('in_clinic', $appointment->status);
    }

    public function test_eye_reception_print_thermal_queue_ticket()
    {
        $appointment = EyeAppointment::create([
            'appointment_number' => 'EYE-20260928-888',
            'patient_id'         => $this->patient->id,
            'doctor_id'          => $this->doctor->id,
            'visit_type'         => 'consultation',
            'queue_number'       => 7,
            'status'             => 'waiting',
            'insurance_type'     => 'health_insurance',
            'created_by'         => $this->admin->id,
        ]);

        $ticketResponse = $this->actingAs($this->admin)->get(route('eye.reception.printTicket', $appointment));
        $ticketResponse->assertStatus(200);
        $ticketResponse->assertSee('تذكرة طابور مراجع العيون');
        $ticketResponse->assertSee('#7');
        $ticketResponse->assertSee('EYE-20260928-888');
        $ticketResponse->assertSee('أحمد سمير علي');
        $ticketResponse->assertSee('د. فراس استشاري عيون');
        $ticketResponse->assertSee('الضمان الصحي');
    }

    public function test_create_and_store_eye_specialized_doctor()
    {
        // 1. Check create view
        $createResponse = $this->actingAs($this->admin)->get(route('eye.availability.doctors.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('إضافة طبيب واستشاري عيون جديد');
        $createResponse->assertSee('مركز وجراحة العيون التخصصي');
        $createResponse->assertSee('جراحة الشبكية والجسم الزجاجي');

        // 2. Post new eye doctor
        $storeResponse = $this->actingAs($this->admin)->post(route('eye.availability.doctors.store'), [
            'name'               => 'د. حيدر جاسم القرني',
            'email'              => 'haider.eye@hospital.iq',
            'phone'              => '07709876543',
            'password'           => 'secret123',
            'type'               => 'consultant',
            'specialization'     => 'أمراض وجراحة القرنية والليزك وتصحيح البصر (Cornea & Refractive)',
            'consultation_fee'   => 30000,
            'is_hi_active'       => 1,
            'hi_price'           => 27000,
            'is_moi_active'      => 1,
            'moi_price'          => 24000,
            'start_time'         => '09:00',
            'end_time'           => '15:00',
            'working_days'       => ['الأحد', 'الثلاثاء', 'الخميس'],
            'is_available_today' => 1,
        ]);

        $storeResponse->assertRedirect(route('eye.availability.index'));
        $storeResponse->assertSessionHas('success');

        // Check user created
        $user = User::where('email', 'haider.eye@hospital.iq')->first();
        $this->assertNotNull($user);
        $this->assertEquals('د. حيدر جاسم القرني', $user->name);

        // Check doctor created
        $newDoc = Doctor::where('user_id', $user->id)->first();
        $this->assertNotNull($newDoc);
        $this->assertEquals(30000, $newDoc->consultation_fee);
        $this->assertTrue((bool)$newDoc->is_available_today);
        $this->assertContains('الأحد', $newDoc->working_days);
    }

    public function test_edit_and_update_eye_doctor_settings()
    {
        // 1. Edit view
        $editResponse = $this->actingAs($this->admin)->get(route('eye.availability.doctors.edit', $this->doctor));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('تعديل بيانات وجدول دوام');

        // 2. Update settings
        $updateResponse = $this->actingAs($this->admin)->put(route('eye.availability.doctors.updateSettings', $this->doctor), [
            'name'             => 'د. فراس حميد المعدل',
            'phone'            => '07701112233',
            'type'             => 'surgeon',
            'specialization'   => 'جراحة الساد والفاكو وزراعة العدسات',
            'consultation_fee' => 35000,
            'start_time'       => '08:00',
            'end_time'         => '16:00',
            'working_days'     => ['السبت', 'الإثنين', 'الأربعاء'],
            'is_active'        => 1,
        ]);

        $updateResponse->assertRedirect(route('eye.availability.index'));
        $updateResponse->assertSessionHas('success');

        $this->doctor->refresh();
        $this->assertEquals('د. فراس حميد المعدل', $this->doctor->user->name);
        $this->assertEquals(35000, $this->doctor->consultation_fee);
        $this->assertEquals('جراحة الساد والفاكو وزراعة العدسات', $this->doctor->specialization);
    }
    public function test_eye_queue_waiting_hall_screens()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('eye.queue.all-clinics.display'));
        $response->assertStatus(200);
        $response->assertViewIs('eye.queue.all-clinics-display');

        $responseJson = $this->getJson(route('eye.queue.all-clinics.data'));
        $responseJson->assertStatus(200)
                     ->assertJsonStructure(['success', 'clinics']);
    }

}