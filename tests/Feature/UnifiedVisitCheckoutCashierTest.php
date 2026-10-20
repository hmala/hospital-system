<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\LabTest;
use App\Models\RadiologyType;
use App\Models\Request as MedicalRequest;
use App\Models\Payment;
use App\Models\RadiologyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UnifiedVisitCheckoutCashierTest extends TestCase
{
    use RefreshDatabase;

    protected $cashierUser;
    protected $doctorUser;
    protected $doctor;
    protected $patient;
    protected $visit;
    protected $labTestCbc;
    protected $radChestXray;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('cashier', 'web');
        Role::findOrCreate('doctor', 'web');

        Permission::findOrCreate('process medical requests payments', 'web');
        Permission::findOrCreate('view cashier', 'web');

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $this->cashierUser = User::create([
            'name' => 'كاشير علي',
            'email' => 'cashier_ali@test.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
        ]);
        $this->cashierUser->assignRole('cashier');
        $this->cashierUser->givePermissionTo('process medical requests payments');
        $this->cashierUser->givePermissionTo('view cashier');

        $this->doctorUser = User::create([
            'name' => 'دكتور أحمد',
            'email' => 'dr_ahmed@test.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
            'is_active' => true,
        ]);
        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-TEST-12',
            'type' => 'consultant',
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $patientUser = User::create([
            'name' => 'أحمد علي السعدي',
            'email' => 'ahmed_patient@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'file_number' => 'PAT-9911',
            'insurance_type' => 'none',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحوصات عامة وألم بالصدر',
            'status' => 'in_progress',
        ]);

        $this->labTestCbc = LabTest::create([
            'name' => 'Complete Blood Count (CBC)',
            'code' => 'CBC-01',
            'price' => 10000,
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->radChestXray = RadiologyType::create([
            'name' => 'Chest X-Ray PA',
            'code' => 'XR-CHEST',
            'base_price' => 25000,
            'main_category' => 'أشعة',
            'subcategory' => 'أشعة',
            'is_active' => true,
        ]);
    }

    /**
     * اختبار: نموذج الدفع للكاشير يجمع كافة الطلبات غير المدفوعة للزيارة في فاتورة موحدة
     */
    public function test_cashier_payment_form_shows_unified_visit_invoice()
    {
        $labRequest = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'طلب تحاليل مختبرية',
            'details' => [
                'lab_test_ids' => [$this->labTestCbc->id],
                'tests' => [$this->labTestCbc->name],
            ],
            'total_amount' => 10000,
        ]);

        $radRequest = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'طلب فحص إشعاعي',
            'details' => [
                'radiology_type_ids' => [$this->radChestXray->id],
                'radiology_targets' => [$this->radChestXray->id => 'صدر'],
            ],
            'total_amount' => 25000,
        ]);

        $response = $this->actingAs($this->cashierUser)
            ->get(route('cashier.request.payment.form', $labRequest->id));

        $response->assertStatus(200);
        $response->assertSee('فاتورة موحدة شاملة لخدمات الزيارة');
        $response->assertSee('Complete Blood Count (CBC)');
        $response->assertSee('Chest X-Ray PA');
        $response->assertSee('35,000 د.ع'); // 10,000 + 25,000
    }

    /**
     * اختبار: تسديد الفاتورة الموحدة للزيارة يحول طلبي المختبر والأشعة إلى مدفوعين بوصل واحد
     */
    public function test_cashier_can_pay_all_visit_requests_in_one_unified_checkout()
    {
        $labRequest = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'طلب تحاليل مختبرية',
            'details' => [
                'lab_test_ids' => [$this->labTestCbc->id],
                'tests' => [$this->labTestCbc->name],
            ],
            'total_amount' => 10000,
        ]);

        $radRequest = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'طلب فحص إشعاعي',
            'details' => [
                'radiology_type_ids' => [$this->radChestXray->id],
                'radiology_targets' => [$this->radChestXray->id => 'صدر'],
            ],
            'total_amount' => 25000,
        ]);

        $response = $this->actingAs($this->cashierUser)
            ->post(route('cashier.request.payment.process', $labRequest->id), [
                'included_request_ids' => [$labRequest->id, $radRequest->id],
                'payment_method' => 'cash',
                'amount' => 35000,
                'notes' => 'تسديد موحد شامل',
            ]);

        $response->assertRedirect();

        // تحقق من إنشاء سجل دفع واحد فقط بقيمة 35,000
        $this->assertEquals(1, Payment::count());
        $payment = Payment::first();
        $this->assertEquals(35000, $payment->amount);
        $this->assertEquals(35000, $payment->total_amount);

        // كلا الطلبين تحولا إلى paid ومرتبطين بنفس الـ payment_id
        $labRequest->refresh();
        $radRequest->refresh();
        $this->assertEquals('paid', $labRequest->payment_status);
        $this->assertEquals($payment->id, $labRequest->payment_id);
        $this->assertEquals('paid', $radRequest->payment_status);
        $this->assertEquals($payment->id, $radRequest->payment_id);

        // تحقق من إنشاء سجل في radiology_requests لفحص الأشعة المدفوع
        $this->assertTrue(RadiologyRequest::where('visit_id', $this->visit->id)->where('radiology_type_id', $this->radChestXray->id)->exists());

        // تحقق من ظهور كلا الفحصين (مختبر + أشعة) في الوصل الحراري الصغير
        $printResponse = $this->actingAs($this->cashierUser)
            ->get(route('cashier.receipt.print', $payment->id));

        $printResponse->assertStatus(200);
        $printResponse->assertSee('Complete Blood Count (CBC)');
        $printResponse->assertSee('Chest X-Ray PA');
        $printResponse->assertSee('35,000');
    }
}
