<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\HrActionSetting;
use App\Models\HrEmployee;
use App\Models\HrEmployeeAction;
use App\Models\HrPayrollCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HrEmployeeActionCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Department $dept;

    protected HrEmployee $employee;

    protected HrActionSetting $penaltySetting;

    protected HrActionSetting $bonusSetting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\HrSettingsSeeder::class);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->dept = Department::create([
            'hospital_id' => \App\Models\Hospital::first()->id,
            'name' => 'قسم التمريض',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'max_patients_per_day' => 30,
        ]);

        $this->employee = HrEmployee::create([
            'employee_code' => 'EMP-TEST-001',
            'full_name' => 'أحمد عبد الله',
            'gender' => 'male',
            'staff_type' => 'medical',
            'phone' => '07701234567',
            'job_title' => 'ممرض جامعي',
            'department_id' => $this->dept->id,
            'employment_type' => 'full_time',
            'basic_salary' => 900000,
            'status' => 'active',
            'hire_date' => now()->subMonths(6),
        ]);

        $this->penaltySetting = HrActionSetting::create([
            'title' => 'خصم تأخير صباحي',
            'category' => 'penalty',
            'effect_type' => 'amount',
            'effect_value' => 25000,
            'is_active' => true,
        ]);

        $this->bonusSetting = HrActionSetting::create([
            'title' => 'مكافأة تميز وإتقان',
            'category' => 'bonus',
            'effect_type' => 'amount',
            'effect_value' => 50000,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_record_action_and_it_is_pending()
    {
        $response = $this->actingAs($this->admin)->post(route('hr.employees.actions.store', $this->employee->id), [
            'hr_action_setting_id' => $this->penaltySetting->id,
            'action_date' => now()->format('Y-m-d'),
            'reason' => 'تأخير متكرر عن الخفارة',
        ]);

        $response->assertRedirect(route('hr.employees.show', $this->employee->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hr_employee_actions', [
            'hr_employee_id' => $this->employee->id,
            'hr_action_setting_id' => $this->penaltySetting->id,
            'financial_amount' => -25000,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_cancel_action_and_it_remains_saved_in_database()
    {
        $action = HrEmployeeAction::create([
            'hr_employee_id' => $this->employee->id,
            'hr_action_setting_id' => $this->penaltySetting->id,
            'action_date' => now(),
            'reason' => 'خصم بسبب الغياب',
            'applied_effect_type' => 'amount',
            'applied_effect_value' => 25000,
            'financial_amount' => -25000,
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.actions.cancel', $action->id), [
            'cancellation_reason' => 'تم العفو بقرار من مدير المستشفى',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Record must NOT be deleted from database!
        $this->assertDatabaseHas('hr_employee_actions', [
            'id' => $action->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->admin->id,
            'cancellation_reason' => 'تم العفو بقرار من مدير المستشفى',
        ]);

        $action->refresh();
        $this->assertTrue($action->isCancelled());
        $this->assertNotNull($action->cancelled_at);
        $this->assertEquals($this->admin->id, $action->cancelled_by);
    }

    public function test_employee_show_displays_cancelled_action_with_archive_details()
    {
        $action = HrEmployeeAction::create([
            'hr_employee_id' => $this->employee->id,
            'hr_action_setting_id' => $this->bonusSetting->id,
            'action_date' => now(),
            'reason' => 'مكافأة جهود استثنائية',
            'applied_effect_type' => 'amount',
            'applied_effect_value' => 50000,
            'financial_amount' => 50000,
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
            'cancelled_by' => $this->admin->id,
            'cancelled_at' => now(),
            'cancellation_reason' => 'خطأ في التنسيب',
        ]);

        $response = $this->actingAs($this->admin)->get(route('hr.employees.show', $this->employee->id));

        $response->assertStatus(200);
        $response->assertSee('ملغي ومؤرشف');
        $response->assertSee('خطأ في التنسيب');
        $response->assertSee('أُلغي بواسطة:');
        $response->assertSee('استعادة');
    }

    public function test_admin_can_restore_cancelled_action()
    {
        $action = HrEmployeeAction::create([
            'hr_employee_id' => $this->employee->id,
            'hr_action_setting_id' => $this->bonusSetting->id,
            'action_date' => now(),
            'reason' => 'مكافأة تم إلغاؤها بالخطأ',
            'applied_effect_type' => 'amount',
            'applied_effect_value' => 50000,
            'financial_amount' => 50000,
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
            'cancelled_by' => $this->admin->id,
            'cancelled_at' => now(),
            'cancellation_reason' => 'إلغاء أولي',
        ]);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.actions.restore', $action->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $action->refresh();
        $this->assertEquals('pending', $action->status);
        $this->assertNull($action->cancelled_by);
        $this->assertNull($action->cancelled_at);
        $this->assertNull($action->cancellation_reason);
    }

    public function test_cannot_cancel_already_processed_action()
    {
        $cycle = HrPayrollCycle::create([
            'cycle_month' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'status' => 'closed',
            'processed_by' => $this->admin->id,
        ]);

        $action = HrEmployeeAction::create([
            'hr_employee_id' => $this->employee->id,
            'hr_action_setting_id' => $this->penaltySetting->id,
            'action_date' => now(),
            'reason' => 'خصم مرحل',
            'applied_effect_type' => 'amount',
            'applied_effect_value' => 25000,
            'financial_amount' => -25000,
            'status' => 'processed',
            'hr_payroll_cycle_id' => $cycle->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.actions.cancel', $action->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $action->refresh();
        $this->assertEquals('processed', $action->status);
    }

    public function test_cancelled_action_is_excluded_from_payroll_deductions_and_bonuses()
    {
        // 1. Create a cancelled penalty and a cancelled bonus
        HrEmployeeAction::create([
            'hr_employee_id' => $this->employee->id,
            'hr_action_setting_id' => $this->penaltySetting->id,
            'action_date' => now(),
            'reason' => 'خصم ملغي',
            'applied_effect_type' => 'amount',
            'applied_effect_value' => 100000,
            'financial_amount' => -100000,
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
            'cancelled_by' => $this->admin->id,
            'cancelled_at' => now(),
            'cancellation_reason' => 'تم الإلغاء',
        ]);

        HrEmployeeAction::create([
            'hr_employee_id' => $this->employee->id,
            'hr_action_setting_id' => $this->bonusSetting->id,
            'action_date' => now(),
            'reason' => 'مكافأة ملغاة',
            'applied_effect_type' => 'amount',
            'applied_effect_value' => 50000,
            'financial_amount' => 50000,
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
            'cancelled_by' => $this->admin->id,
            'cancelled_at' => now(),
            'cancellation_reason' => 'تم الإلغاء',
        ]);

        // Process payroll for current month
        $response = $this->actingAs($this->admin)->post(route('hr.payrolls.store'), [
            'cycle_month' => now()->format('Y-m'),
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->endOfMonth()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        // Check the generated payroll slip for this employee: bonuses and penalties must be 0!
        $this->assertDatabaseHas('hr_payrolls', [
            'hr_employee_id' => $this->employee->id,
            'bonuses_amount' => 0,
            'penalties_amount' => 0,
            'net_salary' => 900000, // untouched basic salary
        ]);
    }
}
