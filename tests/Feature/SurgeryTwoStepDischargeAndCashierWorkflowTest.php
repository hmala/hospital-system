<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgicalOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurgeryTwoStepDischargeAndCashierWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Hospital $hospital;

    protected User $admin;

    protected User $cashier;

    protected Doctor $doctor;

    protected Department $department;

    protected Room $room101;

    protected SurgicalOperation $operation;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'view surgeries cashier']);
        Permission::firstOrCreate(['name' => 'control surgeries']);
        Permission::firstOrCreate(['name' => 'edit surgeries']);
        Permission::firstOrCreate(['name' => 'assign surgery room']);
        Permission::firstOrCreate(['name' => 'process cashier payments']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);
        $cashierRole->givePermissionTo(['process cashier payments', 'view surgeries cashier']);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الشفاء التخصصي',
            'owner_name' => 'د. أحمد',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-TEST-001',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cashier = User::factory()->create();
        $this->cashier->assignRole('cashier');

        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgical',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'code' => 'GEN-SURG',
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create();
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'qualification' => 'استشاري جراحة',
            'license_number' => 'DOC-SURG-001',
            'is_active' => true,
        ]);

        $this->room101 = Room::create([
            'room_number' => '101',
            'room_type' => 'regular',
            'status' => 'available',
            'daily_fee' => 50000,
            'floor' => 1,
        ]);

        $this->operation = SurgicalOperation::create([
            'name' => 'استئصال الزائدة الدودية بالمنظار',
            'code' => 'APP-01',
            'category' => 'major',
            'default_price' => 300000,
            'department_id' => $this->department->id,
        ]);
    }

    protected function createSurgery(string $status = 'completed', ?Room $room = null, float $fee = 300000): Surgery
    {
        $patientUser = User::factory()->create();
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'insurance_type' => 'none',
        ]);

        if ($room) {
            $room->update(['status' => 'occupied']);
        }

        return Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'status' => $status,
            'room_id' => $room?->id,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'surgery_fee' => $fee,
            'original_surgery_fee' => $fee,
            'surgery_fee_paid_amount' => 0,
            'room_fee_paid_amount' => 0,
            'payment_status' => 'pending',
            'billing_status' => 'approved',
        ]);
    }

    public function test_medical_discharge_frees_room_and_sets_medical_discharged_at(): void
    {
        $surgery = $this->createSurgery('completed', $this->room101);
        $this->assertEquals('occupied', $this->room101->fresh()->status);
        $this->assertNull($surgery->medical_discharged_at);
        $this->assertNull($surgery->discharged_at);

        $response = $this->actingAs($this->admin)
            ->post(route('surgeries.medical-discharge', $surgery), [
                'medical_discharge_notes' => 'المريض مستقر ويمكنه الخروج للمنزل بعد تصفية الحساب',
            ]);

        $response->assertRedirect();
        $surgery->refresh();

        $this->assertNotNull($surgery->medical_discharged_at);
        $this->assertEquals($this->admin->id, $surgery->medical_discharge_by);
        $this->assertNull($surgery->discharged_at); // لم يخرّج نهائياً بعد
        $this->assertEquals('available', $this->room101->fresh()->status); // السرير تحرر فوراً
    }

    public function test_undo_medical_discharge_reverts_state_and_reclaims_available_room(): void
    {
        $surgery = $this->createSurgery('completed', $this->room101);

        $this->actingAs($this->admin)->post(route('surgeries.medical-discharge', $surgery));
        $this->assertNotNull($surgery->fresh()->medical_discharged_at);
        $this->assertEquals('available', $this->room101->fresh()->status);

        $response = $this->actingAs($this->admin)->post(route('surgeries.undo-medical-discharge', $surgery));
        $response->assertRedirect();
        $surgery->refresh();

        $this->assertNull($surgery->medical_discharged_at);
        $this->assertEquals('occupied', $this->room101->fresh()->status); // الغرفة شُغلت ثانية
    }

    public function test_cashier_hub_shows_inpatient_and_final_discharge_flow(): void
    {
        $surgery = $this->createSurgery('completed', $this->room101, 300000);
        // دفع العملية بالكامل
        $surgery->update([
            'surgery_fee_paid_amount' => 300000,
            'room_fee_paid_amount' => 50000,
            'payment_status' => 'paid',
        ]);

        // فتح شاشة كاشير العمليات
        $response = $this->actingAs($this->cashier)->get(route('cashier.surgeries.index'));
        $response->assertStatus(200);
        $response->assertViewHas('inpatientSurgeries');

        // محاولة التخريج قبل إذن المقيم يجب أن تُحظر
        $blockedResponse = $this->actingAs($this->cashier)->post(route('cashier.surgeries.final-discharge', $surgery));
        $blockedResponse->assertSessionHas('error');
        $this->assertNull($surgery->fresh()->discharged_at);

        // صدور إذن المقيم الطبي وتفريغ السرير
        $surgery->update(['medical_discharged_at' => now()]);

        // الآن ينجح التخريج النهائي من الكاشير
        $dischargeResponse = $this->actingAs($this->cashier)->post(route('cashier.surgeries.final-discharge', $surgery));
        $dischargeResponse->assertRedirect();
        $surgery->refresh();

        $this->assertNotNull($surgery->discharged_at);
        $this->assertEquals($this->cashier->id, $surgery->discharged_by);
    }

    public function test_cashier_final_discharge_blocked_if_unpaid_balance_remains(): void
    {
        $surgery = $this->createSurgery('completed', $this->room101, 300000);
        // غير مسدد (متبقي 300,000)

        $response = $this->actingAs($this->cashier)->post(route('cashier.surgeries.final-discharge', $surgery));
        $response->assertSessionHas('error');
        $this->assertNull($surgery->fresh()->discharged_at);
    }
}
