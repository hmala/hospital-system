<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorFinancialAccount;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConsultantDoctorAccountsFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Doctor $doctorWithBalance;

    protected Doctor $doctorZeroBalance;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view doctor accounts', 'guard_name' => 'web']);
        $adminRole->givePermissionTo('view doctor accounts');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'مستشفى الفيحاء الأهلي',
            'owner_name' => 'إدارة المستشفى',
            'phone' => '07700000000',
            'address' => 'بغداد - الكرادة',
            'license_number' => 'LIC-TEST-001',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'الاستشاريون',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        // Doctor 1: Has balance
        $user1 = User::factory()->create([
            'name' => 'د. حسام الدين الخزرجي',
            'phone' => '07701234567',
        ]);
        $this->doctorWithBalance = Doctor::create([
            'user_id' => $user1->id,
            'department_id' => $department->id,
            'specialization' => 'جراحة المخ والأعصاب',
            'qualification' => 'بورد جراحة عصبية',
            'license_number' => 'DOC-TEST-'.uniqid(),
            'consultation_fee' => 30000,
            'type' => 'consultant',
            'is_active' => true,
        ]);
        DoctorFinancialAccount::create([
            'doctor_id' => $this->doctorWithBalance->id,
            'balance' => 750000.00,
            'total_earned' => 1000000.00,
            'total_paid' => 250000.00,
            'last_paid_at' => now()->subDays(2),
        ]);

        // Doctor 2: Zero balance
        $user2 = User::factory()->create([
            'name' => 'د. سمير صبري البغدادي',
            'phone' => '07809876543',
        ]);
        $this->doctorZeroBalance = Doctor::create([
            'user_id' => $user2->id,
            'department_id' => $department->id,
            'specialization' => 'أمراض الدم والأورام',
            'qualification' => 'بورد أمراض باطنية',
            'license_number' => 'DOC-TEST-'.uniqid(),
            'consultation_fee' => 25000,
            'type' => 'consultant',
            'is_active' => true,
        ]);
        DoctorFinancialAccount::create([
            'doctor_id' => $this->doctorZeroBalance->id,
            'balance' => 0.00,
            'total_earned' => 500000.00,
            'total_paid' => 500000.00,
            'last_paid_at' => now()->subDay(),
        ]);
    }

    public function test_consultant_doctor_accounts_renders_with_kpis_and_filters(): void
    {
        $response = $this->actingAs($this->admin)->get(route('consultant-availability.doctor-accounts'));

        $response->assertOk();
        $response->assertSee('حسابات الأطباء الاستشاريين');
        $response->assertSee('د. حسام الدين الخزرجي');
        $response->assertSee('د. سمير صبري البغدادي');
        $response->assertSee('750,000');
        $response->assertSee('0 د.ع (مصفى)');

        $stats = $response->viewData('stats');
        $this->assertEquals(2, $stats['total_doctors']);
        $this->assertEquals(1, $stats['doctors_with_balance']);
        $this->assertEquals(750000.00, (float) $stats['total_balance']);
        $this->assertEquals(1500000.00, (float) $stats['total_earned']);
        $this->assertEquals(750000.00, (float) $stats['total_paid']);
    }

    public function test_consultant_doctor_accounts_search_by_name_and_specialization(): void
    {
        // 1. Search by name
        $response = $this->actingAs($this->admin)->get(route('consultant-availability.doctor-accounts', [
            'search' => 'الخزرجي',
        ]));
        $response->assertOk();
        $response->assertSee('د. حسام الدين الخزرجي');
        $response->assertDontSee('د. سمير صبري البغدادي');

        // 2. Search by specialization
        $response2 = $this->actingAs($this->admin)->get(route('consultant-availability.doctor-accounts', [
            'search' => 'الأورام',
        ]));
        $response2->assertOk();
        $response2->assertSee('د. سمير صبري البغدادي');
        $response2->assertDontSee('د. حسام الدين الخزرجي');
    }

    public function test_consultant_doctor_accounts_filter_by_balance_status(): void
    {
        // 1. Filter only with balance
        $responseWithBalance = $this->actingAs($this->admin)->get(route('consultant-availability.doctor-accounts', [
            'balance_status' => 'with_balance',
        ]));
        $responseWithBalance->assertOk();
        $responseWithBalance->assertSee('د. حسام الدين الخزرجي');
        $responseWithBalance->assertDontSee('د. سمير صبري البغدادي');

        // 2. Filter zero balance
        $responseZeroBalance = $this->actingAs($this->admin)->get(route('consultant-availability.doctor-accounts', [
            'balance_status' => 'zero_balance',
        ]));
        $responseZeroBalance->assertOk();
        $responseZeroBalance->assertSee('د. سمير صبري البغدادي');
        $responseZeroBalance->assertDontSee('د. حسام الدين الخزرجي');
    }

    public function test_consultant_doctor_accounts_sorting(): void
    {
        $response = $this->actingAs($this->admin)->get(route('consultant-availability.doctor-accounts', [
            'sort_by' => 'balance_desc',
        ]));
        $response->assertOk();

        $doctors = $response->viewData('consultantDoctors');
        $this->assertEquals($this->doctorWithBalance->id, $doctors->first()->id);
    }
}
