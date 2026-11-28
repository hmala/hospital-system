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

class SurgeryReactivationTest extends TestCase
{
    use RefreshDatabase;

    protected Hospital $hospital;
    protected User $admin;
    protected User $staff;
    protected Doctor $doctor;
    protected Department $department;
    protected Room $room;
    protected SurgicalOperation $operation;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles & Permissions
        Permission::firstOrCreate(['name' => 'control surgeries']);
        Permission::firstOrCreate(['name' => 'edit surgeries']);
        Permission::firstOrCreate(['name' => 'view surgeries']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الشفاء التخصصي',
            'owner_name' => 'د. أحمد',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-TEST-001',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->staff = User::factory()->create();
        $this->staff->givePermissionTo('control surgeries');

        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '201',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'code' => 'SURG01',
        ]);

        $doctorUser = User::factory()->create();
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'type' => 'surgeon',
            'specialization' => 'جراحة عامة',
            'qualification' => 'استشاري جراحة عامة',
            'license_number' => 'DOC-TEST-001',
            'is_active' => true,
        ]);

        $this->room = Room::create([
            'room_number' => '501',
            'type' => 'regular',
            'capacity' => 1,
            'current_occupancy' => 0,
            'price_per_day' => 50000,
            'status' => 'available',
        ]);

        $this->operation = SurgicalOperation::create([
            'name' => 'استئصال الزائدة الدودية',
            'category' => 'جراحة عامة',
            'fee' => 500000,
        ]);
    }

    public function test_can_reactivate_cancelled_surgery_when_room_is_available(): void
    {
        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = Patient::create(['user_id' => $patientUser->id]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'status' => 'cancelled',
            'cancellation_reason' => 'إلغاء لظرف طارئ',
            'payment_status' => 'cancelled',
            'surgery_fee' => 500000,
            'surgery_fee_paid' => 'cancelled',
        ]);

        // Room is available
        $this->room->update(['status' => 'available']);

        $response = $this->actingAs($this->admin)
            ->post(route('surgeries.reactivate', $surgery));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery->refresh();
        $this->assertEquals('waiting', $surgery->status);
        $this->assertNull($surgery->cancellation_reason);
        $this->assertEquals($this->room->id, $surgery->room_id);

        $this->room->refresh();
        $this->assertEquals('occupied', $this->room->status);
    }

    public function test_reactivating_cancelled_surgery_when_room_was_given_to_another_patient_unassigns_room(): void
    {
        $patientUser1 = User::factory()->create(['name' => 'المريض الأول المسترجع']);
        $patient1 = Patient::create(['user_id' => $patientUser1->id]);

        // Surgery 1 was cancelled
        $surgery1 = Surgery::create([
            'patient_id' => $patient1->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'status' => 'cancelled',
            'cancellation_reason' => 'ألغيت بالخطأ',
            'payment_status' => 'cancelled',
            'surgery_fee' => 500000,
            'surgery_fee_paid' => 'cancelled',
        ]);

        // Meanwhile, patient 2 took room 501
        $patientUser2 = User::factory()->create(['name' => 'المريض الجديد الشاغل للغرفة']);
        $patient2 = Patient::create(['user_id' => $patientUser2->id]);

        $surgery2 = Surgery::create([
            'patient_id' => $patient2->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'status' => 'waiting',
            'payment_status' => 'pending',
            'surgery_fee' => 500000,
        ]);
        $this->room->update(['status' => 'occupied']);

        // Now admin reactivates Surgery 1
        $response = $this->actingAs($this->admin)
            ->post(route('surgeries.reactivate', $surgery1));

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $surgery1->refresh();
        $this->assertEquals('waiting', $surgery1->status);
        $this->assertNull($surgery1->cancellation_reason);
        // Room must be cleared on surgery 1 to prevent double occupancy!
        $this->assertNull($surgery1->room_id);

        // Room must remain occupied by surgery 2
        $surgery2->refresh();
        $this->assertEquals($this->room->id, $surgery2->room_id);
        $this->room->refresh();
        $this->assertEquals('occupied', $this->room->status);
    }

    public function test_unauthorized_user_cannot_reactivate_surgery(): void
    {
        $unauthorizedUser = User::factory()->create();

        $patientUser = User::factory()->create();
        $patient = Patient::create(['user_id' => $patientUser->id]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->post(route('surgeries.reactivate', $surgery));

        $response->assertStatus(403);
    }
}
