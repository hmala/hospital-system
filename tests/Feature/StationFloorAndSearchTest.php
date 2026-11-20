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

class StationFloorAndSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Department $department;

    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'resident', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'view resident station', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view nursing station', 'guard_name' => 'web']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-777',
            'phone' => '07700000000',
            'email' => 'info@shifa.com',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '101',
            'consultation_fee' => 50000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 30,
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. حسام الجراح']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'General Surgery',
            'phone' => '12345678',
            'qualification' => 'MBBS',
            'experience_years' => 10,
            'license_number' => 'DOC-99881',
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'مدير النظام',
        ]);
        $this->adminUser->assignRole('admin');
        $this->adminUser->givePermissionTo(['view resident station', 'view nursing station']);
    }

    public function test_resident_station_renders_floor_pills_and_search_input(): void
    {
        $room2 = Room::create([
            'room_number' => '201',
            'floor' => 'الطابق الثاني',
            'type' => 'private',
            'status' => 'available',
            'daily_rate' => 25000,
        ]);

        $room4 = Room::create([
            'room_number' => '405',
            'floor' => 'الطابق الرابع',
            'type' => 'private',
            'status' => 'available',
            'daily_rate' => 30000,
        ]);

        $patientUser = User::factory()->create(['name' => 'أحمد العراقي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => 'FILE-1001',
        ]);

        Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $room2->id,
            'surgery_type' => 'major',
            'surgery_name' => 'استئصال الزائدة',
            'status' => 'scheduled',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('resident-station.index'));

        $response->assertStatus(200);
        $response->assertSee('الطابق الثاني');
        $response->assertSee('الطابق الرابع');
        $response->assertSee('residentPatientSearchInput');
        $response->assertSee('أحمد العراقي');
    }

    public function test_resident_station_filters_by_floor_and_search(): void
    {
        $room2 = Room::create([
            'room_number' => '201',
            'floor' => 'الطابق الثاني',
            'type' => 'private',
            'status' => 'available',
            'daily_rate' => 25000,
        ]);

        $room4 = Room::create([
            'room_number' => '405',
            'floor' => 'الطابق الرابع',
            'type' => 'private',
            'status' => 'available',
            'daily_rate' => 30000,
        ]);

        $patientUser1 = User::factory()->create(['name' => 'كرار سلام']);
        $patient1 = Patient::create([
            'user_id' => $patientUser1->id,
            'national_id' => 'FILE-2001',
        ]);

        $patientUser2 = User::factory()->create(['name' => 'مصطفى جواد']);
        $patient2 = Patient::create([
            'user_id' => $patientUser2->id,
            'national_id' => 'FILE-4001',
        ]);

        Surgery::create([
            'patient_id' => $patient1->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $room2->id,
            'surgery_type' => 'major',
            'surgery_name' => 'ناظور ركبة',
            'status' => 'scheduled',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
        ]);

        Surgery::create([
            'patient_id' => $patient2->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $room4->id,
            'surgery_type' => 'minor',
            'surgery_name' => 'تفتيت حصى',
            'status' => 'scheduled',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '11:00:00',
        ]);

        // Filter by floor 4
        $responseFloor = $this->actingAs($this->adminUser)
            ->get(route('resident-station.index', ['floor' => 'الطابق الرابع']));

        $responseFloor->assertStatus(200);
        $responseFloor->assertSee('مصطفى جواد');
        $responseFloor->assertDontSee('كرار سلام');

        // Search by patient name
        $responseSearch = $this->actingAs($this->adminUser)
            ->get(route('resident-station.index', ['search' => 'كرار']));

        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('كرار سلام');
        $responseSearch->assertDontSee('مصطفى جواد');

        // Search by file number
        $responseFile = $this->actingAs($this->adminUser)
            ->get(route('resident-station.index', ['search' => 'FILE-4001']));

        $responseFile->assertStatus(200);
        $responseFile->assertSee('مصطفى جواد');
        $responseFile->assertDontSee('كرار سلام');
    }

    public function test_nursing_station_filters_by_search(): void
    {
        $room = Room::create([
            'room_number' => '102',
            'floor' => 'الطابق الأول',
            'type' => 'general',
            'status' => 'available',
            'daily_rate' => 20000,
        ]);

        $patientUser = User::factory()->create(['name' => 'حيدر صباح']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => 'FILE-9999',
        ]);

        Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $room->id,
            'surgery_type' => 'major',
            'surgery_name' => 'عملية غضروف',
            'status' => 'scheduled',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '12:00:00',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('nursing-station.index', ['search' => 'حيدر']));

        $response->assertStatus(200);
        $response->assertSee('حيدر صباح');
        $response->assertSee('nursingPatientSearchInput');
    }
}
