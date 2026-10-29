<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Emergency;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentVoidAndReversalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'void payment receipt', 'guard_name' => 'web']);
    }

    public function test_user_without_permission_cannot_void_payment()
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $user->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'amount' => 50000,
            'receipt_number' => 'REC-20261027-0001',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'appointment',
        ]);

        $response = $this->actingAs($user)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'rebill',
            'void_reason' => 'محاولة إلغاء بدون صلاحية',
        ]);

        $response->assertStatus(403);
        $this->assertNull($payment->fresh()->voided_at);
        $this->assertDatabaseMissing('payment_void_logs', ['payment_id' => $payment->id]);
    }

    public function test_authorized_user_can_void_payment_and_reopen_for_rebill()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('void payment receipt');

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'female',
            'date_of_birth' => '1995-05-15',
        ]);

        $emergency = Emergency::create([
            'patient_id' => $patient->id,
            'emergency_type' => 'medical',
            'symptoms' => 'حالة طوارئ تجريبية',
            'admission_time' => now(),
            'arrival_time' => now(),
            'payment_status' => 'paid',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'emergency_id' => $emergency->id,
            'amount' => 31000,
            'receipt_number' => 'REC-20261027-0099',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
            'insurance_type' => 'hi',
            'copay_percentage' => 15.0,
            'patient_share' => 4650,
            'insurance_share' => 26350,
        ]);

        $response = $this->actingAs($admin)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'rebill',
            'void_reason' => 'خطأ في فئة الضمان ونسبة التحمل ويجب إعادة التسديد بالنسبة الصحيحة',
        ]);

        $response->assertRedirect(route('cashier.index'));
        $response->assertSessionHas('success');

        $freshPayment = $payment->fresh();
        $this->assertNull($freshPayment->paid_at);
        $this->assertNull($freshPayment->receipt_number);
        $this->assertEquals('pending', $freshPayment->payment_method);
        $this->assertEquals('rebill', $freshPayment->void_type);
        $this->assertEquals('REC-20261027-0099', $freshPayment->original_receipt_number);
        $this->assertEquals($admin->id, $freshPayment->voided_by);
        $this->assertNotNull($freshPayment->voided_at);

        // التأكد من عودة حالة الطوارئ كغير مسددة
        $this->assertEquals('pending', $emergency->fresh()->payment_status);

        // التأكد من توثيق سجل التدقيق في payment_void_logs
        $this->assertDatabaseHas('payment_void_logs', [
            'payment_id' => $payment->id,
            'receipt_number' => 'REC-20261027-0099',
            'voided_by' => $admin->id,
            'void_type' => 'rebill',
        ]);
    }

    public function test_void_requires_mandatory_reason_min_5_chars()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('void payment receipt');

        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'female',
            'date_of_birth' => '1992-03-03',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'amount' => 10000,
            'receipt_number' => 'REC-20261027-0002',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'appointment',
        ]);

        $response = $this->actingAs($admin)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'rebill',
            'void_reason' => 'خطأ', // أقل من 5 أحرف
        ]);

        $response->assertSessionHasErrors('void_reason');
        $this->assertNull($payment->fresh()->voided_at);
    }

    public function test_authorized_user_can_void_payment_with_refund()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('void payment receipt');

        $doctor = User::factory()->create();

        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1985-02-02',
        ]);

        $hospital = \App\Models\Hospital::firstOrCreate(['name' => 'مستشفى الكفاءات'], [
            'owner_name' => 'د. صاحب المستشفى',
            'phone' => '07700000000',
            'email' => 'info@hospital.com',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $dept = \App\Models\Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة باطنية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $doctorRecord = \App\Models\Doctor::create([
            'user_id' => $doctor->id,
            'department_id' => $dept->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-'.uniqid(),
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorRecord->id,
            'department_id' => $dept->id,
            'consultation_fee' => 15000,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'amount' => 15000,
            'receipt_number' => 'REC-20261027-0003',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'appointment',
        ]);

        $response = $this->actingAs($admin)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'refund',
            'void_reason' => 'رغبة المريض في إلغاء الموعد واسترداد المبلغ كاملاً',
        ]);

        $response->assertRedirect(route('cashier.index'));

        $freshPayment = $payment->fresh();
        $this->assertEquals('refund', $freshPayment->void_type);
        $this->assertEquals('cancelled', $freshPayment->claim_status);
        $this->assertNotNull($freshPayment->voided_at);

        // إلغاء الموعد المرتبط
        $this->assertEquals('cancelled', $appointment->fresh()->status);
        $this->assertEquals('cancelled', $appointment->fresh()->payment_status);

        $this->assertDatabaseHas('payment_void_logs', [
            'payment_id' => $payment->id,
            'void_type' => 'refund',
            'amount' => 15000,
        ]);
    }

    public function test_authorized_user_can_view_void_form()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('void payment receipt');

        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'amount' => 25000,
            'receipt_number' => 'REC-20261027-0777',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'emergency',
        ]);

        $response = $this->actingAs($admin)->get(route('cashier.payments.void.form', $payment->id));
        $response->assertStatus(200);
        $response->assertSee('إبطال الوصل المالي وإعادة فتحه للتصحيح');
        $response->assertSee('REC-20261027-0777');
    }

    public function test_void_medical_request_payment_resets_payment_status_and_reappears_in_cashier_queue()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['void payment receipt', 'view cashier', 'process medical requests payments']);

        $hospital = \App\Models\Hospital::firstOrCreate(['name' => 'مستشفى الشفاء'], [
            'owner_name' => 'د. صاحب المستشفى',
            'phone' => '07700000000',
            'email' => 'info@hospital.com',
            'address' => 'بغداد',
            'license_number' => 'LIC-002',
        ]);

        $dept = \App\Models\Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة باطنية 2',
            'type' => 'internal',
            'room_number' => '102',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $doctorUser = User::factory()->create();
        $doctor = \App\Models\Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'باطنية',
            'qualification' => 'FICMS',
            'license_number' => 'DOC-'.uniqid(),
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'female',
            'date_of_birth' => '1995-05-05',
        ]);

        $visit = \App\Models\Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص عام واستشارة',
            'status' => 'completed',
        ]);

        $medicalRequest = \App\Models\Request::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'status' => 'completed',
            'payment_status' => 'paid',
            'description' => 'تحليل دم كامل CBC',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'request_id' => $medicalRequest->id,
            'amount' => 15000,
            'receipt_number' => 'REC-20261027-8888',
            'paid_at' => now()->subDay(),
            'payment_method' => 'cash',
            'payment_type' => 'lab',
        ]);

        $medicalRequest->update(['payment_id' => $payment->id]);

        $response = $this->actingAs($admin)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'rebill',
            'void_reason' => 'تصحيح إداري لإعادة الفتح واحتساب نسبة الضمان الصحي',
        ]);

        $response->assertRedirect(route('cashier.index'));

        // التأكد من تصفير وإعادة قيد الطلب الطبي
        $freshReq = $medicalRequest->fresh();
        $this->assertEquals('pending', $freshReq->payment_status);
        $this->assertEquals('pending', $freshReq->status);
        $this->assertNull($freshReq->payment_id);

        // التأكد من ظهور الطلب في قائمة الانتظار الحالية بالكاشير
        $cashierResponse = $this->actingAs($admin)->get(route('cashier.index'));
        $cashierResponse->assertStatus(200);
        $cashierResponse->assertSee((string) $freshReq->id);
    }

    public function test_authorized_user_can_view_void_audit_logs()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('void payment receipt');

        $payment = Payment::create([
            'amount' => 50000,
            'receipt_number' => 'REC-20261027-AUDIT',
            'payment_method' => 'cash',
        ]);

        \App\Models\PaymentVoidLog::create([
            'payment_id' => $payment->id,
            'receipt_number' => 'REC-20261027-AUDIT',
            'amount' => 50000,
            'void_type' => 'rebill',
            'void_reason' => 'تدقيق رقابي تجريبي للتأكد من الواجهة',
            'voided_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('cashier.payments.void.logs'));
        $response->assertStatus(200);
        $response->assertSee('سجل تدقيق الوصولات الملغاة والمبطلة');
        $response->assertSee('REC-20261027-AUDIT');
    }

    public function test_unauthorized_user_cannot_view_void_audit_logs()
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)->get(route('cashier.payments.void.logs'));
        $response->assertStatus(403);
    }

    public function test_rebill_unbinds_all_linked_requests_and_cashier_exclusion_cancels_them_from_lab()
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'lab_staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view lab tests', 'guard_name' => 'web']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->givePermissionTo('void payment receipt');
        $admin->assignRole('admin');

        $labStaff = User::factory()->create(['role' => 'lab_staff']);
        $labStaff->assignRole('lab_staff');
        $labStaff->givePermissionTo('view lab tests');

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي للفحص']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);

        $hospital = \App\Models\Hospital::firstOrCreate(['name' => 'مستشفى الكفاءات'], [
            'owner_name' => 'د. صاحب المستشفى',
            'phone' => '07700000000',
            'email' => 'info@hospital.com',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. معالج']);
        $dept = \App\Models\Department::firstOrCreate(['name' => 'عيادة باطنية تجريبية'], [
            'hospital_id' => $hospital->id,
            'type' => 'internal',
            'room_number' => '102',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);
        $doctor = \App\Models\Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-'.uniqid(),
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص روتيني',
            'status' => 'scheduled',
            'payment_status' => 'paid',
        ]);

        $test1 = LabTest::create(['name' => 'CBC & Blood Film', 'code' => 'CBC', 'price' => 10000, 'is_active' => true]);
        $test2 = LabTest::create(['name' => 'Free thyroxin', 'code' => 'FT4', 'price' => 15000, 'is_active' => true]);

        $req1 = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب مختبر 1',
            'status' => 'pending',
            'payment_status' => 'paid',
            'details' => [
                'tests' => ['CBC & Blood Film'],
                'lab_test_ids' => [$test1->id],
            ],
        ]);

        $req2 = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب مختبر 2',
            'status' => 'pending',
            'payment_status' => 'paid',
            'details' => [
                'tests' => ['Free thyroxin'],
                'lab_test_ids' => [$test2->id],
            ],
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'request_id' => $req1->id,
            'lab_request_ids' => json_encode([$req1->id, $req2->id]),
            'amount' => 25000,
            'receipt_number' => 'REC-20261029-REOPEN',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'lab',
        ]);

        $req1->update(['payment_id' => $payment->id]);
        $req2->update(['payment_id' => $payment->id]);

        // 1. إجراء إعادة فتح للوصل (rebill)
        $voidResp = $this->actingAs($admin)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'rebill',
            'void_reason' => 'إعادة فتح لإلغاء تحليل بطلب المريض وتعديل الوصل',
        ]);
        $voidResp->assertRedirect(route('cashier.index'));

        // التأكد من فك ارتباط كِلا الطلبين وعودتهما للانتظار غير المسدد
        $this->assertEquals('pending', $req1->fresh()->payment_status);
        $this->assertNull($req1->fresh()->payment_id);
        $this->assertEquals('pending', $req2->fresh()->payment_status);
        $this->assertNull($req2->fresh()->payment_id);
        $this->assertEquals('pending_payment', $visit->fresh()->status);

        // 2. الكاشير يقوم بتسديد الطلب الأول req1 فقط مع إلغاء الطلب الثاني req2
        $payResp = $this->actingAs($admin)->post(route('cashier.request.payment.process', $req1->id), [
            'payment_method' => 'cash',
            'amount' => 10000,
            'included_request_ids' => [$req1->id, $req2->id],
            'item_actions' => [
                $req1->id => ['lab' => [$test1->id => 'pay']],
                $req2->id => ['lab' => [$test2->id => 'cancel']],
            ],
            'item_reasons' => [
                $req2->id => ['lab' => [$test2->id => 'lack_of_funds']],
            ],
        ]);
        $payResp->assertRedirect();

        // التأكد من أن الطلب الأول أصبح مسدداً بصل جديد
        $freshReq1 = $req1->fresh();
        $this->assertEquals('paid', $freshReq1->payment_status);
        $this->assertNotNull($freshReq1->payment_id);
        $this->assertNotEquals($payment->id, $freshReq1->payment_id);

        // التأكد من أن الطلب الثاني أُلغي تماماً
        $freshReq2 = $req2->fresh();
        $this->assertEquals('cancelled', $freshReq2->status);
        $this->assertEquals('cancelled', $freshReq2->payment_status);

        // 3. التحقق من طابور المختبر: الطلب الملغي لا يظهر نهائياً بينما المسدد يظهر
        $labIndex = $this->actingAs($labStaff)->get(route('lab.index'));
        $labIndex->assertStatus(200);
        $labIndex->assertSee('CBC &amp; Blood Film', false);
        $labIndex->assertDontSee('Free thyroxin');

        // 4. التحقق من شاشة الطبيب: يظهر تنبيه سريري بإلغاء الفحص
        $doctorVisit = $this->actingAs($admin)->get(route('doctor.visits.show', $visit->id));
        $doctorVisit->assertStatus(200);
        $doctorVisit->assertSee('تنبيه سريري: تم إلغاء فحوصات بطلب المريض لدى الكاشير');
        $doctorVisit->assertSee('Free thyroxin');
    }

    public function test_refund_cancels_all_associated_requests_and_excludes_them_from_lab_queue()
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'lab_staff', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view lab tests', 'guard_name' => 'web']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->givePermissionTo('void payment receipt');
        $admin->assignRole('admin');

        $labStaff = User::factory()->create(['role' => 'lab_staff']);
        $labStaff->assignRole('lab_staff');
        $labStaff->givePermissionTo('view lab tests');

        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'female',
            'date_of_birth' => '1993-04-04',
        ]);

        $hospital = \App\Models\Hospital::firstOrCreate(['name' => 'مستشفى الكفاءات'], [
            'owner_name' => 'د. صاحب المستشفى',
            'phone' => '07700000000',
            'email' => 'info@hospital.com',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $dept = \App\Models\Department::firstOrCreate(['name' => 'عيادة باطنية استرداد'], [
            'hospital_id' => $hospital->id,
            'type' => 'internal',
            'room_number' => '103',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $doctorUser2 = User::factory()->create(['name' => 'د. معالج 2']);
        $doctor2 = \App\Models\Doctor::create([
            'user_id' => $doctorUser2->id,
            'department_id' => $dept->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-'.uniqid(),
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor2->id,
            'department_id' => $dept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص روتيني',
            'status' => 'scheduled',
            'payment_status' => 'paid',
        ]);

        $test = LabTest::create(['name' => 'Lipid Profile Full', 'code' => 'LIPID', 'price' => 20000, 'is_active' => true]);

        $req = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب فحص دهون كامل',
            'status' => 'pending',
            'payment_status' => 'paid',
            'details' => [
                'tests' => ['Lipid Profile Full'],
                'lab_test_ids' => [$test->id],
            ],
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'request_id' => $req->id,
            'amount' => 20000,
            'receipt_number' => 'REC-20261029-REFUND',
            'paid_at' => now(),
            'payment_method' => 'cash',
            'payment_type' => 'lab',
        ]);
        $req->update(['payment_id' => $payment->id]);

        // إجراء استرداد نقدي كامل (refund)
        $voidResp = $this->actingAs($admin)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'refund',
            'void_reason' => 'استرداد نقدي بناءً على طلب المريض لعدم رغبته بالفحص',
        ]);
        $voidResp->assertRedirect(route('cashier.index'));

        // التأكد من تحول الطلب إلى ملغي
        $freshReq = $req->fresh();
        $this->assertEquals('cancelled', $freshReq->status);
        $this->assertEquals('cancelled', $freshReq->payment_status);
        $this->assertStringContainsString('استرداد نقدي لدى الكاشير', $freshReq->details['cancellation_reason']);

        // التأكد من عدم ظهوره في طابور المختبر
        $labIndex = $this->actingAs($labStaff)->get(route('lab.index'));
        $labIndex->assertStatus(200);
        $labIndex->assertDontSee('Lipid Profile Full');
    }
}
