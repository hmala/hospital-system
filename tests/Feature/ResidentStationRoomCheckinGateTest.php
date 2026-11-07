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

class ResidentStationRoomCheckinGateTest extends TestCase
{
    use RefreshDatabase;

    protected User $residentUser;

    protected Department $department;

    protected Room $room;

    protected Patient $patient;

    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'view resident station', 'guard_name' => 'web']);
        $residentRole = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $residentRole->givePermissionTo('view resident station');

        $this->residentUser = User::factory()->create();
        $this->residentUser->assignRole($residentRole);

        $hospital = Hospital::create([
            'name' => 'مستشفى الرقود التخصصي',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-RES-01',
            'phone' => '07700000000',
            'email' => 'resident@hospital.com',
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
            'code' => 'SURG-RES',
        ]);

        $patientUser = User::factory()->create();
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'مريض تجريبي للمقيم',
            'phone' => '07701112244',
            'gender' => 'male',
            'file_number' => 'PAT-RES-001',
        ]);

        $doctorUser = User::factory()->create();
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'license_number' => 'LIC-SURG-RES-'.uniqid(),
            'qualification' => 'استشاري جراحة عامة',
        ]);

        $this->room = Room::create([
            'room_number' => '204',
            'type' => 'regular',
            'floor' => 'الطابق الثاني',
            'status' => 'available',
            'price_per_day' => 200000,
        ]);
    }

    public function test_resident_station_locks_button_and_blocks_access_before_nursing_room_checkin(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgery_type' => 'عملية استئصال الزائدة',
            'status' => 'waiting',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'room_checkin_at' => null, // Nursing hasn't checked in yet!
        ]);

        $response = $this->actingAs($this->residentUser)->get(route('resident-station.index'));
        $response->assertStatus(200);

        // Locked button is shown with lock icon and warning tooltip
        $response->assertSee('بانتظار استلام الغرفة');
        $response->assertSee('بانتظار استلام ممرض الطابق');

        // Direct show route is blocked and redirects with warning
        $showResponse = $this->actingAs($this->residentUser)->get(route('resident-station.show', $surgery));
        $showResponse->assertRedirect(route('resident-station.index'));
        $showResponse->assertSessionHas('warning', 'لا يمكن فتح المحطة السريرية للمريض: المريض مخصص للغرفة (204) ولم يقم ممرض الطابق بتسجيل دخوله واستلامه بعد.');
    }

    public function test_resident_station_unlocks_button_and_allows_access_after_nursing_room_checkin(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'surgery_type' => 'عملية استئصال المرارة',
            'status' => 'waiting',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'room_checkin_at' => now(), // Nursing has checked the patient in!
            'room_checkin_by' => $this->residentUser->id,
        ]);

        $response = $this->actingAs($this->residentUser)->get(route('resident-station.index'));
        $response->assertStatus(200);

        // Active link is shown
        $response->assertSee('عرض وتعديل');
        $response->assertSee('تم الاستلام');

        // Direct show route redirects to clinical station
        $showResponse = $this->actingAs($this->residentUser)->get(route('resident-station.show', $surgery));
        $showResponse->assertRedirect(route('clinical-station.show', ['surgery' => $surgery->id]));
    }
}
