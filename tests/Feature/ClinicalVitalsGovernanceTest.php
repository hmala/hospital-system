<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\OperationTheater;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgeryTabPermission;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClinicalVitalsGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $residentUser;

    protected User $nurseUser;

    protected Surgery $surgery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        SurgeryTabPermission::seedDefaults();

        $department = Department::first();

        // Admin User
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Resident User
        $this->residentUser = User::factory()->create();
        $this->residentUser->assignRole('resident');

        $doctor = Doctor::create([
            'user_id' => $this->residentUser->id,
            'department_id' => $department->id,
            'name' => 'Dr. Resident Vitals',
            'phone' => '0770000001',
            'specialization' => 'General Surgery',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-RES-'.uniqid(),
            'type' => 'resident',
            'is_active' => true,
        ]);

        // Nurse User
        $this->nurseUser = User::factory()->create();
        $this->nurseUser->assignRole('nurse');

        // Create Patient & Surgery
        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 35,
            'gender' => 'male',
            'phone' => '0770111222',
        ]);

        $theater = OperationTheater::create([
            'name' => 'صالة 1',
            'code' => 'OT-1',
            'status' => 'available',
        ]);

        $room = Room::create([
            'name' => 'غرفة 101',
            'room_number' => '101',
            'type' => 'regular',
            'daily_price' => 200000,
            'status' => 'occupied',
        ]);

        $this->surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'operation_theater_id' => $theater->id,
            'room_id' => $room->id,
            'room_checkin_at' => now()->subHours(2),
            'surgery_name' => 'استئصال الزائدة',
            'surgery_type' => 'major',
            'status' => 'in_progress',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
        ]);
    }

    public function test_vitals_table_renders_with_horizontal_columns(): void
    {
        $response = $this->actingAs($this->residentUser)
            ->get(route('clinical-station.show', [
                'surgery' => $this->surgery->id,
                'phase' => 'pre_op',
                'tab' => 'vitals',
            ]));

        $response->assertStatus(200);
        $response->assertSee('vitalsGridTable');
        $response->assertSee('ضغط الدم');
        $response->assertSee('الحرارة');
        $response->assertSee('النبض');
        $response->assertSee('التنفس');
        $response->assertSee('الأكسجين');
        $response->assertSee('سكر الدم');
        $response->assertSee('السوائل الداخلة');
        $response->assertSee('السوائل الخارجة');
        $response->assertSee('صافي السوائل');
        $response->assertSee('براز');
        $response->assertDontSee('مقياس الألم');
        $response->assertDontSee('مقياس الوعي');
        $response->assertDontSee('امتلاء الشعيرات');
        $response->assertDontSee('pain_score');
    }

    public function test_trend_charts_are_hidden_from_nurse_and_shown_to_resident_and_admin(): void
    {
        // 1. Nurse: charts should NOT be visible
        $nurseResponse = $this->actingAs($this->nurseUser)
            ->get(route('clinical-station.show', [
                'surgery' => $this->surgery->id,
                'phase' => 'pre_op',
                'tab' => 'vitals',
            ]));

        $nurseResponse->assertStatus(200);
        $nurseResponse->assertDontSee('المنحنيات البيانية لتطور العلامات الحيوية');
        $nurseResponse->assertDontSee('منحنى ضغط الدم (BP Trend)');

        // 2. Resident: charts should be visible
        $residentResponse = $this->actingAs($this->residentUser)
            ->get(route('clinical-station.show', [
                'surgery' => $this->surgery->id,
                'phase' => 'pre_op',
                'tab' => 'vitals',
            ]));

        $residentResponse->assertStatus(200);
        $residentResponse->assertSee('المنحنيات البيانية لتطور العلامات الحيوية');
        $residentResponse->assertSee('منحنى ضغط الدم (BP Trend)');

        // 3. Admin: charts should be visible
        $adminResponse = $this->actingAs($this->admin)
            ->get(route('clinical-station.show', [
                'surgery' => $this->surgery->id,
                'phase' => 'pre_op',
                'tab' => 'vitals',
            ]));

        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('المنحنيات البيانية لتطور العلامات الحيوية');
        $adminResponse->assertSee('منحنى ضغط الدم (BP Trend)');
    }

    public function test_user_can_edit_vitals_within_30_minutes(): void
    {
        // Initial record by resident
        $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '120/80',
                'temp' => '37.0',
                'pr' => '75',
                'rr' => '16',
                'spo2' => '98',
            ]);

        $station = $this->surgery->residentStations()->where('phase', 'pre_op')->first();
        $this->assertEquals('120/80', $station->bp);
        $this->assertEquals($this->residentUser->id, $station->vitals_recorded_by);
        $this->assertNotNull($station->vitals_recorded_at);

        // Edit after 10 minutes by same user
        Carbon::setTestNow(now()->addMinutes(10));

        $response = $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '125/82',
                'temp' => '37.2',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('125/82', $station->fresh()->bp);
        $this->assertEquals('37.2', $station->fresh()->temp);
        Carbon::setTestNow();
    }

    public function test_non_admin_cannot_edit_vitals_after_30_minutes_or_from_different_account(): void
    {
        // Initial record by resident
        $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '120/80',
                'temp' => '37.0',
            ]);

        $station = $this->surgery->residentStations()->where('phase', 'pre_op')->first();

        // 1. Different account within 10 minutes
        Carbon::setTestNow(now()->addMinutes(10));
        $otherResident = User::factory()->create();
        $otherResident->assignRole('resident');

        $responseDifferentAccount = $this->actingAs($otherResident)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '130/85',
            ]);

        $responseDifferentAccount->assertSessionHas('error');
        $this->assertEquals('120/80', $station->fresh()->bp); // unchanged

        // 2. Same account after 35 minutes
        Carbon::setTestNow(now()->addMinutes(35));

        $responseExpired = $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '130/85',
            ]);

        $responseExpired->assertSessionHas('error');
        $this->assertEquals('120/80', $station->fresh()->bp); // unchanged

        // 3. Admin can edit even after 35 minutes
        $responseAdmin = $this->actingAs($this->admin)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '130/85',
            ]);

        $responseAdmin->assertSessionHasNoErrors();
        $this->assertEquals('130/85', $station->fresh()->bp); // updated by Admin

        Carbon::setTestNow();
    }

    public function test_user_can_record_new_reading_with_is_new_reading_flag(): void
    {
        // Initial record by resident
        $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '120/80',
            ]);

        $station = $this->surgery->residentStations()->where('phase', 'pre_op')->first();
        $initialTime = $station->vitals_recorded_at;

        // Advance 2 hours
        Carbon::setTestNow(now()->addHours(2));

        // Attempting normal edit should fail
        $failResponse = $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '135/90',
            ]);
        $failResponse->assertSessionHas('error');

        // Recording a NEW reading with is_new_reading = 1 succeeds
        $successResponse = $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '135/90',
                'is_new_reading' => '1',
            ]);

        $successResponse->assertSessionHasNoErrors();
        $this->assertEquals('135/90', $station->fresh()->bp);
        $this->assertTrue($station->fresh()->vitals_recorded_at->gt($initialTime));

        Carbon::setTestNow();
    }

    public function test_user_can_edit_individual_reading_within_30_minutes_and_syncs_station(): void
    {
        $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '120/80',
                'temp' => '37.0',
            ]);

        $station = $this->surgery->residentStations()->where('phase', 'pre_op')->first();
        $reading = $station->readings()->first();
        $this->assertNotNull($reading);

        // Edit via reading update route
        $response = $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.readings.update', $reading->id), [
                'bp' => '122/82',
                'temp' => '37.1',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals('122/82', $reading->fresh()->bp);
        $this->assertEquals('122/82', $station->fresh()->bp);
    }

    public function test_user_can_delete_individual_reading_and_reverts_station(): void
    {
        // 1. Reading 1
        $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '110/70',
                'temp' => '36.8',
            ]);

        // 2. Reading 2
        $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '130/80',
                'temp' => '37.5',
                'is_new_reading' => '1',
            ]);

        $station = $this->surgery->residentStations()->where('phase', 'pre_op')->first();
        $this->assertEquals('130/80', $station->fresh()->bp);
        $this->assertEquals(2, $station->readings()->count());

        $reading2 = $station->readings()->reorder('id', 'desc')->first();
        $reading1 = $station->readings()->reorder('id', 'asc')->first();

        // Delete reading 2
        $deleteResponse = $this->actingAs($this->residentUser)
            ->delete(route('clinical-station.readings.delete', $reading2->id));

        $deleteResponse->assertSessionHas('success');
        $this->assertEquals(1, $station->readings()->count());
        $this->assertEquals('110/70', $station->fresh()->bp); // reverted to reading 1!

        // Delete reading 1
        $this->actingAs($this->residentUser)
            ->delete(route('clinical-station.readings.delete', $reading1->id));

        $this->assertEquals(0, $station->readings()->count());
        $this->assertNull($station->fresh()->bp); // cleared!
    }

    public function test_user_can_edit_reading_via_main_form_with_editing_reading_id(): void
    {
        $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'bp' => '120/80',
                'temp' => '37.0',
            ]);

        $station = $this->surgery->residentStations()->where('phase', 'pre_op')->first();
        $reading = $station->readings()->first();

        // Submit via main update route with editing_reading_id
        $response = $this->actingAs($this->residentUser)
            ->patch(route('clinical-station.update', $this->surgery->id), [
                'phase' => 'pre_op',
                'editing_reading_id' => $reading->id,
                'bp' => '125/85',
                'temp' => '37.3',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals('125/85', $reading->fresh()->bp);
        $this->assertEquals('125/85', $station->fresh()->bp);
    }
}
