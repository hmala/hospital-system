<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\LabTest;
use App\Models\Request as MedicalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DoctorLabRequestAutoMergeAndLabEditTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $labStaffUser;
    protected $doctor;
    protected $patient;
    protected $visit;
    protected $testCbc;
    protected $testRbs;
    protected $testLipid;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('doctor', 'web');
        Role::findOrCreate('lab_staff', 'web');

        Permission::findOrCreate('manage own visits', 'web');
        Permission::findOrCreate('view own visits', 'web');

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $this->doctorUser = User::create([
            'name' => 'د. حسام علي',
            'email' => 'dr_hussam@hospital.test',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo(['manage own visits', 'view own visits']);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-1234',
            'type' => 'consultant',
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $this->labStaffUser = User::create([
            'name' => 'فني مختبر علي',
            'email' => 'lab_tech@hospital.test',
            'password' => bcrypt('password'),
            'role' => 'lab_staff',
        ]);
        $this->labStaffUser->assignRole('lab_staff');

        $patientUser = User::create([
            'name' => 'أحمد قاسم',
            'email' => 'ahmed@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'blood_type' => 'O+',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص دوري',
            'status' => 'in_progress',
        ]);

        $this->testCbc = LabTest::create([
            'name' => 'Complete Blood Count (CBC)',
            'code' => 'CBC',
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->testRbs = LabTest::create([
            'name' => 'Random Blood Sugar (RBS)',
            'code' => 'RBS',
            'price' => 5000,
            'is_active' => true,
        ]);

        $this->testLipid = LabTest::create([
            'name' => 'Lipid Profile',
            'code' => 'LIPID',
            'price' => 20000,
            'is_active' => true,
        ]);
    }

    public function test_doctor_submitting_initial_lab_request_creates_single_request()
    {
        $response = $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'tests' => [$this->testCbc->name],
            'doctor_notes' => 'فحص مبدئي',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseCount('requests', 1);
        $request = MedicalRequest::where('visit_id', $this->visit->id)->first();
        $this->assertEquals('lab', $request->type);
        $this->assertEquals('pending', $request->payment_status);

        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
        $this->assertContains($this->testCbc->name, $details['tests']);
        $this->assertContains($this->testCbc->id, $details['lab_test_ids']);
    }

    public function test_doctor_submitting_second_lab_request_while_unpaid_merges_into_same_request()
    {
        // 1. First submission
        $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'tests' => [$this->testCbc->name],
            'doctor_notes' => 'الملاحظة الأولى',
        ]);

        $this->assertDatabaseCount('requests', 1);

        // 2. Second submission with RBS and Lipid Profile
        $response = $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'tests' => [$this->testRbs->name, $this->testLipid->name],
            'doctor_notes' => 'الملاحظة الثانية',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verify request count remains 1 (Auto-merged into single cashier invoice)
        $this->assertDatabaseCount('requests', 1);

        $mergedRequest = MedicalRequest::where('visit_id', $this->visit->id)->first();
        $details = is_string($mergedRequest->details) ? json_decode($mergedRequest->details, true) : $mergedRequest->details;

        // All 3 tests should be present
        $this->assertCount(3, $details['tests']);
        $this->assertContains($this->testCbc->name, $details['tests']);
        $this->assertContains($this->testRbs->name, $details['tests']);
        $this->assertContains($this->testLipid->name, $details['tests']);

        // All 3 IDs should be present
        $this->assertCount(3, $details['lab_test_ids']);
        $this->assertContains($this->testCbc->id, $details['lab_test_ids']);
        $this->assertContains($this->testRbs->id, $details['lab_test_ids']);
        $this->assertContains($this->testLipid->id, $details['lab_test_ids']);

        // Doctor notes appended
        $this->assertStringContainsString('الملاحظة الأولى', $details['doctor_notes']);
        $this->assertStringContainsString('الملاحظة الثانية', $details['doctor_notes']);
    }

    public function test_lab_staff_can_remove_test_from_unpaid_doctor_request()
    {
        // Doctor submits CBC and RBS
        $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'tests' => [$this->testCbc->name, $this->testRbs->name],
        ]);

        $request = MedicalRequest::where('visit_id', $this->visit->id)->first();

        // Lab staff deletes RBS
        $response = $this->actingAs($this->labStaffUser)->delete(
            route('staff.lab-requests.remove-test', [$request, $this->testRbs])
        );

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $request->refresh();
        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;

        // RBS removed from both tests and lab_test_ids
        $this->assertNotContains($this->testRbs->name, $details['tests']);
        $this->assertNotContains($this->testRbs->id, $details['lab_test_ids']);

        // CBC still remains
        $this->assertContains($this->testCbc->name, $details['tests']);
        $this->assertContains($this->testCbc->id, $details['lab_test_ids']);
    }

    public function test_lab_staff_cannot_remove_test_from_paid_request()
    {
        $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'tests' => [$this->testCbc->name],
        ]);

        $request = MedicalRequest::where('visit_id', $this->visit->id)->first();
        $request->payment_status = 'paid';
        $request->save();

        // Attempt deletion
        $response = $this->actingAs($this->labStaffUser)->delete(
            route('staff.lab-requests.remove-test', [$request, $this->testCbc])
        );

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $request->refresh();
        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
        $this->assertContains($this->testCbc->name, $details['tests']);
    }

    public function test_lab_staff_can_append_test_to_unpaid_request()
    {
        $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'tests' => [$this->testCbc->name],
        ]);

        $request = MedicalRequest::where('visit_id', $this->visit->id)->first();

        // Lab staff appends Lipid Profile
        $response = $this->actingAs($this->labStaffUser)->post(
            route('staff.lab-requests.append-tests', $request),
            [
                'extra_lab_test_ids' => [$this->testLipid->id],
            ]
        );

        $response->assertStatus(302);

        $this->assertDatabaseCount('requests', 1);

        $request->refresh();
        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;

        $this->assertContains($this->testCbc->name, $details['tests']);
        $this->assertContains($this->testLipid->name, $details['tests']);
        $this->assertContains($this->testLipid->id, $details['lab_test_ids']);
    }
}
