<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SameDayRecheckBookingTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;

    protected $doctor;

    protected $patientUser;

    protected $patient;

    protected $department;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('doctor', 'web');
        Role::findOrCreate('consultation_receptionist', 'web');

        Permission::findOrCreate('manage own visits', 'web');
        Permission::findOrCreate('view own visits', 'web');
        Permission::findOrCreate('create visits', 'web');
        Permission::findOrCreate('manage appointments', 'web');

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 35000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->doctorUser = User::create([
            'name' => 'د. علي حسين',
            'email' => 'dr.ali@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo(['manage own visits', 'view own visits']);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'باطنية',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-123',
            'consultation_fee' => 35000,
            'is_active' => true,
        ]);

        $this->patientUser = User::create([
            'name' => 'سلمى راضي علي',
            'email' => 'salma@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->patient = Patient::create([
            'user_id' => $this->patientUser->id,
            'gender' => 'female',
            'birth_date' => '1980-01-01',
            'blood_type' => 'O+',
        ]);
    }

    public function test_booking_same_day_for_same_doctor_automatically_becomes_free_recheck(): void
    {
        // 1. إنشاء زيارة سابقة مكتملة للمريض في نفس اليوم
        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص عام',
            'status' => 'completed',
        ]);

        $admin = User::create([
            'name' => 'الاستعلامات',
            'email' => 'reception@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        // 2. محاولة حجز موعد جديد لنفس المريض ونفس الطبيب في نفس اليوم
        $response = $this->actingAs($admin)->post(route('appointments.store'), [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '12:30',
            'reason' => 'كشف طبي عام',
            'consultation_fee' => 35000,
        ]);

        $response->assertRedirect(route('appointments.index'));

        // 3. التأكد من أن الحجز الجديد تم تعريفه كمراجعة مجانية برسم 0 د.ع ومرتبط بالزيارة الأصلية
        $newAppt = Appointment::where('patient_id', $this->patient->id)
            ->where('doctor_id', $this->doctor->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($newAppt);
        $this->assertTrue((bool) $newAppt->is_free_recheck);
        $this->assertEquals(0, (float) $newAppt->consultation_fee);
        $this->assertEquals('paid', $newAppt->payment_status);
        $this->assertEquals($visit->id, $newAppt->recheck_parent_visit_id);
    }

    public function test_doctor_workstation_merges_all_same_day_requests_for_patient(): void
    {
        // زيارة أولى بها فحصين
        $visit1 = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص عام',
            'status' => 'completed',
        ]);

        $req1 = MedicalRequest::create([
            'visit_id' => $visit1->id,
            'patient_id' => $this->patient->id,
            'type' => 'lab',
            'status' => 'completed',
            'description' => 'CBC test',
            'details' => ['tests' => ['CBC']],
        ]);

        // زيارة ثانية لنفس اليوم فارغة
        $visit2 = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '12:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص عام ثان',
            'status' => 'in_progress',
        ]);

        // فتح الزيارة الثانية بالطبيب
        $response = $this->actingAs($this->doctorUser)->get(route('doctor.visits.show', $visit2));

        $response->assertStatus(200);
        // التحقق من ظهور فحص الزيارة الأولى داخل الزيارة الثانية
        $loadedVisit = $response->viewData('visit');
        $this->assertCount(1, $loadedVisit->requests);
        $this->assertEquals($req1->id, $loadedVisit->requests->first()->id);
    }
}
