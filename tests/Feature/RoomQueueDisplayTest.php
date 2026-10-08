<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Hospital;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoomQueueDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('inquiry_staff', 'web');
        Permission::findOrCreate('view inquiry', 'web');
        Permission::findOrCreate('manage inquiry', 'web');
    }

    public function test_room_display_page_loads_without_auth()
    {
        $response = $this->get('/queue/room/1');
        $response->assertStatus(200);
        $response->assertSee('عيادة رقم 1');
    }

    public function test_room_queue_data_returns_waiting_when_no_doctor_assigned()
    {
        $response = $this->getJson('/queue/room/5/data');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'room_number' => '5',
            'has_doctor' => false,
        ]);
    }

    public function test_assign_doctor_to_room_and_fetch_live_queue()
    {
        $hospital = Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $dept = Department::create([
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'hospital_id' => $hospital->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['name' => 'د. أحمد علي']);
        
        $doctor = Doctor::create([
            'user_id' => $user->id,
            'specialization' => 'استشاري باطنية وقلب',
            'qualification' => 'دكتوراه / بورد',
            'license_number' => 'DOC-9912',
            'consultation_fee' => 20000,
            'department_id' => $dept->id,
            'type' => 'consultant',
            'is_active' => true,
            'is_available_today' => true,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Assign doctor to room 3
        $assignResponse = $this->actingAs($admin)->postJson('/queue/assign-room', [
            'doctor_id' => $doctor->id,
            'room' => '3'
        ]);

        $assignResponse->assertStatus(200);
        $assignResponse->assertJson([
            'success' => true,
            'room' => '3'
        ]);

        $this->assertEquals('3', $doctor->fresh()->current_room);

        // Fetch room 3 data
        $roomData = $this->getJson('/queue/room/3/data');
        $roomData->assertStatus(200);
        $roomData->assertJson([
            'success' => true,
            'room_number' => '3',
            'has_doctor' => true,
            'doctor_id' => $doctor->id,
            'doctor_name' => 'د. أحمد علي',
        ]);
    }
}
