<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\RadiologyType;
use App\Models\Request as MedicalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DoctorRadiologyRequestAutoMergeAndStaffEditTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $radStaffUser;
    protected $doctor;
    protected $patient;
    protected $visit;
    protected $radChest;
    protected $radKnee;
    protected $radSonar;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('doctor', 'web');
        Role::findOrCreate('radiology_staff', 'web');

        Permission::findOrCreate('manage own visits', 'web');
        Permission::findOrCreate('view own visits', 'web');
        Permission::findOrCreate('view radiology', 'web');
        Permission::findOrCreate('process radiology requests', 'web');

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
            'email' => 'dr_hussam_rad@hospital.test',
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
            'license_number' => 'DOC-RAD-12',
            'type' => 'consultant',
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $this->radStaffUser = User::create([
            'name' => 'فني أشعة مصطفى',
            'email' => 'rad_tech@hospital.test',
            'password' => bcrypt('password'),
            'role' => 'radiology_staff',
        ]);
        $this->radStaffUser->assignRole('radiology_staff');
        $this->radStaffUser->givePermissionTo(['view radiology', 'process radiology requests']);

        $patientUser = User::create([
            'name' => 'ياسر عمار',
            'email' => 'yasser@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'blood_type' => 'A+',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'ألم في الصدر والركبة',
            'status' => 'in_progress',
        ]);

        $this->radChest = RadiologyType::create([
            'name' => 'X-Ray Chest PA',
            'code' => 'XR-CHEST',
            'base_price' => 20000,
            'main_category' => 'أشعة',
            'subcategory' => 'أشعة',
            'is_active' => true,
        ]);

        $this->radKnee = RadiologyType::create([
            'name' => 'X-Ray Knee AP/LAT',
            'code' => 'XR-KNEE',
            'base_price' => 25000,
            'main_category' => 'أشعة',
            'subcategory' => 'أشعة',
            'is_active' => true,
        ]);

        $this->radSonar = RadiologyType::create([
            'name' => 'Ultrasound Abdomen & Pelvis',
            'code' => 'US-ABD',
            'base_price' => 35000,
            'main_category' => 'سونار',
            'subcategory' => 'سونار',
            'is_active' => true,
        ]);
    }

    public function test_doctor_submitting_initial_radiology_request_creates_single_request()
    {
        $response = $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'radiology_types' => [$this->radChest->id],
            'radiology_targets' => [
                $this->radChest->id => 'الصدر',
            ],
            'doctor_notes' => 'صورة صدر للمعاينة',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseCount('requests', 1);
        $request = MedicalRequest::where('visit_id', $this->visit->id)->first();
        $this->assertEquals('radiology', $request->type);
        $this->assertEquals('pending', $request->payment_status);

        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
        $this->assertContains($this->radChest->id, $details['radiology_type_ids']);
    }

    public function test_doctor_submitting_second_radiology_request_while_unpaid_merges_into_same_request()
    {
        // 1. First submission
        $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'radiology_types' => [$this->radChest->id],
            'doctor_notes' => 'الملاحظة الأولى',
        ]);

        $this->assertDatabaseCount('requests', 1);

        // 2. Second submission with Knee and Sonar
        $response = $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'radiology_types' => [$this->radKnee->id, $this->radSonar->id],
            'doctor_notes' => 'الملاحظة الثانية',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verify request count remains 1 (Auto-merged into single cashier invoice)
        $this->assertDatabaseCount('requests', 1);

        $mergedRequest = MedicalRequest::where('visit_id', $this->visit->id)->first();
        $details = is_string($mergedRequest->details) ? json_decode($mergedRequest->details, true) : $mergedRequest->details;

        // All 3 IDs should be present
        $this->assertCount(3, $details['radiology_type_ids']);
        $this->assertContains($this->radChest->id, $details['radiology_type_ids']);
        $this->assertContains($this->radKnee->id, $details['radiology_type_ids']);
        $this->assertContains($this->radSonar->id, $details['radiology_type_ids']);

        // Doctor notes appended
        $this->assertStringContainsString('الملاحظة الأولى', $details['doctor_notes']);
        $this->assertStringContainsString('الملاحظة الثانية', $details['doctor_notes']);
    }

    public function test_radiology_staff_can_remove_test_from_unpaid_doctor_request()
    {
        // Doctor submits Chest and Knee
        $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'radiology_types' => [$this->radChest->id, $this->radKnee->id],
        ]);

        $request = MedicalRequest::where('visit_id', $this->visit->id)->first();

        // Radiology staff deletes Knee
        $response = $this->actingAs($this->radStaffUser)->delete(
            route('radiology-staff.remove-type', [$request, $this->radKnee])
        );

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $request->refresh();
        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;

        // Knee removed
        $this->assertNotContains($this->radKnee->id, $details['radiology_type_ids']);

        // Chest still remains
        $this->assertContains($this->radChest->id, $details['radiology_type_ids']);
    }

    public function test_radiology_staff_cannot_remove_test_from_paid_request()
    {
        $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'radiology_types' => [$this->radChest->id],
        ]);

        $request = MedicalRequest::where('visit_id', $this->visit->id)->first();
        $request->payment_status = 'paid';
        $request->save();

        // Attempt deletion
        $response = $this->actingAs($this->radStaffUser)->delete(
            route('radiology-staff.remove-type', [$request, $this->radChest])
        );

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $request->refresh();
        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
        $this->assertContains($this->radChest->id, $details['radiology_type_ids']);
    }

    public function test_radiology_staff_can_append_test_to_unpaid_request()
    {
        $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'radiology_types' => [$this->radChest->id],
        ]);

        $request = MedicalRequest::where('visit_id', $this->visit->id)->first();

        // Radiology staff appends Sonar
        $response = $this->actingAs($this->radStaffUser)->post(
            route('radiology-staff.append-types', $request),
            [
                'radiology_type_ids' => [$this->radSonar->id],
            ]
        );

        $response->assertStatus(302);

        $this->assertDatabaseCount('requests', 1);

        $request->refresh();
        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;

        $this->assertContains($this->radChest->id, $details['radiology_type_ids']);
        $this->assertContains($this->radSonar->id, $details['radiology_type_ids']);
    }
}
