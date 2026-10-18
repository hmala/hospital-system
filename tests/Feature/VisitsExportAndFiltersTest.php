<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VisitsExportAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $hospital;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hospital = \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'view visits', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($perm);
    }

    public function test_admin_can_view_visits_with_filters()
    {
        $admin = User::factory()->create(['email' => 'admin_visits@hospital.com']);
        $admin->assignRole('admin');

        $dept = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'قسم الباطنية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'is_active' => true,
        ]);
        $doctorUser = User::factory()->create(['name' => 'د. حسام علي']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'phone' => '07701234567',
            'specialization' => 'استشاري باطنية',
            'qualification' => 'بورد / دكتوراه',
            'license_number' => 'DOC-9988',
            'consultation_fee' => 50000,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'عمار كريم', 'phone' => '07712345678']);
        $patient = Patient::create(['user_id' => $patientUser->id, 'gender' => 'male', 'date_of_birth' => '1990-01-01']);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'visit_date' => today(),
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'status' => 'completed',
            'chief_complaint' => 'صداع مستمر وارتفاع ضغط',
            'needs_surgery' => false,
        ]);

        \App\Models\Prescription::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('visits.index', ['search' => 'عمار']));
        $response->assertStatus(200);
        $response->assertSee('عمار كريم');
        $response->assertSee('د. حسام علي');
        $response->assertSee('قسم الباطنية');
    }

    public function test_admin_can_export_visits_to_excel()
    {
        $admin = User::factory()->create(['email' => 'admin_vis_exp@hospital.com']);
        $admin->assignRole('admin');

        $dept = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '201',
            'consultation_fee' => 30000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'is_active' => true,
        ]);
        $doctorUser = User::factory()->create(['name' => 'د. محمد جاسم']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'phone' => '07709876543',
            'specialization' => 'استشاري جراحة عامة',
            'qualification' => 'بورد / زمالة',
            'license_number' => 'DOC-7766',
            'consultation_fee' => 40000,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'ليلى أحمد', 'phone' => '07800000000']);
        $patient = Patient::create(['user_id' => $patientUser->id, 'gender' => 'female', 'date_of_birth' => '1995-05-05']);

        Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'visit_date' => today(),
            'visit_time' => '11:30:00',
            'visit_type' => 'surgery',
            'status' => 'in_progress',
            'chief_complaint' => 'ألم حاد في الزائدة الدودية',
            'needs_surgery' => true,
            'surgery_notes' => 'تحتاج عملية استئصال الزائدة',
        ]);

        $response = $this->actingAs($admin)->get(route('visits.export'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-type') ?? '', 'spreadsheetml') ||
            str_contains($response->headers->get('content-disposition') ?? '', '.xlsx')
        );
    }

    public function test_unauthenticated_user_cannot_access_visits_or_export()
    {
        $response1 = $this->get(route('visits.index'));
        $response1->assertRedirect('/login');

        $response2 = $this->get(route('visits.export'));
        $response2->assertRedirect('/login');
    }
}
