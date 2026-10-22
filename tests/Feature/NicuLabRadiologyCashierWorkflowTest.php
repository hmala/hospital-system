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
use App\Models\NicuMedicalOrder;
use App\Models\LabTest;
use App\Models\RadiologyType;
use App\Models\Payment;
use App\Models\NicuPayment;
use Database\Seeders\NicuServicesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

class NicuLabRadiologyCashierWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $labStaff;
    protected User $radStaff;
    protected User $cashier;
    protected IncubatorReservation $reservation;
    protected LabTest $labTest;
    protected RadiologyType $radType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(NicuServicesSeeder::class);

        $this->admin = User::factory()->create(['name' => 'مدير النظام']);
        $this->admin->assignRole('admin');

        $this->labStaff = User::factory()->create(['name' => 'فني مختبر الخدج']);
        $this->labStaff->assignRole('lab_staff');

        $this->radStaff = User::factory()->create(['name' => 'أخصائي أشعة الخدج']);
        $this->radStaff->assignRole('radiology_staff');

        $this->cashier = User::factory()->create(['name' => 'كاشير الصندوق الرئيسي']);
        $this->cashier->assignRole('cashier');

        $doctorUser = User::factory()->create(['name' => 'د. حيدر الخفاجي']);
        $doctorUser->assignRole('doctor');

        $hospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'مستشفى الشفاء المركزي',
            'owner_name' => 'د. حيدر',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-NICU-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $departmentId = DB::table('departments')->insertGetId([
            'hospital_id' => $hospitalId,
            'name' => 'قسم الأطفال والخدج',
            'type' => 'pediatrics',
            'room_number' => 'NICU-1',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $departmentId,
            'specialization' => 'أخصائي طب الأطفال وحديثي الولادة',
            'qualification' => 'بورد عربي في طب الأطفال وحديثي الولادة',
            'license_number' => 'DOC-NICU-' . uniqid(),
            'consultation_fee' => 25000,
        ]);

        $room = Room::create([
            'room_number' => 'NICU-1',
            'room_type' => 'incubator',
            'status' => 'available',
        ]);

        $incubator = Incubator::create([
            'incubator_number' => 'INC-01',
            'incubator_type' => 'normal',
            'status' => 'available',
            'room_id' => $room->id,
            'daily_fee' => 200000,
            'is_active' => true,
        ]);

        $babyUser = User::factory()->create(['name' => 'أم الطفل الخديج']);
        $patient = Patient::create([
            'user_id' => $babyUser->id,
            'gender' => 'female',
            'age' => 28,
            'phone' => '07712345678',
            'medical_number' => 'MED-NICU-001',
        ]);

        $nicuService = NicuService::first();

        $this->reservation = IncubatorReservation::create([
            'incubator_id' => $incubator->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'nicu_service_id' => $nicuService->id,
            'baby_name' => 'طفل زينب علي',
            'admission_date' => now()->subDays(2),
            'admission_time' => '10:00',
            'status' => 'admitted',
            'insurance_type' => 'none',
        ]);

        $this->labTest = LabTest::create([
            'name' => 'Serum Bilirubin (Total & Direct)',
            'code' => 'BIL-TD',
            'price' => 15000,
            'unit' => 'mg/dL',
            'is_active' => true,
        ]);

        $this->radType = RadiologyType::create([
            'name' => 'Portable Chest X-Ray (Incubator)',
            'code' => 'RAD-CHEST',
            'base_price' => 35000,
            'is_active' => true,
        ]);
    }

    /**
     * اختبار الدورة الكاملة لطلب وفحص واعتماد وطباعة تحاليل الخدج
     */
    public function test_nicu_lab_order_workflow_from_doctor_to_lab_station_to_print(): void
    {
        // 1. طلب التحليل من محطة طبيب الخدج
        $response = $this->actingAs($this->admin)->post(route('nicu.lab-orders.store', $this->reservation), [
            'tests' => [$this->labTest->name],
            'priority' => 'urgent',
            'instructions' => 'فحص نسبة الصفار بشكل عاجل داخل الحاضنة',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('nicu_medical_orders', [
            'reservation_id' => $this->reservation->id,
            'order_type' => 'lab',
            'item_name' => $this->labTest->name,
            'priority' => 'urgent',
            'status' => 'pending',
            'lab_test_id' => $this->labTest->id,
            'price' => 15000,
        ]);

        $order = NicuMedicalOrder::where('item_name', $this->labTest->name)->first();
        $this->assertNotNull($order->order_group_id);

        // 2. ظهور الطلب في واجهة المختبر المركزي
        $labIndexRes = $this->actingAs($this->labStaff)->get(route('lab.index', ['source' => 'nicu']));
        $labIndexRes->assertOk();
        $labIndexRes->assertSee($this->labTest->name);
        $labIndexRes->assertSee('طفل زينب علي');
        $labIndexRes->assertSee('#INC-01');

        // 3. فني المختبر يبدأ الفحص
        $startRes = $this->actingAs($this->labStaff)->post(route('lab.nicu-orders.start', $order));
        $startRes->assertSessionHas('success');
        $order->refresh();
        $this->assertEquals('in_progress', $order->status);
        $this->assertEquals($this->labStaff->id, $order->processed_by);

        // 4. فني المختبر يوثق النتيجة ويعتمدها
        $completeRes = $this->actingAs($this->labStaff)->post(route('lab.nicu-orders.complete', $order), [
            'result' => '14.8',
            'unit' => 'mg/dL',
            'reference_range' => '0.2 - 1.2',
            'result_status' => 'high',
            'technician_notes' => 'الصفار مرتفع ويحتاج إلى علاج ضوئي مكثف',
        ]);

        $completeRes->assertSessionHas('success');
        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('14.8', $order->result);
        $this->assertEquals('mg/dL', $order->unit);
        $this->assertEquals('high', $order->result_status);
        $this->assertNotNull($order->completed_at);

        // 5. التحقق من صفحة الطباعة الرسمية للتحليل
        $printRes = $this->actingAs($this->labStaff)->get(route('lab.nicu-orders.print', $order));
        $printRes->assertOk();
        $printRes->assertSee('تقرير فحص مخبري');
        $printRes->assertSee('14.8');
        $printRes->assertSee('mg/dL');
        $printRes->assertSee('مرتفع');
        $printRes->assertSee('طفل زينب علي');
    }

    /**
     * اختبار الدورة الكاملة لطلب وتصوير واعتماد وطباعة تقرير الأشعة والسونار للخدج
     */
    public function test_nicu_radiology_order_workflow_from_doctor_to_radiology_station_to_print(): void
    {
        // 1. طلب فحص الأشعة من محطة طبيب الخدج
        $response = $this->actingAs($this->admin)->post(route('nicu.radiology-orders.store', $this->reservation), [
            'radiology_types' => [$this->radType->name],
            'priority' => 'stat',
            'instructions' => 'صورة صدر نقالة بالحاضنة لاستبعاد متلازمة الضائقة التنفسية RDS',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('nicu_medical_orders', [
            'reservation_id' => $this->reservation->id,
            'order_type' => 'radiology',
            'item_name' => $this->radType->name,
            'priority' => 'stat',
            'status' => 'pending',
            'radiology_type_id' => $this->radType->id,
            'price' => 35000,
        ]);

        $order = NicuMedicalOrder::where('item_name', $this->radType->name)->first();

        // 2. ظهور الطلب في واجهة قسم الأشعة المركزي
        $radIndexRes = $this->actingAs($this->radStaff)->get(route('radiology-staff.index'));
        $radIndexRes->assertOk();
        $radIndexRes->assertSee('الخدج والحاضنات');
        $radIndexRes->assertSee($this->radType->name);
        $radIndexRes->assertSee('طفل زينب علي');

        // 3. اختصاصي الأشعة يبدأ الفحص والتصوير
        $startRes = $this->actingAs($this->radStaff)->post(route('radiology-staff.nicu-orders.start', $order));
        $startRes->assertSessionHas('success');
        $order->refresh();
        $this->assertEquals('in_progress', $order->status);

        // 4. اختصاصي الأشعة يكتب التقرير ويعتمده
        $completeRes = $this->actingAs($this->radStaff)->post(route('radiology-staff.nicu-orders.complete', $order), [
            'result' => 'Lungs show bilateral fine reticular granular opacities (Grade II RDS). Heart size is normal. No pneumothorax.',
            'technician_notes' => 'يوصى بالمتابعة وإعادة التصوير بعد إعطاء مادة السيرفاكتنت',
        ]);

        $completeRes->assertSessionHas('success');
        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertStringContainsString('Grade II RDS', $order->result);
        $this->assertNotNull($order->completed_at);

        // 5. التحقق من طباعة تقرير الأشعة الرسمي
        $printRes = $this->actingAs($this->radStaff)->get(route('radiology-staff.nicu-orders.print', $order));
        $printRes->assertOk();
        $printRes->assertSee('قسم الأشعة والتصوير الطبي');
        $printRes->assertSee('Grade II RDS');
        $printRes->assertSee('طفل زينب علي');
    }

    /**
     * اختبار القيد المالي الموحد للكاشير واستلام دفعات الأمانات والسلف وإصدار الإيصالات
     */
    public function test_cashier_consolidated_dynamic_folio_and_deposit_payment(): void
    {
        // إضافة فحص مختبر وفحص أشعة للحاضنة لتراكم الفاتورة
        NicuMedicalOrder::create([
            'reservation_id' => $this->reservation->id,
            'order_type' => 'lab',
            'item_name' => $this->labTest->name,
            'lab_test_id' => $this->labTest->id,
            'price' => 15000,
            'status' => 'completed',
            'result' => '12.5',
            'ordered_at' => now(),
        ]);

        NicuMedicalOrder::create([
            'reservation_id' => $this->reservation->id,
            'order_type' => 'radiology',
            'item_name' => $this->radType->name,
            'radiology_type_id' => $this->radType->id,
            'price' => 35000,
            'status' => 'completed',
            'result' => 'Normal chest',
            'ordered_at' => now(),
        ]);

        // 1. فحص ظهور القيد الديناميكي الموحد للحاضنة في لوحة الكاشير
        $cashierRes = $this->actingAs($this->cashier)->get(route('cashier.index'));
        $cashierRes->assertOk();
        $cashierRes->assertSee('الحسابات المالية الموحدة للحاضنات والخدج');
        $cashierRes->assertSee('طفل زينب علي');
        $cashierRes->assertSee('قبض دفعة');

        // 2. الكاشير يقبض سلفة تحت الحساب (أمانة) بمبلغ 100,000 د.ع في جدول nicu_payments المستقل
        $depositRes = $this->actingAs($this->cashier)->post(route('cashier.nicu.payment', $this->reservation), [
            'amount' => 100000,
            'payment_method' => 'cash',
            'payment_type' => 'deposit',
            'notes' => 'سلفة مسلمة من والد الطفل عند الدخول',
        ]);

        // التحقق من الحفظ في جدول nicu_payments المستقل تماماً
        $this->assertDatabaseHas('nicu_payments', [
            'incubator_reservation_id' => $this->reservation->id,
            'amount' => 100000,
            'payment_type' => 'deposit',
            'payment_method' => 'cash',
            'financial_status' => 'paid',
            'cashier_id' => $this->cashier->id,
        ]);

        // التأكيد الحاسم على أن جدول payments العام لم يتم تلويثه أو التداخل معه
        $this->assertEquals(0, Payment::count(), 'يجب ألا يتم تسجيل أي دفعة حاضنة في جدول payments العام؛ العزل في قاعدة البيانات 100%');

        $payment = NicuPayment::where('incubator_reservation_id', $this->reservation->id)->first();
        $this->assertNotNull($payment->receipt_number);
        $depositRes->assertRedirect(route('cashier.nicu.receipt', $payment->id));

        // 3. التحقق من شاشة عرض سند القبض
        $receiptViewRes = $this->actingAs($this->cashier)->get(route('cashier.nicu.receipt', $payment->id));
        $receiptViewRes->assertOk();
        $receiptViewRes->assertSee($payment->receipt_number);
        $receiptViewRes->assertSee('طفل زينب علي');
        $receiptViewRes->assertSee('100,000');

        // 4. التحقق من كشف الحساب التراكمي للحاضنة بعد استلام السلفة
        $statementRes = $this->actingAs($this->admin)->get(route('nicu.statement', $this->reservation));
        $statementRes->assertOk();
        $statementRes->assertSee('سجل الأمانات والمدفوعات المسددة في الصندوق');
        $statementRes->assertSee($payment->receipt_number);
        $statementRes->assertSee('100,000');

        // 5. التحقق من طباعة الوصل الحراري للكاشير (78mm)
        $receiptPrintRes = $this->actingAs($this->cashier)->get(route('cashier.nicu.receipt.print', $payment->id));
        $receiptPrintRes->assertOk();
        $receiptPrintRes->assertSee($payment->receipt_number);
        $receiptPrintRes->assertSee('طفل زينب علي');
        $receiptPrintRes->assertSee('INC-01');

        // 6. التحقق من طباعة الكشف التفصيلي الحراري بالتحاليل والأشعة
        $itemizedPrintRes = $this->actingAs($this->cashier)->get(route('cashier.nicu.itemized-receipt.print', $this->reservation->id));
        $itemizedPrintRes->assertOk();
        $itemizedPrintRes->assertSee('كشف حساب وخدمات تفصيلي');
        $itemizedPrintRes->assertSee($this->labTest->name);
        $itemizedPrintRes->assertSee($this->radType->name);
        $itemizedPrintRes->assertSee('100,000');
    }

    /**
     * اختبار استرجاع وصرف فائض الأمانات للخدج في الكاشير وسند الاسترجاع الرسمي
     */
    public function test_cashier_can_refund_excess_deposits_for_nicu_reservation(): void
    {
        // 1. دفع أمانات كبيرة (مثلاً 500,000 د.ع)
        $depositRes = $this->actingAs($this->cashier)->post(route('cashier.nicu.payment', $this->reservation), [
            'amount' => 500000,
            'payment_method' => 'cash',
            'payment_type' => 'deposit',
            'notes' => 'أمانات وسلفة دخول كبيرة',
        ]);
        $depositRes->assertSessionHasNoErrors();

        $billingService = app(\App\Services\NicuBillingService::class);
        $stayDetails = $billingService->calculateStayDetails($this->reservation);
        $this->assertGreaterThan(0, $stayDetails['excess_deposit']);
        $excessAmount = $stayDetails['excess_deposit'];

        // 2. التحقق من ظهور حالة فائض الأمانات وزر إرجاع الأمانات في لوحة الكاشير
        $cashierRes = $this->actingAs($this->cashier)->get(route('cashier.index'));
        $cashierRes->assertOk();
        $cashierRes->assertSee('إرجاع الأمانات');
        $cashierRes->assertSee('فائض: +' . number_format($excessAmount, 0));

        // 3. محاولة صرف استرجاع يتجاوز الفائض المتوفر (يجب أن يتم رفضه)
        $invalidRefundRes = $this->actingAs($this->cashier)->post(route('cashier.nicu.payment', $this->reservation), [
            'amount' => $excessAmount + 50000,
            'payment_method' => 'cash',
            'payment_type' => 'refund',
            'notes' => 'محاولة استرجاع مبلغ زائد',
        ]);
        $invalidRefundRes->assertSessionHas('error');

        // 4. صرف كامل فائض الأمانات بشكل صحيح
        $validRefundRes = $this->actingAs($this->cashier)->post(route('cashier.nicu.payment', $this->reservation), [
            'amount' => $excessAmount,
            'payment_method' => 'cash',
            'payment_type' => 'refund',
            'notes' => 'صرف واسترجاع كامل فائض الأمانات لوالد الطفل الخديج',
        ]);
        $validRefundRes->assertSessionHasNoErrors();
        $validRefundRes->assertSessionHas('success');

        // التحقق من إنشاء قيد استرجاع بنوع refund
        $this->assertDatabaseHas('nicu_payments', [
            'incubator_reservation_id' => $this->reservation->id,
            'amount' => $excessAmount,
            'payment_type' => 'refund',
            'cashier_id' => $this->cashier->id,
        ]);

        $refundPayment = NicuPayment::where('incubator_reservation_id', $this->reservation->id)
            ->where('payment_type', 'refund')
            ->first();
        $this->assertNotNull($refundPayment);

        // 5. التحقق من أن فائض الأمانات أصبح صفراً بعد الصرف التام
        $freshStay = $billingService->calculateStayDetails($this->reservation->fresh());
        $this->assertEquals(0, $freshStay['excess_deposit']);
        $this->assertEquals(0, $freshStay['net_balance']);

        // 6. التحقق من سند الاسترجاع الرسمي وصفحة الطباعة
        $receiptRes = $this->actingAs($this->cashier)->get(route('cashier.nicu.receipt', $refundPayment->id));
        $receiptRes->assertOk();
        $receiptRes->assertSee('سند استرجاع فائض أمانات');
        $receiptRes->assertSee(number_format($excessAmount, 0));

        $printRes = $this->actingAs($this->cashier)->get(route('cashier.nicu.receipt.print', $refundPayment->id));
        $printRes->assertOk();
        $printRes->assertSee('سند صرف واسترجاع أمانات');
        $printRes->assertSee(number_format($excessAmount, 0));
    }
}
