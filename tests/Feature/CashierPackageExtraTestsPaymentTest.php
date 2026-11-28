<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\Package;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierPackageExtraTestsPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Patient $patient;

    protected Visit $visit;

    protected Package $package;

    protected LabTest $testInPkg1;

    protected LabTest $testInPkg2;

    protected LabTest $extraTest1;

    protected LabTest $extraTest2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'مستشفى الشفاء التخصصي',
            'owner_name' => 'د. أحمد',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-TEST-002',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'المختبر الرئيسي',
            'type' => 'lab',
            'room_number' => '101',
            'consultation_fee' => 0,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'code' => 'LAB01',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. طبيب فاحص']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'hospital_id' => $hospital->id,
            'specialization' => 'مختبرات سريرية',
            'qualification' => 'اختصاص',
            'license_number' => 'DOC-LAB-123',
            'type' => 'specialist',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'المريضة وسن']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'none',
            'copay_percentage' => 100.00,
            'national_id' => '9876543210',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'visit_date' => now(),
            'visit_time' => '10:00:00',
            'status' => 'active',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص روتيني',
        ]);

        // Package with 2 tests
        $this->package = Package::create([
            'name' => 'Virology',
            'code' => 'PKG-VIR',
            'price' => 40000,
            'is_active' => true,
        ]);

        $this->testInPkg1 = LabTest::create([
            'name' => 'HBsAg screen',
            'code' => 'HBS',
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->testInPkg2 = LabTest::create([
            'name' => 'HCV screen',
            'code' => 'HCV',
            'price' => 15000,
            'is_active' => true,
        ]);

        $this->package->labTests()->attach([$this->testInPkg1->id, $this->testInPkg2->id]);

        // Extra tests outside package
        $this->extraTest1 = LabTest::create([
            'name' => 'Cross match 10',
            'code' => 'XM10',
            'price' => 50000,
            'is_active' => true,
        ]);

        $this->extraTest2 = LabTest::create([
            'name' => 'Cross match 5',
            'code' => 'XM5',
            'price' => 50000,
            'is_active' => true,
        ]);
    }

    public function test_postponing_package_and_paying_extra_tests_charges_exact_extra_tests_amount(): void
    {
        $allTestIds = [
            $this->testInPkg1->id,
            $this->testInPkg2->id,
            $this->extraTest1->id,
            $this->extraTest2->id,
        ];

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل وباقة مختبر',
            'status' => 'pending',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
            'details' => [
                'package_id' => $this->package->id,
                'package_name' => $this->package->name,
                'package_price' => $this->package->price,
                'lab_test_ids' => $allTestIds,
                'tests' => [$this->testInPkg1->name, $this->testInPkg2->name, $this->extraTest1->name, $this->extraTest2->name],
            ],
        ]);

        // Postpone package, pay extra tests
        $payload = [
            'included_request_ids' => [$request->id],
            'payment_method' => 'cash',
            'amount' => 100000, // 50,000 + 50,000
            'item_actions' => [
                $request->id => [
                    'package' => [
                        'pkg_'.$this->package->id => 'postpone',
                    ],
                    'lab' => [
                        $this->extraTest1->id => 'pay',
                        $this->extraTest2->id => 'pay',
                    ],
                ],
            ],
            'item_reasons' => [
                $request->id => [
                    'package' => [
                        $this->package->id => 'time_constraint',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('cashier.request.payment.process', $request->id), $payload);
        $response->assertRedirect();
        $this->assertStringContainsString('/cashier/receipt/', $response->headers->get('Location'));

        // Check payment record
        $payment = Payment::where('payment_type', 'lab')->orderBy('id', 'desc')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(100000, (float) $payment->amount);
        $this->assertEquals(100000, (float) $payment->patient_share);

        // Verify that a postponed request was created for the postponed package
        $postponedReq = MedicalRequest::where('visit_id', $this->visit->id)
            ->where('status', 'postponed')
            ->first();
        $this->assertNotNull($postponedReq);
        $postponedDetails = $postponedReq->details;
        $this->assertEquals($this->package->id, $postponedDetails['package_id']);

        // Verify receipt prints accurate 100,000
        $receiptResponse = $this->actingAs($this->admin)->get(route('cashier.receipt', $payment->id));
        $receiptResponse->assertOk();
        $receiptResponse->assertSee('100,000');
    }

    public function test_paying_package_and_paying_extra_tests_charges_sum_of_both(): void
    {
        $allTestIds = [
            $this->testInPkg1->id,
            $this->testInPkg2->id,
            $this->extraTest1->id,
        ];

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'طلب باقة وتحليل إضافي',
            'status' => 'pending',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
            'details' => [
                'package_id' => $this->package->id,
                'package_name' => $this->package->name,
                'package_price' => $this->package->price,
                'lab_test_ids' => $allTestIds,
            ],
        ]);

        // Pay both package (40,000) and extra test (50,000) -> total 90,000
        $payload = [
            'included_request_ids' => [$request->id],
            'payment_method' => 'cash',
            'amount' => 90000,
            'item_actions' => [
                $request->id => [
                    'package' => [
                        'pkg_'.$this->package->id => 'pay',
                    ],
                    'lab' => [
                        $this->extraTest1->id => 'pay',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('cashier.request.payment.process', $request->id), $payload);
        $response->assertRedirect();
        $this->assertStringContainsString('/cashier/receipt/', $response->headers->get('Location'));

        $payment = Payment::where('payment_type', 'lab')->orderBy('id', 'desc')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(90000, (float) $payment->amount);
    }
}
