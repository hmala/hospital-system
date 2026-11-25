<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RadiologyStaffCallNextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        Permission::firstOrCreate(['name' => 'view radiology', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process radiology requests', 'guard_name' => 'web']);
    }

    public function test_radiology_call_next_route_exists_and_responds()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view radiology');

        $response = $this->actingAs($user)
            ->postJson(route('radiology-staff.call-next', ['category' => 'ultrasound']));

        // Returns 404 (no patients in queue) but NOT route 404
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('لا يوجد مرضى', $response->json('message'));
    }

    public function test_radiology_call_next_responds_to_get_and_post()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view radiology');

        $responseGet = $this->actingAs($user)
            ->getJson(route('radiology-staff.call-next', ['category' => 'ultrasound']));

        $this->assertEquals(404, $responseGet->getStatusCode());
        $this->assertFalse($responseGet->json('success'));
    }

    public function test_radiology_alias_route_responds()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view radiology');

        $response = $this->actingAs($user)
            ->postJson('/radiology/call-next?category=ultrasound');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($response->json('success'));
    }

    public function test_radiology_call_next_successfully_calls_ultrasound_patient()
    {
        $staffUser = User::factory()->create();
        $staffUser->givePermissionTo('view radiology');
        $staffUser->givePermissionTo('process radiology requests');

        $patientUser = User::factory()->create(['name' => 'أحمد علي السونار']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'أحمد علي السونار',
            'gender' => 'male',
            'birth_date' => '1990-01-01',
        ]);

        $hospital = \App\Models\Hospital::first();
        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم السونار',
            'type' => 'other',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'is_active' => true,
        ]);
        $doctorUser = User::factory()->create(['name' => 'د. خالد أخصائي السونار']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'أشعة وسونار',
            'qualification' => 'بورد عربي',
            'license_number' => 'DOC-RAD-'.uniqid(),
            'consultation_fee' => 30000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص سونار',
            'status' => 'pending',
        ]);

        $req = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'radiology',
            'subtype' => 'ultrasound',
            'description' => 'سونار بطن وحوض',
            'status' => 'pending',
            'payment_status' => 'paid',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($staffUser)
            ->postJson(route('radiology-staff.call-next', ['category' => 'ultrasound']));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('أحمد علي السونار', $response->json('patient_name'));

        $this->assertDatabaseHas('requests', [
            'id' => $req->id,
            'status' => 'calling',
        ]);
    }

    public function test_radiology_index_page_renders_with_nicu_medical_request_having_array_result()
    {
        $staffUser = User::factory()->create();
        $staffUser->givePermissionTo('view radiology');

        $patientUser = User::factory()->create(['name' => 'أم الطفل']);
        $patient = Patient::create(['user_id' => $patientUser->id]);

        $room = \App\Models\Room::create(['room_number' => 'NICU-9', 'room_type' => 'incubator', 'status' => 'available']);
        $incubator = \App\Models\Incubator::create([
            'incubator_number' => 'INC-09',
            'incubator_type' => 'normal',
            'status' => 'available',
            'room_id' => $room->id,
            'daily_fee' => 100000,
            'is_active' => true,
        ]);

        $hosp = \App\Models\Hospital::first();
        $dept = Department::create([
            'hospital_id' => $hosp->id,
            'name' => 'قسم الخدج',
            'type' => 'other',
            'room_number' => '105',
            'consultation_fee' => 20000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'is_active' => true,
        ]);
        $doctorUser = User::factory()->create(['name' => 'طبيب خبير']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'أطفال',
            'qualification' => 'بورد',
            'license_number' => 'DOC-NICU-'.uniqid(),
            'consultation_fee' => 20000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $reservation = \App\Models\IncubatorReservation::create([
            'incubator_id' => $incubator->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'baby_name' => 'طفل مصطفى',
            'admission_date' => now()->toDateString(),
            'admission_time' => '08:00',
            'status' => 'admitted',
        ]);

        // Create MedicalRequest with incubator_reservation_id and array result (which previously crashed Blade htmlspecialchars)
        MedicalRequest::create([
            'type' => 'radiology',
            'subtype' => 'xray',
            'description' => 'أشعة صدر خديج',
            'status' => 'completed',
            'payment_status' => 'paid',
            'incubator_reservation_id' => $reservation->id,
            'result' => [
                'radiologist_report' => 'Normal chest findings with no pneumothorax',
                'findings' => 'Clear lung fields',
            ],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($staffUser)->get(route('radiology-staff.index'));

        $response->assertStatus(200);
        $response->assertSee('طفل مصطفى');
    }
}
