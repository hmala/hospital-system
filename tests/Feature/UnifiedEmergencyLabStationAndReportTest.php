<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Emergency;
use App\Models\EmergencyLabRequest;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnifiedEmergencyLabStationAndReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $labStaffUser;

    protected Emergency $emergency;

    protected EmergencyLabRequest $emergencyLab;

    protected LabTest $labTest1;

    protected LabTest $labTest2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);

        $dept = Department::first();

        Role::firstOrCreate(['name' => 'lab_staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'view lab tests', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view emergencies', 'guard_name' => 'web']);

        $this->labStaffUser = User::factory()->create([
            'name' => 'فني مختبر تجريبي',
            'email' => 'lab_staff_'.uniqid().'@example.com',
        ]);
        $this->labStaffUser->assignRole('lab_staff');
        $this->labStaffUser->givePermissionTo(['view lab tests', 'view emergencies']);

        $patientUser = User::factory()->create([
            'name' => 'مريض طوارئ تجريبي',
            'phone' => '07701234567',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'age' => 35,
            'blood_type' => 'O+',
        ]);

        $this->emergency = Emergency::create([
            'patient_id' => $patient->id,
            'emergency_type' => 'cardiac',
            'status' => 'in_progress',
            'priority' => 'critical',
            'symptoms' => 'ألم حاد في الصدر مع ضيق تنفس',
            'admission_time' => now(),
            'notes' => 'حالة طوارئ قلبية فورية',
        ]);

        $this->labTest1 = LabTest::create([
            'name' => 'Troponin I STAT',
            'code' => 'TROP_I',
            'main_category' => 'Biochemistry',
            'unit' => 'ng/mL',
            'price' => 15000,
            'is_active' => true,
        ]);

        $this->labTest2 = LabTest::create([
            'name' => 'Complete Blood Count (CBC)',
            'code' => 'CBC',
            'main_category' => 'Hematology',
            'unit' => 'g/dL',
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->emergencyLab = EmergencyLabRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $patient->id,
            'status' => 'pending',
            'priority' => 'critical',
            'notes' => 'فحص فوري عاجل للقلب',
            'requested_at' => now(),
        ]);

        $this->emergencyLab->labTests()->attach([
            $this->labTest1->id => ['result' => null],
            $this->labTest2->id => ['result' => null],
        ]);
    }

    public function test_lab_index_displays_emergency_requests_with_unified_station_buttons(): void
    {
        $response = $this->actingAs($this->labStaffUser)
            ->get(route('lab.index', ['source' => 'emergency']));

        $response->assertStatus(200);
        $response->assertSee('#'.$this->emergency->id, false);
        $response->assertSee(route('staff.emergency-lab.show', $this->emergencyLab), false);
        $response->assertSee(route('staff.emergency-lab.print', $this->emergencyLab), false);
    }

    public function test_emergency_lab_show_station_renders_full_clinical_view(): void
    {
        $response = $this->actingAs($this->labStaffUser)
            ->get(route('staff.emergency-lab.show', $this->emergencyLab));

        $response->assertStatus(200);
        $response->assertSee('طلب تحاليل طوارئ #'.$this->emergency->id);
        $response->assertSee('مريض طوارئ تجريبي');
        $response->assertSee('Troponin I STAT');
        $response->assertSee('Complete Blood Count (CBC)');
        $response->assertSee('إلحاق تحاليل أو باقات إضافية لطلب الطوارئ هذا');
        $response->assertSee(route('staff.emergency-lab.complete', $this->emergencyLab), false);
    }

    public function test_emergency_lab_results_can_be_saved_and_completed(): void
    {
        $resultsPayload = [
            'from_station' => '1',
            'action' => 'complete',
            'results' => [
                $this->labTest1->id => [
                    'value' => '0.04',
                    'unit' => 'ng/mL',
                    'status' => 'normal',
                    'reference' => '0.00 - 0.05',
                    'notes' => 'سليم ضمن الحدود الطبيعية',
                ],
                $this->labTest2->id => [
                    'value' => '14.2',
                    'unit' => 'g/dL',
                    'status' => 'normal',
                    'reference' => '13.0 - 17.0',
                    'notes' => 'Hb جيد',
                ],
            ],
            'notes' => 'تم الفحص والتدقيق في مختبر الطوارئ المركزي',
        ];

        $response = $this->actingAs($this->labStaffUser)
            ->put(route('staff.emergency-lab.complete', $this->emergencyLab), $resultsPayload);

        $response->assertRedirect(route('staff.emergency-lab.show', $this->emergencyLab));
        $response->assertSessionHas('success');

        $this->emergencyLab->refresh();
        $this->assertEquals('completed', $this->emergencyLab->status);
        $this->assertNotNull($this->emergencyLab->completed_at);
        $this->assertEquals($this->labStaffUser->id, $this->emergencyLab->verified_by);

        // التحقق من تخزين النتائج في pivot
        $rt1 = $this->emergencyLab->requestTests()->where('lab_test_id', $this->labTest1->id)->first();
        $this->assertNotNull($rt1);
        $this->assertStringContainsString('0.04', $rt1->result);
    }

    public function test_emergency_lab_results_can_be_saved_via_unified_test_results_form(): void
    {
        $payload = [
            'from_station' => '1',
            'action' => 'complete',
            'status' => 'completed',
            'test_results' => [
                'Troponin I STAT' => [
                    'value' => '0.08',
                    'unit' => 'ng/mL',
                    'reference_range' => '0.00 - 0.05',
                    'lab_test_id' => $this->labTest1->id,
                    'test_name' => 'Troponin I STAT',
                ],
                'Complete Blood Count (CBC)' => [
                    'value' => '13.5',
                    'unit' => 'g/dL',
                    'reference_range' => '12.0 - 16.0',
                    'lab_test_id' => $this->labTest2->id,
                    'test_name' => 'Complete Blood Count (CBC)',
                ],
            ],
            'result' => 'ملاحظات نهائية للمختبر',
        ];

        $response = $this->actingAs($this->labStaffUser)
            ->put(route('staff.emergency-lab.complete', $this->emergencyLab), $payload);

        $response->assertRedirect(route('staff.emergency-lab.show', $this->emergencyLab));
        $response->assertSessionHas('success');

        $this->emergencyLab->refresh();
        $this->assertEquals('completed', $this->emergencyLab->status);
        $this->assertEquals('ملاحظات نهائية للمختبر', $this->emergencyLab->notes);

        $rt1 = $this->emergencyLab->requestTests()->where('lab_test_id', $this->labTest1->id)->first();
        $this->assertNotNull($rt1);
        $this->assertStringContainsString('0.08', $rt1->result);
    }

    public function test_emergency_lab_print_report_matches_clinical_standard(): void
    {
        $response = $this->actingAs($this->labStaffUser)
            ->get(route('staff.emergency-lab.print', $this->emergencyLab));

        $response->assertStatus(200);
        $response->assertSee('CLINICAL LABORATORY REPORT (EMERGENCY STAT)');
        $response->assertSee('AL-KAFAAT PRIVATE HOSPITAL');
        $response->assertSee('مريض طوارئ تجريبي');
        $response->assertSee('Troponin I STAT');
        $response->assertSee('INVESTIGATION / TEST NAME');
        $response->assertSee('Laboratory Technician');
    }

    public function test_can_append_additional_lab_tests_to_emergency_request(): void
    {
        $newTest = LabTest::create([
            'name' => 'Blood Urea',
            'code' => 'UREA',
            'main_category' => 'Biochemistry',
            'unit' => 'mg/dL',
            'price' => 8000,
            'is_active' => true,
        ]);

        $payload = [
            'extra_lab_test_ids' => [$newTest->id],
        ];

        $response = $this->actingAs($this->labStaffUser)
            ->post(route('staff.emergency-lab.append-tests', $this->emergencyLab), $payload);

        $response->assertRedirect(route('staff.emergency-lab.show', $this->emergencyLab));
        $response->assertSessionHas('success');

        $this->emergencyLab->refresh();
        $this->assertContains($newTest->id, $this->emergencyLab->labTests->pluck('id')->toArray());
        $this->assertContains($this->labTest1->id, $this->emergencyLab->labTests->pluck('id')->toArray());
    }

    public function test_emergency_lab_print_report_isolates_general_urine_examination_to_page_2_when_combined_with_standard_tests(): void
    {
        $gueTest = LabTest::create([
            'name' => 'General Urine Examination (G.U.E)',
            'code' => 'GUE',
            'main_category' => 'Clinical Pathology',
            'unit' => '',
            'price' => 5000,
            'is_active' => true,
        ]);

        $this->emergencyLab->labTests()->attach($gueTest->id, ['result' => null]);

        $payload = [
            'from_station' => '1',
            'action' => 'complete',
            'status' => 'completed',
            'test_results' => [
                'Troponin I STAT' => [
                    'value' => '0.04',
                    'unit' => 'ng/mL',
                    'reference_range' => '0.00 - 0.05',
                    'lab_test_id' => $this->labTest1->id,
                    'test_name' => 'Troponin I STAT',
                ],
                'Color' => [
                    'value' => 'Pale Yellow',
                    'unit' => '',
                    'reference_range' => 'Pale Yellow',
                    'lab_test_id' => $gueTest->id,
                    'test_name' => 'Color',
                    'parent_test_name' => 'General Urine Examination (G.U.E)',
                ],
                'Pus Cells' => [
                    'value' => '2-4',
                    'unit' => 'HPF',
                    'reference_range' => '0 - 5',
                    'lab_test_id' => $gueTest->id,
                    'test_name' => 'Pus Cells',
                    'parent_test_name' => 'General Urine Examination (G.U.E)',
                ],
                'R.B.C' => [
                    'value' => '0-2',
                    'unit' => 'HPF',
                    'reference_range' => '0 - 3',
                    'lab_test_id' => $gueTest->id,
                    'test_name' => 'R.B.C',
                    'parent_test_name' => 'General Urine Examination (G.U.E)',
                ],
            ],
        ];

        $this->actingAs($this->labStaffUser)
            ->put(route('staff.emergency-lab.complete', $this->emergencyLab), $payload);

        $response = $this->actingAs($this->labStaffUser)
            ->get(route('staff.emergency-lab.print', $this->emergencyLab));

        $response->assertStatus(200);
        $response->assertSee('page-1', false);
        $response->assertSee('page-2', false);
        $response->assertSee('gue-page-break', false);
        $response->assertSee('GENERAL URINE EXAMINATION (G.U.E) - فحص الإدرار العام');
        $response->assertSee('Pus Cells');
        $response->assertSee('2-4');
        $response->assertSee('Troponin I STAT');
    }

    public function test_emergency_lab_print_report_renders_general_urine_examination_on_page_1_when_alone(): void
    {
        $gueTest = LabTest::create([
            'name' => 'General Urine Examination (G.U.E)',
            'code' => 'GUE_STANDALONE',
            'main_category' => 'Clinical Pathology',
            'unit' => '',
            'price' => 5000,
            'is_active' => true,
        ]);

        $standaloneReq = EmergencyLabRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->emergency->patient_id,
            'status' => 'completed',
            'priority' => 'urgent',
            'requested_at' => now(),
            'details' => [
                'test_results' => [
                    'Color' => [
                        'value' => 'Yellow',
                        'unit' => '',
                        'reference_range' => 'Pale Yellow',
                        'lab_test_id' => $gueTest->id,
                        'test_name' => 'Color',
                        'parent_test_name' => 'General Urine Examination (G.U.E)',
                    ],
                ],
            ],
        ]);
        $standaloneReq->labTests()->attach($gueTest->id);

        $response = $this->actingAs($this->labStaffUser)
            ->get(route('staff.emergency-lab.print', $standaloneReq));

        $response->assertStatus(200);
        $response->assertSee('page-1', false);
        $response->assertDontSee('page-2', false);
        $response->assertDontSee('class="report-page page-2 gue-page-break"', false);
        $response->assertSee('GENERAL URINE EXAMINATION (G.U.E) - فحص الإدرار العام');
        $response->assertSee('Yellow');
    }

    public function test_emergency_lab_print_report_handles_cbc_as_attachment_only_and_does_not_dump_empty_subtests(): void
    {
        $cbcTest = LabTest::create([
            'name' => 'CBC & Blood Film',
            'code' => 'cbc_3DIFF',
            'main_category' => 'Hematology',
            'result_entry_type' => LabTest::ENTRY_TYPE_ATTACHMENT,
            'price' => 20000,
            'is_active' => true,
        ]);

        $sub1 = \App\Models\LabTestSubTest::create([
            'lab_test_id' => $cbcTest->id,
            'name' => 'WBC',
            'unit' => '10^3/uL',
            'reference_range' => '4.0 - 10.0',
            'sort_order' => 1,
        ]);
        $sub2 = \App\Models\LabTestSubTest::create([
            'lab_test_id' => $cbcTest->id,
            'name' => 'RBC',
            'unit' => '10^6/uL',
            'reference_range' => '4.5 - 5.5',
            'sort_order' => 2,
        ]);

        $req = EmergencyLabRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->emergency->patient_id,
            'status' => 'completed',
            'priority' => 'critical',
            'requested_at' => now(),
            'details' => [
                'test_attachments' => [
                    'CBC & Blood Film' => 'emergency_lab_results/dummy_cbc.pdf',
                ],
            ],
        ]);
        $req->labTests()->attach($cbcTest->id);

        $response = $this->actingAs($this->labStaffUser)
            ->get(route('staff.emergency-lab.print', $req));

        $response->assertStatus(200);
        $response->assertSee('CBC &amp; Blood Film (PDF)', false);
        $response->assertSee('تقارير الأجهزة والفحوصات المرفقة');
        $response->assertDontSee('CBC & Blood Film — WBC', false);
        $response->assertDontSee('CBC & Blood Film — RBC', false);
    }

    public function test_emergency_lab_print_report_differentiates_cbc_attachment_from_blood_film_microscopic(): void
    {
        $cbcTest = LabTest::create([
            'name' => 'CBC & Blood Film',
            'code' => 'cbc_test_diff',
            'main_category' => 'Hematology',
            'result_entry_type' => LabTest::ENTRY_TYPE_ATTACHMENT,
            'price' => 20000,
            'is_active' => true,
        ]);

        $bloodFilmTest = LabTest::create([
            'name' => 'Blood Film',
            'code' => 'BLOOD_FILM_SPECIAL',
            'main_category' => 'Hematology',
            'result_entry_type' => LabTest::ENTRY_TYPE_SINGLE,
            'price' => 50000,
            'is_active' => true,
        ]);

        $req = EmergencyLabRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->emergency->patient_id,
            'status' => 'completed',
            'priority' => 'urgent',
            'requested_at' => now(),
            'details' => [
                'test_results' => [
                    'Blood Film' => [
                        'value' => 'Normocytic Normochromic Smear',
                        'unit' => '',
                        'reference_range' => 'Normal Morphology',
                        'lab_test_id' => $bloodFilmTest->id,
                        'test_name' => 'Blood Film',
                    ],
                ],
                'test_attachments' => [
                    'CBC & Blood Film' => 'emergency_lab_results/dummy_cbc.pdf',
                ],
            ],
        ]);
        $req->labTests()->attach([$cbcTest->id, $bloodFilmTest->id]);

        $response = $this->actingAs($this->labStaffUser)
            ->get(route('staff.emergency-lab.print', $req));

        $response->assertStatus(200);
        $response->assertSee('Blood Film');
        $response->assertSee('Normocytic Normochromic Smear');
        $response->assertSee('CBC &amp; Blood Film (PDF)', false);
        $response->assertSee('تقارير الأجهزة والفحوصات المرفقة');
    }
}
