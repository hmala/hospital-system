<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ConsultationRevenue;
use App\Models\Doctor;
use App\Models\DoctorFinancialAccount;
use App\Models\DoctorDue;
use App\Models\Emergency;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyConsultationPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $consultantDoctor;
    protected $patient;
    protected $emergency;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $hospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'د. حسام',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-LIC-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deptId = DB::table('departments')->insertGetId([
            'hospital_id' => $hospitalId,
            'name' => 'قسم الباطنية والاستشارية',
            'type' => 'internal',
            'room_number' => 'C-1',
            'consultation_fee' => 50000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $docUser = User::factory()->create(['name' => 'د. قمر سعد']);
        $docUser->assignRole('doctor');

        $docId = DB::table('doctors')->insertGetId([
            'user_id' => $docUser->id,
            'department_id' => $deptId,
            'phone' => '07712345678',
            'specialization' => 'أخصائي باطنية وقلبية',
            'qualification' => 'بورد عربي',
            'license_number' => 'DOC-LIC-' . uniqid(),
            'consultation_fee' => 50000,
            'is_active' => true,
            'type' => 'consultant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->consultantDoctor = Doctor::find($docId);

        $patUser = User::factory()->create(['name' => 'علي محمد حسن']);
        $this->patient = Patient::create([
            'user_id' => $patUser->id,
            'medical_number' => 'MED-ER-' . rand(1000, 9999),
            'blood_group' => 'O+',
        ]);

        $this->emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->consultantDoctor->id,
            'admission_time' => now(),
            'priority' => 'yellow',
            'emergency_type' => 'medical',
            'symptoms' => 'ألم حاد بالصدر',
            'status' => 'in_progress',
            'payment_status' => 'pending',
        ]);
    }

    public function test_paying_emergency_consultation_allocates_40k_to_doctor_and_10k_to_hospital(): void
    {
        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->consultantDoctor->id,
            'department_id' => $this->consultantDoctor->department_id,
            'emergency_id' => $this->emergency->id,
            'appointment_date' => now(),
            'reason' => 'استشارة طوارئ باطنية عاجلة',
            'consultation_fee' => 50000,
            'duration' => 30,
            'status' => 'confirmed',
            'type' => 'emergency_consultation',
            'payment_status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        $payment = Payment::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'amount' => 50000,
            'total_amount' => 50000,
            'patient_share' => 50000,
            'insurance_share' => 0,
            'insurance_type' => 'none',
            'payment_type' => 'emergency',
            'payment_method' => 'cash',
            'receipt_number' => Payment::generateReceiptNumber(),
            'description' => 'رسوم استشارة طوارئ',
            'paid_at' => null,
        ]);

        // Process payment at cashier
        $response = $this->actingAs($this->admin)->post(route('cashier.emergency.payment.process', $payment), [
            'payment_method' => 'cash',
            'amount' => 50000,
            'notes' => 'تسديد استشارة الطوارئ',
        ]);

        $response->assertRedirect(route('cashier.index'));

        // 1. Verify Appointment is paid
        $appointment->refresh();
        $this->assertEquals('paid', $appointment->payment_status);
        $this->assertEquals($payment->id, $appointment->payment_id);

        // 2. Verify ConsultationRevenue is created with 40k doctor and 10k hospital
        $revenue = ConsultationRevenue::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($revenue);
        $this->assertEquals(50000, (float)$revenue->total_amount);
        $this->assertEquals(40000, (float)$revenue->doctor_share);
        $this->assertEquals(10000, (float)$revenue->hospital_share);
        $this->assertEquals(80.0, (float)$revenue->doctor_percentage);
        $this->assertEquals($this->consultantDoctor->id, $revenue->doctor_id);

        // 3. Verify DoctorDue is created with 40,000 IQD
        $due = DoctorDue::where('doctor_id', $this->consultantDoctor->id)->where('payment_id', $payment->id)->first();
        $this->assertNotNull($due);
        $this->assertEquals(40000, (float)$due->amount);
        $this->assertEquals('pending', $due->status);

        // 4. Verify DoctorAccount balance is increased by 40,000
        $docAccount = DoctorFinancialAccount::where('doctor_id', $this->consultantDoctor->id)->first();
        $this->assertNotNull($docAccount);
        $this->assertEquals(40000, (float)$docAccount->balance);

        // 5. Verify Receipt print contains doctor name and service description
        $receiptResponse = $this->actingAs($this->admin)->get(route('cashier.receipt.print', $payment));
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('د. قمر سعد');
        $receiptResponse->assertSee('استشارة طبية طوارئ');
        $receiptResponse->assertSee('50,000');
    }
}
