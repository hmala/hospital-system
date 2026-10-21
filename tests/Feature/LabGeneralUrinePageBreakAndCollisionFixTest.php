<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\LabTestSubTest;
use App\Models\Patient;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LabGeneralUrinePageBreakAndCollisionFixTest extends TestCase
{
    use RefreshDatabase;

    protected $hospital;
    protected $dept;
    protected $doctorUser;
    protected $doctor;
    protected $labStaffUser;
    protected $patient;
    protected $visit;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'doctor']);
        Role::firstOrCreate(['name' => 'lab_staff']);
        Role::firstOrCreate(['name' => 'patient']);

        Permission::firstOrCreate(['name' => 'view lab tests']);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-TEST-' . rand(100, 999),
        ]);

        $this->dept = Department::firstOrCreate(
            ['name' => 'عيادة الباطنية'],
            [
                'hospital_id' => $this->hospital->id,
                'type' => 'internal',
                'room_number' => '1',
                'consultation_fee' => 15000,
                'working_hours_start' => '08:00:00',
                'working_hours_end' => '16:00:00',
                'is_active' => true,
            ]
        );

        $this->doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->doctorUser->assignRole('doctor');

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $this->dept->id,
            'type' => 'consultant',
            'specialization' => 'طب باطني',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-TEST-' . rand(100, 999),
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $this->labStaffUser = User::factory()->create(['role' => 'lab_staff']);
        $this->labStaffUser->assignRole('lab_staff');
        $this->labStaffUser->givePermissionTo('view lab tests');

        $patientUser = User::create([
            'name' => 'جاسم حامد عباس',
            'username' => 'jasim_' . uniqid(),
            'email' => 'jasim_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'status' => 'active',
        ]);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1992-05-26',
            'blood_group' => 'O+',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->dept->id,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'type' => 'consultation',
            'consultation_fee' => 15000,
            'status' => 'in_progress',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_id' => $appointment->id,
            'department_id' => $this->dept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص دوري',
            'status' => 'in_progress',
        ]);
    }

    public function test_subtest_unique_key_prevents_collision_between_fsh_and_lh()
    {
        $fsh = LabTest::create([
            'name' => 'Follicular-stimulating hormone',
            'code' => 'FSH',
            'price' => 15000,
            'is_active' => true,
            'result_entry_type' => 'sub_tests',
        ]);
        $fshSubMen = LabTestSubTest::create([
            'lab_test_id' => $fsh->id,
            'name' => 'Men',
            'unit' => 'mIU/mL',
            'reference_range' => '1.5 - 12.4',
        ]);

        $lh = LabTest::create([
            'name' => 'Luteinizing hormone',
            'code' => 'LH',
            'price' => 15000,
            'is_active' => true,
            'result_entry_type' => 'sub_tests',
        ]);
        $lhSubMen = LabTestSubTest::create([
            'lab_test_id' => $lh->id,
            'name' => 'Men',
            'unit' => 'mIU/mL',
            'reference_range' => '1.7 - 8.6',
        ]);

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'description' => 'طلب تحاليل مختبرية',
            'type' => 'lab',
            'status' => 'in_progress',
            'details' => [
                'tests' => ['Follicular-stimulating hormone', 'Luteinizing hormone'],
                'lab_test_ids' => [$fsh->id, $lh->id],
            ],
        ]);

        // Submit form with unique sub_{id} keys
        $response = $this->actingAs($this->labStaffUser)->put(route('lab.update', $request), [
            'status' => 'completed',
            'action' => 'complete',
            'test_results' => [
                'sub_' . $fshSubMen->id => [
                    'value' => '4.2',
                    'test_name' => 'Men',
                    'parent_test_name' => 'Follicular-stimulating hormone',
                    'sub_test_id' => $fshSubMen->id,
                    'lab_test_id' => $fsh->id,
                    'unit' => 'mIU/mL',
                    'reference_range' => '1.5 - 12.4',
                ],
                'sub_' . $lhSubMen->id => [
                    'value' => '3.1',
                    'test_name' => 'Men',
                    'parent_test_name' => 'Luteinizing hormone',
                    'sub_test_id' => $lhSubMen->id,
                    'lab_test_id' => $lh->id,
                    'unit' => 'mIU/mL',
                    'reference_range' => '1.7 - 8.6',
                ],
            ],
        ]);

        $response->assertRedirect(route('lab.index'));

        // Assert BOTH results are stored without overwriting
        $this->assertDatabaseHas('lab_results', [
            'request_id' => $request->id,
            'parent_test_name' => 'Follicular-stimulating hormone',
            'test_name' => 'Men',
            'value' => '4.2',
            'sub_test_id' => $fshSubMen->id,
        ]);

        $this->assertDatabaseHas('lab_results', [
            'request_id' => $request->id,
            'parent_test_name' => 'Luteinizing hormone',
            'test_name' => 'Men',
            'value' => '3.1',
            'sub_test_id' => $lhSubMen->id,
        ]);
    }

    public function test_print_page_isolates_general_urine_examination_with_page_break_and_shows_all_tests()
    {
        $cbc = LabTest::create([
            'name' => 'Complete blood count & Differential',
            'code' => 'CBC',
            'price' => 10000,
            'is_active' => true,
        ]);

        $gue = LabTest::create([
            'name' => 'General Urine Examination',
            'code' => 'GUE',
            'price' => 5000,
            'is_active' => true,
            'result_entry_type' => 'sub_tests',
        ]);

        $ferritin = LabTest::create([
            'name' => 'Ferritin',
            'code' => 'FER',
            'price' => 12000,
            'is_active' => true,
        ]);

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'description' => 'طلب تحاليل مختبرية',
            'type' => 'lab',
            'status' => 'completed',
            'details' => [
                'tests' => ['Complete blood count & Differential', 'Ferritin', 'General Urine Examination'],
                'test_attachments' => [
                    'Complete blood count & Differential' => [
                        'path' => 'lab_attachments/sample.pdf',
                        'name' => 'sample.pdf',
                        'mime' => 'application/pdf',
                        'title' => 'تقرير فحص: Complete blood count & Differential',
                    ],
                ],
            ],
        ]);

        // Create standard test result
        LabResult::create([
            'visit_id' => $this->visit->id,
            'request_id' => $request->id,
            'test_name' => 'Ferritin',
            'value' => '105.0',
            'unit' => 'µg/L',
            'status' => 'normal',
            'reference_range' => '30 - 400',
        ]);

        // Create Urine test results
        LabResult::create([
            'visit_id' => $this->visit->id,
            'request_id' => $request->id,
            'test_name' => 'Color',
            'parent_test_name' => 'General Urine Examination',
            'value' => 'Yellow',
            'status' => 'normal',
            'reference_range' => 'Yellow',
        ]);
        LabResult::create([
            'visit_id' => $this->visit->id,
            'request_id' => $request->id,
            'test_name' => 'Protein',
            'parent_test_name' => 'General Urine Examination',
            'value' => 'Nil',
            'status' => 'normal',
            'reference_range' => 'Nil',
        ]);
        LabResult::create([
            'visit_id' => $this->visit->id,
            'request_id' => $request->id,
            'test_name' => 'Pus cell',
            'parent_test_name' => 'General Urine Examination',
            'value' => '2-4',
            'unit' => '/HPF',
            'status' => 'normal',
            'reference_range' => '0-6',
        ]);

        $response = $this->actingAs($this->labStaffUser)->get(route('lab.print', $request));
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Attached device report for CBC appears in standard section
        $this->assertStringContainsString('Complete blood count', $html);
        $this->assertStringContainsString('Attached Device Report', $html);

        // 2. Ferritin appears
        $this->assertStringContainsString('Ferritin', $html);
        $this->assertStringContainsString('105.0', $html);

        // 3. GUE page break and tests appear directly without sub-categorization
        $this->assertStringContainsString('gue-page-break', $html);
        $this->assertStringContainsString('GENERAL URINE EXAMINATION (G.U.E)', $html);
        $this->assertStringNotContainsString('PHYSICAL EXAMINATION', $html);
        $this->assertStringNotContainsString('CHEMICAL EXAMINATION', $html);
        $this->assertStringNotContainsString('MICROSCOPIC EXAMINATION', $html);

        // 4. Urine subtests appear under GUE
        $this->assertStringContainsString('Color', $html);
        $this->assertStringContainsString('Protein', $html);
        $this->assertStringContainsString('Pus cell', $html);
    }
}
