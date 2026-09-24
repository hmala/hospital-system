<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrescriptionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $doctorUser;
    protected $doctor;
    protected $patient;
    protected $medicine;
    protected $batch;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء الأدوار والصلاحيات
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->doctorUser = User::factory()->create();
        $this->doctorUser->assignRole('doctor');

        $hospitalId = \Illuminate\Support\Facades\DB::table('hospitals')->insertGetId([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'د. حسام',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-LIC-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deptId = \Illuminate\Support\Facades\DB::table('departments')->insertGetId([
            'hospital_id' => $hospitalId,
            'name' => 'قسم الباطنية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $deptId,
            'specialization' => 'باطنية',
            'qualification' => 'دكتوراه باطنية',
            'license_number' => 'MD-' . uniqid(),
            'consultation_fee' => 15000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'علي التميمي', 'phone' => '07701234567']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'blood_type' => 'O+',
        ]);

        // دواء مع وجبة رصيد FEFO
        $this->medicine = Medicine::create([
            'name' => 'Amoxicillin 500mg',
            'generic_name' => 'Amoxicillin',
            'national_code' => '01-C00-001',
            'dosage_form' => 'Capsule',
            'strength' => '500mg',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2,
            'purchase_price' => 2000,
            'sale_price' => 3000,
            'sub_unit_sale_price' => 1500,
            'hi_price' => 2500,
            'is_active' => true,
            'is_insurance_covered' => true,
        ]);

        $this->batch = MedicineBatch::create([
            'medicine_id' => $this->medicine->id,
            'batch_number' => 'BATCH-TEST-01',
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'open_sub_units' => 0,
            'expiry_date' => now()->addMonths(12),
            'status' => 'active',
        ]);
    }

    protected function createVisit(array $overrides = []): Visit
    {
        return Visit::create(array_merge([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->doctor->department_id,
            'visit_date' => today(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'ألم وفحص عام',
            'status' => 'in_progress',
        ], $overrides));
    }

    public function test_doctor_can_prescribe_medication_and_create_e_prescription()
    {
        $visit = $this->createVisit();

        $response = $this->actingAs($this->doctorUser)->put(route('doctor.visits.update', $visit), [
            'diagnosis' => ['description' => 'التهاب قصبات حاد', 'code' => 'J20'],
            'treatment_plan' => 'شرب السوائل وأخذ المضاد',
            'prescribed_medications' => [
                0 => [
                    'name' => 'Amoxicillin 500mg',
                    'medicine_id' => $this->medicine->id,
                    'type' => 'tablet',
                    'dosage' => '500mg',
                    'frequency' => '3',
                    'times' => 'صباح، ظهر، مساء',
                    'duration' => '7',
                    'instructions' => 'بعد الأكل مع كمية وافرة من الماء',
                    'quantity' => 2,
                    'unit_type' => 'main_unit',
                ]
            ]
        ]);

        $response->assertSessionHas('success');

        // التحقق من إنشاء الوصفة الطبية وبنودها
        $this->assertDatabaseHas('prescriptions', [
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
            'diagnosis' => 'التهاب قصبات حاد',
        ]);

        $prescription = Prescription::where('visit_id', $visit->id)->first();
        $this->assertNotNull($prescription);
        $this->assertStringStartsWith('RX-', $prescription->prescription_number);

        $this->assertDatabaseHas('prescription_items', [
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->medicine->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);
    }

    public function test_doctor_can_print_prescription()
    {
        $visit = $this->createVisit();

        $prescription = Prescription::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
            'diagnosis' => 'نزلة معوية',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->medicine->id,
            'quantity' => 1,
            'unit_type' => 'main_unit',
            'dosage_frequency' => '500mg x3',
            'duration_days' => 5,
            'instructions' => 'قبل الطعام',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->doctorUser)->get(route('doctor.visits.prescription.print', $visit));
        $response->assertStatus(200);
        $response->assertSee($prescription->prescription_number);
        $response->assertSee('Amoxicillin 500mg');
        $response->assertSee('علي التميمي');
    }

    public function test_pharmacy_pos_can_fetch_pending_prescriptions()
    {
        $visit = $this->createVisit();

        $prescription = Prescription::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
            'diagnosis' => 'حالة فحص تجريبي',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->medicine->id,
            'quantity' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('pharmacy.pos.pending-prescriptions'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);
        $response->assertJsonFragment([
            'prescription_number' => $prescription->prescription_number,
            'patient_name' => 'علي التميمي',
        ]);
    }

    public function test_pharmacy_pos_can_load_prescription_details_and_stock()
    {
        $visit = $this->createVisit();

        $prescription = Prescription::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
            'diagnosis' => 'التهاب حاد',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->medicine->id,
            'quantity' => 2,
            'unit_type' => 'main_unit',
            'dosage_frequency' => '1x3',
            'duration_days' => 5,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('pharmacy.pos.prescriptions.show', $prescription));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'prescription' => [
                'id' => $prescription->id,
                'patient_name' => 'علي التميمي',
            ]
        ]);
        $response->assertJsonFragment([
            'name' => 'Amoxicillin 500mg',
            'is_in_stock' => true,
            'total_stock' => 10,
        ]);
    }

    public function test_dispensing_e_prescription_deducts_stock_and_marks_prescription_dispensed()
    {
        $visit = $this->createVisit();

        $prescription = Prescription::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
            'diagnosis' => 'التهاب لوزتين',
        ]);

        $pItem = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->medicine->id,
            'quantity' => 2,
            'unit_type' => 'main_unit',
            'status' => 'pending',
        ]);

        $this->assertEquals(10, $this->batch->fresh()->current_quantity);

        // صرف الوصفة عبر POS
        $response = $this->actingAs($this->admin)->postJson(route('pharmacy.pos.store'), [
            'prescription_id' => $prescription->id,
            'patient_id' => $this->patient->id,
            'patient_name' => 'علي التميمي',
            'sale_type' => 'prescription',
            'insurance_type' => 'none',
            'payment_route' => 'pharmacy_cashier',
            'items' => [
                [
                    'item_type' => 'medicine',
                    'medicine_id' => $this->medicine->id,
                    'unit_type' => 'main_unit',
                    'quantity' => 2,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // التحقق من تحديث الوصفة إلى صُرفت
        $this->assertEquals('dispensed', $prescription->fresh()->status);
        $this->assertNotNull($prescription->fresh()->dispensed_at);
        $this->assertNotNull($prescription->fresh()->sale_id);
        $this->assertEquals('dispensed', $pItem->fresh()->status);

        // التحقق من خصم الرصيد بنظام FEFO (10 - 2 = 8)
        $this->assertEquals(8, $this->batch->fresh()->current_quantity);
    }

    public function test_pharmacy_station_can_one_click_dispense_prescription()
    {
        $visit = $this->createVisit();

        $prescription = Prescription::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
            'diagnosis' => 'نزلة صدرية',
        ]);

        $pItem = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->medicine->id,
            'quantity' => 3,
            'unit_type' => 'main_unit',
            'status' => 'pending',
        ]);

        $this->assertEquals(10, $this->batch->fresh()->current_quantity);

        $response = $this->actingAs($this->admin)->postJson(route('pharmacy.pos.prescriptions.dispense', $prescription), [
            'items' => [
                [
                    'id' => $pItem->id,
                    'medicine_id' => $this->medicine->id,
                    'quantity' => 3,
                    'unit_type' => 'main_unit',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'prescription_id' => $prescription->id,
        ]);

        $this->assertEquals('dispensed', $prescription->fresh()->status);
        $this->assertEquals('dispensed', $pItem->fresh()->status);
        $this->assertEquals(7, $this->batch->fresh()->current_quantity); // 10 - 3 = 7
    }

    public function test_doctor_can_view_consultation_show_page()
    {
        $visit = $this->createVisit();

        $response = $this->actingAs($this->doctorUser)->get(route('doctor.visits.show', $visit->id));
        $response->assertStatus(200);
        $response->assertSee('medicine-select2');
    }

    public function test_doctor_can_remove_medication_and_save_cleared_prescription()
    {
        $visit = $this->createVisit();

        // إضافة دواء أولاً
        $this->actingAs($this->doctorUser)->put(route('doctor.visits.update', $visit), [
            'is_prescription_form' => 1,
            'prescribed_medications' => [
                0 => ['name' => 'Amoxicillin 500mg', 'medicine_id' => $this->medicine->id, 'type' => 'tablet', 'dosage' => '500mg']
            ]
        ]);

        $this->assertDatabaseCount('prescription_items', 1);

        // حذف الدواء وإرسال النموذج فارغاً
        $this->actingAs($this->doctorUser)->put(route('doctor.visits.update', $visit), [
            'is_prescription_form' => 1,
        ]);

        $this->assertDatabaseCount('prescription_items', 0);
        $this->assertDatabaseCount('prescribed_medications', 0);
    }

    public function test_pharmacy_can_suggest_alternative_to_doctor()
    {
        $visit = $this->createVisit();

        $prescription = Prescription::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
            'diagnosis' => 'نزلة معوية',
        ]);

        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->medicine->id,
            'quantity' => 1,
            'status' => 'pending',
        ]);

        $altMedicine = Medicine::create([
            'name' => 'Amoxil 500mg (Alternative)',
            'generic_name' => 'Amoxicillin',
            'dosage_form' => 'Capsule',
            'strength' => '500mg',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('pharmacy.pos.items.suggest-alternative', $item), [
            'suggested_medicine_id' => $altMedicine->id,
            'substitution_reason' => 'غير متوفر بالصيدلية - مقترح البديل',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals('pending_approval', $item->fresh()->substitution_status);
        $this->assertEquals($altMedicine->id, $item->fresh()->suggested_medicine_id);
    }

    public function test_doctor_can_approve_alternative_suggestion()
    {
        $visit = $this->createVisit();

        $prescription = Prescription::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
            'diagnosis' => 'التهاب حاد',
        ]);

        $altMedicine = Medicine::create([
            'name' => 'Cefixime 400mg (Alternative)',
            'generic_name' => 'Cefixime',
            'dosage_form' => 'Tablet',
            'strength' => '400mg',
            'is_active' => true,
        ]);

        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->medicine->id,
            'suggested_medicine_id' => $altMedicine->id,
            'substitution_status' => 'pending_approval',
            'substitution_reason' => 'مقترح بديل',
            'quantity' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->doctorUser)->postJson(route('doctor.prescriptions.items.respond-substitution', $item), [
            'action' => 'approve',
            'response_notes' => 'موافق على البديل',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals('approved', $item->fresh()->substitution_status);
        $this->assertEquals($altMedicine->id, $item->fresh()->medicine_id);
        $this->assertEquals($altMedicine->id, $item->fresh()->dispensed_medicine_id);
        $this->assertNotNull($item->fresh()->substitution_responded_at);
    }

    public function test_doctor_can_reject_alternative_suggestion()
    {
        $visit = $this->createVisit();

        $prescription = Prescription::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
            'diagnosis' => 'فحص',
        ]);

        $altMedicine = Medicine::create([
            'name' => 'Alt Med',
            'is_active' => true,
        ]);

        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->medicine->id,
            'suggested_medicine_id' => $altMedicine->id,
            'substitution_status' => 'pending_approval',
            'quantity' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->doctorUser)->postJson(route('doctor.prescriptions.items.respond-substitution', $item), [
            'action' => 'reject',
            'response_notes' => 'مرفوض يرجى إحضار الأصلي',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals('rejected', $item->fresh()->substitution_status);
        $this->assertEquals($this->medicine->id, $item->fresh()->medicine_id);
    }
}

