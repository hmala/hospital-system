<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DoctorQueueCallSpecificTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('doctor', 'web');
        Permission::findOrCreate('view own visits', 'web');
        Permission::findOrCreate('manage own visits', 'web');

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

        $this->doctorUser = User::create([
            'name' => 'د. زياد طارق',
            'email' => 'dr.ziad@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo(['view own visits', 'manage own visits']);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-1234',
            'consultation_fee' => 15000,
            'is_available_today' => true,
        ]);
    }

    public function test_doctor_can_call_specific_patient_out_of_turn()
    {
        $appointments = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = User::create([
                'name' => "مريض رقم {$i}",
                'email' => "patient{$i}@example.com",
                'password' => bcrypt('password'),
            ]);
            $patient = Patient::create([
                'user_id' => $user->id,
                'name' => "مريض رقم {$i}",
                'gender' => 'male',
                'phone' => "077000000{$i}",
            ]);

            $appointments[$i] = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $this->doctor->id,
                'department_id' => $this->doctor->department_id,
                'consultation_fee' => 15000,
                'appointment_date' => today(),
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'queue_number' => $i,
            ]);
        }

        // Doctor specifically calls patient #9
        $targetAppointment = $appointments[9];

        $response = $this->actingAs($this->doctorUser)
            ->postJson("/queue/appointment/{$targetAppointment->id}/call-specific");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'appointment_id' => $targetAppointment->id,
            'queue_number' => 9,
            'patient_name' => 'مريض رقم 9',
        ]);

        $targetAppointment->refresh();
        $this->assertEquals('calling', $targetAppointment->status);
        $this->assertNotNull($targetAppointment->called_at);

        // Verify queueData returns patient #9 as currently calling
        $dataResponse = $this->getJson("/queue/doctor/{$this->doctor->id}/data");
        $dataResponse->assertStatus(200);
        $dataResponse->assertJsonPath('current_patient.queue_number', 9);
        $dataResponse->assertJsonPath('current_patient.name', 'مريض رقم 9');
    }
}
