<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Request as ServiceRequest;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Hospital;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class LabShowPatientPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_show_displays_patient_phone_number()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view lab tests', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo('view lab tests');

        $hospital = Hospital::firstOrCreate(
            ['name' => 'مستشفى الكفاءات'],
            [
                'owner_name' => 'Admin',
                'phone' => '07700000000',
                'address' => 'Baghdad',
                'license_number' => 'LIC-12345',
            ]
        );

        $dept = Department::firstOrCreate(
            ['name' => 'المختبر'],
            [
                'hospital_id' => $hospital->id,
                'type' => 'laboratory',
                'room_number' => '101',
                'consultation_fee' => 0,
                'working_hours_start' => '08:00:00',
                'working_hours_end' => '20:00:00',
                'is_active' => true,
            ]
        );

        $patientUser = User::factory()->create([
            'role' => 'patient',
            'phone' => '07904251386',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'blood_type' => 'B+',
            'date_of_birth' => '1990-01-01',
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $dept->id,
            'visit_type' => 'consultation',
            'chief_complaint' => 'Checkup',
            'status' => 'pending',
            'visit_date' => now()->toDateString(),
            'visit_time' => now()->toTimeString(),
        ]);

        $mockReq = ServiceRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'CBC Test',
            'status' => 'pending',
            'details' => ['tests' => []],
        ]);

        $response = $this->actingAs($admin)->get(route('lab.show', $mockReq));

        $response->assertStatus(200);
        $response->assertSee('رقم الهاتف');
        $response->assertSee('07904251386');
        $response->assertSee('tel:07904251386');
    }
}
