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
use App\Models\RadiologyType;
use App\Models\Request as MedicalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DoctorRemoveRequestItemTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $doctor;
    protected $patient;
    protected $visit;
    protected $testCbc;
    protected $testRbs;
    protected $radCXR;
    protected $radAbdomen;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('doctor', 'web');

        Permission::findOrCreate('manage own visits', 'web');
        Permission::findOrCreate('view own visits', 'web');

        $hospital = Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-002',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '1',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $this->doctorUser = User::create([
            'name' => 'د. حيدر',
            'username' => 'dr_haidar',
            'email' => 'dr_haidar@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo(['manage own visits', 'view own visits']);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $department->id,
            'type' => 'consultant',
            'specialization' => 'internal_medicine',
            'qualification' => 'MBChB, FIBMS',
            'license_number' => 'DOC-9988',
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $patientUser = User::create([
            'name' => 'كرار علي',
            'username' => 'karrar',
            'email' => 'karrar@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
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
            'name' => 'CBC Test',
            'code' => 'CBC',
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->testRbs = LabTest::create([
            'name' => 'RBS Test',
            'code' => 'RBS',
            'price' => 5000,
            'is_active' => true,
        ]);

        $this->radCXR = RadiologyType::create([
            'name' => 'Chest X-Ray',
            'code' => 'XR-CHEST',
            'base_price' => 20000,
            'price' => 20000,
            'main_category' => 'أشعة',
            'subcategory' => 'أشعة',
            'is_active' => true,
        ]);

        $this->radAbdomen = RadiologyType::create([
            'name' => 'Abdominal Ultrasound',
            'code' => 'US-ABD',
            'base_price' => 25000,
            'price' => 25000,
            'main_category' => 'سونار',
            'subcategory' => 'سونار',
            'is_active' => true,
        ]);
    }

    public function test_doctor_can_remove_individual_lab_test_from_unpaid_request()
    {
        $this->actingAs($this->doctorUser);

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل دم',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => [
                'tests' => ['CBC Test', 'RBS Test'],
                'lab_test_ids' => [$this->testCbc->id, $this->testRbs->id],
                'total_amount' => 15000,
            ]
        ]);

        $response = $this->deleteJson(route('doctor.requests.remove-item', $request->id), [
            'item_value' => 'CBC Test',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'remaining_count' => 1,
            'is_cancelled' => false,
            'new_total' => 5000,
        ]);

        $request->refresh();
        $this->assertEquals(['RBS Test'], $request->details['tests']);
        $this->assertEquals([$this->testRbs->id], $request->details['lab_test_ids']);
        $this->assertEquals(5000, $request->total_amount);
        $this->assertEquals('pending', $request->status);
    }

    public function test_removing_last_lab_test_marks_request_as_cancelled()
    {
        $this->actingAs($this->doctorUser);

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'طلب تحليل واحد',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => [
                'tests' => ['CBC Test'],
                'lab_test_ids' => [$this->testCbc->id],
                'total_amount' => 10000,
            ]
        ]);

        $response = $this->deleteJson(route('doctor.requests.remove-item', $request->id), [
            'item_value' => 'CBC Test',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'remaining_count' => 0,
            'is_cancelled' => true,
        ]);

        $request->refresh();
        $this->assertEquals([], $request->details['tests']);
        $this->assertEquals('cancelled', $request->status);
    }

    public function test_doctor_can_remove_radiology_type_from_unpaid_request()
    {
        $this->actingAs($this->doctorUser);

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'description' => 'طلب أشعة متعددة',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => [
                'radiology_types' => [$this->radCXR->id, $this->radAbdomen->id],
                'radiology_type_ids' => [$this->radCXR->id, $this->radAbdomen->id],
                'radiology_targets' => [
                    $this->radCXR->id => 'الصدر',
                    $this->radAbdomen->id => 'البطن',
                ],
                'total_amount' => 45000,
            ]
        ]);

        $response = $this->deleteJson(route('doctor.requests.remove-item', $request->id), [
            'item_value' => $this->radCXR->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'remaining_count' => 1,
            'is_cancelled' => false,
            'new_total' => 25000,
        ]);

        $request->refresh();
        $this->assertEquals([$this->radAbdomen->id], $request->details['radiology_types']);
        $this->assertEquals([$this->radAbdomen->id => 'البطن'], $request->details['radiology_targets']);
        $this->assertEquals(25000, $request->total_amount);
    }

    public function test_doctor_cannot_remove_item_from_paid_request()
    {
        $this->actingAs($this->doctorUser);

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'طلب تحليل مسدد',
            'status' => 'pending',
            'payment_status' => 'paid',
            'details' => [
                'tests' => ['CBC Test'],
                'lab_test_ids' => [$this->testCbc->id],
                'total_amount' => 10000,
            ]
        ]);

        $response = $this->deleteJson(route('doctor.requests.remove-item', $request->id), [
            'item_value' => 'CBC Test',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        $request->refresh();
        $this->assertEquals(['CBC Test'], $request->details['tests']);
        $this->assertEquals('paid', $request->payment_status);
    }

    public function test_doctor_visit_page_hides_cancelled_requests_from_active_list()
    {
        $this->actingAs($this->doctorUser);

        MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'طلب ملغي تجريبي',
            'status' => 'cancelled',
            'payment_status' => 'pending',
            'details' => [
                'description' => 'طلب ملغي تجريبي',
                'cancellation_reason' => 'ألغي بواسطة الطبيب',
            ]
        ]);

        $activeRequest = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'طلب تحليل نشط',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => [
                'tests' => ['CBC Test'],
                'lab_test_ids' => [$this->testCbc->id],
            ]
        ]);

        $response = $this->get(route('doctor.visits.show', $this->visit->id));

        $response->assertStatus(200);
        $response->assertSee('CBC Test');
        $response->assertSee('أرشيف الطلبات الملغاة');
        $response->assertSee('طلب ملغي تجريبي');
    }
}
