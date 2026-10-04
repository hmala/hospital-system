<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorQueueLiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_unified_live_queue_screen_and_available_doctors_api()
    {
        $hospital = \App\Models\Hospital::first() ?? \App\Models\Hospital::create([
            'name' => 'مستشفى الفحص',
            'owner_name' => 'المدير',
            'phone' => '123456',
            'address' => 'بغداد',
            'license_number' => 'LIC-' . uniqid(),
        ]);
        $dept = Department::create(['name' => 'الباطنية', 'type' => 'clinical', 'room_number' => '101', 'hospital_id' => $hospital->id]);
        $user = User::factory()->create(['name' => 'أحمد علي']);
        $doctor = Doctor::create([
            'user_id' => $user->id,
            'department_id' => $dept->id,
            'specialization' => 'أخصائي باطنية',
            'is_active' => true,
            'is_available_today' => true,
            'available_date' => today(),
        ]);

        // 1. Test unified live screen loads with doctors list
        $response = $this->get(route('queue.live'));
        $response->assertStatus(200);
        $response->assertSee('د. أحمد علي');
        $response->assertSee('شاشة الانتظار الموحدة - اختر الطبيب أو العيادة');

        // 2. Test unified live screen with doctor_id query parameter
        $responseWithDoc = $this->get(route('queue.live', ['doctor_id' => $doctor->id]));
        $responseWithDoc->assertStatus(200);
        $responseWithDoc->assertSee('د. أحمد علي');

        // 3. Test available-doctors JSON endpoint
        $apiResponse = $this->get(route('queue.available-doctors'));
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonFragment([
            'id' => $doctor->id,
            'name' => 'د. أحمد علي',
            'is_available_today' => true,
        ]);
    }
}
