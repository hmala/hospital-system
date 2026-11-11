<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorDue;
use App\Models\DoctorFinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConsultantDoctorPayoutVoucherAndReversalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Doctor $doctor;

    protected DoctorFinancialAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view doctor accounts', 'guard_name' => 'web']);
        $adminRole->givePermissionTo('view doctor accounts');

        $this->admin = User::factory()->create(['name' => 'مدير النظام']);
        $this->admin->assignRole($adminRole);

        $hospital = Hospital::create([
            'name' => 'مستشفى الفيحاء الأهلي',
            'owner_name' => 'إدارة المستشفى',
            'phone' => '07700000000',
            'address' => 'بغداد - الكرادة',
            'license_number' => 'LIC-TEST-001',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'الاستشاريون',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $doctorUser = User::factory()->create([
            'name' => 'د. نورس ناصر الحميري',
            'phone' => '07800000000',
        ]);

        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'جراحة عامة',
            'qualification' => 'بورد جراحة عامة',
            'license_number' => 'DOC-TEST-'.uniqid(),
            'consultation_fee' => 30000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $this->account = DoctorFinancialAccount::create([
            'doctor_id' => $this->doctor->id,
            'balance' => 500000.00,
            'total_earned' => 500000.00,
            'total_paid' => 0.00,
        ]);

        DoctorDue::create([
            'doctor_id' => $this->doctor->id,
            'amount' => 500000.00,
            'status' => 'pending',
            'notes' => 'مستحقات كشفيات استشارية معلقة',
        ]);
    }

    public function test_doctor_payout_creates_transaction_and_flash_session(): void
    {
        $response = $this->actingAs($this->admin)->post(route('consultant-availability.doctor-payout', $this->doctor), [
            'amount' => 200000,
            'notes' => 'صرف مستحقات كشفيات الفترة',
        ]);

        $response->assertRedirect(route('consultant-availability.doctor-account', $this->doctor));
        $response->assertSessionHas('success');
        $response->assertSessionHas('new_payout_tx_id');
        $response->assertSessionHas('new_payout_voucher');

        $tx = FinancialTransaction::where('related_type', Doctor::class)
            ->where('related_id', $this->doctor->id)
            ->where('category', 'doctor_payout')
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals(200000.00, (float) $tx->amount);
        $this->assertEquals('outflow', $tx->voucher_type);

        // Check account
        $this->account->refresh();
        $this->assertEquals(300000.00, (float) $this->account->balance);
        $this->assertEquals(200000.00, (float) $this->account->total_paid);

        // Check page view shows the banner and the voucher in the table
        $pageResponse = $this->actingAs($this->admin)->get(route('consultant-availability.doctor-account', $this->doctor));
        $pageResponse->assertOk();
        $pageResponse->assertSee($tx->voucher_number);
        $pageResponse->assertSee('طباعة السند');
        $pageResponse->assertSee('استرجاع / إلغاء');
    }

    public function test_print_doctor_payout_voucher_view(): void
    {
        $tx = FinancialTransaction::create([
            'transaction_type' => 'expense',
            'voucher_type' => 'outflow',
            'category' => 'doctor_payout',
            'voucher_number' => 'EXP-DOC-261108-7777',
            'related_type' => Doctor::class,
            'related_id' => $this->doctor->id,
            'amount' => 250000,
            'currency' => 'IQD',
            'payment_method' => 'cash',
            'description' => 'صرف مستحقات للطبيب د. نورس ناصر الحميري',
            'notes' => 'بيان صرف نقدي رسمي',
            'performed_by_id' => $this->admin->id,
            'performed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('consultant-availability.doctor-payout.print', $tx->id));
        $response->assertOk();
        $response->assertSee('EXP-DOC-261108-7777');
        $response->assertSee('د. نورس ناصر الحميري');
        $response->assertSee('جراحة عامة');
        $response->assertSee('250,000 د.ع');
        $response->assertSee('توقيع المستلم (الطبيب)');
        $response->assertSee('منظم السند (المحاسب)');
        $response->assertSee('اعتماد الإدارة المالية');
        $response->assertSee('مائتان وخمسون ألف دينار عراقي');
    }

    public function test_reverse_doctor_payout_full(): void
    {
        // 1. Perform payout of 200,000
        $this->actingAs($this->admin)->post(route('consultant-availability.doctor-payout', $this->doctor), [
            'amount' => 200000,
            'notes' => 'صرف تجريبي للاسترجاع',
        ]);

        $tx = FinancialTransaction::where('related_type', Doctor::class)
            ->where('related_id', $this->doctor->id)
            ->where('category', 'doctor_payout')
            ->first();

        $this->account->refresh();
        $this->assertEquals(300000.00, (float) $this->account->balance);
        $this->assertEquals(200000.00, (float) $this->account->total_paid);

        // 2. Perform Full Reversal
        $revResponse = $this->actingAs($this->admin)->post(route('consultant-availability.doctor-payout.reverse', $tx->id), [
            'reversal_type' => 'full',
            'reversal_reason' => 'تم الصرف بالخطأ وإلغاء الحركة بناء على طلب المحاسب',
        ]);

        $revResponse->assertRedirect(route('consultant-availability.doctor-account', $this->doctor));
        $revResponse->assertSessionHas('success');

        // Check account restored: balance back to 500,000, total_paid back to 0
        $this->account->refresh();
        $this->assertEquals(500000.00, (float) $this->account->balance);
        $this->assertEquals(0.00, (float) $this->account->total_paid);

        // Check reversal transaction created
        $revTx = FinancialTransaction::where('related_type', Doctor::class)
            ->where('related_id', $this->doctor->id)
            ->where('category', 'doctor_payout_reversal')
            ->first();

        $this->assertNotNull($revTx);
        $this->assertEquals(200000.00, (float) $revTx->amount);
        $this->assertEquals('inflow', $revTx->voucher_type);
        $this->assertStringContainsString('استرجاع كلي', $revTx->description);
    }

    public function test_receipts_show_pending_and_paid_and_return_to_pending_on_reversal(): void
    {
        // 1. Create a consultation revenue with payment and doctorDue
        $patientUser = User::factory()->create(['name' => 'المريض سجاد علي']);
        $patient = \App\Models\Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1995-01-01',
        ]);

        $payment = \App\Models\Payment::create([
            'patient_id' => $patient->id,
            'amount' => 25000,
            'total_amount' => 25000,
            'payment_type' => 'consultation',
            'payment_method' => 'cash',
            'receipt_number' => 'REC-STATUS-001',
            'cashier_id' => $this->admin->id,
            'paid_at' => now(),
        ]);

        $rev = \App\Models\ConsultationRevenue::create([
            'doctor_id' => $this->doctor->id,
            'payment_id' => $payment->id,
            'receipt_number' => 'REC-STATUS-001',
            'payment_method' => 'cash',
            'payment_type' => 'consultation',
            'cashier_id' => $this->admin->id,
            'total_amount' => 25000,
            'doctor_share' => 15000,
            'hospital_share' => 10000,
            'movement_type' => 'payment',
            'revenue_date' => now()->toDateString(),
            'paid_at' => now(),
        ]);

        $due = DoctorDue::create([
            'doctor_id' => $this->doctor->id,
            'payment_id' => $payment->id,
            'amount' => 15000,
            'status' => 'pending',
            'notes' => 'مستحقات كشفية تجريبية',
        ]);

        // 2. View page: before payout, receipt must show "غير مقبوضة"
        $page1 = $this->actingAs($this->admin)->get(route('consultant-availability.doctor-account', $this->doctor));
        $page1->assertOk();
        $page1->assertSee('غير مقبوضة');

        // 3. Perform payout
        $this->actingAs($this->admin)->post(route('consultant-availability.doctor-payout', $this->doctor), [
            'amount' => 15000,
            'notes' => 'تسديد كشفية تجريبية',
        ]);

        $tx = FinancialTransaction::where('related_type', Doctor::class)
            ->where('related_id', $this->doctor->id)
            ->where('category', 'doctor_payout')
            ->latest('id')
            ->first();

        // Check receipt now shows "مقبوضة"
        $page2 = $this->actingAs($this->admin)->get(route('consultant-availability.doctor-account', $this->doctor));
        $page2->assertOk();
        $page2->assertSee('مقبوضة');

        // 4. Perform reversal
        $this->actingAs($this->admin)->post(route('consultant-availability.doctor-payout.reverse', $tx->id), [
            'reversal_reason' => 'إلغاء سند الصرف لإعادة الوصولات غير مقبوضة',
        ]);

        // Check receipt returns to "غير مقبوضة"
        $page3 = $this->actingAs($this->admin)->get(route('consultant-availability.doctor-account', $this->doctor));
        $page3->assertOk();
        $page3->assertSee('غير مقبوضة');
    }
}
