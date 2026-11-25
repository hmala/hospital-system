<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\HrActionBatch;
use App\Models\HrActionSetting;
use App\Models\HrEmployee;
use App\Models\HrLeaveRequest;
use App\Models\HrPayroll;
use App\Models\HrPayrollCycle;
use App\Models\HrRosterApproval;
use App\Models\HrSchedule;
use App\Models\HrShift;
use App\Models\User;
use Database\Seeders\HospitalSeeder;
use Database\Seeders\HrSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrGovernanceLeavesAndCallPayrollTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $hrManager;

    protected User $deptManagerA;

    protected User $staffUserA;

    protected Department $deptA;

    protected Department $deptB;

    protected HrEmployee $empFixedA;

    protected HrEmployee $empPerCallResident;

    protected HrEmployee $empPerCallNurse;

    protected HrEmployee $empPerCallEmergencyDoc;

    protected HrEmployee $empDeptB;

    protected HrActionSetting $penaltySetting;

    protected HrActionSetting $bonusSetting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(HospitalSeeder::class);
        $this->seed(HrSettingsSeeder::class);

        $hospital = Hospital::first();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->hrManager = User::factory()->create(['role' => 'hr_manager']);
        $this->hrManager->assignRole('hr_manager');

        $this->deptManagerA = User::factory()->create(['role' => 'department_manager']);
        $this->deptManagerA->assignRole('department_manager');

        $this->staffUserA = User::factory()->create(['role' => 'nurse']);
        $this->staffUserA->assignRole('nurse');

        $this->deptA = Department::create([
            'hospital_id' => $hospital->id,
            'manager_id' => $this->deptManagerA->id,
            'name' => 'قسم الطوارئ والتمريض',
            'type' => 'emergency',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 50,
        ]);

        $this->deptB = Department::create([
            'hospital_id' => $hospital->id,
            'manager_id' => null,
            'name' => 'قسم المختبر',
            'type' => 'other',
            'room_number' => '202',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'max_patients_per_day' => 50,
        ]);

        $this->empFixedA = HrEmployee::create([
            'employee_code' => 'EMP-GOV-01',
            'user_id' => $this->staffUserA->id,
            'department_id' => $this->deptA->id,
            'full_name' => 'سارة علي',
            'gender' => 'female',
            'phone' => '07701111111',
            'staff_type' => 'nursing',
            'job_title' => 'ممرضة ثابتة',
            'employment_type' => 'full_time',
            'basic_salary' => 900000,
            'salary_calculation_mode' => 'fixed_salary',
            'status' => 'active',
            'hire_date' => now()->subYear(),
        ]);

        $this->empPerCallResident = HrEmployee::create([
            'employee_code' => 'EMP-GOV-02',
            'department_id' => $this->deptA->id,
            'full_name' => 'د. مصطفى كريم',
            'gender' => 'male',
            'phone' => '07702222222',
            'staff_type' => 'medical',
            'job_title' => 'طبيب مقيم',
            'employment_type' => 'daily_shift',
            'schedule_type' => 'rotating',
            'basic_salary' => 0,
            'salary_calculation_mode' => 'per_call',
            'status' => 'active',
            'hire_date' => now()->subMonths(6),
        ]);

        $this->empPerCallNurse = HrEmployee::create([
            'employee_code' => 'EMP-GOV-03',
            'department_id' => $this->deptA->id,
            'full_name' => 'حسين فاضل',
            'gender' => 'male',
            'phone' => '07703333333',
            'staff_type' => 'nursing',
            'job_title' => 'ممرض شفتات',
            'employment_type' => 'daily_shift',
            'schedule_type' => 'rotating',
            'basic_salary' => 0,
            'salary_calculation_mode' => 'per_call',
            'status' => 'active',
            'hire_date' => now()->subMonths(6),
        ]);

        $this->empPerCallEmergencyDoc = HrEmployee::create([
            'employee_code' => 'EMP-GOV-04',
            'department_id' => $this->deptA->id,
            'full_name' => 'د. زيد طارق',
            'gender' => 'male',
            'phone' => '07704444444',
            'staff_type' => 'medical',
            'job_title' => 'طبيب طوارئ',
            'employment_type' => 'daily_shift',
            'schedule_type' => 'rotating',
            'basic_salary' => 0,
            'salary_calculation_mode' => 'per_call',
            'status' => 'active',
            'hire_date' => now()->subMonths(6),
        ]);

        $this->empDeptB = HrEmployee::create([
            'employee_code' => 'EMP-GOV-05',
            'department_id' => $this->deptB->id,
            'full_name' => 'نور الهدى',
            'gender' => 'female',
            'phone' => '07705555555',
            'staff_type' => 'technical',
            'job_title' => 'محللة مختبر',
            'employment_type' => 'full_time',
            'basic_salary' => 600000,
            'salary_calculation_mode' => 'fixed_salary',
            'status' => 'active',
            'hire_date' => now()->subYear(),
        ]);

        $this->penaltySetting = HrActionSetting::create([
            'title' => 'عقوبة تقصير جماعية',
            'category' => 'penalty',
            'effect_type' => 'amount',
            'effect_value' => 25000,
            'is_active' => true,
        ]);

        $this->bonusSetting = HrActionSetting::create([
            'title' => 'مكافأة أداء متميز',
            'category' => 'bonus',
            'effect_type' => 'amount',
            'effect_value' => 50000,
            'is_active' => true,
        ]);
    }

    public function test_bulk_actions_for_department_and_hospital_with_48h_cancellation_governance(): void
    {
        // 1. HR applies a bonus to Department A (4 employees)
        $res = $this->actingAs($this->hrManager)->post(route('hr.bulk-actions.store'), [
            'scope' => 'department',
            'department_id' => $this->deptA->id,
            'hr_action_setting_id' => $this->bonusSetting->id,
            'action_date' => now()->format('Y-m-d'),
            'reason' => 'مكافأة تغطية طوارئ',
        ]);
        $res->assertRedirect();

        $batch = HrActionBatch::firstOrFail();
        $this->assertEquals(4, $batch->target_count);
        $this->assertEquals(4, $batch->actions()->count());

        // 2. Within 48 hours: HR can cancel a single employee action directly
        $firstAction = $batch->actions()->first();
        $this->actingAs($this->hrManager)
            ->post(route('hr.employees.actions.cancel', $firstAction->id), ['cancellation_reason' => 'تصحيح فردي'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals('cancelled', $firstAction->fresh()->status);
        $this->assertEquals('partially_cancelled', $batch->fresh()->status);

        // 3. Simulate 48 hours elapsed: HR CANNOT cancel directly anymore
        $batch->update(['cancellable_until' => now()->subHour()]);
        $batch->actions()->where('status', 'pending')->update(['cancellable_until' => now()->subHour()]);

        $secondAction = $batch->actions()->where('status', 'pending')->first();
        $this->actingAs($this->hrManager)
            ->post(route('hr.employees.actions.cancel', $secondAction->id))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertEquals('pending', $secondAction->fresh()->status);

        // HR cannot cancel whole batch directly after 48h either
        $this->actingAs($this->hrManager)
            ->post(route('hr.bulk-actions.cancel', $batch->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        // 4. HR submits a cancellation request after 48h, and Admin Director approves it
        $this->actingAs($this->hrManager)
            ->post(route('hr.bulk-actions.request-cancel', $batch->id), [
                'cancel_request_reason' => 'تم الصرف بالخطأ بعد 48 ساعة',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($batch->fresh()->cancel_requested_at);

        $this->actingAs($this->admin)
            ->post(route('hr.bulk-actions.approve-cancel', $batch->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals('cancelled', $batch->fresh()->status);
        $this->assertEquals(0, $batch->actions()->where('status', 'pending')->count());

        // 5. Hospital-wide penalty applies to all 5 active employees
        $this->actingAs($this->hrManager)->post(route('hr.bulk-actions.store'), [
            'scope' => 'hospital',
            'hr_action_setting_id' => $this->penaltySetting->id,
            'action_date' => now()->format('Y-m-d'),
            'reason' => 'تعميم عام للمستشفى',
        ])->assertRedirect();

        $hospitalBatch = HrActionBatch::latest('id')->first();
        $this->assertEquals('hospital', $hospitalBatch->scope);
        $this->assertEquals(5, $hospitalBatch->target_count);
    }

    public function test_electronic_leave_request_workflow_and_roster_and_unpaid_payroll_sync(): void
    {
        // 1. Linked staff submits leave electronically -> goes to pending_department
        $this->actingAs($this->staffUserA)
            ->post(route('my.leaves.store'), [
                'leave_type' => 'annual',
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-12',
                'reason' => 'ظرف عائلي',
            ])
            ->assertRedirect(route('my.leaves.index'))
            ->assertSessionHas('success');

        $leave = HrLeaveRequest::firstOrFail();
        $this->assertEquals('pending_department', $leave->status);
        $this->assertEquals(3, $leave->days_count);

        // 2. Department manager approves and classifies it as unpaid leave
        $this->actingAs($this->deptManagerA)
            ->post(route('hr.leaves.dept-approve', $leave->id), [
                'leave_type' => 'unpaid',
                'is_paid' => 0,
                'dept_notes' => 'موافق بدون راتب',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $leave->refresh();
        $this->assertEquals('pending_admin', $leave->status);
        $this->assertFalse($leave->is_paid);

        // 3. Admin Director grants final approval -> syncs 3 days into hr_schedules as is_leave = true
        $this->actingAs($this->admin)
            ->post(route('hr.leaves.admin-approve', $leave->id), [
                'leave_type' => 'unpaid',
                'is_paid' => 0,
                'admin_notes' => 'معتمد نهائياً',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals('approved', $leave->fresh()->status);
        $this->assertEquals(3, HrSchedule::where('hr_employee_id', $this->empFixedA->id)
            ->where('is_leave', true)
            ->where('hr_leave_request_id', $leave->id)
            ->count());

        // 4. Department without manager (deptB) skips department step -> HR manager can approve directly
        $this->actingAs($this->hrManager)
            ->post(route('hr.leaves.store'), [
                'hr_employee_id' => $this->empDeptB->id,
                'leave_type' => 'sick',
                'start_date' => '2026-11-15',
                'end_date' => '2026-11-15',
                'reason' => 'إجازة مرضية',
            ])
            ->assertRedirect();

        $deptBLeave = HrLeaveRequest::where('hr_employee_id', $this->empDeptB->id)->firstOrFail();
        $this->assertEquals('pending_admin', $deptBLeave->status);

        $this->actingAs($this->hrManager)
            ->post(route('hr.leaves.admin-approve', $deptBLeave->id))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertEquals('approved', $deptBLeave->fresh()->status);

        // 5. Generate monthly payroll -> empFixedA (900,000 basic) loses 3 days unpaid leave = 90,000 IQD
        $this->actingAs($this->admin)
            ->post(route('hr.payrolls.store'), [
                'cycle_month' => '2026-11',
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-30',
            ])
            ->assertRedirect();

        $slipA = HrPayroll::where('hr_employee_id', $this->empFixedA->id)->firstOrFail();
        $this->assertEquals(90000.0, (float) $slipA->unpaid_leave_deduction);
        $this->assertEquals(810000.0, (float) $slipA->net_salary);
    }

    public function test_department_manager_is_isolated_to_own_department_roster(): void
    {
        // Department Manager A can view Dept A roster
        $this->actingAs($this->deptManagerA)
            ->get(route('hr.schedules.index', ['department_id' => $this->deptA->id]))
            ->assertStatus(200)
            ->assertSee($this->empPerCallResident->full_name)
            ->assertDontSee($this->empDeptB->full_name);

        // Department Manager A gets 403 trying to view Dept B roster
        $this->actingAs($this->deptManagerA)
            ->get(route('hr.schedules.index', ['department_id' => $this->deptB->id]))
            ->assertStatus(403);

        // Department Manager A gets 403 trying to update Dept B employee cell
        $this->actingAs($this->deptManagerA)
            ->postJson(route('hr.schedules.update-cell'), [
                'hr_employee_id' => $this->empDeptB->id,
                'shift_date' => '2026-11-05',
                'shift_value' => 'off',
            ])
            ->assertStatus(403);
    }

    public function test_per_call_pricing_roster_approval_and_monthly_payroll_aggregation(): void
    {
        // Define the 3 real hospital shift/call rates
        $residentShift = HrShift::create([
            'name' => 'Call مقيم 12 ساعة',
            'start_time' => '08:00',
            'end_time' => '20:00',
            'duration_hours' => 12,
            'call_rate' => 70000,
            'shift_category' => 'resident',
            'color_code' => '#0d6efd',
            'is_active' => true,
        ]);

        $nurseShift = HrShift::create([
            'name' => 'شفت تمريض 8 ساعات',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'duration_hours' => 8,
            'call_rate' => 30000,
            'shift_category' => 'nursing',
            'color_code' => '#198754',
            'is_active' => true,
        ]);

        $emergencyDocShift = HrShift::create([
            'name' => 'Call طبيب طوارئ 9ص-6م',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'duration_hours' => 9,
            'call_rate' => 150000,
            'shift_category' => 'emergency_doctor',
            'color_code' => '#dc3545',
            'is_active' => true,
        ]);

        // Week 1 (2026-11-01 to 2026-11-07): schedule shifts via Dept Manager A
        // Resident: 3 calls (Nov 1, 2, 3), mark Nov 3 as ABSENT -> 2 paid calls = 140,000 IQD
        foreach (['2026-11-01', '2026-11-02', '2026-11-03'] as $d) {
            HrSchedule::create([
                'hr_employee_id' => $this->empPerCallResident->id,
                'hr_shift_id' => $residentShift->id,
                'shift_date' => $d,
                'is_off_day' => false,
                'attendance_status' => 'present',
                'created_by' => $this->deptManagerA->id,
            ]);
        }

        // Toggle Nov 3 to absent
        $this->actingAs($this->deptManagerA)
            ->postJson(route('hr.schedules.toggle-attendance'), [
                'hr_employee_id' => $this->empPerCallResident->id,
                'shift_date' => '2026-11-03',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'attendance_status' => 'absent']);

        // Nurse: 4 shifts in Week 1 -> 4 * 30,000 = 120,000 IQD
        foreach (['2026-11-01', '2026-11-02', '2026-11-04', '2026-11-05'] as $d) {
            HrSchedule::create([
                'hr_employee_id' => $this->empPerCallNurse->id,
                'hr_shift_id' => $nurseShift->id,
                'shift_date' => $d,
                'is_off_day' => false,
                'attendance_status' => 'present',
                'created_by' => $this->deptManagerA->id,
            ]);
        }

        // Emergency Doctor: 2 calls in Week 1 -> 2 * 150,000 = 300,000 IQD
        foreach (['2026-11-02', '2026-11-06'] as $d) {
            HrSchedule::create([
                'hr_employee_id' => $this->empPerCallEmergencyDoc->id,
                'hr_shift_id' => $emergencyDocShift->id,
                'shift_date' => $d,
                'is_off_day' => false,
                'attendance_status' => 'present',
                'created_by' => $this->deptManagerA->id,
            ]);
        }

        // Week 2 (2026-11-08 to 2026-11-14): Resident has 1 call BUT Week 2 is NOT approved yet -> MUST NOT be paid
        HrSchedule::create([
            'hr_employee_id' => $this->empPerCallResident->id,
            'hr_shift_id' => $residentShift->id,
            'shift_date' => '2026-11-09',
            'is_off_day' => false,
            'attendance_status' => 'present',
            'created_by' => $this->deptManagerA->id,
        ]);

        // Dept Manager A submits Week 1 for approval
        $this->actingAs($this->deptManagerA)
            ->post(route('hr.schedules.submit-approval'), [
                'department_id' => $this->deptA->id,
                'period_type' => 'week',
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-07',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $approval = HrRosterApproval::firstOrFail();
        $this->assertEquals('submitted_by_dept', $approval->status);

        // HR Manager / Admin Director gives final approval for Week 1 -> locks Week 1
        $this->actingAs($this->hrManager)
            ->post(route('hr.schedules.final-approve', $approval->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals('approved', $approval->fresh()->status);

        // Editing a locked Week 1 cell is blocked with 403
        $this->actingAs($this->deptManagerA)
            ->postJson(route('hr.schedules.update-cell'), [
                'hr_employee_id' => $this->empPerCallResident->id,
                'shift_date' => '2026-11-02',
                'shift_value' => 'off',
            ])
            ->assertStatus(403);

        // Generate Monthly Payroll for 2026-11
        $this->actingAs($this->admin)
            ->post(route('hr.payrolls.store'), [
                'cycle_month' => '2026-11',
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-30',
            ])
            ->assertRedirect();

        $cycle = HrPayrollCycle::where('cycle_month', '2026-11')->firstOrFail();

        $residentSlip = HrPayroll::where('hr_payroll_cycle_id', $cycle->id)
            ->where('hr_employee_id', $this->empPerCallResident->id)
            ->firstOrFail();
        $this->assertEquals('per_call', $residentSlip->salary_calculation_mode);
        $this->assertEquals(0.0, (float) $residentSlip->basic_salary);
        $this->assertEquals(2, $residentSlip->calls_count);
        $this->assertEquals(140000.0, (float) $residentSlip->calls_amount);
        $this->assertEquals(140000.0, (float) $residentSlip->net_salary);

        $nurseSlip = HrPayroll::where('hr_payroll_cycle_id', $cycle->id)
            ->where('hr_employee_id', $this->empPerCallNurse->id)
            ->firstOrFail();
        $this->assertEquals(4, $nurseSlip->calls_count);
        $this->assertEquals(120000.0, (float) $nurseSlip->calls_amount);
        $this->assertEquals(120000.0, (float) $nurseSlip->net_salary);

        $docSlip = HrPayroll::where('hr_payroll_cycle_id', $cycle->id)
            ->where('hr_employee_id', $this->empPerCallEmergencyDoc->id)
            ->firstOrFail();
        $this->assertEquals(2, $docSlip->calls_count);
        $this->assertEquals(300000.0, (float) $docSlip->calls_amount);
        $this->assertEquals(300000.0, (float) $docSlip->net_salary);

        // Verify payroll sheet & payslip render cleanly (HTTP 200)
        $this->actingAs($this->admin)->get(route('hr.payrolls.show', $cycle->id))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('hr.payrolls.slip.print', $docSlip->id))->assertStatus(200);
    }
}
