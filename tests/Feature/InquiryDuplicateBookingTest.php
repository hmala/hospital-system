<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\RadiologyType;
use App\Models\ServiceType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InquiryDuplicateBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Doctor $doctor;
    protected Department $department;
    protected Department $inquiryDepartment;
    protected Hospital $hospital;
    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create(['name' => 'Admin User']);
        $this->adminUser->assignRole($adminRole);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'email' => 'info@alkafaat.test',
            'owner_name' => 'المدير العام',
            'phone' => '0770000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-' . uniqid(),
        ]);

        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'العيادات الاستشارية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'is_active' => true,
        ]);

        $this->inquiryDepartment = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'الاستعلامات',
            'type' => 'other',
            'room_number' => 'INQ-1',
            'consultation_fee' => 0,
            'working_hours_start' => '08:00',
            'working_hours_end' => '22:00',
            'is_active' => true,
        ]);

        ServiceType::create([
            'name' => 'checkup',
            'label' => 'كشف طبي',
            'is_active' => true,
        ]);

        ServiceType::create([
            'name' => 'radiology',
            'label' => 'أشعة وسونار',
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. زياد طارق']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'qualification' => 'استشاري',
            'license_number' => 'DOC-' . uniqid(),
            'consultation_fee' => 35000,
            'doctor_share_percentage' => 70,
            'hospital_share_percentage' => 30,
            'type' => 'consultant',
            'is_active' => true,
            'is_available_today' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'أحمد علي حسن', 'phone' => '07801112233']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'patient_type' => 'regular',
            'insurance_type' => 'none',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);
    }

    public function test_check_active_booking_endpoint_returns_has_active_true_for_active_checkup(): void
    {
        Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 5,
            'reason' => 'كشف طبي عام',
            'consultation_fee' => 35000,
            'duration' => 20,
            'status' => 'scheduled',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('inquiry.check-active-booking', [
                'patient_id' => $this->patient->id,
                'type' => 'checkup',
                'doctor_id' => $this->doctor->id,
            ]));

        $response->assertOk();
        $response->assertJson([
            'has_active' => true,
            'type' => 'checkup',
            'queue_number' => 5,
            'doctor_name' => 'د. زياد طارق',
        ]);
    }

    public function test_check_active_booking_endpoint_returns_has_active_false_when_appointment_completed(): void
    {
        Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 3,
            'reason' => 'كشف طبي سابق مكتمل',
            'consultation_fee' => 35000,
            'duration' => 20,
            'status' => 'completed',
            'payment_status' => 'paid',
            'insurance_type' => 'none',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('inquiry.check-active-booking', [
                'patient_id' => $this->patient->id,
                'type' => 'checkup',
                'doctor_id' => $this->doctor->id,
            ]));

        $response->assertOk();
        $response->assertJson([
            'has_active' => false,
        ]);
    }

    public function test_store_checkup_blocks_duplicate_booking_without_confirmation(): void
    {
        // Existing active appointment
        Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 1,
            'reason' => 'حجز أولي',
            'consultation_fee' => 35000,
            'duration' => 20,
            'status' => 'scheduled',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        // Attempt second booking without confirmation
        $response = $this->actingAs($this->adminUser)
            ->post(route('inquiry.store'), [
                'patient_id' => $this->patient->id,
                'request_type' => ['checkup'],
                'doctor_id' => $this->doctor->id,
                'department_id' => $this->department->id,
                'appointment_date' => Carbon::today()->format('Y-m-d'),
                'confirm_duplicate_booking' => '0',
            ]);

        $response->assertSessionHas('duplicate_booking_warning');
        $this->assertEquals(1, Appointment::where('patient_id', $this->patient->id)->count());
    }

    public function test_store_checkup_allows_duplicate_booking_when_confirmed_by_user(): void
    {
        // Existing active appointment
        Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 1,
            'reason' => 'حجز أولي',
            'consultation_fee' => 35000,
            'duration' => 20,
            'status' => 'scheduled',
            'payment_status' => 'pending',
            'insurance_type' => 'none',
        ]);

        // Attempt second booking WITH confirmation
        $response = $this->actingAs($this->adminUser)
            ->post(route('inquiry.store'), [
                'patient_id' => $this->patient->id,
                'request_type' => ['checkup'],
                'doctor_id' => $this->doctor->id,
                'department_id' => $this->department->id,
                'appointment_date' => Carbon::today()->format('Y-m-d'),
                'confirm_duplicate_booking' => '1',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(2, Appointment::where('patient_id', $this->patient->id)->count());

        $secondAppointment = Appointment::where('patient_id', $this->patient->id)->orderBy('id', 'desc')->first();
        $this->assertStringContainsString('حجز إضافي مؤكد من الاستعلامات', (string)$secondAppointment->notes);
    }

    public function test_check_active_booking_and_store_ultrasound_with_duplicate_prevention(): void
    {
        // Create an ultrasound radiology type
        $sonarType = RadiologyType::create([
            'name' => 'سونار البطن والحوض',
            'code' => 'US-01',
            'main_category' => 'سونار',
            'subcategory' => 'سونار',
            'base_price' => 25000,
            'is_active' => true,
        ]);

        // Create ultrasound staff
        $staffUser = User::factory()->create(['name' => 'أخصائي سونار']);
        $staffRole = Role::firstOrCreate(['name' => 'radiology_technician', 'guard_name' => 'web']);
        $staffUser->assignRole($staffRole);

        // Initial ultrasound booking
        $initialResponse = $this->actingAs($this->adminUser)
            ->post(route('inquiry.store'), [
                'patient_id' => $this->patient->id,
                'request_type' => ['radiology'],
                'radiology_category' => 'ultrasound',
                'ultrasound_type_id' => $sonarType->id,
                'ultrasound_staff_id' => $staffUser->id,
                'confirm_duplicate_booking' => '0',
            ]);

        $initialResponse->assertSessionHasNoErrors();

        // Verify active check endpoint detects it
        $checkResponse = $this->actingAs($this->adminUser)
            ->getJson(route('inquiry.check-active-booking', [
                'patient_id' => $this->patient->id,
                'type' => 'radiology',
                'radiology_category' => 'ultrasound',
            ]));

        $checkResponse->assertOk();
        $checkResponse->assertJson([
            'has_active' => true,
            'type' => 'ultrasound',
        ]);

        // Second ultrasound booking without confirmation should be blocked
        $secondAttempt = $this->actingAs($this->adminUser)
            ->post(route('inquiry.store'), [
                'patient_id' => $this->patient->id,
                'request_type' => ['radiology'],
                'radiology_category' => 'ultrasound',
                'ultrasound_type_id' => $sonarType->id,
                'ultrasound_staff_id' => $staffUser->id,
                'confirm_duplicate_booking' => '0',
            ]);

        $secondAttempt->assertSessionHas('duplicate_booking_warning');

        // Third ultrasound booking WITH confirmation should succeed
        $confirmedAttempt = $this->actingAs($this->adminUser)
            ->post(route('inquiry.store'), [
                'patient_id' => $this->patient->id,
                'request_type' => ['radiology'],
                'radiology_category' => 'ultrasound',
                'ultrasound_type_id' => $sonarType->id,
                'ultrasound_staff_id' => $staffUser->id,
                'confirm_duplicate_booking' => '1',
            ]);

        $confirmedAttempt->assertSessionHasNoErrors();
    }
}
