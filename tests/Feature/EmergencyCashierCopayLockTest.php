<?php

namespace Tests\Feature;

use App\Models\Emergency;
use App\Models\EmergencyService;
use App\Models\HealthInsuranceCategory;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyCashierCopayLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_emergency_payment_form_enforces_category_copay_and_readonly_amount()
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        // إنشاء فئة الضمان H (طوارئ 0%)
        $catH = HealthInsuranceCategory::create([
            'code' => 'H',
            'name' => 'الفئة H',
            'emergency_copay' => 0.0,
            'lab_copay' => 25.0,
            'radiology_copay' => 25.0,
            'surgery_copay' => 25.0,
            'consultation_copay' => 10.0,
            'medication_copay' => 25.0,
            'support_services_copay' => 25.0,
            'dental_copay' => 25.0,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'فاطمة تجريبية']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'female',
            'date_of_birth' => '1995-01-01',
            'insurance_type' => 'hi',
            'health_insurance_category_id' => $catH->id,
            'copay_percentage' => 15.0, // نسبة قديمة
            'insurance_card_no' => '444-TEST-123',
        ]);

        $service1 = EmergencyService::create([
            'name' => 'خدمة تمريض 1',
            'price' => 25000,
            'hi_price' => 25000,
            'is_hi_active' => true,
            'category' => 'طوارئ عامة',
            'is_active' => true,
        ]);

        $service2 = EmergencyService::create([
            'name' => 'خدمة تمريض 2',
            'price' => 50000,
            'hi_price' => 50000,
            'is_hi_active' => true,
            'category' => 'طوارئ عامة',
            'is_active' => true,
        ]);

        $emergency = Emergency::create([
            'patient_id' => $patient->id,
            'emergency_type' => 'medical',
            'is_insured' => true,
            'insurance_type' => 'hi',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'symptoms' => 'طوارئ عامة',
            'admission_time' => now(),
            'arrival_time' => now(),
            'payment_status' => 'pending',
        ]);

        $emergency->services()->attach([
            $service1->id => ['quantity' => 1],
            $service2->id => ['quantity' => 1],
        ]);

        $payment = Payment::create([
            'emergency_id' => $emergency->id,
            'patient_id' => $patient->id,
            'amount' => 0.0,
            'total_amount' => 75000.0,
            'patient_share' => 0.0,
            'insurance_share' => 75000.0,
            'copay_percentage' => 0.0,
            'insurance_type' => 'hi',
            'payment_type' => 'emergency',
            'payment_method' => 'insurance',
            'paid_at' => null,
        ]);

        // 1. فتح استمارة الدفع والتأكد من أنها تعرض المبلغ 0 ومقفول readonly
        $response = $this->actingAs($admin)->get(route('cashier.emergency.payment.form', $payment));
        $response->assertStatus(200);
        $response->assertSee('readonly', false);
        $response->assertSee('محسوب آلياً ومطابق للوصل');

        // 2. معالجة الدفع والتأكد من فرض نسبة التحمل 0% وحصة المريض 0 وحصة الضمان 75,000
        $postResponse = $this->actingAs($admin)->post(route('cashier.emergency.payment.process', $payment), [
            'payment_method' => 'insurance',
            'amount' => 0,
        ]);

        $postResponse->assertRedirect(route('cashier.receipt', $payment));

        $payment->refresh();
        $this->assertEquals(0.0, (float) $payment->amount);
        $this->assertEquals(0.0, (float) $payment->patient_share);
        $this->assertEquals(75000.0, (float) $payment->insurance_share);
        $this->assertEquals(0.0, (float) $payment->copay_percentage);
        $this->assertNotNull($payment->paid_at);
        $this->assertEquals('paid', $emergency->fresh()->payment_status);
    }
}
