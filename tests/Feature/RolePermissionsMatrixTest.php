<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\RoleManagementController;
use PHPUnit\Framework\Attributes\Test;

class RolePermissionsMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        // Core roles checked in views/dashboard
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'consultation_receptionist', 'guard_name' => 'web']);

        // Representative permissions for each of the 9 categories
        $samples = [
            'inquiry.create.radiology.ultrasound',
            'view inquiries',
            'view doctors',
            'manage consultant availability',
            'view cashier',
            'process consultation payments',
            'view radiology',
            'process radiology requests',
            'view lab tests',
            'process lab requests',
            'view pharmacy',
            'process pharmacy requests',
            'view emergencies',
            'manage emergency services',
            'view surgeries',
            'manage rooms',
            'manage users',
            'manage roles',
        ];

        foreach ($samples as $sample) {
            Permission::firstOrCreate(['name' => $sample, 'guard_name' => 'web']);
        }
    }

    #[Test]
    public function admin_can_view_role_edit_screen_with_9_operational_cards()
    {
        $admin = User::factory()->create(['email' => 'admin_test@hospital.com']);
        $admin->assignRole('admin');

        $targetRole = Role::firstOrCreate(['name' => 'test_operational_role', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->get(route('roles.edit', $targetRole));

        $response->assertStatus(200);
        $response->assertSee('الاستعلامات والحجوزات');
        $response->assertSee('العيادات والاستشارية ومحطة الأطباء');
        $response->assertSee('الصندوق والمالية');
        $response->assertSee('الأشعة والسونار والإيكو');
        $response->assertSee('المختبر والتحاليل الطبية');
        $response->assertSee('الصيدلية والمخزن الطبي');
        $response->assertSee('قسم الطوارئ');
        $response->assertSee('العمليات الجراحية والرقود');
        $response->assertSee('إدارة النظام والإعدادات');
    }

    #[Test]
    public function all_permissions_are_mapped_without_leaving_unmapped_groups()
    {
        $controller = app(RoleManagementController::class);
        $moduleKeys = array_keys(RoleManagementController::getModuleOrder());

        $permissions = Permission::all();
        $this->assertNotEmpty($permissions);

        foreach ($permissions as $p) {
            $reflector = new \ReflectionMethod(RoleManagementController::class, 'permissionGroup');
            $reflector->setAccessible(true);
            $group = $reflector->invoke($controller, $p->name);

            $this->assertContains($group, $moduleKeys, "Permission [{$p->name}] mapped to unknown group [{$group}]");
        }
    }

    #[Test]
    public function cashier_section_is_hidden_from_receptionist_who_lacks_view_cashier_permission()
    {
        $receptionist = User::factory()->create(['email' => 'reception_test@hospital.com']);
        $role = Role::findByName('consultation_receptionist');
        $receptionist->assignRole($role);

        $role->syncPermissions(['view inquiries', 'inquiry.create.radiology.ultrasound']);

        $response = $this->actingAs($receptionist)->get(route('roles.index'));
        $this->assertFalse($receptionist->can('view cashier'));
    }

    #[Test]
    public function patient_history_is_forbidden_without_view_patient_history_permission()
    {
        $receptionist = User::factory()->create(['email' => 'reception_hist@hospital.com']);
        $role = Role::findByName('receptionist');
        $receptionist->assignRole($role);

        // Has view inquiries but NOT view patient history
        $role->syncPermissions(['view inquiries']);

        $response = $this->actingAs($receptionist)->get(route('inquiry.patients.history'));
        $response->assertStatus(403);
    }

    #[Test]
    public function radiology_modalities_are_strictly_gated_by_granular_permissions()
    {
        \App\Models\ServiceType::firstOrCreate(
            ['name' => 'radiology'],
            ['label' => 'أشعة وسونار', 'icon' => 'fa-x-ray', 'color' => 'info', 'is_active' => true, 'order' => 2]
        );

        $receptionist = User::factory()->create(['email' => 'reception_rad@hospital.com']);
        $role = Role::findByName('receptionist');
        $receptionist->assignRole($role);

        // Grant only ultrasound, not general or echo or mri
        $role->syncPermissions(['view inquiries', 'inquiry.create.radiology.ultrasound']);

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = \App\Models\Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'blood_group' => 'O+',
        ]);

        // General radiology booking should be forbidden
        $response = $this->actingAs($receptionist)->post(route('inquiry.store'), [
            'patient_id' => $patient->id,
            'request_type' => ['radiology'],
            'radiology_category' => 'radiology',
        ]);
        $response->assertStatus(403);
    }

    #[Test]
    public function patient_crud_actions_are_strictly_gated_by_permissions()
    {
        Permission::firstOrCreate(['name' => 'view patients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create patients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit patients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete patients', 'guard_name' => 'web']);

        $receptionist = User::factory()->create(['email' => 'reception_crud@hospital.com']);
        $role = Role::findByName('receptionist');
        $receptionist->assignRole($role);

        // Give ONLY view patients (no create, no edit, no delete)
        $role->syncPermissions(['view patients']);

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = \App\Models\Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'blood_group' => 'O+',
        ]);

        // 1. Can view index and show
        $this->actingAs($receptionist)->get(route('patients.index'))->assertStatus(200);
        $this->actingAs($receptionist)->get(route('patients.show', $patient))->assertStatus(200);

        // 2. Cannot access create or store
        $this->actingAs($receptionist)->get(route('patients.create'))->assertStatus(403);
        $this->actingAs($receptionist)->post(route('patients.store'), ['name' => 'مريض جديد'])->assertStatus(403);

        // 3. Cannot access edit or update
        $this->actingAs($receptionist)->get(route('patients.edit', $patient))->assertStatus(403);
        $this->actingAs($receptionist)->put(route('patients.update', $patient), ['name' => 'تعديل اسم'])->assertStatus(403);

        // 4. Cannot delete
        $this->actingAs($receptionist)->delete(route('patients.destroy', $patient))->assertStatus(403);
    }

    #[Test]
    public function cashier_routes_are_strictly_gated_by_granular_permissions()
    {
        Permission::firstOrCreate(['name' => 'view cashier', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process consultation payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process medical requests payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view cashier reports', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view cashier surgeries', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process surgery payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'review surgery prices', 'guard_name' => 'web']);

        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $cashierUser = User::factory()->create(['email' => 'cashier_test@hospital.com']);
        $cashierUser->assignRole($cashierRole);

        // 1. Without 'view cashier', accessing cashier index returns 403
        $cashierRole->syncPermissions([]);
        $this->actingAs($cashierUser)->get(route('cashier.index'))->assertStatus(403);

        // 2. Give ONLY 'view cashier'
        $cashierRole->syncPermissions(['view cashier']);
        $this->actingAs($cashierUser)->get(route('cashier.index'))->assertStatus(200);

        // 3. Create dummy appointment
        $patientUser = User::factory()->create(['name' => 'مريض كاشير']);
        $patient = \App\Models\Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'blood_group' => 'O+',
        ]);
        $hospital = \App\Models\Hospital::first();
        if (!$hospital) {
            $hospital = \App\Models\Hospital::create([
                'name' => 'مستشفى الفحص',
                'code' => 'TEST',
                'address' => 'بغداد',
                'phone' => '123456',
                'email' => 'test@hospital.com',
                'status' => 'active',
            ]);
        }
        $dept = \App\Models\Department::firstOrCreate(
            ['name' => 'قسم الباطنية'],
            [
                'hospital_id' => $hospital->id,
                'room_number' => '101',
                'consultation_fee' => 25000,
                'working_hours_start' => '08:00',
                'working_hours_end' => '16:00',
                'max_patients_per_day' => 30,
                'type' => 'internal',
                'is_active' => true,
            ]
        );
        $doctorUser = User::factory()->create(['name' => 'دكتور فحص']);
        $doctor = \App\Models\Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'طب عام',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-TEST-' . uniqid(),
            'consultation_fee' => 25000,
            'status' => 'active',
            'is_active' => true,
        ]);
        $appointment = \App\Models\Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'scheduled',
            'consultation_fee' => 25000,
        ]);

        // Attempt consultation payment form without 'process consultation payments' => 403
        $this->actingAs($cashierUser)->get(route('cashier.payment.form', $appointment->id))->assertStatus(403);

        // Grant 'process consultation payments' => 200
        $cashierRole->givePermissionTo('process consultation payments');
        $this->actingAs($cashierUser)->get(route('cashier.payment.form', $appointment->id))->assertStatus(200);

        // 4. Reports without 'view cashier reports' => 403
        $this->actingAs($cashierUser)->get(route('cashier.report'))->assertStatus(403);
        $this->actingAs($cashierUser)->get(route('cashier.statements'))->assertStatus(403);

        // 5. Surgery cashier without 'view cashier surgeries' => 403
        $this->actingAs($cashierUser)->get(route('cashier.surgeries.index'))->assertStatus(403);

        // 6. Accountant surgery review without 'review surgery prices' => 403
        $this->actingAs($cashierUser)->get(route('accountant.surgeries.index'))->assertStatus(403);
    }
}


