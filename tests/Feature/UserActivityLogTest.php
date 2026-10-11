<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\UserActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure roles & permissions exist
        Permission::firstOrCreate(['name' => 'view activity logs', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete activity logs', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
    }

    public function test_user_activity_logger_service_records_activity()
    {
        $user = User::factory()->create(['name' => 'علي أحمد']);
        $user->assignRole('admin');

        $activity = UserActivityLogger::log(
            action: 'test_action',
            category: 'security',
            description: 'حركة اختبار تجريبية',
            isSensitive: true,
            properties: ['foo' => 'bar'],
            user: $user
        );

        $this->assertNotNull($activity);
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'user_name' => 'علي أحمد',
            'action' => 'test_action',
            'category' => 'security',
            'is_sensitive' => true,
        ]);
        $this->assertEquals(['foo' => 'bar'], $activity->properties);
    }

    public function test_authorized_admin_can_view_activity_logs_dashboard()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create some sample activities
        UserActivity::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'role_name' => 'admin',
            'action' => 'test',
            'category' => 'reception',
            'description' => 'حجز موعد لمريض',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('activity-logs.index'));

        $response->assertStatus(200);
        $response->assertSee('رادار المستشفى اللحظي');
        $response->assertSee('شريط الأحداث والحركات التشغيلية');
        $response->assertSee('حجز موعد لمريض');
    }

    public function test_unauthorized_user_is_forbidden_from_activity_logs()
    {
        $user = User::factory()->create();
        // Regular user without permissions
        $response = $this->actingAs($user)->get(route('activity-logs.index'));

        $response->assertStatus(403);
    }

    public function test_appointment_creation_automatically_logs_reception_activity()
    {
        $receptionist = User::factory()->create(['name' => 'موظف الاستقبال']);
        $receptionist->assignRole('receptionist');

        $hospital = \App\Models\Hospital::create([
            'name' => 'مستشفى الفيحاء',
            'owner_name' => 'المدير العام',
            'phone' => '0770000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-' . uniqid(),
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. محمد']);
        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'باطنية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
        ]);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'باطنية عامة',
            'qualification' => 'بورد',
            'license_number' => 'DOC-' . uniqid(),
            'consultation_fee' => 15000,
        ]);

        $patientUser = User::factory()->create(['name' => 'سجاد كاظم']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'سجاد كاظم',
            'phone' => '07700000000',
        ]);

        $this->actingAs($receptionist);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'appointment_date' => today(),
            'status' => 'pending',
            'queue_number' => 5,
            'consultation_fee' => 15000,
        ]);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $receptionist->id,
            'category' => 'reception',
            'action' => 'create_appointment',
            'subject_id' => $appointment->id,
        ]);
    }

    public function test_export_endpoint_streams_csv()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        UserActivity::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'role_name' => 'admin',
            'action' => 'test_export',
            'category' => 'cashier',
            'description' => 'تحصيل كشفية 25000',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('activity-logs.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_purge_endpoint_archives_older_logs()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $oldActivity = UserActivity::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'role_name' => 'admin',
            'action' => 'old_record',
            'category' => 'auth',
            'description' => 'دخول قديم جدا',
        ]);
        \Illuminate\Support\Facades\DB::table('user_activities')
            ->where('id', $oldActivity->id)
            ->update(['created_at' => now()->subDays(120)]);

        $recentActivity = UserActivity::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'role_name' => 'admin',
            'action' => 'recent_record',
            'category' => 'auth',
            'description' => 'دخول حديث',
        ]);

        $response = $this->actingAs($admin)->post(route('activity-logs.purge'), [
            'days' => 90,
        ]);

        $response->assertRedirect(route('activity-logs.index'));
        $this->assertDatabaseMissing('user_activities', ['id' => $oldActivity->id]);
        $this->assertDatabaseHas('user_activities', ['id' => $recentActivity->id]);
    }

    public function test_user_logout_records_activity_and_clears_active_status()
    {
        $user = User::factory()->create(['name' => 'علي حسن', 'last_activity_at' => now()]);
        $user->assignRole('receptionist');

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/login');

        // التحقق من تسجيل حركة الخروج في جدول user_activities
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'action' => 'logout',
            'category' => 'auth',
        ]);

        // التحقق من تثبيت last_logout_at لتمييز أنه مسجل خروج
        $user->refresh();
        $this->assertNotNull($user->last_logout_at);
    }
}
