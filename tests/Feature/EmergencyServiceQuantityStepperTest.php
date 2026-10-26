<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Emergency;
use App\Models\EmergencyService;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyServiceQuantityStepperTest extends TestCase
{
    use RefreshDatabase;

    protected $hospital;

    protected $department;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'د. حسام',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-LIC-'.uniqid(),
        ]);

        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'قسم الطوارئ',
            'type' => 'emergency',
            'room_number' => '101',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
        ]);
    }

    public function test_emergency_show_renders_quantity_stepper_for_services(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $patientUser = User::factory()->create(['name' => 'أحمد العراقي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'medical_number' => 'MED-TEST-'.rand(1000, 9999),
            'birth_date' => '1995-01-01',
            'gender' => 'male',
            'insurance_type' => 'none',
        ]);

        $service = EmergencyService::create([
            'name' => 'زرق إبرة عضلية',
            'code' => 'INJ-01',
            'price' => 5000,
            'is_active' => true,
        ]);

        $emergency = Emergency::create([
            'patient_id' => $patient->id,
            'emergency_type' => 'medical',
            'admission_time' => now(),
            'symptoms' => 'ألم بالبطن',
            'priority' => 'urgent',
            'arrival_time' => now(),
            'status' => 'under_treatment',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('emergency.show', $emergency));
        $response->assertStatus(200);
        $response->assertSee('service-qty-group');
        $response->assertSee('service-qty-input');
        $response->assertSee('btn-service-plus');
        $response->assertSee('btn-service-minus');
        $response->assertSee('زرق إبرة عضلية');
    }

    public function test_emergency_update_medical_saves_service_quantities(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $patientUser = User::factory()->create(['name' => 'سارة كريم']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'medical_number' => 'MED-TEST-'.rand(1000, 9999),
            'birth_date' => '1992-05-10',
            'gender' => 'female',
            'insurance_type' => 'none',
        ]);

        $service = EmergencyService::create([
            'name' => 'زرق إبرة وريدية',
            'code' => 'INJ-IV',
            'price' => 7000,
            'is_active' => true,
        ]);

        $emergency = Emergency::create([
            'patient_id' => $patient->id,
            'emergency_type' => 'medical',
            'admission_time' => now(),
            'symptoms' => 'ألم بالبطن',
            'priority' => 'urgent',
            'arrival_time' => now(),
            'status' => 'under_treatment',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('emergency.update-medical', $emergency), [
            'diagnosis' => 'نزلة معوية حادة',
            'service_ids' => [$service->id],
            'service_quantities' => [
                $service->id => 3,
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('emergency_emergency_service', [
            'emergency_id' => $emergency->id,
            'emergency_service_id' => $service->id,
            'quantity' => 3,
        ]);

        // الفاتورة الموحدة يجب أن تكون 3 × 7,000 = 21,000 د.ع
        $payment = Payment::where('emergency_id', $emergency->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(21000, (float) $payment->total_amount);
        $this->assertEquals(21000, (float) $payment->patient_share);
        $this->assertStringContainsString('(×3)', $payment->description);
        $this->assertEquals(21000, $emergency->fresh()->getUnpaidAmount());
    }
}
