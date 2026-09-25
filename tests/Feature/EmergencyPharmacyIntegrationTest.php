<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Emergency;
use App\Models\EmergencyPatient;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyPharmacyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pharmacist;
    protected User $emergencyDoctor;
    protected Doctor $doctorRecord;
    protected Emergency $emergency;
    protected Medicine $medicine;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء الأدوار والصلاحيات
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $pharmacyRole = Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'emergency_staff', 'guard_name' => 'web']);

        $viewPharmacyPerm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view pharmacy', 'guard_name' => 'web']);
        $pharmacyRole->givePermissionTo($viewPharmacyPerm);

        $this->admin = User::factory()->create(['email' => 'admin_er@test.com']);
        $this->admin->assignRole('admin');

        $this->pharmacist = User::factory()->create(['email' => 'pharmacist_er@test.com']);
        $this->pharmacist->assignRole('pharmacy_staff');

        $hospital = \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء الدولي'],
            [
                'owner_name' => 'د. أحمد',
                'phone' => '07700000000',
                'email' => 'hospital@test.com',
                'address' => 'بغداد',
                'license_number' => 'LIC-12345',
            ]
        );

        $dept = \App\Models\Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الطوارئ',
            'room_number' => 'ER-101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'type' => 'emergency',
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['email' => 'doc_er@test.com']);
        $doctorUser->assignRole('doctor');
        $this->doctorRecord = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'طوارئ',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-9988',
            'consultation_fee' => 25000,
            'type' => 'emergency',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'حيدر علي الطائي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'phone' => '07701234567',
        ]);

        $this->emergency = Emergency::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctorRecord->id,
            'priority' => 'critical',
            'emergency_type' => 'general',
            'symptoms' => 'ألم حاد مفاجئ',
            'status' => 'in_progress',
            'room_assigned' => 'سرير طوارئ 04',
            'admission_time' => now(),
        ]);

        $this->medicine = Medicine::create([
            'name' => 'Paracetamol IV Infusion 1000mg',
            'generic_name' => 'Paracetamol',
            'dosage_form' => 'Infusion',
            'strength' => '1000mg/100ml',
            'sale_price' => 5000,
            'main_unit' => 'قارورة',
            'is_active' => true,
        ]);
    }

    public function test_can_submit_emergency_treatment_and_auto_generate_stat_prescription()
    {
        $response = $this->actingAs($this->admin)->postJson(route('emergency.treatments.store', $this->emergency), [
            'treatments' => [
                [
                    'treatment_type' => 'drip',
                    'description' => 'Paracetamol IV Infusion 1000mg',
                    'medicine_id' => $this->medicine->id,
                    'quantity' => 2,
                    'unit_type' => 'main_unit',
                    'dosage_frequency' => 'جرعة وريدية فورية STAT',
                    'instructions' => 'تسريب وريدي بطيء',
                    'status' => 'in_progress',
                ],
                [
                    'treatment_type' => 'oxygen',
                    'description' => 'أكسجين عبر القناع 5L/min',
                    'status' => 'in_progress',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // التحقق من إنشاء سجلات علاج الطوارئ
        $this->assertDatabaseHas('emergency_treatments', [
            'emergency_id' => $this->emergency->id,
            'description' => 'Paracetamol IV Infusion 1000mg',
            'treatment_type' => 'drip',
        ]);
        $this->assertDatabaseHas('emergency_treatments', [
            'emergency_id' => $this->emergency->id,
            'description' => 'أكسجين عبر القناع 5L/min',
            'treatment_type' => 'oxygen',
        ]);

        // التحقق من إنشاء وصفة إلكترونية فورية STAT مرتبطة بالطوارئ
        $this->assertDatabaseHas('prescriptions', [
            'emergency_id' => $this->emergency->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('prescription_items', [
            'medicine_id' => $this->medicine->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);
    }

    public function test_pharmacy_pos_queue_displays_stat_emergency_prescriptions_with_priority()
    {
        // إنشاء طلب علاج طوارئ
        $this->actingAs($this->admin)->postJson(route('emergency.treatments.store', $this->emergency), [
            'treatments' => [
                [
                    'treatment_type' => 'medication',
                    'description' => $this->medicine->name,
                    'medicine_id' => $this->medicine->id,
                    'quantity' => 1,
                    'dosage_frequency' => 'STAT',
                ]
            ]
        ]);

        // الصيدلي يطلب قائمة الوصفات المعلقة
        $response = $this->actingAs($this->pharmacist)->getJson(route('pharmacy.pos.pending-prescriptions'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $prescriptions = $response->json('prescriptions');
        $this->assertNotEmpty($prescriptions);
        
        $erRx = collect($prescriptions)->firstWhere('emergency_id', $this->emergency->id);
        $this->assertNotNull($erRx);
        $this->assertTrue($erRx['is_emergency']);
        $this->assertEquals('حيدر علي الطائي', $erRx['patient_name']);
        $this->assertEquals('سرير طوارئ 04', $erRx['emergency_room']);
    }

    public function test_pharmacy_can_dispense_emergency_prescription_and_update_treatment_status()
    {
        // 1. إنشاء الوصفة
        $this->actingAs($this->admin)->postJson(route('emergency.treatments.store', $this->emergency), [
            'treatments' => [
                [
                    'treatment_type' => 'medication',
                    'description' => $this->medicine->name,
                    'medicine_id' => $this->medicine->id,
                    'quantity' => 1,
                    'dosage_frequency' => 'STAT',
                ]
            ]
        ]);

        $prescription = Prescription::where('emergency_id', $this->emergency->id)->first();
        $this->assertNotNull($prescription);

        // 2. صرف الوصفة من الصيدلية
        $response = $this->actingAs($this->pharmacist)->postJson(route('pharmacy.pos.prescriptions.dispense', $prescription), [
            'items' => [
                [
                    'id' => $prescription->items->first()->id,
                    'is_available' => true,
                    'quantity' => 1,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'dispensed',
            'is_emergency' => true,
        ]);

        // 3. التحقق من تحديث الوصفة وعلاجات الطوارئ إلى مكتملة ومصروفة
        $this->assertEquals('dispensed', $prescription->fresh()->status);
        $this->assertDatabaseHas('emergency_treatments', [
            'emergency_id' => $this->emergency->id,
            'status' => 'completed',
        ]);
    }
}
