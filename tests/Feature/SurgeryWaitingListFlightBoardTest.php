<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\OperationTheater;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\HospitalSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryWaitingListFlightBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(HospitalSeeder::class);
        $this->seed(DepartmentSeeder::class);
    }

    private function createSurgerySetup(string $status = 'scheduled'): array
    {
        $department = Department::where('type', 'surgery')->first() ?? Department::first();

        $doctorUser = User::create([
            'name' => 'Dr. Surgeon Test',
            'email' => 'surgeon_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctorUser->assignRole('doctor');

        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'General Surgery',
            'qualification' => 'MBBS',
            'license_number' => 'LIC'.rand(1000, 9999),
            'consultation_fee' => 50000,
            'is_active' => true,
        ]);

        $patientUser = User::create([
            'name' => 'Patient Waiting Test',
            'email' => 'patient_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 42,
            'gender' => 'male',
            'blood_group' => 'O+',
            'file_number' => 'FILE-'.rand(1000, 9999),
            'insurance_type' => 'hi',
        ]);

        $room = Room::create([
            'name' => 'OR Suite '.rand(1, 999),
            'room_number' => 'OR-'.uniqid(),
            'department_id' => $department->id,
            'room_type' => 'normal',
            'status' => 'available',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'room_id' => $room->id,
            'surgery_type' => 'Laparoscopic Cholecystectomy',
            'scheduled_date' => today()->format('Y-m-d'),
            'scheduled_time' => now(),
            'status' => $status,
            'payment_status' => 'paid',
            'insurance_type' => 'hi',
        ]);

        return compact('department', 'doctor', 'patient', 'room', 'surgery');
    }

    public function test_unauthorized_user_cannot_view_waiting_list(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $response = $this->actingAs($user)->get(route('surgeries.waiting'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_waiting_list_and_week_navigation(): void
    {
        $setup = $this->createSurgerySetup('scheduled');

        $admin = User::create([
            'name' => 'OR Admin',
            'email' => 'oradmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('surgeries.waiting'));

        $response->assertStatus(200);
        $response->assertSee('جدول وقائمة انتظار العمليات الجراحية');
        $response->assertSee('اختر اليوم من الأسبوع');
        $response->assertSee('Laparoscopic Cholecystectomy');
        $response->assertSee('Dr. Surgeon Test');
        $response->assertSee('Patient Waiting Test');
        $response->assertSee('الصالة المخصصة');
        $response->assertSee('الوقت');
        $response->assertSee('حالة المسار');
    }

    public function test_waiting_list_shows_scheduled_waiting_and_in_progress_statuses(): void
    {
        $this->createSurgerySetup('scheduled');
        $this->createSurgerySetup('waiting');
        $this->createSurgerySetup('in_progress');

        $staff = User::create([
            'name' => 'Surgery Staff',
            'email' => 'surgery_staff@example.com',
            'password' => bcrypt('password'),
            'role' => 'surgery_staff',
        ]);
        $staff->assignRole('surgery_staff');
        $staff->givePermissionTo('manage surgery waiting list');
        $staff->givePermissionTo('control surgeries');

        $response = $this->actingAs($staff)->get(route('surgeries.waiting'));

        $response->assertStatus(200);
        $response->assertSee('مجدولة');
        $response->assertSee('بالانتظار');
        $response->assertSee('داخل الصالة');
    }

    public function test_check_in_transitions_surgery_from_scheduled_to_waiting(): void
    {
        $setup = $this->createSurgerySetup('scheduled');
        $surgery = $setup['surgery'];

        $staff = User::create([
            'name' => 'Waiting Lounge Staff',
            'email' => 'lounge_staff@example.com',
            'password' => bcrypt('password'),
            'role' => 'surgery_staff',
        ]);
        $staff->assignRole('surgery_staff');
        $staff->givePermissionTo('manage surgery waiting list');

        $response = $this->actingAs($staff)->post(route('surgeries.check-in', $surgery));

        $response->assertRedirect();
        $this->assertDatabaseHas('surgeries', [
            'id' => $surgery->id,
            'status' => 'waiting',
        ]);
    }

    public function test_start_transitions_surgery_to_in_progress(): void
    {
        $setup = $this->createSurgerySetup('waiting');
        $surgery = $setup['surgery'];

        $admin = User::create([
            'name' => 'OR Coordinator',
            'email' => 'coordinator@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('surgeries.start', $surgery));

        $response->assertRedirect();
        $this->assertDatabaseHas('surgeries', [
            'id' => $surgery->id,
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseHas('operation_theater_stations', [
            'surgery_id' => $surgery->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_complete_transitions_ot_station_and_advances(): void
    {
        $setup = $this->createSurgerySetup('in_progress');
        $surgery = $setup['surgery'];
        $surgery->started_at = now()->subMinutes(45);
        $surgery->save();

        $admin = User::create([
            'name' => 'OR Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('surgeries.complete', $surgery));

        $response->assertRedirect();
        $this->assertDatabaseHas('operation_theater_stations', [
            'surgery_id' => $surgery->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('surgeon_stations', [
            'surgery_id' => $surgery->id,
            'surgeon_id' => $surgery->doctor_id,
        ]);
    }

    public function test_quick_store_operation_theater_via_ajax(): void
    {
        $admin = User::create([
            'name' => 'Admin Theater Creator',
            'email' => 'theater_admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('surgeries.theaters.quick-store'), [
            'name' => 'صالة القسطرة والقلب',
            'code' => 'OR-CATH',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'تم إضافة صالة العمليات بنجاح',
        ]);

        $this->assertDatabaseHas('operation_theaters', [
            'name' => 'صالة القسطرة والقلب',
            'code' => 'OR-CATH',
        ]);
    }

    public function test_quick_update_surgery_schedule_time_and_theater_via_ajax(): void
    {
        $setup = $this->createSurgerySetup('scheduled');
        $surgery = $setup['surgery'];

        $theater = OperationTheater::create([
            'name' => 'صالة الجراحة العامة',
            'code' => 'OR-GEN',
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin Schedule Editor',
            'email' => 'schedule_admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('surgeries.quick-update-schedule', $surgery), [
            'operation_theater_id' => $theater->id,
            'scheduled_time' => '11:45',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'theater_name' => 'صالة الجراحة العامة',
        ]);

        $this->assertDatabaseHas('surgeries', [
            'id' => $surgery->id,
            'operation_theater_id' => $theater->id,
        ]);
    }
}
