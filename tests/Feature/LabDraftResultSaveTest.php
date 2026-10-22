<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Department;
use App\Models\Appointment;
use App\Models\Visit;
use App\Models\Request as MedicalRequest;
use App\Models\LabTest;
use App\Models\LabResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class LabDraftResultSaveTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $doctor;
    protected $labStaffUser;
    protected $patient;
    protected $visit;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'doctor']);
        Role::firstOrCreate(['name' => 'lab_staff']);
        Role::firstOrCreate(['name' => 'patient']);

        Permission::firstOrCreate(['name' => 'manage own visits']);
        Permission::firstOrCreate(['name' => 'view own visits']);
        Permission::firstOrCreate(['name' => 'view lab tests']);

        $hospital = \App\Models\Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-TEST-' . rand(100, 999),
        ]);

        $dept = Department::firstOrCreate(
            ['name' => 'عيادة الباطنية'],
            [
                'hospital_id' => $hospital->id,
                'type' => 'internal',
                'room_number' => '1',
                'consultation_fee' => 15000,
                'working_hours_start' => '08:00:00',
                'working_hours_end' => '16:00:00',
                'is_active' => true,
            ]
        );

        $this->doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo(['manage own visits', 'view own visits']);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $dept->id,
            'type' => 'consultant',
            'specialization' => 'طب باطني',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-TEST-' . rand(100, 999),
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $this->labStaffUser = User::factory()->create(['role' => 'lab_staff']);
        $this->labStaffUser->assignRole('lab_staff');
        $this->labStaffUser->givePermissionTo('view lab tests');

        $patientUser = User::create([
            'name' => 'حسن كريم',
            'username' => 'hasan_' . uniqid(),
            'email' => 'hasan_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'status' => 'active',
        ]);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'blood_group' => 'O+',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $dept->id,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'type' => 'consultation',
            'consultation_fee' => 15000,
            'status' => 'in_progress',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $dept->id,
            'appointment_id' => $appointment->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص دوري',
            'status' => 'in_progress',
        ]);
    }

    /**
     * اختبار: حفظ مسودة نتائج المختبر يُبقي حالة الطلب in_progress ويبقى في شاشة المختبر ولا يرسل للطبيب
     */
    public function test_lab_staff_can_save_draft_results_without_completing_or_transmitting_to_doctor(): void
    {
        $testCbc = LabTest::create([
            'name' => 'Complete Blood Count (CBC)',
            'code' => 'CBC-DRAFT',
            'price' => 15000,
            'is_active' => true,
        ]);

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبرية',
            'status' => 'in_progress',
            'payment_status' => 'paid',
            'details' => [
                'lab_test_ids' => [$testCbc->id],
                'tests' => [$testCbc->name],
            ],
        ]);

        $payload = [
            'action' => 'draft',
            'status' => 'in_progress',
            'test_results' => [
                'Complete Blood Count (CBC)' => [
                    'value' => '14.5',
                    'unit' => 'g/dL',
                    'reference_range' => '13.0 - 17.0',
                    'status' => 'normal',
                    'test_name' => 'Complete Blood Count (CBC)',
                    'lab_test_id' => $testCbc->id,
                ],
            ],
            'result' => 'ملاحظة مسودة: العينة ممتازة ويجري التحقق من باقي المؤشرات.',
        ];

        $response = $this->actingAs($this->labStaffUser)
            ->put(route('lab.update', $request), $payload);

        $response->assertRedirect(route('lab.show', $request));
        $response->assertSessionHas('success');

        $request->refresh();
        $this->assertEquals('in_progress', $request->status, 'حالة الطلب يجب أن تظل in_progress كمسودة');
        $this->assertNotNull($request->result);

        $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
        $this->assertEquals('14.5', $resultData['test_results']['Complete Blood Count (CBC)']['value']);
        $this->assertEquals('ملاحظة مسودة: العينة ممتازة ويجري التحقق من باقي المؤشرات.', $resultData['notes']);

        // التحقق من إنشاء سجل النتيجة في جدول lab_results
        $this->assertDatabaseHas('lab_results', [
            'request_id' => $request->id,
            'test_name' => 'Complete Blood Count (CBC)',
            'value' => '14.5',
        ]);
    }

    /**
     * اختبار: الاعتماد النهائي والإرسال للطبيب يحول حالة الطلب إلى completed ويحول للصفحة الرئيسية
     */
    public function test_lab_staff_can_finalize_and_transmit_results_to_doctor(): void
    {
        $testGlu = LabTest::create([
            'name' => 'Fasting Blood Sugar (FBS)',
            'code' => 'FBS-DRAFT',
            'price' => 10000,
            'is_active' => true,
        ]);

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبرية',
            'status' => 'in_progress',
            'payment_status' => 'paid',
            'details' => [
                'lab_test_ids' => [$testGlu->id],
                'tests' => [$testGlu->name],
            ],
        ]);

        $payload = [
            'action' => 'complete',
            'status' => 'completed',
            'test_results' => [
                'Fasting Blood Sugar (FBS)' => [
                    'value' => '95',
                    'unit' => 'mg/dL',
                    'reference_range' => '70 - 100',
                    'status' => 'normal',
                    'test_name' => 'Fasting Blood Sugar (FBS)',
                    'lab_test_id' => $testGlu->id,
                ],
            ],
            'result' => 'النتيجة طبيعية ومعتمدة رسمياً.',
        ];

        $response = $this->actingAs($this->labStaffUser)
            ->put(route('lab.update', $request), $payload);

        $response->assertRedirect(route('lab.index'));
        $response->assertSessionHas('success');

        $request->refresh();
        $this->assertEquals('completed', $request->status, 'حالة الطلب يجب أن تصبح completed');

        $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
        $this->assertEquals('95', $resultData['test_results']['Fasting Blood Sugar (FBS)']['value']);
        $this->assertEquals('النتيجة طبيعية ومعتمدة رسمياً.', $resultData['notes']);

        $this->assertDatabaseHas('lab_results', [
            'request_id' => $request->id,
            'test_name' => 'Fasting Blood Sugar (FBS)',
            'value' => '95',
        ]);
    }

    /**
     * اختبار: التحقق من وجود زري الحفظ المسودة والاعتماد النهائي في شاشة المختبر
     */
    public function test_lab_show_blade_displays_both_draft_and_final_submission_buttons(): void
    {
        $test = LabTest::create([
            'name' => 'Serum Creatinine',
            'code' => 'CREAT-TEST',
            'price' => 8000,
            'is_active' => true,
        ]);

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبرية',
            'status' => 'in_progress',
            'payment_status' => 'paid',
            'details' => [
                'lab_test_ids' => [$test->id],
                'tests' => [$test->name],
            ],
        ]);

        $response = $this->actingAs($this->labStaffUser)
            ->get(route('lab.show', $request));

        $response->assertOk();
        $response->assertSee('حفظ مؤقت (مسودة)');
        $response->assertSee('اعتماد نهائي وإرسال للطبيب');
        $response->assertSee('labFormAction');
        $response->assertSee('labFormStatus');
    }
}
