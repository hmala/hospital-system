<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\EmergencyService;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorECGRequestPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $cashierUser;

    protected $doctorUser;

    protected $patientUser;

    protected $patient;

    protected $doctor;

    protected $visit;

    protected $ecgService;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'process medical requests payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process consultation payments', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-001',
            'phone' => '07700000000',
            'email' => 'info@hospital.com',
            'address' => 'بغداد',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'الباطنية والقلبية',
            'code' => 'CARD',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 20000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $this->cashierUser = User::factory()->create();
        $this->cashierUser->assignRole('cashier');
        $this->cashierUser->givePermissionTo('process medical requests payments');

        $this->doctorUser = User::factory()->create();
        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'أمراض القلب',
            'qualification' => 'بورد عراقي',
            'license_number' => 'DOC-CARD-'.uniqid(),
            'is_active' => true,
        ]);

        $this->patientUser = User::factory()->create();
        $this->patient = Patient::create([
            'user_id' => $this->patientUser->id,
            'insurance_type' => 'hi',
            'copay_percentage' => 10.00,
        ]);

        $appointment = \App\Models\Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $dept->id,
            'consultation_fee' => 20000,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'insurance_type' => 'hi',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $dept->id,
            'appointment_id' => $appointment->id,
            'visit_type' => 'consultation',
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'chief_complaint' => 'ألم في الصدر وخفقان',
            'status' => 'in_progress',
        ]);

        $this->ecgService = EmergencyService::create([
            'id' => 93,
            'name' => 'تخطيط قلب',
            'category' => 'طوارئ عامة',
            'price' => 15000.00,
            'hi_price' => 10000.00,
            'is_hi_active' => true,
            'is_active' => true,
        ]);
    }

    public function test_cashier_can_view_ecg_nursing_request_with_correct_hi_pricing()
    {
        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'nursing',
            'status' => 'pending',
            'description' => 'تخطيط قلب',
            'details' => json_encode([
                'description' => 'تخطيط قلب',
                'priority' => 'urgent',
            ]),
        ]);

        $response = $this->actingAs($this->cashierUser)
            ->get(route('cashier.request.payment.form', $request->id));

        $response->assertStatus(200);
        $response->assertSee('تخطيط قلب');
        $response->assertSee('10,000'); // HI approved price is 10,000
    }

    public function test_cashier_can_process_ecg_nursing_request_under_hi_insurance()
    {
        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'nursing',
            'status' => 'pending',
            'description' => 'تخطيط قلب',
            'details' => json_encode([
                'description' => 'تخطيط قلب',
                'priority' => 'urgent',
            ]),
        ]);

        $postData = [
            'item_action' => [
                $request->id => [
                    'nursing' => [
                        $this->ecgService->id => 'pay',
                    ],
                ],
            ],
            'insurance_type' => 'hi',
            'copay_percentage' => 10.00,
            'amount' => 1000.00,
            'payment_method' => 'cash',
        ];

        $response = $this->actingAs($this->cashierUser)
            ->post(route('cashier.request.payment.process', $request->id), $postData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $payment = Payment::latest()->first();
        $this->assertNotNull($payment);
        $this->assertEquals('hi', $payment->insurance_type);
        $this->assertEquals(10000.00, (float) $payment->total_amount);
        $this->assertEquals(1000.00, (float) $payment->patient_share); // 10% of 10,000
        $this->assertEquals(9000.00, (float) $payment->insurance_share);
        $this->assertEquals(1000.00, (float) $payment->amount); // paid in cash by patient
    }
}
