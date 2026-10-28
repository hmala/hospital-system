<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\OperationTheater;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgeryTabPermission;
use App\Models\SurgeryTreatment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedClinicalStationAndTabPermissionsTest extends TestCase
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
            'name' => 'Dr. Resident Test',
            'phone' => '0770000000',
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
        $patientUser = User::factory()->create([
            'name' => 'حسام عبد الستار',
        ]);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 30,
            'gender' => 'male',
            'blood_group' => 'A+',
        ]);

        $room = Room::create([
            'room_number' => 'OR-'.uniqid(),
            'type' => 'surgery',
            'status' => 'available',
            'price_per_day' => 100,
        ]);

        $theater = OperationTheater::create([
            'name' => 'صالة العمليات الكبرى 1',
            'code' => 'OR-1',
            'is_active' => true,
        ]);

        $this->surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'room_id' => $room->id,
            'operation_theater_id' => $theater->id,
            'surgery_name' => 'استئصال الزائدة الدودية',
            'surgery_type' => 'major',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'scheduled',
        ]);
    }

    public function test_admin_can_view_tab_permissions_matrix()
    {
        $response = $this->actingAs($this->admin)->get(route('surgery-stations.tab-settings.index'));
        $response->assertStatus(200);
        $response->assertSee('مصفوفة صلاحيات تبويبات المحطة السريرية الموحدة');
        $response->assertSee('الطبيب المقيم');
        $response->assertSee('كادر التمريض');
    }

    public function test_admin_can_update_tab_permissions_matrix()
    {
        $payload = [
            'permissions' => [
                'nurse' => [
                    'history' => 'write', // upgraded from read to write
                    'vitals' => 'write',
                    'tests' => 'hidden',  // changed from read to hidden
                    'plan' => 'read',
                    'treatments' => 'write',
                    'follow_up' => 'write',
                    'fluids' => 'write',
                    'nursing_care' => 'write',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('surgery-stations.tab-settings.update'), $payload);
        $response->assertRedirect(route('surgery-stations.tab-settings.index'));

        $this->assertDatabaseHas('surgery_tab_permissions', [
            'role_name' => 'nurse',
            'tab_key' => 'history',
            'access_level' => 'write',
        ]);

        $this->assertDatabaseHas('surgery_tab_permissions', [
            'role_name' => 'nurse',
            'tab_key' => 'tests',
            'access_level' => 'hidden',
        ]);
    }

    public function test_admin_can_reset_tab_permissions_to_defaults()
    {
        // Change one permission
        SurgeryTabPermission::where('role_name', 'nurse')->where('tab_key', 'history')->update(['access_level' => 'hidden']);

        $response = $this->actingAs($this->admin)->post(route('surgery-stations.tab-settings.reset'));
        $response->assertRedirect(route('surgery-stations.tab-settings.index'));

        // Default for nurse history is 'read'
        $this->assertDatabaseHas('surgery_tab_permissions', [
            'role_name' => 'nurse',
            'tab_key' => 'history',
            'access_level' => 'read',
        ]);
    }

    public function test_clinical_station_displays_tabs_according_to_role_permissions()
    {
        // 1. As Resident (default: history write, vitals write, fluids read)
        $response = $this->actingAs($this->residentUser)->get(route('clinical-station.show', $this->surgery));
        $response->assertStatus(200);
        $response->assertSee('المحطة السريرية الموحدة');
        $response->assertSee('السيرة المرضية');
        $response->assertSee('العلامات الحيوية والفحص');

        // 2. Set nurse 'history' to hidden and check as Nurse
        SurgeryTabPermission::where('role_name', 'nurse')->where('tab_key', 'history')->update(['access_level' => 'hidden']);

        $nurseResponse = $this->actingAs($this->nurseUser)->get(route('clinical-station.show', $this->surgery));
        $nurseResponse->assertStatus(200);
        $nurseResponse->assertDontSee('السيرة المرضية'); // Hidden for nurse
        $nurseResponse->assertSee('العلامات الحيوية والفحص'); // Visible for nurse
    }

    public function test_resident_or_nurse_can_record_vitals_in_clinical_station()
    {
        $payload = [
            'phase' => 'pre_op',
            'active_tab' => 'vitals',
            'bp' => '125/82',
            'temp' => '37.1',
            'pr' => '78',
            'rr' => '16',
            'spo2' => '99',
            'clinical_examination' => 'Chest clear, abdomen soft',
        ];

        $response = $this->actingAs($this->residentUser)->patch(route('clinical-station.update', $this->surgery), $payload);
        $response->assertRedirect(route('clinical-station.show', ['surgery' => $this->surgery->id, 'phase' => 'pre_op', 'tab' => 'vitals']));

        $this->assertDatabaseHas('resident_stations', [
            'surgery_id' => $this->surgery->id,
            'phase' => 'pre_op',
            'bp' => '125/82',
            'temp' => '37.1',
        ]);

        $this->assertDatabaseHas('resident_station_readings', [
            'bp' => '125/82',
            'temp' => '37.1',
            'pr' => '78',
        ]);
    }

    public function test_can_add_and_administer_treatment_in_clinical_station()
    {
        // Add treatment as resident
        $treatmentPayload = [
            'description' => 'Paracetamol 1g IV',
            'dosage' => '1 vial',
            'timing' => 'Every 8 hours',
        ];

        $response = $this->actingAs($this->residentUser)->post(route('clinical-station.treatments.store', $this->surgery), $treatmentPayload);
        $response->assertRedirect(route('clinical-station.show', ['surgery' => $this->surgery->id, 'tab' => 'treatments']));

        $treatment = SurgeryTreatment::where('surgery_id', $this->surgery->id)->first();
        $this->assertNotNull($treatment);
        $this->assertEquals('Paracetamol 1g IV', $treatment->description);
        $this->assertEquals('planned', $treatment->status);

        // Nurse administers the medication
        $administerPayload = [
            'status' => 'administered',
            'admin_notes' => 'Given slowly via IV drip without complications',
        ];

        $adminResponse = $this->actingAs($this->nurseUser)->post(route('clinical-station.treatments.administer', $treatment), $administerPayload);
        $adminResponse->assertSessionHas('success');

        $treatment->refresh();
        $this->assertEquals('administered', $treatment->status);
        $this->assertEquals($this->nurseUser->id, $treatment->administered_by);
    }

    public function test_can_add_follow_up_note_in_clinical_station()
    {
        $followUpPayload = [
            'phase' => 'pre_op',
            'follow_up_date' => now()->toDateString(),
            'session' => 'morning',
            'notes' => 'Patient is resting comfortably, vitals stable.',
        ];

        $response = $this->actingAs($this->residentUser)->post(route('clinical-station.follow-ups.store', $this->surgery), $followUpPayload);
        $response->assertRedirect(route('clinical-station.show', ['surgery' => $this->surgery->id, 'phase' => 'pre_op', 'tab' => 'follow_up']));

        $this->assertDatabaseHas('resident_station_follow_ups', [
            'surgery_id' => $this->surgery->id,
            'session' => 'morning',
            'notes' => 'Patient is resting comfortably, vitals stable.',
        ]);
    }

    public function test_legacy_routes_redirect_seamlessly_to_unified_clinical_station()
    {
        // Legacy resident route redirects to clinical station
        $residentRedirect = $this->actingAs($this->residentUser)->get(route('resident-station.show', $this->surgery));
        $residentRedirect->assertRedirect(route('clinical-station.show', ['surgery' => $this->surgery->id]));

        // Legacy nursing route redirects to clinical station
        $nursingRedirect = $this->actingAs($this->nurseUser)->get(route('nursing-station.show', $this->surgery));
        $nursingRedirect->assertRedirect(route('clinical-station.show', ['surgery' => $this->surgery->id]));
    }

    public function test_cashier_can_view_surgery_payment_form()
    {
        $cashierUser = User::factory()->create();
        $cashierUser->givePermissionTo('process surgery payments');

        $response = $this->actingAs($cashierUser)->get(route('cashier.surgeries.payment.form', $this->surgery));
        $response->assertStatus(200);
        $response->assertSee($this->surgery->surgery_name);
    }
}
