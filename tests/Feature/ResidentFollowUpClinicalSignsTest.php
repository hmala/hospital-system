<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\OperationTheater;
use App\Models\Patient;
use App\Models\ResidentStationFollowUp;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgeryTabPermission;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentFollowUpClinicalSignsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $doctorUser;

    protected Doctor $doctor;

    protected Surgery $surgery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        SurgeryTabPermission::seedDefaults();

        $department = Department::first();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->doctorUser = User::factory()->create();
        $this->doctorUser->assignRole('doctor');
        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $department->id,
            'name' => 'د. حسام مقيم',
            'specialization' => 'General Surgery',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-RES-'.uniqid(),
            'type' => 'surgeon',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 40,
            'gender' => 'male',
        ]);

        $room = Room::create([
            'room_number' => 'SURG-303',
            'type' => 'surgery',
            'status' => 'available',
            'price_per_day' => 100,
        ]);

        $theater = OperationTheater::create([
            'name' => 'صالة الجراحة العامة',
            'code' => 'OR-GEN-1',
            'is_active' => true,
        ]);

        $this->surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $department->id,
            'room_id' => $room->id,
            'operation_theater_id' => $theater->id,
            'surgery_name' => 'عملية استئصال الزائدة الدودية',
            'surgery_type' => 'major',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'room_checkin_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    public function test_follow_up_tab_renders_clinical_checkboxes_and_others_field(): void
    {
        $response = $this->actingAs($this->admin)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'tab' => 'follow_up',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Oriented');
        $response->assertSee('Clear chest');
        $response->assertSee('Soft abdomen');
        $response->assertSee('Urine output');
        $response->assertSee('Yellowish discolouration');
        $response->assertSee('Negative');
        $response->assertSee('bg-danger');
        $response->assertSee('Others');
        $response->assertSee('name="clinical_signs[]"', false);
        $response->assertSee('name="other_notes"', false);
    }

    public function test_can_store_follow_up_with_clinical_signs_via_clinical_station(): void
    {
        $payload = [
            'phase' => 'post_op',
            'follow_up_date' => now()->toDateString(),
            'session' => 'morning',
            'clinical_signs' => [
                'oriented',
                'clear_chest',
                'urine_output',
            ],
            'other_notes' => 'المريض مستقر والألم خفيف تحت السيطرة.',
        ];

        $response = $this->actingAs($this->admin)->post(
            route('clinical-station.follow-ups.store', $this->surgery),
            $payload
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('resident_station_follow_ups', [
            'surgery_id' => $this->surgery->id,
            'session' => 'morning',
            'other_notes' => 'المريض مستقر والألم خفيف تحت السيطرة.',
        ]);

        $followUp = ResidentStationFollowUp::where('surgery_id', $this->surgery->id)->first();
        $this->assertNotNull($followUp);
        $this->assertIsArray($followUp->clinical_signs);
        $this->assertEquals(['oriented', 'clear_chest', 'urine_output'], $followUp->clinical_signs);

        // Verify compiled notes contains signs and notes for backward compatibility
        $this->assertStringContainsString('Oriented', $followUp->notes);
        $this->assertStringContainsString('Clear chest', $followUp->notes);
        $this->assertStringContainsString('Urine output', $followUp->notes);
        $this->assertStringContainsString('المريض مستقر والألم خفيف تحت السيطرة.', $followUp->notes);
    }

    public function test_can_store_follow_up_via_resident_station_controller(): void
    {
        $payload = [
            'follow_up_date' => now()->toDateString(),
            'session' => 'evening',
            'clinical_signs' => [
                'soft_abdomen',
                'yellowish_discolouration',
            ],
            'other_notes' => 'فحص البنكرياس مطلوب غداً صباحاً.',
        ];

        $response = $this->actingAs($this->doctorUser)->post(
            route('resident-station.follow-ups.store', $this->surgery),
            $payload
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $followUp = ResidentStationFollowUp::where('surgery_id', $this->surgery->id)
            ->where('session', 'evening')
            ->first();

        $this->assertNotNull($followUp);
        $this->assertEquals(['soft_abdomen', 'yellowish_discolouration'], $followUp->clinical_signs);
        $this->assertEquals('فحص البنكرياس مطلوب غداً صباحاً.', $followUp->other_notes);
        $this->assertStringContainsString('Soft abdomen', $followUp->notes);
        $this->assertStringContainsString('Yellowish discolouration', $followUp->notes);
    }

    public function test_follow_up_validation_rejects_empty_submission(): void
    {
        $payload = [
            'follow_up_date' => now()->toDateString(),
            'session' => 'morning',
            'clinical_signs' => [],
            'other_notes' => '',
        ];

        $response = $this->actingAs($this->admin)->post(
            route('clinical-station.follow-ups.store', $this->surgery),
            $payload
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(0, ResidentStationFollowUp::where('surgery_id', $this->surgery->id)->count());
    }

    public function test_clinical_signs_badges_render_in_view_timeline(): void
    {
        $station = $this->surgery->residentStations()->create([
            'phase' => 'post_op',
            'status' => 'in_progress',
        ]);

        ResidentStationFollowUp::create([
            'surgery_id' => $this->surgery->id,
            'resident_station_id' => $station->id,
            'resident_name' => 'د. أحمد المقيم',
            'follow_up_date' => now()->toDateString(),
            'session' => 'morning',
            'clinical_signs' => [
                'oriented',
                'clear_chest',
                'soft_abdomen',
                'urine_output',
                'yellowish_discolouration',
            ],
            'other_notes' => 'ملاحظة سريرية شاملة للفحص',
            'notes' => 'Compiled notes string',
        ]);

        $response = $this->actingAs($this->admin)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'tab' => 'follow_up',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Oriented (Positive)');
        $response->assertSee('Clear chest (Positive)');
        $response->assertSee('Soft abdomen (Positive)');
        $response->assertSee('Urine output (Positive)');
        $response->assertSee('Yellowish discolouration (Positive)');
        $response->assertSee('ملاحظة سريرية شاملة للفحص');
    }
}
