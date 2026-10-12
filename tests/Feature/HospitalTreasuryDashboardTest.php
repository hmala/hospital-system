<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\FinancialTransaction;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class HospitalTreasuryDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perms = ['view treasury', 'manage treasury', 'view account statements'];
        foreach ($perms as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $adminRole->givePermissionTo($perm);
        }
    }

    public function test_admin_can_access_treasury_with_three_tabs_and_department_stats()
    {
        $admin = User::factory()->create(['email' => 'admin_treasury@hospital.com']);
        $admin->assignRole('admin');

        FinancialTransaction::create([
            'transaction_type' => 'hospital_revenue',
            'category' => 'consultation',
            'voucher_type' => 'inflow',
            'voucher_number' => 'V-TEST-1',
            'amount' => 50000,
            'currency' => 'IQD',
            'payment_method' => 'cash',
            'description' => 'كشفية عيادة استشارية',
            'performed_at' => now(),
        ]);

        FinancialTransaction::create([
            'transaction_type' => 'hospital_revenue',
            'category' => 'radiology',
            'voucher_type' => 'inflow',
            'voucher_number' => 'V-TEST-2',
            'amount' => 75000,
            'currency' => 'IQD',
            'payment_method' => 'card',
            'description' => 'فحص إيكو وسونار',
            'performed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('treasury.index'));

        $response->assertStatus(200);
        $response->assertSee('المخططات البيانية والتحليل المالي');
        $response->assertSee('جدول إيرادات ومصروفات الأقسام');
        $response->assertSee('سجل القيود والوصولات التفصيلية');
        $response->assertSee('العيادات الاستشارية');
        $response->assertSee('الأشعة والسونار والإيكو');
        $response->assertSee('50,000');
        $response->assertSee('75,000');
    }

    public function test_treasury_displays_doctor_bi_and_referrals_tab()
    {
        $admin = User::factory()->create(['email' => 'admin_bi@hospital.com']);
        $admin->assignRole('admin');

        $hosp = \App\Models\Hospital::first();
        $dept = \App\Models\Department::create([
            'hospital_id' => $hosp->id,
            'name' => 'العيادة الباطنية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'is_active' => true,
        ]);

        $docUser = User::factory()->create(['name' => 'د. حسام العلي']);
        $doc = \App\Models\Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'باطنية وقلبية',
            'qualification' => 'بورد عربي',
            'license_number' => 'DOC-TEST-' . uniqid(),
            'consultation_fee' => 40000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $patUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = \App\Models\Patient::create([
            'user_id' => $patUser->id,
            'name' => 'مريض تجريبي',
            'phone' => '07711112222',
            'gender' => 'male',
        ]);

        // موعد كاش وموعد ضمان
        \App\Models\Appointment::create([
            'doctor_id' => $doc->id,
            'department_id' => $dept->id,
            'patient_id' => $patient->id,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '09:00:00',
            'consultation_fee' => 25000,
            'insurance_type' => 'none',
            'status' => 'completed',
        ]);

        \App\Models\Appointment::create([
            'doctor_id' => $doc->id,
            'department_id' => $dept->id,
            'patient_id' => $patient->id,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '09:30:00',
            'consultation_fee' => 25000,
            'insurance_type' => 'hi',
            'status' => 'completed',
        ]);

        // إيراد كشفية
        \App\Models\ConsultationRevenue::create([
            'doctor_id' => $doc->id,
            'revenue_date' => now()->toDateString(),
            'total_amount' => 50000,
            'doctor_share' => 30000,
            'hospital_share' => 20000,
        ]);

        // زيارة وإحالة تحاليل
        $visit = \App\Models\Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doc->id,
            'department_id' => $dept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص روتيني',
            'status' => 'completed',
        ]);

        \App\Models\Request::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'تحليل دم شامل CBC',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('treasury.index'));

        $response->assertStatus(200);
        $response->assertSee('تحليل أطباء الاستشارية والتحويلات');
        $response->assertSee('د. حسام العلي');
        $response->assertSee('باطنية وقلبية');
        $response->assertSee('50,000');
        $response->assertSee('30,000');
        $response->assertSee('20,000');
    }

    public function test_unauthorized_user_is_blocked_from_treasury()
    {
        $user = User::factory()->create(['email' => 'guest_user@hospital.com']);
        $response = $this->actingAs($user)->get(route('treasury.index'));
        $response->assertStatus(403);
    }
}
