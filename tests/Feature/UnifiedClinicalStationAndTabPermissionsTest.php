<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Location;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\OperationTheater;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Room;
use App\Models\StockBatch;
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
            'room_checkin_at' => now(),
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

    public function test_non_authorized_user_cannot_access_or_modify_tab_permissions_matrix()
    {
        // 1. Nurse without 'manage surgery tab permissions' gets 403 Forbidden
        $response = $this->actingAs($this->nurseUser)->get(route('surgery-stations.tab-settings.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->nurseUser)->post(route('surgery-stations.tab-settings.update'), ['permissions' => []]);
        $response->assertStatus(403);

        $response = $this->actingAs($this->nurseUser)->post(route('surgery-stations.tab-settings.reset'));
        $response->assertStatus(403);
    }

    public function test_user_with_explicit_permission_can_manage_tab_settings()
    {
        $customStaff = User::factory()->create();
        $customStaff->givePermissionTo('manage surgery tab permissions');

        $response = $this->actingAs($customStaff)->get(route('surgery-stations.tab-settings.index'));
        $response->assertStatus(200);
        $response->assertSee('مصفوفة صلاحيات تبويبات المحطة السريرية الموحدة');
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

    public function test_fluids_tab_is_removed_from_surgery_details_and_merged_into_clinical_vitals()
    {
        // 1. Surgery show page (/surgeries/{id}) must no longer render required_fluids tab
        $surgeryPage = $this->actingAs($this->admin)->get(route('surgeries.show', $this->surgery));
        $surgeryPage->assertStatus(200);
        $surgeryPage->assertDontSee('مكونات السوائل المطلوبة');
        $surgeryPage->assertDontSee('v-pills-fluids-tab');

        // 2. Clinical station no longer has standalone fluids tab, and ?tab=fluids opens single unified flowsheet table
        $clinicalPage = $this->actingAs($this->nurseUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'fluids',
        ]));
        $clinicalPage->assertStatus(200);
        $clinicalPage->assertSee('جدول العلامات الحيوية السريرية وميزان السوائل (Intake & Output)', false);
        $clinicalPage->assertSee('vitalsGridTable');
        $clinicalPage->assertSee('row1SaveBtn');
        $clinicalPage->assertSee('output_stool');
        $clinicalPage->assertDontSee('حفظ التحديثات السريرية');
        $clinicalPage->assertDontSee('fluidsFlowsheetCard');
        $clinicalPage->assertDontSee('سجل القراءات التاريخية للمريض');
        $clinicalPage->assertDontSee('مقياس الألم');
        $clinicalPage->assertDontSee('مقياس الوعي');
        $clinicalPage->assertDontSee('امتلاء الشعيرات');
        $clinicalPage->assertDontSee('grid_pain_score');
        $clinicalPage->assertDontSee('tab=fluids');
    }

    public function test_nurse_and_admin_can_edit_merged_fluids_inside_vitals_even_with_resident_vitals_lock()
    {
        // 1. Resident records vitals first (acquiring 30-min lock on vitals)
        $this->actingAs($this->residentUser)->patch(route('clinical-station.update', $this->surgery), [
            'phase' => 'pre_op',
            'active_tab' => 'vitals',
            'bp' => '120/80',
            'temp' => '37.0',
        ])->assertRedirect();

        // 2. Nurse opens clinical station vitals tab: sees editable fluids (no read-only lock badge for fluids)
        $nurseView = $this->actingAs($this->nurseUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'vitals',
        ]));
        $nurseView->assertStatus(200);
        $nurseView->assertSee('متاح للإدخال والتعديل (التمريض / الإدارة)');

        // 3. Nurse submits all 8 fluid fields (while keeping resident's readonly vitals unchanged)
        $response = $this->actingAs($this->nurseUser)->patch(route('clinical-station.update', $this->surgery), [
            'phase' => 'pre_op',
            'active_tab' => 'vitals',
            'bp' => '120/80',
            'temp' => '37.0',
            'intake_iv_fluids' => '1000',
            'intake_oral' => '250',
            'intake_blood' => '300',
            'output_urine' => '600',
            'output_drain' => '120',
            'output_gtube_ng' => '80',
            'output_vomiting' => '50',
            'output_stool' => '100',
        ]);
        $response->assertRedirect(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'vitals',
        ]));
        $response->assertSessionHas('success');

        // Total Intake = 1550, Total Output = 950 -> Balance = +600
        $this->assertDatabaseHas('resident_stations', [
            'surgery_id' => $this->surgery->id,
            'phase' => 'pre_op',
            'bp' => '120/80',
            'intake_iv_fluids' => 1000,
            'intake_oral' => 250,
            'intake_blood' => 300,
            'output_urine' => 600,
            'output_drain' => 120,
            'output_gtube_ng' => 80,
            'output_vomiting' => 50,
            'output_stool' => 100,
            'fluid_balance' => 600,
        ]);

        $this->assertDatabaseHas('resident_station_readings', [
            'user_id' => $this->nurseUser->id,
            'intake_iv_fluids' => 1000,
            'output_stool' => 100,
            'fluid_balance' => 600,
        ]);

        // 4. Admin can also update fluids directly
        $this->actingAs($this->admin)->patch(route('clinical-station.update', $this->surgery), [
            'phase' => 'pre_op',
            'active_tab' => 'vitals',
            'bp' => '120/80',
            'temp' => '37.0',
            'intake_iv_fluids' => '500',
            'output_stool' => '200',
        ])->assertRedirect();

        $this->assertDatabaseHas('resident_stations', [
            'surgery_id' => $this->surgery->id,
            'phase' => 'pre_op',
            'intake_iv_fluids' => 500,
            'output_stool' => 200,
            'fluid_balance' => 300,
        ]);
    }

    public function test_doctor_and_resident_see_fluids_readonly_and_cannot_overwrite_fluids()
    {
        // Seed existing fluids recorded by Nurse
        $this->actingAs($this->nurseUser)->patch(route('clinical-station.update', $this->surgery), [
            'phase' => 'pre_op',
            'active_tab' => 'vitals',
            'intake_iv_fluids' => '800',
            'output_urine' => '350',
            'output_stool' => '50',
        ])->assertRedirect();

        // Resident opens vitals tab -> sees read-only fluids banner & editable vitals
        $residentView = $this->actingAs($this->residentUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'vitals',
        ]));
        $residentView->assertStatus(200);
        $residentView->assertSee('للقراءة فقط — التعديل متاح لكادر التمريض والأدمن فقط');

        // Resident submits vitals update alongside tampered/stale fluid values -> vitals save, fluids remain untouched
        $this->actingAs($this->residentUser)->patch(route('clinical-station.update', $this->surgery), [
            'phase' => 'pre_op',
            'active_tab' => 'vitals',
            'bp' => '118/76',
            'temp' => '36.8',
            'intake_iv_fluids' => '9999',
            'output_urine' => '1111',
            'output_stool' => '2222',
        ])->assertRedirect();

        $this->assertDatabaseHas('resident_stations', [
            'surgery_id' => $this->surgery->id,
            'phase' => 'pre_op',
            'bp' => '118/76',
            'temp' => '36.8',
            'intake_iv_fluids' => 800,
            'output_urine' => 350,
            'output_stool' => 50,
            'fluid_balance' => 400,
        ]);

        // Doctor role user also sees fluids read-only and cannot overwrite fluids
        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('doctor');

        $doctorView = $this->actingAs($doctorUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'vitals',
        ]));
        $doctorView->assertStatus(200);
        $doctorView->assertSee('للقراءة فقط — التعديل متاح لكادر التمريض والأدمن فقط');

        $this->actingAs($doctorUser)->patch(route('clinical-station.update', $this->surgery), [
            'phase' => 'pre_op',
            'active_tab' => 'vitals',
            'is_new_reading' => '1',
            'bp' => '130/85',
            'intake_iv_fluids' => '5555',
            'output_stool' => '777',
        ])->assertRedirect();

        $this->assertDatabaseHas('resident_stations', [
            'surgery_id' => $this->surgery->id,
            'phase' => 'pre_op',
            'bp' => '130/85',
            'intake_iv_fluids' => 800,
            'output_urine' => 350,
            'output_stool' => 50,
            'fluid_balance' => 400,
        ]);
    }

    public function test_unified_row1_new_reading_preserves_other_staff_fields_and_syncs_independently_on_delete()
    {
        // 1. Resident enters vitals + clinical exam via Row 1 (is_new_reading = 1)
        $this->actingAs($this->residentUser)->patch(route('clinical-station.update', $this->surgery), [
            'phase' => 'pre_op',
            'active_tab' => 'vitals',
            'is_new_reading' => '1',
            'bp' => '120/80',
            'temp' => '37.0',
            'clinical_examination' => 'Abdomen soft, non-tender',
        ])->assertSessionHas('success');

        $station = $this->surgery->residentStations()->where('phase', 'pre_op')->first();
        $this->assertEquals('120/80', $station->bp);
        $this->assertEquals('Abdomen soft, non-tender', $station->clinical_examination);

        // 2. Nurse enters ONLY fluids in empty Row 1 (is_new_reading = 1) without clinical_examination -> must not hit vitals lock or wipe Resident vitals/exam
        $nurseResp = $this->actingAs($this->nurseUser)->patch(route('clinical-station.update', $this->surgery), [
            'phase' => 'pre_op',
            'active_tab' => 'vitals',
            'is_new_reading' => '1',
            'bp' => '',
            'temp' => '',
            'clinical_examination' => 'Attempted nurse overwrite should be ignored',
            'intake_iv_fluids' => '1000',
            'output_urine' => '300',
            'output_stool' => '100',
        ]);
        $nurseResp->assertSessionHas('success');
        $nurseResp->assertSessionMissing('error');

        $station->refresh();
        $this->assertEquals('120/80', $station->bp);
        $this->assertEquals('Abdomen soft, non-tender', $station->clinical_examination);
        $this->assertEquals(1000, (float) $station->intake_iv_fluids);
        $this->assertEquals(600, (float) $station->fluid_balance);

        // Unified table shows both rows underneath Row 1 in one single table, and hides Clinical Examination from Nurse
        $page = $this->actingAs($this->nurseUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'vitals',
        ]));
        $page->assertStatus(200);
        $page->assertSee('120/80');
        $page->assertSee('+600 ml');
        $page->assertDontSee('ملاحظات الفحص السريري');
        $page->assertDontSee('name="clinical_examination"', false);

        // Resident still sees Clinical Examination box
        $residentPage = $this->actingAs($this->residentUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'vitals',
        ]));
        $residentPage->assertStatus(200);
        $residentPage->assertSee('ملاحظات الفحص السريري');
        $residentPage->assertSee('name="clinical_examination"', false);

        // 3. Admin enters ONLY new vitals in empty Row 1 (is_new_reading = 1) -> must preserve saved fluids
        $this->actingAs($this->admin)->patch(route('clinical-station.update', $this->surgery), [
            'phase' => 'pre_op',
            'active_tab' => 'vitals',
            'is_new_reading' => '1',
            'bp' => '125/82',
            'clinical_examination' => 'Abdomen soft, non-tender',
            'intake_iv_fluids' => '',
            'output_urine' => '',
            'output_stool' => '',
        ])->assertSessionHas('success');

        $station->refresh();
        $this->assertEquals('125/82', $station->bp);
        $this->assertEquals(1000, (float) $station->intake_iv_fluids);
        $this->assertEquals(600, (float) $station->fluid_balance);

        // 4. Deleting Nurse's fluids-only reading clears station fluids without wiping latest vitals/exam
        $nurseFluidReading = $station->readings()->whereNotNull('fluid_balance')->first();
        $this->assertNotNull($nurseFluidReading);

        $this->actingAs($this->nurseUser)
            ->delete(route('clinical-station.readings.delete', $nurseFluidReading->id))
            ->assertSessionHas('success');

        $station->refresh();
        $this->assertEquals('125/82', $station->bp);
        $this->assertEquals('Abdomen soft, non-tender', $station->clinical_examination);
        $this->assertNull($station->fluid_balance);
        $this->assertNull($station->intake_iv_fluids);
    }

    public function test_disconnected_phase_complete_footer_is_removed_from_clinical_station()
    {
        foreach (['pre_op', 'post_op'] as $phase) {
            $response = $this->actingAs($this->residentUser)->get(route('clinical-station.show', [
                'surgery' => $this->surgery->id,
                'phase' => $phase,
                'tab' => 'vitals',
            ]));

            $response->assertStatus(200);
            $response->assertDontSee('إنهاء محطة ما قبل العملية ونقل للصالة');
            $response->assertDontSee('إنهاء متابعة ما بعد العملية');
            $response->assertDontSee(route('clinical-station.complete', $this->surgery), false);
        }
    }

    public function test_organized_clinical_history_tab_renders_and_saves_all_fields()
    {
        // 1. Resident (write permission) sees grouped history sections & quick chips
        $residentView = $this->actingAs($this->residentUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'history',
        ]));

        $residentView->assertStatus(200);
        $residentView->assertSee('clinicalHistoryGrid');
        $residentView->assertSee('القصة والشكوى الحالية');
        $residentView->assertSee('السوابق الطبية والأدوية والتحسس');
        $residentView->assertSee('onclick="fillHistoryQuickText', false);
        $residentView->assertSee('حفظ التحديثات السريرية');

        // 2. Nurse (default read permission) sees history read-only without quick chips
        $nurseView = $this->actingAs($this->nurseUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'history',
        ]));

        $nurseView->assertStatus(200);
        $nurseView->assertSee('clinicalHistoryGrid');
        $nurseView->assertDontSee('onclick="fillHistoryQuickText', false);
        $nurseView->assertDontSee('حفظ التحديثات السريرية');

        // 3. Submitting history saves all 6 fields seamlessly
        $this->actingAs($this->residentUser)->patch(route('clinical-station.update', $this->surgery), [
            'phase' => 'pre_op',
            'active_tab' => 'history',
            'chief_complaint' => 'ألم أيمن أسفل البطن منذ 12 ساعة',
            'history_present_illness' => 'بدأ الألم حول السرة ثم تمركز بالربع السفلي الأيمن',
            'past_medical_hx' => 'لا يوجد',
            'past_surgical_hx' => 'لا يوجد',
            'drug_hx' => 'باراسيتامول عند اللزوم',
            'drug_allergy' => 'لا توجد حساسية معروفة (NKDA)',
        ])->assertRedirect(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'history',
        ]));

        $this->assertDatabaseHas('resident_stations', [
            'surgery_id' => $this->surgery->id,
            'phase' => 'pre_op',
            'chief_complaint' => 'ألم أيمن أسفل البطن منذ 12 ساعة',
            'history_present_illness' => 'بدأ الألم حول السرة ثم تمركز بالربع السفلي الأيمن',
            'past_medical_hx' => 'لا يوجد',
            'past_surgical_hx' => 'لا يوجد',
            'drug_hx' => 'باراسيتامول عند اللزوم',
            'drug_allergy' => 'لا توجد حساسية معروفة (NKDA)',
        ]);
    }

    public function test_organized_clinical_plan_tab_renders_surgeon_summary_and_saves_all_fields()
    {
        $this->surgery->surgeonStation()->create([
            'surgeon_id' => $this->surgery->doctor_id,
            'monitoring_protocol' => 'intensive',
            'treatment_plan' => 'مضاد حيوي قبل الشق الجراحي',
            'notes' => 'تجهيز وحدتين دم',
            'status' => 'in_progress',
        ]);

        // 1. Resident sees organized 2x2 plan grid + surgeon summary + quick ROS chip
        $residentView = $this->actingAs($this->residentUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'plan',
        ]));

        $residentView->assertStatus(200);
        $residentView->assertSee('clinicalPlanGrid');
        $residentView->assertSee('خلاصة توصية الجراح:');
        $residentView->assertSee('مضاد حيوي قبل الشق الجراحي');
        $residentView->assertSee('name="treatment_plan"', false);
        $residentView->assertSee('onclick="fillHistoryQuickText', false);
        $residentView->assertSee('حفظ التحديثات السريرية');

        // 2. Nurse (read-only) sees plan read-only without save button or quick chip
        $nurseView = $this->actingAs($this->nurseUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'plan',
        ]));

        $nurseView->assertStatus(200);
        $nurseView->assertSee('clinicalPlanGrid');
        $nurseView->assertDontSee('onclick="fillHistoryQuickText', false);
        $nurseView->assertDontSee('حفظ التحديثات السريرية');

        // 3. Submitting plan saves all 4 plan fields
        $this->actingAs($this->residentUser)->patch(route('clinical-station.update', $this->surgery), [
            'phase' => 'pre_op',
            'active_tab' => 'plan',
            'notes' => 'صيام تام 8 ساعات قبل الصالة',
            'post_op_notes' => 'مضاد حيوي ومسكن وريدي كل 8 ساعات',
            'treatment_plan' => 'متابعة السوائل والعلامات كل ساعتين',
            'review_of_other_systems' => 'سليمة ولا توجد ملاحظات (NAD)',
        ])->assertRedirect(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'plan',
        ]));

        $this->assertDatabaseHas('resident_stations', [
            'surgery_id' => $this->surgery->id,
            'phase' => 'pre_op',
            'notes' => 'صيام تام 8 ساعات قبل الصالة',
            'post_op_notes' => 'مضاد حيوي ومسكن وريدي كل 8 ساعات',
            'treatment_plan' => 'متابعة السوائل والعلامات كل ساعتين',
            'review_of_other_systems' => 'سليمة ولا توجد ملاحظات (NAD)',
        ]);
    }

    public function test_medications_and_supplies_are_separated_into_independent_tabs_and_respect_permissions(): void
    {
        // 1. Verify supplies tab appears in permissions matrix
        $matrixResponse = $this->actingAs($this->admin)->get(route('surgery-stations.tab-settings.index'));
        $matrixResponse->assertStatus(200);
        $matrixResponse->assertSee('المستلزمات الطبية');
        $matrixResponse->assertSee('(supplies)');

        // 2. Attach patient's room to Floor 3 sub-store with distinct Medication (مغذيات) and Supplies (مستلزمات طبية / مواد طبية)
        $floorLocation = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);
        $this->surgery->room->update([
            'room_number' => '305',
            'floor' => 'الطابق الثالث',
        ]);

        $medProduct = Product::create([
            'name' => 'نورمل سلاين 500ml',
            'code' => 'MED-NS-500',
            'category' => 'مغذيات',
            'unit' => 'عبوة',
        ]);
        $medBatch = StockBatch::create([
            'product_id' => $medProduct->id,
            'location_id' => $floorLocation->id,
            'internal_barcode' => 'BC-MED-305',
            'cost_price' => 1000,
            'initial_qty' => 10,
            'current_qty' => 10,
            'received_at' => now(),
            'expiry_date' => now()->addYear(),
        ]);

        $supplyProduct = Product::create([
            'name' => 'كانيولا زرقاء 22G',
            'code' => 'SUP-CAN-22',
            'category' => 'مستلزمات طبية',
            'unit' => 'قطعة',
        ]);
        $supplyBatch = StockBatch::create([
            'product_id' => $supplyProduct->id,
            'location_id' => $floorLocation->id,
            'internal_barcode' => 'BC-SUP-305',
            'cost_price' => 500,
            'initial_qty' => 20,
            'current_qty' => 20,
            'received_at' => now(),
            'expiry_date' => now()->addYear(),
        ]);

        $consumableProduct = Product::create([
            'name' => 'شاش معقم 10سم',
            'code' => 'MAT-GAUZE-10',
            'category' => 'مواد طبية',
            'unit' => 'باكيت',
        ]);
        StockBatch::create([
            'product_id' => $consumableProduct->id,
            'location_id' => $floorLocation->id,
            'internal_barcode' => 'BC-MAT-305',
            'cost_price' => 400,
            'initial_qty' => 15,
            'current_qty' => 15,
            'received_at' => now(),
            'expiry_date' => now()->addYear(),
        ]);

        // 3. Resident prescribes an IV medication (explicitly medication type)
        $this->actingAs($this->residentUser)->post(route('clinical-station.treatments.store', $this->surgery), [
            'phase' => 'pre_op',
            'description' => 'Amoxicillin 500mg IV',
            'dosage' => '1 vial',
            'timing' => 'كل 8 ساعات',
        ])->assertRedirect(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'treatments',
        ]));

        $this->assertDatabaseHas('surgery_treatments', [
            'surgery_id' => $this->surgery->id,
            'item_type' => SurgeryTreatment::TYPE_MEDICATION,
            'description' => 'Amoxicillin 500mg IV',
        ]);

        // 4. Treatments tab shows prescribed MAR grid and has ZERO floor sub-store dispensing linkage
        $treatmentsPage = $this->actingAs($this->nurseUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'treatments',
        ]));
        $treatmentsPage->assertStatus(200);
        $treatmentsPage->assertSee('Amoxicillin 500mg IV');
        $treatmentsPage->assertDontSee('dispenseFloorMedModal');
        $treatmentsPage->assertDontSee('صرف دواء / مغذي من عهدة الطابق');
        $treatmentsPage->assertDontSee('نورمل سلاين 500ml');
        $treatmentsPage->assertDontSee('كانيولا زرقاء 22G');
        $treatmentsPage->assertDontSee('شاش معقم 10سم');
        $treatmentsPage->assertDontSee('حفظ التحديثات السريرية');

        // 5. Supplies tab shows supply batches only & excludes medication batches/orders
        $suppliesPage = $this->actingAs($this->nurseUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'supplies',
        ]));
        $suppliesPage->assertStatus(200);
        $suppliesPage->assertSee('صرف مستلزم طبي من عهدة الطابق');
        $suppliesPage->assertSee('كانيولا زرقاء 22G');
        $suppliesPage->assertSee('شاش معقم 10سم');
        $suppliesPage->assertDontSee('نورمل سلاين 500ml');
        $suppliesPage->assertDontSee('Amoxicillin 500mg IV');
        $suppliesPage->assertDontSee('حفظ التحديثات السريرية');

        // 6. Floor warehouse strictly rejects medication batches
        $this->actingAs($this->nurseUser)->post(route('clinical-station.supplies.dispense', $this->surgery), [
            'batch_id' => $medBatch->id,
            'quantity' => 1,
            'expected_type' => 'supply',
        ])->assertSessionHas('error');

        $this->assertEquals(20, $supplyBatch->fresh()->current_qty);
        $this->assertEquals(10, $medBatch->fresh()->current_qty);

        // 7. Nurse dispenses a supply item from supplies tab -> stored as item_type=supply & visible only in supplies tab
        $dispenseSupplyResp = $this->actingAs($this->nurseUser)->post(route('clinical-station.supplies.dispense', $this->surgery), [
            'batch_id' => $supplyBatch->id,
            'quantity' => 2,
            'timing' => 'تركيب كانيولا وريدية',
            'admin_notes' => 'تم التثبيت باليد اليمنى',
            'expected_type' => 'supply',
            'phase' => 'pre_op',
        ]);
        $dispenseSupplyResp->assertRedirect(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'supplies',
        ]));
        $dispenseSupplyResp->assertSessionHas('success');

        $this->assertEquals(18, $supplyBatch->fresh()->current_qty);
        $supplyRecord = SurgeryTreatment::where('surgery_id', $this->surgery->id)
            ->where('item_type', SurgeryTreatment::TYPE_SUPPLY)
            ->first();
        $this->assertNotNull($supplyRecord);
        $this->assertEquals('كانيولا زرقاء 22G', $supplyRecord->description);

        // Re-check tab separation after both records exist
        $this->actingAs($this->nurseUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'treatments',
        ]))->assertSee('Amoxicillin 500mg IV')->assertDontSee('تركيب كانيولا وريدية');

        $this->actingAs($this->nurseUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'supplies',
        ]))->assertSee('كانيولا زرقاء 22G')->assertSee('تركيب كانيولا وريدية')->assertDontSee('Amoxicillin 500mg IV');

        // 8. Resident (default read on supplies) sees supplies read-only and cannot dispense/delete supplies
        $residentSupplies = $this->actingAs($this->residentUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'supplies',
        ]));
        $residentSupplies->assertStatus(200);
        $residentSupplies->assertSee('وضع القراءة والاطلاع فقط');
        $residentSupplies->assertSee('كانيولا زرقاء 22G');
        $residentSupplies->assertDontSee('sup_dispense_batch_id');

        $this->actingAs($this->residentUser)->post(route('clinical-station.supplies.dispense', $this->surgery), [
            'batch_id' => $supplyBatch->id,
            'quantity' => 1,
            'expected_type' => 'supply',
        ])->assertSessionHas('error');

        $this->actingAs($this->residentUser)
            ->delete(route('clinical-station.treatments.delete', $supplyRecord))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('surgery_treatments', ['id' => $supplyRecord->id]);

        // 9. Nurse deletes the supply record & stays on supplies tab
        $this->actingAs($this->nurseUser)
            ->delete(route('clinical-station.treatments.delete', $supplyRecord), ['phase' => 'pre_op'])
            ->assertRedirect(route('clinical-station.show', [
                'surgery' => $this->surgery->id,
                'phase' => 'pre_op',
                'tab' => 'supplies',
            ]))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('surgery_treatments', ['id' => $supplyRecord->id]);
    }

    public function test_medications_mar_grid_pharmacy_catalog_and_role_separation(): void
    {
        // 1. Seed active & inactive pharmacy medicines
        $meropenem = Medicine::create([
            'name' => 'Meropenem',
            'generic_name' => 'Meropenem Trihydrate',
            'dosage_form' => 'Vial',
            'strength' => '1g',
            'main_unit' => 'فيال',
            'sub_unit' => 'فيال',
            'sub_units_count' => 1,
            'is_active' => true,
        ]);
        MedicineBatch::create([
            'medicine_id' => $meropenem->id,
            'batch_number' => 'LOT-MERO-01',
            'expiry_date' => now()->addYear()->toDateString(),
            'initial_quantity' => 12,
            'current_quantity' => 12,
            'current_sub_units' => 0,
            'status' => 'active',
        ]);

        Medicine::create([
            'name' => 'MeroInactive',
            'dosage_form' => 'Tablet',
            'is_active' => false,
        ]);

        // 2. Resident searches pharmacy catalog without needing 'view pharmacy' permission
        $searchResponse = $this->actingAs($this->residentUser)
            ->getJson(route('clinical-station.medicines.search', ['q' => 'Mero']));
        $searchResponse->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(1, 'medicines')
            ->assertJsonPath('medicines.0.id', $meropenem->id)
            ->assertJsonPath('medicines.0.total_stock', 12);

        // 3. Resident prescribes from Pharmacy catalog (q8h x 2 days = 6 dose slots)
        $this->actingAs($this->residentUser)->post(route('clinical-station.treatments.store', $this->surgery), [
            'phase' => 'pre_op',
            'medicine_id' => $meropenem->id,
            'dosage' => '1g IV',
            'route' => 'IV',
            'frequency_preset' => 'q8h',
            'duration_value' => 2,
            'duration_unit' => 'days',
            'start_at' => now()->format('Y-m-d H:i'),
            'admin_notes' => 'Infuse over 30 mins',
        ])->assertRedirect(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'treatments',
        ]));

        $scheduledMed = SurgeryTreatment::where('surgery_id', $this->surgery->id)
            ->where('medicine_id', $meropenem->id)
            ->first();
        $this->assertNotNull($scheduledMed);
        $this->assertEquals($this->residentUser->id, $scheduledMed->prescribed_by);
        $this->assertEquals('scheduled', $scheduledMed->frequency_mode);
        $this->assertEquals(8, $scheduledMed->interval_hours);
        $this->assertEquals(6, $scheduledMed->total_doses);
        $this->assertEquals('planned', $scheduledMed->status);
        $this->assertStringContainsString('Meropenem', $scheduledMed->description);

        // 4. Resident prescribes free-text PRN medication
        $this->actingAs($this->residentUser)->post(route('clinical-station.treatments.store', $this->surgery), [
            'phase' => 'pre_op',
            'description' => 'Morphine Sulfate 5mg',
            'dosage' => '5mg',
            'route' => 'IV',
            'frequency_preset' => 'prn',
        ])->assertSessionHas('success');

        $prnMed = SurgeryTreatment::where('surgery_id', $this->surgery->id)
            ->where('description', 'Morphine Sulfate 5mg')
            ->first();
        $this->assertNotNull($prnMed);
        $this->assertNull($prnMed->medicine_id);
        $this->assertEquals('prn', $prnMed->frequency_mode);
        $this->assertNull($prnMed->total_doses);

        // 5. Nurse view: sees MAR dose checkboxes for all 6 slots, does NOT see prescribe form or delete button
        $nurseView = $this->actingAs($this->nurseUser)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'phase' => 'pre_op',
            'tab' => 'treatments',
        ]));
        $nurseView->assertStatus(200);
        $nurseView->assertDontSee('prescribeMedicationForm');
        $nurseView->assertSee('data-slot-index="0"', false);
        $nurseView->assertSee('data-slot-index="5"', false);
        $nurseView->assertSee('تأشير صرف جرعة عند اللزوم');
        $nurseView->assertDontSee('action="'.route('clinical-station.treatments.delete', $scheduledMed).'"', false);
        $nurseView->assertDontSee('title="حذف الدواء"', false);

        // Nurse cannot prescribe or delete doctor's medication order
        $this->actingAs($this->nurseUser)->post(route('clinical-station.treatments.store', $this->surgery), [
            'description' => 'Nurse Unauthorized Med',
        ])->assertSessionHas('error');
        $this->assertDatabaseMissing('surgery_treatments', ['description' => 'Nurse Unauthorized Med']);

        $this->actingAs($this->nurseUser)->delete(route('clinical-station.treatments.delete', $scheduledMed))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('surgery_treatments', ['id' => $scheduledMed->id]);

        // 6. Resident cannot toggle dose checkboxes (403) and sees them disabled read-only
        $this->actingAs($this->residentUser)
            ->postJson(route('clinical-station.treatments.toggle-dose', $scheduledMed), [
                'slot_index' => 0,
                'checked' => 1,
            ])->assertStatus(403);

        // 7. Nurse checks doses #0 and #2 via MAR checkbox endpoint
        $this->actingAs($this->nurseUser)
            ->postJson(route('clinical-station.treatments.toggle-dose', $scheduledMed), [
                'slot_index' => 0,
                'checked' => 1,
            ])
            ->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('checked', true)
            ->assertJsonPath('administered_count', 1)
            ->assertJsonPath('expected_doses_count', 6);

        $this->actingAs($this->nurseUser)
            ->postJson(route('clinical-station.treatments.toggle-dose', $scheduledMed), [
                'slot_index' => 2,
                'checked' => 1,
            ])
            ->assertStatus(200)
            ->assertJsonPath('administered_count', 2)
            ->assertJsonPath('status', 'administered');

        // Reject out-of-range slot index (> expected - 1)
        $this->actingAs($this->nurseUser)
            ->postJson(route('clinical-station.treatments.toggle-dose', $scheduledMed), [
                'slot_index' => 10,
                'checked' => 1,
            ])
            ->assertStatus(422);

        // 8. Different nurse cannot undo another nurse's checked dose (422)
        $nurse2 = User::factory()->create();
        $nurse2->assignRole('nurse');

        $this->actingAs($nurse2)
            ->postJson(route('clinical-station.treatments.toggle-dose', $scheduledMed), [
                'slot_index' => 0,
                'checked' => 0,
            ])
            ->assertStatus(422);

        // Original nurse can undo within 30 minutes
        $this->actingAs($this->nurseUser)
            ->postJson(route('clinical-station.treatments.toggle-dose', $scheduledMed), [
                'slot_index' => 2,
                'checked' => 0,
            ])
            ->assertStatus(200)
            ->assertJsonPath('checked', false)
            ->assertJsonPath('administered_count', 1);

        // 9. Expired 30-min lock prevents nurse from undoing slot 0, while Admin can undo anytime
        $scheduledMed->refresh();
        $entries = $scheduledMed->administrations;
        $entries[0]['administered_at'] = now()->subMinutes(45)->format('Y-m-d H:i');
        $scheduledMed->update(['administrations' => $entries]);

        $this->actingAs($this->nurseUser)
            ->postJson(route('clinical-station.treatments.toggle-dose', $scheduledMed), [
                'slot_index' => 0,
                'checked' => 0,
            ])
            ->assertStatus(422);

        $this->actingAs($this->admin)
            ->postJson(route('clinical-station.treatments.toggle-dose', $scheduledMed), [
                'slot_index' => 0,
                'checked' => 0,
            ])
            ->assertStatus(200)
            ->assertJsonPath('checked', false)
            ->assertJsonPath('administered_count', 0)
            ->assertJsonPath('status', 'planned');

        // 10. PRN medication dose logging & undoing
        $prnResp = $this->actingAs($this->nurseUser)
            ->postJson(route('clinical-station.treatments.toggle-dose', $prnMed), [
                'slot_index' => null,
                'checked' => 1,
            ]);
        $prnResp->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('administered_count', 1)
            ->assertJsonPath('is_prn', true);

        $firstSlotIndex = $prnResp->json('slot_index');

        $this->actingAs($this->nurseUser)
            ->postJson(route('clinical-station.treatments.toggle-dose', $prnMed), [
                'slot_index' => $firstSlotIndex,
                'checked' => 0,
            ])
            ->assertStatus(200)
            ->assertJsonPath('administered_count', 0)
            ->assertJsonPath('status', 'planned');
    }
}
