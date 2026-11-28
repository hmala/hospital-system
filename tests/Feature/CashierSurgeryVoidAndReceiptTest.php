<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgeryExtraService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierSurgeryVoidAndReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Patient $patient;
    protected Surgery $surgery;

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
            'license_number' => 'HOSP-TEST-001',
        ]);
        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '201',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'code' => 'SURG01',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. جراح فحص']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'hospital_id' => $hospital->id,
            'specialization' => 'جراحة عامة',
            'qualification' => 'استشاري جراحة',
            'license_number' => 'DOC-TEST-123',
            'type' => 'surgeon',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'مريضة تجريبية']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'hi',
            'copay_percentage' => 15.00,
            'national_id' => '1234567890',
        ]);

        $room = Room::create([
            'room_number' => '501',
            'room_type' => 'regular',
            'floor' => 'الطابق الخامس',
            'daily_fee' => 200000,
            'status' => 'available',
            'beds_count' => 1,
            'has_bathroom' => true,
            'has_tv' => true,
            'has_ac' => true,
            'is_active' => true,
        ]);

        $this->surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'surgery_type' => 'ناظور تشخيصي',
            'surgery_category' => 'major',
            'surgery_fee' => 400000,
            'original_surgery_fee' => 400000,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'status' => 'completed',
            'payment_status' => 'paid',
            'surgery_fee_paid' => 'paid',
            'surgery_fee_paid_amount' => 400000,
            'room_id' => $room->id,
            'room_fee' => 0,
            'room_fee_paid_amount' => 0,
            'insurance_type' => 'hi',
            'copay_percentage' => 15.00,
        ]);
    }

    public function test_voiding_surgery_payment_returns_surgery_to_cashier_pending_list(): void
    {
        $extra = SurgeryExtraService::create([
            'surgery_id' => $this->surgery->id,
            'name' => 'خدمة فندقية خاصة',
            'price' => 20000,
            'quantity' => 1,
            'total_amount' => 20000,
            'payment_status' => 'paid',
        ]);

        $payment = Payment::create([
            'patient_id' => $this->patient->id,
            'cashier_id' => $this->admin->id,
            'surgery_id' => $this->surgery->id,
            'receipt_number' => 'REC-TEST-001',
            'amount' => 20000,
            'total_amount' => 20000,
            'patient_share' => 20000,
            'insurance_share' => 0,
            'payment_method' => 'cash',
            'payment_type' => 'surgery',
            'description' => "دفع رسوم العملية الجراحية: ناظور تشخيصي (ID: #{$this->surgery->id})\nالعناصر المدفوعة:\n- خدمة إضافية: خدمة فندقية خاصة - مدفوع: 20,000 د.ع",
            'paid_at' => now(),
        ]);

        $extra->update(['payment_id' => $payment->id]);

        // Void the payment with rebill
        $response = $this->actingAs($this->admin)->post(route('cashier.payments.void', $payment->id), [
            'void_type' => 'rebill',
            'void_reason' => 'إعادة الفتح للتصحيح',
        ]);

        $response->assertRedirect(route('cashier.index'));

        // Refresh surgery and verify it is returned to pending
        $this->surgery->refresh();
        $this->assertEquals('pending', $this->surgery->payment_status);
        $this->assertEquals('pending', $extra->fresh()->payment_status);
        $this->assertNull($extra->fresh()->payment_id);

        // Verify surgery is visible in cashier surgeries index
        $cashierResponse = $this->actingAs($this->admin)->get(route('cashier.surgeries.index'));
        $cashierResponse->assertOk();
        $cashierResponse->assertSee($this->patient->user->name);
    }

    public function test_surgery_receipt_shows_accurate_amounts_without_bloated_fees(): void
    {
        $payment = Payment::create([
            'patient_id' => $this->patient->id,
            'cashier_id' => $this->admin->id,
            'surgery_id' => $this->surgery->id,
            'receipt_number' => 'REC-TEST-002',
            'amount' => 30000,
            'total_amount' => 30000,
            'patient_share' => 30000,
            'insurance_share' => 0,
            'payment_method' => 'cash',
            'payment_type' => 'surgery',
            'description' => "دفع رسوم العملية الجراحية: ناظور تشخيصي (ID: #{$this->surgery->id})\nالعناصر المدفوعة:\n- أجور الغرفة والإقامة (مشمولة بالكامل ضمن باقة العملية - 0 د.ع)\n- خدمة إضافية: خدمات فندقية - مدفوع: 30,000 د.ع\n- حصة المريض: 30,000 د.ع",
            'paid_at' => now(),
        ]);

        $receiptResponse = $this->actingAs($this->admin)->get(route('cashier.receipt', $payment->id));
        $receiptResponse->assertOk();
        $receiptResponse->assertSee('30,000');
        // It should NOT show the unbilled 400,000 surgery fee in the table
        $receiptResponse->assertDontSee('400,000');
    }
}
