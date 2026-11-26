<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PharmacySale;
use App\Models\Prescription;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgeryTreatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InpatientPharmacyPosWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctorUser;

    protected User $pharmacistUser;

    protected User $cashierUser;

    protected Department $department;

    protected Room $room;

    protected Patient $patient;

    protected Doctor $doctor;

    protected Medicine $medicine;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء الصلاحيات والأدوار
        Permission::firstOrCreate(['name' => 'view pharmacy', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view surgery stations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view clinical surgery station', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view pharmacy pos', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'dispense pharmacy medications', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view cashier', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process pharmacy payments', 'guard_name' => 'web']);

        $doctorRole = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $doctorRole->givePermissionTo(['view surgery stations', 'view clinical surgery station']);

        $pharmacistRole = Role::firstOrCreate(['name' => 'pharmacist', 'guard_name' => 'web']);
        $pharmacistRole->givePermissionTo(['view pharmacy', 'view pharmacy pos', 'dispense pharmacy medications']);

        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $cashierRole->givePermissionTo(['view cashier', 'process pharmacy payments']);

        $this->doctorUser = User::factory()->create();
        $this->doctorUser->assignRole($doctorRole);

        $this->pharmacistUser = User::factory()->create();
        $this->pharmacistUser->assignRole($pharmacistRole);

        $this->cashierUser = User::factory()->create();
        $this->cashierUser->assignRole($cashierRole);

        $hospital = Hospital::create([
            'name' => 'مستشفى الرقود والصيدلية التخصصي',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-RX-01',
            'phone' => '07700000000',
            'email' => 'hospital@test.com',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '401',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'code' => 'SURG-RX',
        ]);

        $patientUser = User::factory()->create();
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'مريض رقود تجريبي',
            'phone' => '07701112233',
            'gender' => 'male',
            'file_number' => 'PAT-RX-001',
        ]);

        $docUser = User::factory()->create();
        $this->doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'license_number' => 'LIC-SURG-'.uniqid(),
            'qualification' => 'استشاري جراحة',
        ]);

        $this->room = Room::create([
            'room_number' => '502',
            'type' => 'regular',
            'floor' => 'الطابق الخامس',
            'status' => 'available',
            'price_per_day' => 150000,
        ]);

        $this->medicine = Medicine::create([
            'name' => 'Ceftriaxone 1g',
            'generic_name' => 'Ceftriaxone',
            'dosage_form' => 'vial',
            'strength' => '1g',
            'main_unit' => 'فيال',
            'sale_price' => 5000,
            'purchase_price' => 3000,
            'is_active' => true,
        ]);
    }

    public function test_inpatient_prescribed_treatment_generates_prescription_and_flows_to_pharmacy_and_cashier(): void
    {
        // 1. إنشاء عملية جراحية مع غرفة (تخصيص مباشر)
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgery_type' => 'استئصال الزائدة الدودية',
            'status' => 'waiting',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'room_checkin_at' => now(),
            'room_checkin_by' => $this->doctorUser->id,
        ]);

        // 2. الطبيب يصف دواء للمريض بالرقود
        $response = $this->actingAs($this->doctorUser)
            ->post(route('clinical-station.treatments.store', $surgery), [
                'medicine_id' => $this->medicine->id,
                'description' => 'Ceftriaxone 1g vial',
                'dosage' => '1g',
                'route' => 'IV',
                'frequency_preset' => 'q12h',
                'duration_value' => 3,
                'duration_unit' => 'days',
                'admin_notes' => 'بعد اختبار الحساسية',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // التحقق من إنشاء سجل العلاج وحالته
        $treatment = SurgeryTreatment::where('surgery_id', $surgery->id)->first();
        $this->assertNotNull($treatment);
        $this->assertEquals('sent_to_pharmacy', $treatment->pharmacy_status);
        $this->assertNotNull($treatment->prescription_item_id);

        // التحقق من إنشاء الوصفة الطبية المرتبطة بالعملية
        $prescription = Prescription::where('surgery_id', $surgery->id)->first();
        $this->assertNotNull($prescription);
        $this->assertEquals('pending', $prescription->status);
        $this->assertEquals($surgery->patient_id, $prescription->patient_id);

        // 3. فحص ظهور الوصفة في طابور صيدلية المستشفى (Pharmacy POS queue)
        $posQueueResponse = $this->actingAs($this->pharmacistUser)
            ->get(route('pharmacy.pos.pending-prescriptions'));

        $posQueueResponse->assertStatus(200);
        $posQueueResponse->assertJsonFragment([
            'prescription_number' => $prescription->prescription_number,
            'is_inpatient' => true,
            'room_number' => '502',
        ]);

        // 4. الصيدلي يجهز الوصفة ويصرفها للكاشير المركزي
        $dispenseResponse = $this->actingAs($this->pharmacistUser)
            ->postJson(route('pharmacy.pos.prescriptions.dispense', $prescription), [
                'items' => [
                    [
                        'id' => $treatment->prescription_item_id,
                        'medicine_id' => $this->medicine->id,
                        'quantity' => 6,
                        'unit_type' => 'main_unit',
                        'is_available' => true,
                    ],
                ],
            ]);

        $dispenseResponse->assertStatus(200);
        $dispenseResponse->assertJsonPath('success', true);

        // التحقق من إنشاء سجل مبيعات الصيدلية من نوع inpatient ومحول للكاشير
        $sale = PharmacySale::where('surgery_id', $surgery->id)->first();
        $this->assertNotNull($sale);
        $this->assertEquals('inpatient', $sale->sale_type);
        $this->assertEquals('pending_cashier', $sale->payment_status);
        $this->assertEquals('central_cashier', $sale->payment_route);

        // التحقق من تحديث حالة دواء الرقود إلى بانتظار الكاشير
        $treatment->refresh();
        $this->assertEquals('pending_cashier', $treatment->pharmacy_status);

        // 5. في شاشة الكاشير المركزي: يظهر للمحاسب
        $cashierIndexResponse = $this->actingAs($this->cashierUser)
            ->get(route('cashier.index'));

        $cashierIndexResponse->assertStatus(200);
        $cashierIndexResponse->assertSee($sale->invoice_number);

        // تسديد الفاتورة في الكاشير
        $sale->update(['payment_status' => 'paid']);

        // 6. الصيدلي يؤكد تسليم الأدوية للردهة بعد السداد
        $handoffResponse = $this->actingAs($this->pharmacistUser)
            ->postJson(route('pharmacy.pos.sales.handoff', $sale));

        $handoffResponse->assertStatus(200);
        $handoffResponse->assertJsonPath('success', true);

        // التحقق النهائي من اكتمال صرف وتسليم الدواء
        $sale->refresh();
        $this->assertEquals('dispensed', $sale->dispensing_status);

        $prescription->refresh();
        $this->assertEquals('dispensed', $prescription->status);

        $treatment->refresh();
        $this->assertEquals('dispensed', $treatment->pharmacy_status);
    }
}
