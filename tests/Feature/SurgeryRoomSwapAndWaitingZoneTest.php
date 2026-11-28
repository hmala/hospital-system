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

class SurgeryRoomSwapAndWaitingZoneTest extends TestCase
{
    use RefreshDatabase;

    protected Hospital $hospital;

    protected User $admin;

    protected User $unauthorizedUser;

    protected Doctor $doctor;

    protected Department $department;

    protected Room $room101;

    protected Room $room102;

    protected SurgicalOperation $operation;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'assign surgery room']);
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

        $this->unauthorizedUser = User::factory()->create();

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

        $this->operation = SurgicalOperation::create([
            'name' => 'استئصال الزائدة الدودية',
            'category' => 'major',
            'fee' => 500000,
            'is_active' => true,
        ]);

        $this->room101 = Room::create([
            'hospital_id' => $this->hospital->id,
            'department_id' => $this->department->id,
            'room_number' => '101',
            'floor' => 'الطابق الأول',
            'room_type' => 'regular',
            'status' => 'occupied',
            'daily_fee' => 50000,
            'is_active' => true,
            'room_purpose' => 'beds',
        ]);

        $this->room102 = Room::create([
            'hospital_id' => $this->hospital->id,
            'department_id' => $this->department->id,
            'room_number' => '102',
            'floor' => 'الطابق الأول',
            'room_type' => 'vip',
            'status' => 'occupied',
            'daily_fee' => 100000,
            'is_active' => true,
            'room_purpose' => 'beds',
        ]);
    }

    public function test_can_swap_rooms_between_two_active_surgeries(): void
    {
        $patientUser1 = User::factory()->create(['name' => 'المريض الأول أحمد']);
        $patient1 = Patient::create(['user_id' => $patientUser1->id]);

        $surgery1 = Surgery::create([
            'patient_id' => $patient1->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room101->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'waiting',
            'room_fee' => 0,
        ]);

        $patientUser2 = User::factory()->create(['name' => 'المريض الثاني علي']);
        $patient2 = Patient::create(['user_id' => $patientUser2->id]);

        $surgery2 = Surgery::create([
            'patient_id' => $patient2->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room102->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '11:00:00',
            'status' => 'waiting',
            'room_fee' => 100000,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('surgeries.swap-room', $surgery1), [
                'target_surgery_id' => $surgery2->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery1->refresh();
        $surgery2->refresh();

        // Surgery 1 now has Room 102 (VIP)
        $this->assertEquals($this->room102->id, $surgery1->room_id);
        $this->assertEquals(100000, $surgery1->room_fee);

        // Surgery 2 now has Room 101 (Regular)
        $this->assertEquals($this->room101->id, $surgery2->room_id);
        $this->assertEquals(0, $surgery2->room_fee); // regular first night is 0
    }

    public function test_can_move_patient_from_room_to_waiting_zone(): void
    {
        $patientUser = User::factory()->create(['name' => 'المريض للانتظار']);
        $patient = Patient::create(['user_id' => $patientUser->id]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room101->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'waiting',
            'room_fee' => 50000,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('surgeries.move-to-waiting-zone', $surgery));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery->refresh();
        $this->room101->refresh();

        // Surgery is now in waiting zone
        $this->assertNull($surgery->room_id);
        $this->assertEquals(0, $surgery->room_fee);

        // Room 101 is now freed and available
        $this->assertEquals('available', $this->room101->status);
    }

    public function test_unauthorized_user_cannot_swap_or_move_to_waiting_zone(): void
    {
        $patientUser = User::factory()->create();
        $patient = Patient::create(['user_id' => $patientUser->id]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room101->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'waiting',
        ]);

        $responseSwap = $this->actingAs($this->unauthorizedUser)
            ->post(route('surgeries.swap-room', $surgery), [
                'target_surgery_id' => 9999,
            ]);
        $responseSwap->assertStatus(403);

        $responseWaiting = $this->actingAs($this->unauthorizedUser)
            ->post(route('surgeries.move-to-waiting-zone', $surgery));
        $responseWaiting->assertStatus(403);
    }

    public function test_can_swap_rooms_for_post_op_completed_surgeries_in_ward(): void
    {
        $patientUser1 = User::factory()->create(['name' => 'المريض المكتمل الأول']);
        $patient1 = Patient::create(['user_id' => $patientUser1->id]);

        $surgery1 = Surgery::create([
            'patient_id' => $patient1->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room101->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'completed',
            'discharged_at' => null,
            'room_fee' => 0,
        ]);

        $patientUser2 = User::factory()->create(['name' => 'المريض الثاني بالردهة']);
        $patient2 = Patient::create(['user_id' => $patientUser2->id]);

        $surgery2 = Surgery::create([
            'patient_id' => $patient2->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room102->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '11:00:00',
            'status' => 'completed',
            'discharged_at' => null,
            'room_fee' => 100000,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('surgeries.swap-room', $surgery1), [
                'target_surgery_id' => $surgery2->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery1->refresh();
        $surgery2->refresh();

        $this->assertEquals($this->room102->id, $surgery1->room_id);
        $this->assertEquals($this->room101->id, $surgery2->room_id);
    }

    public function test_can_undo_discharge_and_restore_patient(): void
    {
        $patientUser = User::factory()->create(['name' => 'مريض التخريج الخاطئ']);
        $patient = Patient::create(['user_id' => $patientUser->id]);

        $this->room101->update(['status' => 'available']);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room101->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'completed',
            'discharged_at' => now()->subHour(),
            'discharge_notes' => 'ملاحظات خروج سابقة',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('surgeries.undo-discharge', $surgery));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery->refresh();
        $this->room101->refresh();

        $this->assertNull($surgery->discharged_at);
        $this->assertNull($surgery->discharge_notes);
        $this->assertEquals($this->room101->id, $surgery->room_id);
        $this->assertEquals('occupied', $this->room101->status);
    }

    public function test_undo_discharge_moves_to_waiting_zone_if_previous_room_is_occupied(): void
    {
        $patientUser1 = User::factory()->create(['name' => 'مريض خرج سابقا']);
        $patient1 = Patient::create(['user_id' => $patientUser1->id]);

        $surgery1 = Surgery::create([
            'patient_id' => $patient1->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room101->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => 'completed',
            'discharged_at' => now()->subHours(2),
        ]);

        // مريض آخر حجز الغرفة 101 حالياً
        $patientUser2 = User::factory()->create(['name' => 'المريض الشاغل الحالي']);
        $patient2 = Patient::create(['user_id' => $patientUser2->id]);

        $surgery2 = Surgery::create([
            'patient_id' => $patient2->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room101->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'waiting',
            'discharged_at' => null,
        ]);

        $this->room101->update(['status' => 'occupied']);

        $response = $this->actingAs($this->admin)
            ->post(route('surgeries.undo-discharge', $surgery1));

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $surgery1->refresh();

        $this->assertNull($surgery1->discharged_at);
        $this->assertNull($surgery1->room_id); // نقل لوضع الانتظار بأمان
    }

    public function test_can_transfer_patient_from_occupied_room_to_available_vacant_room(): void
    {
        $patientUser = User::factory()->create(['name' => 'مريض نقل غرفة شاغرة']);
        $patient = Patient::create(['user_id' => $patientUser->id]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room101->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'completed',
            'room_fee' => 0,
        ]);

        $roomVacant = Room::create([
            'hospital_id' => $this->hospital->id,
            'department_id' => $this->department->id,
            'room_number' => '103',
            'floor' => 'الطابق الأول',
            'room_type' => 'vip',
            'status' => 'available',
            'daily_fee' => 120000,
            'is_active' => true,
            'room_purpose' => 'beds',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('surgeries.assign-room', $surgery), [
                'room_id' => $roomVacant->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery->refresh();
        $this->room101->refresh();
        $roomVacant->refresh();

        // Old room is now available
        $this->assertEquals('available', $this->room101->status);

        // New room is occupied
        $this->assertEquals('occupied', $roomVacant->status);

        // Surgery has new room assigned
        $this->assertEquals($roomVacant->id, $surgery->room_id);
        $this->assertEquals(100000, $surgery->room_fee); // VIP fee

        // Check index page returns view with availableRoomsForTransfer
        $indexResponse = $this->actingAs($this->admin)->get(route('surgeries.index', ['tab' => 'completed']));
        $indexResponse->assertOk();
        $indexResponse->assertViewHas('availableRoomsForTransfer');
        $indexResponse->assertSee('إدارة ونقل غرفة المريض');
    }

    public function test_available_rooms_for_transfer_excludes_rooms_with_active_occupants_and_fixes_status(): void
    {
        // Room 104 is occupied by an active surgery but has stale status = available in DB
        $staleRoom = Room::create([
            'hospital_id' => $this->hospital->id,
            'department_id' => $this->department->id,
            'room_number' => '104',
            'floor' => 'الطابق الأول',
            'room_type' => 'regular',
            'status' => 'available', // Intentionally stale
            'daily_fee' => 50000,
            'is_active' => true,
            'room_purpose' => 'beds',
        ]);

        $patient = Patient::factory()->create(['hospital_id' => $this->hospital->id]);

        Surgery::create([
            'hospital_id' => $this->hospital->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgical_operation_id' => $this->operation->id,
            'room_id' => $staleRoom->id,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'waiting',
            'room_fee' => 0,
        ]);

        // Genuinely vacant room
        $trulyVacantRoom = Room::create([
            'hospital_id' => $this->hospital->id,
            'department_id' => $this->department->id,
            'room_number' => '105',
            'floor' => 'الطابق الأول',
            'room_type' => 'regular',
            'status' => 'available',
            'daily_fee' => 50000,
            'is_active' => true,
            'room_purpose' => 'beds',
        ]);

        $response = $this->actingAs($this->admin)->get(route('surgeries.index'));
        $response->assertOk();

        // Self-healing should have marked the stale room as occupied
        $staleRoom->refresh();
        $this->assertEquals('occupied', $staleRoom->status);

        $availableRooms = $response->viewData('availableRoomsForTransfer');
        $this->assertFalse($availableRooms->contains('id', $staleRoom->id));
        $this->assertTrue($availableRooms->contains('id', $trulyVacantRoom->id));

        // Ensure dynamic URL is used in the rendered script
        $response->assertSee(url('/surgeries') . '/\' + activeSwapSurgeryId + \'/assign-room', false);
    }
}
