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

class DoctorRequestClinicalNotesTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $doctor;
    protected $visit;
    protected $labTest;
    protected $radiologyType;
    protected $labUser;
    protected $radUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('doctor', 'web');
        Role::findOrCreate('lab_staff', 'web');
        Role::findOrCreate('radiology', 'web');

        Permission::findOrCreate('manage own visits', 'web');
        Permission::findOrCreate('view lab tests', 'web');
        Permission::findOrCreate('view radiology', 'web');

        if (!\Illuminate\Support\Facades\Schema::hasTable('lab_test_sub_tests')) {
            \Illuminate\Support\Facades\Schema::create('lab_test_sub_tests', function ($table) {
                $table->id();
                $table->unsignedBigInteger('lab_test_id');
                $table->string('name');
                $table->string('unit')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $hospital = Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $dept = Department::create([
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'hospital_id' => $hospital->id,
            'is_active' => true,
        ]);

        $this->doctorUser = User::create([
            'name' => 'د. زياد طارق',
            'email' => 'dr.ziad@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo('manage own visits');

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-1234',
            'consultation_fee' => 15000,
            'is_available_today' => true,
        ]);

        $patientUser = User::create([
            'name' => 'علي حسن',
            'email' => 'ali@example.com',
            'password' => bcrypt('password'),
        ]);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => $hospital->id,
            'gender' => 'male',
            'national_id' => '1234567890',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $dept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص سريري',
            'status' => 'in_progress',
        ]);

        $this->labTest = LabTest::create([
            'name' => 'Complete Blood Count (CBC)',
            'code' => 'CBC-01',
            'price' => 10000,
            'category' => 'hematology',
            'is_active' => true,
        ]);

        $this->radiologyType = RadiologyType::create([
            'name' => 'Chest X-Ray PA',
            'code' => 'CXR-01',
            'price' => 15000,
            'base_price' => 15000,
            'main_category' => 'أشعة',
            'is_active' => true,
        ]);

        $this->labUser = User::create([
            'name' => 'فني المختبر أحمد',
            'email' => 'lab.ahmed@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->labUser->assignRole('lab_staff');
        $this->labUser->givePermissionTo('view lab tests');

        $this->radUser = User::create([
            'name' => 'فني الأشعة سامر',
            'email' => 'rad.samer@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->radUser->assignRole('radiology');
        $this->radUser->givePermissionTo('view radiology');
    }

    public function test_doctor_can_send_lab_request_with_clinical_notes_and_it_appears_in_lab_views()
    {
        $noteText = 'المريض صائم 12 ساعة - يرجى فحص عاجل مع كتابة مستوى السكر';

        $response = $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'tests' => [$this->labTest->name],
            'doctor_notes' => $noteText,
            'priority' => 'normal',
        ]);

        $response->assertStatus(302);

        $request = MedicalRequest::where('visit_id', $this->visit->id)->where('type', 'lab')->first();
        $this->assertNotNull($request);
        $this->assertEquals($noteText, $request->details['doctor_notes']);
        $this->assertEquals($noteText, $request->doctor_notes);

        // التحقق من ظهور الملاحظة في شاشة فحص المختبر
        $showResponse = $this->actingAs($this->labUser)->get(route('lab.show', $request));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($noteText);
        $showResponse->assertSee('ملاحظات وتوجيهات الطبيب المحول');

        // التحقق من ظهور الملاحظة في قائمة طابور المختبر
        $indexResponse = $this->actingAs($this->labUser)->get(route('lab.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($noteText);
    }

    public function test_doctor_can_send_radiology_request_with_clinical_notes_and_it_appears_in_radiology_views()
    {
        $noteText = 'شك سريري بوجود كسر في القفص الصدري الأيمن - يرجى وضعية جانبية إضافية';

        $response = $this->actingAs($this->doctorUser)->post(route('doctor.requests.store'), [
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'radiology_types' => [$this->radiologyType->id],
            'doctor_notes' => $noteText,
            'priority' => 'normal',
        ]);

        $response->assertStatus(302);

        $request = MedicalRequest::where('visit_id', $this->visit->id)->where('type', 'radiology')->first();
        $this->assertNotNull($request);
        $this->assertEquals($noteText, $request->details['doctor_notes']);
        $this->assertEquals($noteText, $request->doctor_notes);

        // التحقق من ظهور الملاحظة في شاشة كتابة تقرير الأشعة
        $showResponse = $this->actingAs($this->radUser)->get(route('radiology-staff.show', $request));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($noteText);
        $showResponse->assertSee('ملاحظات وتوجيهات الطبيب المحول');

        // التحقق من ظهور الملاحظة في قائمة طابور الأشعة
        $indexResponse = $this->actingAs($this->radUser)->get(route('radiology-staff.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($noteText);
    }
}
