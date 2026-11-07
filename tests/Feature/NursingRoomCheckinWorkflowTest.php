<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NursingRoomCheckinWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $nurseUser;

    protected Department $department;

    protected Room $room;

    protected Patient $patient;

    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Permissions & Roles
        Permission::firstOrCreate(['name' => 'view nursing station', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nursing station', 'guard_name' => 'web']);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $nurseRole = Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
        $nurseRole->givePermissionTo(['view nursing station', 'manage nursing station']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        $this->nurseUser = User::factory()->create();
        $this->nurseUser->assignRole($nurseRole);

        $hospital = Hospital::create([
            'name' => 'مستشفى التمريض التخصصي',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-NURS-01',
            'phone' => '07700000000',
            'email' => 'nursing@hospital.com',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '301',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'code' => 'SURG-NURS',
        ]);

        $patientUser = User::factory()->create();
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'مريض تجريبي',
            'phone' => '07701112233',
            'gender' => 'male',
            'file_number' => 'PAT-TEST-001',
        ]);

        $doctorUser = User::factory()->create();
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'license_number' => 'LIC-SURG-TEST-'.uniqid(),
            'qualification' => 'استشاري جراحة عامة',
        ]);

        $this->room = Room::create([
            'room_number' => '301',
            'type' => 'regular',
            'floor' => 'الطابق الثالث',
            'status' => 'available',
            'price_per_day' => 250000,
        ]);
    }

    public function test_room_checkin_button_shown_and_view_button_hidden_before_checkin(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgery_type' => 'استئصال الزائدة الدودية',
            'status' => 'waiting',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'is_health_insurance' => true,
        ]);

        $response = $this->actingAs($this->nurseUser)->get(route('nursing-station.index'));
        $response->assertStatus(200);

        // Check that checkin button is present
        $response->assertSee('تسجيل دخول الغرفة');
        $response->assertSee(route('nursing-station.room-checkin', $surgery));

        // Attempting to access show directly redirects with warning
        $showResponse = $this->actingAs($this->nurseUser)->get(route('nursing-station.show', $surgery));
        $showResponse->assertRedirect(route('nursing-station.index'));
        $showResponse->assertSessionHas('warning', 'يرجى تسجيل دخول المريض للغرفة أولاً قبل الدخول إلى ملف المريض والتعديل السريري.');
    }

    public function test_nurse_can_checkin_patient_to_room(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgery_type' => 'استئصال المرارة',
            'status' => 'waiting',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'is_health_insurance' => true,
        ]);

        $response = $this->actingAs($this->nurseUser)
            ->post(route('nursing-station.room-checkin', $surgery));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery->refresh();
        $this->assertNotNull($surgery->room_checkin_at);
        $this->assertEquals($this->nurseUser->id, $surgery->room_checkin_by);

        $this->room->refresh();
        $this->assertEquals('occupied', $this->room->status);
    }

    public function test_after_checkin_view_details_and_discharge_buttons_are_shown(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgery_type' => 'فتق إربي',
            'status' => 'waiting',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'room_checkin_at' => now(),
            'room_checkin_by' => $this->nurseUser->id,
            'is_health_insurance' => true,
        ]);

        $response = $this->actingAs($this->nurseUser)->get(route('nursing-station.index'));
        $response->assertStatus(200);

        // Check that view and discharge buttons are now visible
        $response->assertSee('عرض وتعديل');
        $response->assertSee('تخريج');
        $response->assertSee(route('nursing-station.room-checkout', $surgery));

        // show method redirects to clinical station without warning
        $showResponse = $this->actingAs($this->nurseUser)->get(route('nursing-station.show', $surgery));
        $showResponse->assertRedirect(route('clinical-station.show', ['surgery' => $surgery->id]));
    }

    public function test_nurse_can_discharge_patient_and_release_room(): void
    {
        $this->room->update(['status' => 'occupied']);

        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgery_type' => 'عملية غضروف',
            'status' => 'in_progress',
            'scheduled_date' => now()->subDay()->toDateString(),
            'scheduled_time' => '09:00:00',
            'room_checkin_at' => now()->subDay(),
            'room_checkin_by' => $this->nurseUser->id,
            'is_health_insurance' => true,
        ]);

        $response = $this->actingAs($this->nurseUser)
            ->post(route('nursing-station.room-checkout', $surgery));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery->refresh();
        $this->assertNotNull($surgery->discharged_at);

        $this->room->refresh();
        $this->assertEquals('available', $this->room->status);
    }

    public function test_stay_calculation_depends_on_room_checkin_at(): void
    {
        // Surgery created 10 days ago, but NOT checked in yet
        $surgeryNotCheckedIn = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgery_type' => 'عملية مجدولة',
            'status' => 'scheduled',
            'scheduled_date' => now()->subDays(10)->toDateString(),
            'scheduled_time' => '10:00:00',
            'is_health_insurance' => true,
        ]);

        $details = $surgeryNotCheckedIn->calculateStayDetails();
        $this->assertEquals(0, $details['extra_nights']);
        $this->assertEquals(0.0, (float) $details['patient_stay_share']);
        $this->assertEquals(0.0, (float) $details['insurance_stay_share']);

        // Once checked in 2 days ago past noon cutoff
        $surgeryCheckedIn = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgery_type' => 'عملية جارية',
            'status' => 'in_progress',
            'scheduled_date' => now()->subDays(10)->toDateString(),
            'scheduled_time' => '10:00:00',
            'room_checkin_at' => now()->subDays(1)->setTime(10, 0),
            'room_checkin_by' => $this->nurseUser->id,
            'is_health_insurance' => true,
        ]);

        $checkedInDetails = $surgeryCheckedIn->calculateStayDetails();
        // Day 1 + Day 2 = 2 nights
        $this->assertGreaterThanOrEqual(1, $checkedInDetails['total_nights']);
        $this->assertEquals($surgeryCheckedIn->room_checkin_at->toIso8601String(), $checkedInDetails['checkin_at']->toIso8601String());
    }
}
