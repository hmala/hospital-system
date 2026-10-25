<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\RadiologyType;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierPrintReceiptItemPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_receipt_shows_individual_patient_fee_per_service_not_grand_total()
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);

        $hospital = \App\Models\Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $dep = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);
        $docUser = User::factory()->create(['name' => 'د. فاحص']);
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dep->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-TEST-99',
            'type' => 'consultant',
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        // 1. Lab Test with Insurance HI
        $labTest = LabTest::create([
            'name' => 'Lipid Profile Test',
            'code' => 'LIPID01',
            'price' => 20000,
            'hi_price' => 20000,
            'is_hi_active' => true,
            'department_id' => $dep->id,
        ]);

        // 2. Radiology Type with Insurance HI
        $radType = RadiologyType::create([
            'name' => 'Chest X-Ray',
            'code' => 'CXR01',
            'base_price' => 30000,
            'hi_price' => 30000,
            'is_hi_active' => true,
        ]);

        $visit = \App\Models\Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dep->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحوصات طبية',
            'status' => 'in_progress',
        ]);

        // Medical Requests
        $labReq = MedicalRequest::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبر',
            'status' => 'completed',
            'payment_status' => 'paid',
            'details' => ['lab_test_ids' => [$labTest->id]],
        ]);

        $radReq = MedicalRequest::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'type' => 'radiology',
            'description' => 'طلب فحص أشعة',
            'status' => 'completed',
            'payment_status' => 'paid',
            'details' => ['radiology_type_ids' => [$radType->id]],
        ]);

        // Payment with 25% copay
        // Lab: approved 20,000 -> patient 5,000
        // Rad: approved 30,000 -> patient 7,500
        // Total patient paid = 12,500
        $payment = Payment::create([
            'patient_id' => $patient->id,
            'cashier_id' => $admin->id,
            'receipt_number' => '10999',
            'amount' => 12500,
            'total_amount' => 50000,
            'patient_share' => 12500,
            'insurance_share' => 37500,
            'insurance_type' => 'hi',
            'copay_percentage' => 25.0,
            'payment_method' => 'cash',
            'payment_type' => 'lab',
            'paid_at' => now(),
        ]);

        $labReq->update(['payment_id' => $payment->id]);
        $radReq->update(['payment_id' => $payment->id]);

        $response = $this->actingAs($admin)->get(route('cashier.receipt.print', $payment->id));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Must show individual patient fee
        $this->assertStringContainsString('5,000', $html, 'Expected Lab Test patient fee of 5,000 to be in receipt');
        $this->assertStringContainsString('7,500', $html, 'Expected Radiology patient fee of 7,500 to be in receipt');

        // Check that the grand total (12,500) only appears for total row, not in individual item rows
        preg_match_all('/<td class="col-price">([^<]+)<\/td>/', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        $this->assertContains('5,000', $matches[1], 'Item row price must be 5,000');
        $this->assertContains('7,500', $matches[1], 'Item row price must be 7,500');
        $this->assertNotContains('12,500', $matches[1], 'Individual item row must NOT contain the grand total 12,500');
    }

    public function test_receipt_web_view_shows_correct_insurance_pricing_and_patient_fee()
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        $patientUser = User::factory()->create(['name' => 'حسام عبدالستار']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1995-01-01',
            'insurance_type' => 'hi',
            'copay_percentage' => 25.0,
        ]);

        $hospital = \App\Models\Hospital::first() ?? \App\Models\Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-002',
        ]);

        $dep = Department::first() ?? Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);
        $docUser = User::factory()->create(['name' => 'د. قمر']);
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dep->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-TEST-100',
            'type' => 'consultant',
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        // Private price 30,000, but HI price 12,000
        $labTest = LabTest::create([
            'name' => 'Vitamin B12 Test',
            'code' => 'VB12',
            'price' => 30000,
            'hi_price' => 12000,
            'is_hi_active' => true,
            'department_id' => $dep->id,
        ]);

        $visit = \App\Models\Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dep->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'تحليل دم',
            'status' => 'in_progress',
        ]);

        $labReq = MedicalRequest::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبر',
            'status' => 'completed',
            'payment_status' => 'paid',
            'insurance_type' => 'hi',
            'details' => ['lab_test_ids' => [$labTest->id]],
        ]);

        // 25% of 12,000 is 3,000 (not 25% of 30,000 which is 7,500)
        $payment = Payment::create([
            'patient_id' => $patient->id,
            'cashier_id' => $admin->id,
            'receipt_number' => '50025',
            'amount' => 3000,
            'total_amount' => 12000,
            'patient_share' => 3000,
            'insurance_share' => 9000,
            'insurance_type' => 'hi',
            'copay_percentage' => 25.0,
            'payment_method' => 'cash',
            'payment_type' => 'lab',
            'paid_at' => now(),
        ]);

        $labReq->update(['payment_id' => $payment->id]);

        $response = $this->actingAs($admin)->get(route('cashier.receipt', $payment->id));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Must show insurance approved (12,000) and patient share (3,000)
        $this->assertStringContainsString('12,000', $html, 'Expected approved price 12,000 in receipt rows');
        $this->assertStringContainsString('3,000', $html, 'Expected patient share 3,000 in receipt rows');
        $this->assertStringContainsString('9,000', $html, 'Expected insurance share 9,000 in receipt rows');

        // Must NOT show private price calculations (30,000 or 7,500)
        $this->assertStringNotContainsString('30,000', $html, 'Private price 30,000 must NOT appear in insured receipt rows');
        $this->assertStringNotContainsString('7,500', $html, 'Private 25% fee 7,500 must NOT appear in insured receipt rows');
    }
}
