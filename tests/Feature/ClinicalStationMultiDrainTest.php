<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\OperationTheater;
use App\Models\Patient;
use App\Models\ResidentStationReading;
use App\Models\ResidentStationReadingDrain;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgeryTabPermission;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalStationMultiDrainTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $nurse;

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

        $this->nurse = User::factory()->create();
        $this->nurse->assignRole('nurse');

        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'name' => 'د. محمد جراح',
            'specialization' => 'General Surgery',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-SURG-'.uniqid(),
            'type' => 'surgeon',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 45,
            'gender' => 'male',
        ]);

        $room = Room::create([
            'room_number' => 'SURG-101',
            'type' => 'surgery',
            'status' => 'available',
            'price_per_day' => 100,
        ]);

        $theater = OperationTheater::create([
            'name' => 'صالة 1',
            'code' => 'OR-1',
            'is_active' => true,
        ]);

        $this->surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'room_id' => $room->id,
            'operation_theater_id' => $theater->id,
            'surgery_name' => 'استئصال مرارة بالناظور',
            'surgery_type' => 'major',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'room_checkin_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    public function test_nurse_can_record_vitals_with_multiple_drains_array(): void
    {
        $drains = [
            ['drain_number' => 1, 'drain_label' => 'درنقة تحت الكبد', 'amount' => 60.5],
            ['drain_number' => 2, 'drain_label' => 'درنقة حوض', 'amount' => 90.0],
            ['drain_number' => 3, 'drain_label' => 'درنقة ميزاب', 'amount' => 49.5],
        ];

        $response = $this->actingAs($this->nurse)->patch(route('clinical-station.update', [
            'surgery' => $this->surgery->id,
            'phase' => 'post_op',
            'tab' => 'vitals',
        ]), [
            'active_tab' => 'vitals',
            'is_new_reading' => '1',
            'bp' => '120/80',
            'temp' => '37.1',
            'pr' => '76',
            'rr' => '18',
            'spo2' => '99',
            'rbs' => '115',
            'intake_iv_fluids' => 500,
            'output_urine' => 200,
            'drains' => $drains,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $reading = ResidentStationReading::latest('id')->first();
        $this->assertNotNull($reading);

        // Sum of drains: 60.5 + 90.0 + 49.5 = 200.00
        $this->assertEquals(200.00, (float) $reading->output_drain);

        // Fluid balance = Intake (500) - Output (Urine 200 + Drains 200) = +100
        $this->assertEquals(100.00, (float) $reading->fluid_balance);

        // Verify child table records
        $childDrains = ResidentStationReadingDrain::where('resident_station_reading_id', $reading->id)
            ->orderBy('drain_number')
            ->get();

        $this->assertCount(3, $childDrains);
        $this->assertEquals('درنقة تحت الكبد', $childDrains[0]->drain_label);
        $this->assertEquals(60.5, (float) $childDrains[0]->amount);

        $this->assertEquals('درنقة حوض', $childDrains[1]->drain_label);
        $this->assertEquals(90.0, (float) $childDrains[1]->amount);

        $this->assertEquals('درنقة ميزاب', $childDrains[2]->drain_label);
        $this->assertEquals(49.5, (float) $childDrains[2]->amount);
    }

    public function test_nurse_can_record_vitals_with_drains_json_string(): void
    {
        $drains = [
            ['drain_number' => 1, 'drain_label' => 'درنقة 1', 'amount' => 120.0],
            ['drain_number' => 2, 'drain_label' => 'درنقة 2', 'amount' => 80.0],
        ];

        $response = $this->actingAs($this->nurse)->patch(route('clinical-station.update', [
            'surgery' => $this->surgery->id,
            'phase' => 'post_op',
            'tab' => 'vitals',
        ]), [
            'active_tab' => 'vitals',
            'is_new_reading' => '1',
            'bp' => '115/75',
            'temp' => '36.8',
            'intake_iv_fluids' => 300,
            'output_urine' => 100,
            'drains_json' => json_encode($drains),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $reading = ResidentStationReading::latest('id')->first();
        $this->assertNotNull($reading);

        // 120 + 80 = 200
        $this->assertEquals(200.00, (float) $reading->output_drain);
        $this->assertCount(2, $reading->drains);
    }

    public function test_legacy_or_single_output_drain_auto_creates_child_drain_record(): void
    {
        $response = $this->actingAs($this->nurse)->patch(route('clinical-station.update', [
            'surgery' => $this->surgery->id,
            'phase' => 'post_op',
            'tab' => 'vitals',
        ]), [
            'active_tab' => 'vitals',
            'is_new_reading' => '1',
            'bp' => '120/80',
            'temp' => '37.0',
            'output_drain' => 150.00,
        ]);

        $response->assertRedirect();
        $reading = ResidentStationReading::latest('id')->first();
        $this->assertNotNull($reading);
        $this->assertEquals(150.00, (float) $reading->output_drain);

        $this->assertCount(1, $reading->drains);
        $this->assertEquals(150.00, (float) $reading->drains->first()->amount);
        $this->assertEquals('الدرنقة', $reading->drains->first()->drain_label);
    }

    public function test_can_update_reading_with_modified_drains(): void
    {
        $drains = [
            ['drain_number' => 1, 'drain_label' => 'درنقة 1', 'amount' => 50.0],
        ];

        $this->actingAs($this->nurse)->patch(route('clinical-station.update', [
            'surgery' => $this->surgery->id,
            'phase' => 'post_op',
            'tab' => 'vitals',
        ]), [
            'active_tab' => 'vitals',
            'is_new_reading' => '1',
            'bp' => '120/80',
            'drains' => $drains,
        ]);

        $reading = ResidentStationReading::latest('id')->first();
        $this->assertCount(1, $reading->drains);

        // Now update reading with 3 drains
        $updatedDrains = [
            ['drain_number' => 1, 'drain_label' => 'درنقة 1 معدلة', 'amount' => 70.0],
            ['drain_number' => 2, 'drain_label' => 'درنقة 2 جديدة', 'amount' => 30.0],
            ['drain_number' => 3, 'drain_label' => 'درنقة 3 إضافية', 'amount' => 40.0],
        ];

        $response = $this->actingAs($this->nurse)->patch(route('clinical-station.update', [
            'surgery' => $this->surgery->id,
            'phase' => 'post_op',
            'tab' => 'vitals',
        ]), [
            'active_tab' => 'vitals',
            'editing_reading_id' => $reading->id,
            'is_new_reading' => '0',
            'bp' => '125/85',
            'drains' => $updatedDrains,
        ]);

        $response->assertRedirect();
        $reading->refresh();

        // 70 + 30 + 40 = 140
        $this->assertEquals(140.00, (float) $reading->output_drain);
        $this->assertCount(3, $reading->drains);
    }

    public function test_deleting_reading_cascades_and_removes_drain_records(): void
    {
        $drains = [
            ['drain_number' => 1, 'drain_label' => 'درنقة 1', 'amount' => 50.0],
            ['drain_number' => 2, 'drain_label' => 'درنقة 2', 'amount' => 80.0],
        ];

        $this->actingAs($this->admin)->patch(route('clinical-station.update', [
            'surgery' => $this->surgery->id,
            'phase' => 'post_op',
            'tab' => 'vitals',
        ]), [
            'active_tab' => 'vitals',
            'is_new_reading' => '1',
            'bp' => '120/80',
            'drains' => $drains,
        ]);

        $reading = ResidentStationReading::latest('id')->first();
        $readingId = $reading->id;
        $this->assertEquals(2, ResidentStationReadingDrain::where('resident_station_reading_id', $readingId)->count());

        $this->actingAs($this->admin)->delete(route('clinical-station.readings.delete', $readingId));

        $this->assertDatabaseMissing('resident_station_readings', ['id' => $readingId]);
        $this->assertEquals(0, ResidentStationReadingDrain::where('resident_station_reading_id', $readingId)->count());
    }

    public function test_clinical_station_page_renders_drains_modal_and_trigger_elements(): void
    {
        $response = $this->actingAs($this->nurse)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'post_op',
            'tab' => 'vitals',
        ]));

        $response->assertStatus(200);
        $response->assertSee('id="btnOpenDrainModal"', false);
        $response->assertSee('id="drainDetailsModal"', false);
        $response->assertSee('id="viewDrainDetailsModal"', false);
        $response->assertSee('id="grid_drains_json"', false);
        $response->assertSee('قراءات الدرنقات والمفجرات الجراحية');
    }

    public function test_nursing_care_tab_renders_notes_fields_and_saves(): void
    {
        $response = $this->actingAs($this->nurse)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'nursing_care',
        ]));

        $response->assertStatus(200);
        $response->assertSee('name="nursing_notes"', false);
        $response->assertSee('name="discharge_notes"', false);
        $response->assertSee('name="nurse_id"', false);
        $response->assertSee('ملاحظات الرعاية التمريضية');
        $response->assertSee('تعليمات وملاحظات التخريج');

        // Save nursing notes
        $updateResponse = $this->actingAs($this->nurse)->patch(route('clinical-station.update', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'nursing_care',
        ]), [
            'active_tab' => 'nursing_care',
            'nursing_notes' => 'المريض مستقر وتم تغيير الضماد الجراحي بنجاح',
            'discharge_notes' => 'مراجعة العيادة بعد أسبوع مع إكمال المضاد الحيوي',
        ]);

        $updateResponse->assertRedirect();
        $this->surgery->refresh();
        $nursingStation = $this->surgery->nursingStation;
        $this->assertNotNull($nursingStation);
        $this->assertEquals('المريض مستقر وتم تغيير الضماد الجراحي بنجاح', $nursingStation->nursing_notes);
        $this->assertEquals('مراجعة العيادة بعد أسبوع مع إكمال المضاد الحيوي', $nursingStation->discharge_notes);
    }
}
