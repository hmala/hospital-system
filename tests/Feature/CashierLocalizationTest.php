<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_cashier_index_page_renders_in_both_locales(): void
    {
        $admin = User::factory()->create(['name' => 'Cashier Admin', 'email' => 'cashier_admin@hospital.com']);
        $admin->assignRole('admin');

        // 1. English Locale
        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('cashier.index'));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('Cashier Workstation');
        $responseEn->assertSee('Collected Today');
        $responseEn->assertSee('Doctor Fees');
        $responseEn->assertSee('Hospital Profit');
        $responseEn->assertSee('Pending Transactions Awaiting Payment');

        // 2. Arabic Locale
        $responseAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('cashier.index'));

        $responseAr->assertStatus(200);
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('محطة الكاشير');
        $responseAr->assertSee('المحصلة اليوم');
        $responseAr->assertSee('أجور الأطباء');
        $responseAr->assertSee('ربح المستشفى');
        $responseAr->assertSee('المعاملات المعلقة بانتظار الدفع');
    }

    public function test_cashier_payment_form_renders_in_both_locales(): void
    {
        $admin = User::factory()->create(['name' => 'Cashier Admin 2', 'email' => 'cashier2@hospital.com']);
        $admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'Test Hospital',
            'owner_name' => 'Director',
            'license_number' => 'LIC-CASH-01',
            'phone' => '07700000020',
            'email' => 'cash@hospital.com',
            'address' => 'Baghdad',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'Internal Medicine',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 25,
            'is_active' => true,
        ]);

        $docUser = User::factory()->create(['name' => 'Dr. Ahmed']);
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'Internal Medicine',
            'phone' => '07711223399',
            'qualification' => 'MBBS',
            'license_number' => 'DOC-991',
            'consultation_fee' => 25000,
            'type' => 'permanent',
        ]);

        $patUser = User::factory()->create(['name' => 'Ali Hassan']);
        $patient = Patient::create([
            'user_id' => $patUser->id,
            'patient_id' => 'P-CASH-101',
            'name' => 'Ali Hassan',
            'gender' => 'male',
            'age' => 30,
            'blood_group' => 'O+',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'appointment_date' => now(),
            'appointment_time' => now()->format('H:i:s'),
            'type' => 'consultation',
            'status' => 'pending',
            'consultation_fee' => 25000,
            'payment_status' => 'pending',
        ]);

        // English
        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('cashier.payment.form', $appointment->id));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('Approved Service Price');
        $responseEn->assertSee('Required from Patient');
        $responseEn->assertSee('Confirm Collection &amp; Issue Receipt', false);

        // Arabic
        $responseAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('cashier.payment.form', $appointment->id));

        $responseAr->assertStatus(200);
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('السعر المعتمد للخدمة');
        $responseAr->assertSee('المطلوب من المريض');
        $responseAr->assertSee('تأكيد القبض وإصدار الوصل');
    }

    public function test_cashier_receipt_renders_in_both_locales(): void
    {
        $admin = User::factory()->create(['name' => 'Cashier Admin 3', 'email' => 'cashier3@hospital.com']);
        $admin->assignRole('admin');

        $patUser = User::factory()->create(['name' => 'Huda Karim']);
        $patient = Patient::create([
            'user_id' => $patUser->id,
            'patient_id' => 'P-CASH-102',
            'name' => 'Huda Karim',
            'gender' => 'female',
            'age' => 28,
            'blood_group' => 'A+',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'cashier_id' => $admin->id,
            'amount' => 30000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'payment_type' => 'consultation',
            'paid_at' => now(),
            'receipt_number' => 'REC-2026-9999',
            'notes' => 'Test receipt',
        ]);

        // English
        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('cashier.receipt', $payment->id));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('Official Payment Receipt');
        $responseEn->assertSee('Voucher No.:');
        $responseEn->assertSee('Collected from Patient');
        $responseEn->assertSee('Payment Method:');

        // Arabic
        $responseAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('cashier.receipt', $payment->id));

        $responseAr->assertStatus(200);
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('إيصال قبض رسمي');
        $responseAr->assertSee('رقم السند:');
        $responseAr->assertSee('المقبوض من المريض');
        $responseAr->assertSee('طريقة الدفع:');
    }

    public function test_cashier_receipt_print_renders_in_both_locales(): void
    {
        $admin = User::factory()->create(['name' => 'Cashier Admin 4', 'email' => 'cashier4@hospital.com']);
        $admin->assignRole('admin');

        $patUser = User::factory()->create(['name' => 'Zainab Omar']);
        $patient = Patient::create([
            'user_id' => $patUser->id,
            'patient_id' => 'P-CASH-103',
            'name' => 'Zainab Omar',
            'gender' => 'female',
            'age' => 32,
            'blood_group' => 'B+',
        ]);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'cashier_id' => $admin->id,
            'amount' => 50000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'payment_type' => 'consultation',
            'paid_at' => now(),
            'receipt_number' => 'REC-2026-8888',
            'notes' => 'Print test receipt',
        ]);

        // English
        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('cashier.receipt.print', $payment->id));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('Payment Receipt');
        $responseEn->assertSee('Patient Name:');
        $responseEn->assertSee('Medical Service');
        $responseEn->assertSee('Paid Amount');

        // Arabic
        $responseAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('cashier.receipt.print', $payment->id));

        $responseAr->assertStatus(200);
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('إيصال دفع');
        $responseAr->assertSee('اسم المريض:');
        $responseAr->assertSee('الخدمة الطبية');
        $responseAr->assertSee('المبلغ المدفوع');
    }

    public function test_cashier_report_print_renders_department_grouped_table_and_summary_box(): void
    {
        $admin = User::factory()->create(['name' => 'Cashier Supervisor', 'email' => 'supervisor@hospital.com']);
        $admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'Central Hospital',
            'owner_name' => 'Dr. Director',
            'license_number' => 'LIC-PRINT-01',
            'phone' => '07700000088',
            'email' => 'print@hospital.com',
            'address' => 'Baghdad',
        ]);

        $patUser = User::factory()->create(['name' => 'Haider Ali']);
        $patient = Patient::create([
            'user_id' => $patUser->id,
            'patient_id' => 'P-PRINT-01',
            'name' => 'Haider Ali',
            'gender' => 'male',
            'age' => 35,
        ]);

        // 1. Surgery Payment
        Payment::create([
            'patient_id' => $patient->id,
            'cashier_id' => $admin->id,
            'amount' => 1500000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'payment_type' => 'surgery',
            'paid_at' => now(),
            'receipt_number' => '45601',
            'description' => 'عملية استئصال المرارة بالمنظار',
        ]);

        // 2. Lab Payment (Card)
        Payment::create([
            'patient_id' => $patient->id,
            'cashier_id' => $admin->id,
            'amount' => 50000,
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'payment_type' => 'lab',
            'paid_at' => now(),
            'receipt_number' => '45602',
            'description' => 'تحليل CBC و بايوكيميستري',
        ]);

        // 3. Refunded Payment
        Payment::create([
            'patient_id' => $patient->id,
            'cashier_id' => $admin->id,
            'amount' => 25000,
            'payment_method' => 'cash',
            'payment_status' => 'cancelled',
            'payment_type' => 'emergency',
            'paid_at' => now(),
            'voided_at' => now(),
            'voided_by' => $admin->id,
            'void_type' => 'refund',
            'void_reason' => 'إلغاء المريض قبل الفحص',
            'receipt_number' => '45603',
            'description' => 'رسوم طوارئ مسترجعة',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('cashier.report', ['print' => '1']));

        $response->assertStatus(200);
        $response->assertSee('كشف حساب يومية الصندوق ومقبوضات الكاشير');
        $response->assertSee('ت الوصل');
        $response->assertSee('الخدمة الطبية');
        $response->assertSee('القسم');
        $response->assertSee('المجموع');
        $response->assertSee('المجموع الكلي للمقبوضات');
        $response->assertSee('الاسترجاعات المدفوعة من صندوق القبض بموجب النماذج');
        $response->assertSee('ملخص الصندوق');
        $response->assertSee('مجموع المبالغ المقبوضة');
        $response->assertSee('(-) مجموع المبالغ المسترجعة');
        $response->assertSee('(-) المبالغ المقبوضة إلكترونياً (POS / بطاقة)');
        $response->assertSee('صافي الصندوق (الرصيد النقدي الفعلي بالدرج)');
        $response->assertSee('Haider Ali');
        $response->assertSee('45601');
    }
}
