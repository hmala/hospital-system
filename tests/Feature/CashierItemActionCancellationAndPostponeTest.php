<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierItemActionCancellationAndPostponeTest extends TestCase
{
    use RefreshDatabase;

    protected $cashierUser;

    protected $doctorUser;

    protected $doctor;

    protected $patient;

    protected $visit;

    protected $cbcTest;

    protected $lipidTest;

    protected $vitDTest;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('cashier', 'web');
        Role::findOrCreate('doctor', 'web');

        Permission::findOrCreate('view cashier', 'web');
        Permission::findOrCreate('process medical requests payments', 'web');
        Permission::findOrCreate('process consultation payments', 'web');
        Permission::findOrCreate('manage own visits', 'web');
        Permission::findOrCreate('view own visits', 'web');

        $hospital = Hospital::create([
            'name' => 'مستشفى الشفاء التخصصي',
            'owner_name' => 'الإدارة العامة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-100',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة الباطنية الاستشارية',
            'type' => 'internal',
            'room_number' => '10',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->doctorUser = User::create([
            'name' => 'د. محمد فاضل',
            'email' => 'dr.mohammed@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo(['manage own visits', 'view own visits']);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'باطنية',
            'qualification' => 'FICMS',
            'license_number' => 'DOC-9988',
            'consultation_fee' => 25000,
            'is_active' => true,
        ]);

        $this->cashierUser = User::create([
            'name' => 'محاسب الكاشير - أحمد',
            'email' => 'cashier.ahmed@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->cashierUser->assignRole('cashier');
        $this->cashierUser->givePermissionTo(['view cashier', 'process medical requests payments', 'process consultation payments']);

        $patientUser = User::create([
            'name' => 'حسين كاظم كريم',
            'email' => 'hussein@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'birth_date' => '1985-05-15',
            'blood_type' => 'B+',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $department->id,
            'visit_date' => Carbon::today()->toDateString(),
            'visit_time' => '09:30:00',
            'chief_complaint' => 'فحص عام واستشارة طبية',
            'status' => 'in_progress',
            'visit_type' => 'consultation',
        ]);

        $this->cbcTest = LabTest::create([
            'name' => 'Complete Blood Count (CBC)',
            'code' => 'CBC-01',
            'price' => 15000,
            'is_active' => true,
        ]);

        $this->lipidTest = LabTest::create([
            'name' => 'Lipid Profile',
            'code' => 'LIPID-01',
            'price' => 25000,
            'is_active' => true,
        ]);

        $this->vitDTest = LabTest::create([
            'name' => 'Vitamin D3 Total',
            'code' => 'VITD-01',
            'price' => 40000,
            'is_active' => true,
        ]);
    }

    public function test_cashier_can_pay_selected_item_and_cancel_or_postpone_others_with_reasons(): void
    {
        // 1. الطبيب ينشئ طلباً مخبرياً يحوي 3 تحاليل
        $originalRequest = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'طلب تحاليل فحص شامل',
            'details' => [
                'lab_test_ids' => [$this->cbcTest->id, $this->lipidTest->id, $this->vitDTest->id],
                'tests' => [$this->cbcTest->name, $this->lipidTest->name, $this->vitDTest->name],
            ],
        ]);

        // 2. الكاشير يدفع CBC فقط (15,000 د.ع)، ويلغي Lipid بطلب المريض لقلة السيولة، ويؤجل Vitamin D لضيق الوقت
        $payload = [
            'payment_method' => 'cash',
            'amount' => 15000,
            'insurance_type' => 'none',
            'notes' => 'تسديد فحص CBC فقط حسب رغبة المريض',
            'included_request_ids' => [$originalRequest->id],
            'item_actions' => [
                $originalRequest->id => [
                    'lab' => [
                        $this->cbcTest->id => 'pay',
                        $this->lipidTest->id => 'cancel',
                        $this->vitDTest->id => 'postpone',
                    ],
                ],
            ],
            'item_reasons' => [
                $originalRequest->id => [
                    'lab' => [
                        $this->lipidTest->id => 'lack_of_funds',
                        $this->vitDTest->id => 'time_constraint',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierUser)
            ->post(route('cashier.request.payment.process', $originalRequest), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 3. التحقق من السجل المالي وسند القبض
        $this->assertDatabaseHas('payments', [
            'patient_id' => $this->patient->id,
            'amount' => 15000,
            'payment_method' => 'cash',
            'cashier_id' => $this->cashierUser->id,
        ]);

        // 4. التحقق من أن الطلب الأصلي يحتوي فقط على الفحص المسدد CBC
        $originalRequest->refresh();
        $this->assertEquals('paid', $originalRequest->payment_status);
        $this->assertEquals([$this->cbcTest->id], $originalRequest->details['lab_test_ids']);
        $this->assertEquals([$this->cbcTest->name], $originalRequest->details['tests']);

        // 5. التحقق من وجود طلب ملغي مستقل لتحليل Lipid Profile مع ذكر السبب والموظف
        $cancelledReq = MedicalRequest::where('visit_id', $this->visit->id)
            ->where('status', 'cancelled')
            ->first();

        $this->assertNotNull($cancelledReq);
        $this->assertEquals('cancelled', $cancelledReq->payment_status);
        $this->assertContains($this->lipidTest->id, $cancelledReq->details['lab_test_ids']);
        $this->assertEquals($this->cashierUser->id, $cancelledReq->details['cancelled_by_cashier_id']);
        $this->assertStringContainsString('قلة السيولة', $cancelledReq->details['cancellation_reason']);

        // 6. التحقق من وجود طلب مؤجل مستقل لتحليل Vitamin D3
        $postponedReq = MedicalRequest::where('visit_id', $this->visit->id)
            ->where('status', 'postponed')
            ->first();

        $this->assertNotNull($postponedReq);
        $this->assertEquals('pending', $postponedReq->payment_status);
        $this->assertContains($this->vitDTest->id, $postponedReq->details['lab_test_ids']);
        $this->assertEquals($this->cashierUser->id, $postponedReq->details['postponed_by_cashier_id']);
        $this->assertStringContainsString('ضيق الوقت', $postponedReq->details['postpone_reason']);

        // 7. محطة الطبيب: التحقق من أن الفحوصات الملغاة والمؤجلة لا تعرقل إقفال الزيارة
        // عند إكمال نتيجة CBC
        $originalRequest->update(['status' => 'completed']);

        // يحاول الطبيب إنهاء الزيارة الآن
        $finishResponse = $this->actingAs($this->doctorUser)
            ->put(route('doctor.visits.update', $this->visit), [
                'status' => 'completed',
            ]);

        $finishResponse->assertRedirect();
        $finishResponse->assertSessionHas('success');

        $this->visit->refresh();
        $this->assertEquals('completed', $this->visit->status);
    }

    public function test_cashier_can_cancel_all_items_without_charging_patient(): void
    {
        $originalRequest = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'تحاليل استشارية',
            'details' => [
                'lab_test_ids' => [$this->cbcTest->id, $this->lipidTest->id],
                'tests' => [$this->cbcTest->name, $this->lipidTest->name],
            ],
        ]);

        $payload = [
            'payment_method' => 'cash',
            'amount' => 0,
            'insurance_type' => 'none',
            'included_request_ids' => [$originalRequest->id],
            'item_actions' => [
                $originalRequest->id => [
                    'lab' => [
                        $this->cbcTest->id => 'cancel',
                        $this->lipidTest->id => 'cancel',
                    ],
                ],
            ],
            'item_reasons' => [
                $originalRequest->id => [
                    'lab' => [
                        $this->cbcTest->id => 'patient_refused',
                        $this->lipidTest->id => 'patient_refused',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierUser)
            ->post(route('cashier.request.payment.process', $originalRequest), $payload);

        $response->assertRedirect(route('cashier.index'));
        $response->assertSessionHas('info');

        // لا يتم تسجيل أي دفعة مالية بالصندوق
        $this->assertEquals(0, Payment::where('patient_id', $this->patient->id)->count());

        // تحول الطلب الأصلي إلى ملغي
        $originalRequest->refresh();
        $this->assertEquals('cancelled', $originalRequest->status);
        $this->assertEquals('cancelled', $originalRequest->payment_status);
        $this->assertStringContainsString('رفض', $originalRequest->details['cancellation_reason']);

        // يستطيع الطبيب إنهاء الزيارة فوراً لعدم وجود أي فحوصات معلقة
        $finishResponse = $this->actingAs($this->doctorUser)
            ->put(route('doctor.visits.update', $this->visit), [
                'status' => 'completed',
            ]);

        $finishResponse->assertRedirect();
        $finishResponse->assertSessionHas('success');

        $this->visit->refresh();
        $this->assertEquals('completed', $this->visit->status);
    }

    public function test_cashier_index_displays_postponed_requests_tab_and_details(): void
    {
        // إنشاء طلب مؤجل
        $postponedRequest = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'status' => 'postponed',
            'payment_status' => 'pending',
            'description' => 'تحليل مؤجل لعدم توفر السيولة',
            'details' => [
                'lab_test_ids' => [$this->vitDTest->id],
                'tests' => [$this->vitDTest->name],
                'postponement_reason' => 'عدم توفر المبلغ المالي / قلة السيولة',
            ],
        ]);

        $response = $this->actingAs($this->cashierUser)
            ->get(route('cashier.index'));

        $response->assertOk();
        $response->assertViewHas('postponedMedicalRequests');
        $response->assertViewHas('postponedRequestsCount', 1);
        $response->assertSee('الفحوصات المؤجلة');
        $response->assertSee('tab-postponed');
        $response->assertSee($this->patient->user->name);
        $response->assertSee('Vitamin D3 Total');
        $response->assertSee('قلة السيولة');
        $response->assertSee(route('cashier.request.payment.form', $postponedRequest->id));
    }
}
