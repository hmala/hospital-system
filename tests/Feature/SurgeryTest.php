<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Surgery;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Visit;
use App\Models\Room;
use App\Models\SurgicalOperation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_surgery_details(): void
    {
        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Seed hospital and departments
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $department = Department::where('type', 'surgery')->first();

        // Create doctor user and doctor profile
        $doctorUser = User::create([
            'name' => 'Dr. Ahmad',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctorUser->assignRole('doctor');

        $doctor = new Doctor();
        $doctor->user_id = $doctorUser->id;
        $doctor->department_id = $department->id;
        $doctor->phone = '12345678';
        $doctor->specialization = 'General Surgery';
        $doctor->qualification = 'MBBS';
        $doctor->license_number = 'LIC123';
        $doctor->experience_years = 10;
        $doctor->bio = 'Surgeon';
        $doctor->consultation_fee = 50000;
        $doctor->max_patients_per_day = 10;
        $doctor->is_active = true;
        $doctor->save();

        // Create patient user and profile
        $patientUser = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 30,
            'gender' => 'male',
            'blood_group' => 'A+',
        ]);

        // Create an admin user to access the pages
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        // Create a visit
        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'doctor_id' => $doctor->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => now()->toTimeString(),
            'visit_type' => 'surgery',
            'status' => 'pending_payment',
            'chief_complaint' => 'Normal',
        ]);

        // Create a surgery
        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'visit_id' => $visit->id,
            'surgery_type' => 'Appendectomy',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now()->toTimeString(),
            'status' => 'scheduled',
            'surgery_fee' => 100000,
        ]);

        // Access the page as admin
        $response = $this->actingAs($admin)->get(route('surgeries.show', $surgery));
        $response->assertStatus(200);
        $response->assertSee('تفاصيل العملية');
    }

    public function test_can_update_surgery_details(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        // Seed hospital and departments
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $department = Department::where('type', 'surgery')->first();

        $doctorUser = User::create([
            'name' => 'Dr. Ahmad',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctorUser->assignRole('doctor');

        $doctor = new Doctor();
        $doctor->user_id = $doctorUser->id;
        $doctor->department_id = $department->id;
        $doctor->phone = '12345678';
        $doctor->specialization = 'General Surgery';
        $doctor->qualification = 'MBBS';
        $doctor->license_number = 'LIC123';
        $doctor->experience_years = 10;
        $doctor->bio = 'Surgeon';
        $doctor->consultation_fee = 50000;
        $doctor->max_patients_per_day = 10;
        $doctor->is_active = true;
        $doctor->save();

        $patientUser = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 30,
            'gender' => 'male',
            'blood_group' => 'A+',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'doctor_id' => $doctor->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => now()->toTimeString(),
            'visit_type' => 'surgery',
            'status' => 'pending_payment',
            'chief_complaint' => 'Normal',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'visit_id' => $visit->id,
            'surgery_type' => 'Appendectomy',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now()->toTimeString(),
            'status' => 'scheduled',
            'surgery_fee' => 100000,
        ]);

        $response = $this->actingAs($admin)->patch(route('surgeries.updateDetails', $surgery), [
            'diagnosis' => 'Acute Appendicitis',
            'anesthesia_type' => 'general',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'estimated_duration_minutes' => 90,
            'post_op_notes' => 'Patient stable, recovery initiated.',
            'prescribed_medications' => [
                'surgery_treatments' => [
                    $surgery->id => [
                        [
                            'description' => 'Paracetamol',
                            'dosage' => '500mg',
                            'timing' => 'Every 6 hours',
                            'duration_value' => 3,
                            'duration_unit' => 'days'
                        ]
                    ]
                ]
            ]
        ]);

        $response->assertRedirect(route('surgeries.show', $surgery));
        
        $surgery->refresh();
        $this->assertEquals('Acute Appendicitis', $surgery->diagnosis);
        $this->assertEquals('general', $surgery->anesthesia_type);
        $this->assertEquals(90, $surgery->estimated_duration);
        $this->assertCount(1, $surgery->surgeryTreatments);
        $this->assertEquals('Paracetamol', $surgery->surgeryTreatments->first()->description);
    }

    public function test_can_book_surgery_with_multiple_operations_and_verify_fees(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $department = Department::where('type', 'surgery')->first();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_multi_ops@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $doctorUser = User::create([
            'name' => 'Dr. Ali',
            'email' => 'ali@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctorUser->assignRole('doctor');

        $doctor = new Doctor();
        $doctor->user_id = $doctorUser->id;
        $doctor->department_id = $department->id;
        $doctor->phone = '12345678';
        $doctor->specialization = 'General Surgery';
        $doctor->qualification = 'Board';
        $doctor->license_number = 'LIC456';
        $doctor->experience_years = 12;
        $doctor->is_active = true;
        $doctor->save();

        $patientUser = User::create([
            'name' => 'Patient Multi Ops',
            'email' => 'multi_ops@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 45,
            'gender' => 'female',
            'blood_group' => 'B+',
        ]);

        $room = Room::create([
            'room_number' => 'OP-101',
            'room_type' => 'regular',
            'department_id' => $department->id,
            'status' => 'available',
        ]);

        $primaryOp = SurgicalOperation::create([
            'name' => 'Cholecystectomy',
            'category' => 'General Surgery',
            'fee' => 500000,
        ]);

        $secondaryOp = SurgicalOperation::create([
            'name' => 'Hernia Repair',
            'category' => 'General Surgery',
            'fee' => 250000,
        ]);

        // اختبار عرض صفحة حجز العملية والتحقق من عدم وجود أي خطأ بليد أو جافاسكربت
        $createPageResponse = $this->actingAs($admin)->get(route('surgeries.create', [
            'department_id' => $department->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'referring_doctor_name' => 'د. قمر سعد',
            'visit_id' => 161,
        ]));
        $createPageResponse->assertStatus(200);
        $createPageResponse->assertSee('إضافة عملية مرافقة');

        $response = $this->actingAs($admin)->post(route('surgeries.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'room_id' => $room->id,
            'expected_stay_days' => 2,
            'surgery_category' => 'General Surgery',
            'surgical_operation_id' => $primaryOp->id,
            'custom_surgery_fee' => '500,000',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '10:00',
            'referring_doctor_name' => 'د. أحمد الاستشاري',
            'additional_operations' => [
                [
                    'surgical_operation_id' => $secondaryOp->id,
                    'fee' => '250,000',
                    'notes' => 'تداخل جراحي تكميلي في نفس الجلسة',
                ]
            ]
        ]);

        $response->assertRedirect(route('surgeries.index'));

        $surgery = Surgery::where('patient_id', $patient->id)->first();
        $this->assertNotNull($surgery);
        $this->assertEquals('Cholecystectomy', $surgery->surgery_type);
        $this->assertEquals(500000, $surgery->surgery_fee);

        $this->assertCount(1, $surgery->additionalOperations);
        $addOp = $surgery->additionalOperations->first();
        $this->assertEquals($secondaryOp->id, $addOp->surgical_operation_id);
        $this->assertEquals(250000, $addOp->fee);
        $this->assertEquals('تداخل جراحي تكميلي في نفس الجلسة', $addOp->notes);

        // فحص احتساب الكاشير وإجمالي العمليات
        $totalCombinedFee = ($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee');
        $this->assertEquals(750000, $totalCombinedFee);
    }
}
