<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\HealthInsuranceCategory;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HealthInsuranceClaimsReportTest extends TestCase
{
    use RefreshDatabase;

    protected $hospital;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hospital = Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );
        Permission::findOrCreate('manage health insurance', 'web');
        Permission::findOrCreate('view account statements', 'web');
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('insurance.claims.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_insurance_claims_report()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage health insurance');

        $category = HealthInsuranceCategory::create([
            'name' => 'الفئة A - تجريبية',
            'code' => 'TEST-A',
            'copay_consultation' => 10.00,
            'copay_lab' => 20.00,
            'copay_radiology' => 25.00,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'المريض المشمول بالضمان']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'hi',
            'insurance_card_no' => 'INS-TEST-12345',
            'health_insurance_category_id' => $category->id,
        ]);

        $dept = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'عيادة الباطنية التجريبية',
            'type' => 'internal',
            'room_number' => '102',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. سمير العبيدي']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-' . uniqid(),
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'completed',
            'type' => 'consultation',
            'consultation_fee' => 25000,
        ]);

        $payment = Payment::create([
            'receipt_number' => 'REC-TEST-9988',
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'payment_type' => 'appointment',
            'amount' => 2500,
            'insurance_share' => 22500,
            'insurance_type' => 'hi',
            'insurance_card_no' => 'INS-TEST-12345',
            'copay_percentage' => 10.00,
            'paid_at' => now(),
            'description' => 'كشفية استشارية للمريض المشمول بالضمان',
        ]);

        $response = $this->actingAs($user)->get(route('insurance.claims.index'));

        $response->assertStatus(200);
        $response->assertSee('المريض المشمول بالضمان');
        $response->assertSee('الفئة A - تجريبية');
        $response->assertSee('INS-TEST-12345');
        $response->assertSee('REC-TEST-9988');
        $response->assertSee('22,500');
        $response->assertSee('2,500');
        $response->assertSee('سمير العبيدي');
    }

    public function test_insurance_claims_can_be_filtered_by_category()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage health insurance');

        $catA = HealthInsuranceCategory::create([
            'name' => 'الفئة A - فلترة',
            'code' => 'FA',
            'is_active' => true,
        ]);

        $catB = HealthInsuranceCategory::create([
            'name' => 'الفئة B - فلترة',
            'code' => 'FB',
            'is_active' => true,
        ]);

        $userA = User::factory()->create(['name' => 'مريض الفئة ألف']);
        $patientA = Patient::create([
            'user_id' => $userA->id,
            'health_insurance_category_id' => $catA->id,
            'insurance_type' => 'hi',
        ]);

        $userB = User::factory()->create(['name' => 'مريض الفئة باء']);
        $patientB = Patient::create([
            'user_id' => $userB->id,
            'health_insurance_category_id' => $catB->id,
            'insurance_type' => 'hi',
        ]);

        Payment::create([
            'receipt_number' => 'REC-CAT-A',
            'patient_id' => $patientA->id,
            'payment_type' => 'lab',
            'amount' => 5000,
            'insurance_share' => 15000,
            'paid_at' => now(),
        ]);

        Payment::create([
            'receipt_number' => 'REC-CAT-B',
            'patient_id' => $patientB->id,
            'payment_type' => 'radiology',
            'amount' => 7000,
            'insurance_share' => 21000,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('insurance.claims.index', ['category_id' => $catA->id]));

        $response->assertStatus(200);
        $response->assertSee('مريض الفئة ألف');
        $response->assertDontSee('مريض الفئة باء');
    }

    public function test_insurance_claims_can_be_exported_to_excel()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage health insurance');

        $response = $this->actingAs($user)->get(route('insurance.claims.export'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition') ?? '', 'insurance_claims_')
        );
    }
}
