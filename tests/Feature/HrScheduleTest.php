<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\HrEmployee;
use App\Models\HrSchedule;
use App\Models\HrShift;
use App\Models\User;
use Database\Seeders\HospitalSeeder;
use Database\Seeders\HrSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $department;

    protected $morningShift;

    protected $nightShift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(HospitalSeeder::class);
        $this->seed(HrSettingsSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        $hospital = Hospital::first();
        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الطوارئ',
            'type' => 'emergency',
            'room_number' => '102',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 50,
        ]);

        $this->morningShift = HrShift::create([
            'name' => 'صباحي اعتيادي',
            'start_time' => '08:00',
            'end_time' => '15:00',
            'color_code' => '#0d6efd',
            'is_active' => true,
        ]);

        $this->nightShift = HrShift::create([
            'name' => 'خفارة ليلية',
            'start_time' => '20:00',
            'end_time' => '08:00',
            'color_code' => '#198754',
            'is_active' => true,
        ]);
    }

    protected function createEmployee(array $overrides = []): HrEmployee
    {
        static $counter = 1;
        $idx = $counter++;

        return HrEmployee::create(array_merge([
            'employee_code' => "EMP-SCH-$idx",
            'full_name' => "موظف اختبار $idx",
            'gender' => 'male',
            'phone' => "0770000000$idx",
            'staff_type' => 'medical',
            'department_id' => $this->department->id,
            'job_title' => 'ممرض',
            'employment_type' => 'full_time',
            'hire_date' => now()->subYear()->format('Y-m-d'),
            'basic_salary' => 700000,
            'status' => 'active',
        ], $overrides));
    }

    public function test_admin_can_view_schedules_in_week_and_month_modes()
    {
        $employee = $this->createEmployee([
            'full_name' => 'عمار الحكيم',
            'job_title' => 'ممرض جامعي',
            'schedule_type' => 'fixed',
            'default_shift_id' => $this->morningShift->id,
            'weekly_off_days' => ['friday', 'saturday'],
        ]);

        // 1. Week view
        $weekResponse = $this->actingAs($this->admin)->get(route('hr.schedules.index', [
            'view_mode' => 'week',
            'department_id' => $this->department->id,
            'start_date' => now()->startOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d'),
        ]));
        $weekResponse->assertStatus(200);
        $weekResponse->assertSee($employee->full_name);
        $weekResponse->assertSee('جدول الدوام والخفارات');
        $weekResponse->assertSee('إجمالي التغطية اليومية');
        $weekResponse->assertSee('أداة الختم السريع');

        // 2. Month view
        $monthResponse = $this->actingAs($this->admin)->get(route('hr.schedules.index', [
            'view_mode' => 'month',
            'department_id' => $this->department->id,
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
        ]));
        $monthResponse->assertStatus(200);
        $monthResponse->assertSee('شهري كامل');
    }

    public function test_admin_can_auto_fill_defaults_for_fixed_schedule_employees()
    {
        $employee = $this->createEmployee([
            'full_name' => 'زينب كاظم',
            'job_title' => 'طبيبة طوارئ',
            'schedule_type' => 'fixed',
            'default_shift_id' => $this->morningShift->id,
            'weekly_off_days' => ['friday'],
        ]);

        $startDate = now()->startOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d');
        $endDate = now()->startOfWeek(\Carbon\Carbon::SUNDAY)->addDays(6)->format('Y-m-d');

        $response = $this->actingAs($this->admin)->post(route('hr.schedules.auto-fill-defaults'), [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'department_id' => $this->department->id,
            'overwrite' => '1',
        ]);

        $response->assertSessionHas('success');

        // Check that records were created in hr_schedules
        $schedulesCount = HrSchedule::where('hr_employee_id', $employee->id)->count();
        $this->assertEquals(7, $schedulesCount);

        // Check off day on friday (day 5 of week starting on Sunday)
        $fridayDate = \Carbon\Carbon::parse($startDate)->addDays(5)->format('Y-m-d');
        $fridaySchedule = HrSchedule::where('hr_employee_id', $employee->id)
            ->whereDate('shift_date', $fridayDate)
            ->first();

        $this->assertNotNull($fridaySchedule);
        $this->assertTrue($fridaySchedule->is_off_day);
        $this->assertNull($fridaySchedule->hr_shift_id);
    }

    public function test_admin_can_update_single_cell_via_ajax()
    {
        $employee = $this->createEmployee([
            'full_name' => 'حسين علي',
            'job_title' => 'معاون طبي',
        ]);

        $testDate = now()->format('Y-m-d');

        // 1. Assign night shift
        $response = $this->actingAs($this->admin)->postJson(route('hr.schedules.update-cell'), [
            'hr_employee_id' => $employee->id,
            'shift_date' => $testDate,
            'shift_value' => (string) $this->nightShift->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action' => 'saved',
            'is_off' => false,
            'shift_id' => $this->nightShift->id,
        ]);

        $this->assertDatabaseHas('hr_schedules', [
            'hr_employee_id' => $employee->id,
            'shift_date' => $testDate,
            'hr_shift_id' => $this->nightShift->id,
            'is_off_day' => false,
        ]);

        // 2. Set to off
        $offResponse = $this->actingAs($this->admin)->postJson(route('hr.schedules.update-cell'), [
            'hr_employee_id' => $employee->id,
            'shift_date' => $testDate,
            'shift_value' => 'off',
        ]);

        $offResponse->assertStatus(200);
        $offResponse->assertJson([
            'success' => true,
            'action' => 'saved',
            'is_off' => true,
        ]);

        // 3. Clear cell
        $clearResponse = $this->actingAs($this->admin)->postJson(route('hr.schedules.update-cell'), [
            'hr_employee_id' => $employee->id,
            'shift_date' => $testDate,
            'shift_value' => '',
        ]);

        $clearResponse->assertStatus(200);
        $clearResponse->assertJson(['success' => true, 'action' => 'deleted']);
        $this->assertDatabaseMissing('hr_schedules', [
            'hr_employee_id' => $employee->id,
            'shift_date' => $testDate,
        ]);
    }

    public function test_admin_can_clear_schedule_range()
    {
        $employee = $this->createEmployee([
            'full_name' => 'كرار جاسم',
            'job_title' => 'ممرض',
        ]);

        $date1 = now()->format('Y-m-d');
        $date2 = now()->addDay()->format('Y-m-d');

        HrSchedule::create(['hr_employee_id' => $employee->id, 'shift_date' => $date1, 'hr_shift_id' => $this->morningShift->id]);
        HrSchedule::create(['hr_employee_id' => $employee->id, 'shift_date' => $date2, 'hr_shift_id' => $this->nightShift->id]);

        $response = $this->actingAs($this->admin)->post(route('hr.schedules.clear-range'), [
            'start_date' => $date1,
            'end_date' => $date2,
            'department_id' => $this->department->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(0, HrSchedule::where('hr_employee_id', $employee->id)->count());
    }

    public function test_admin_can_swap_shifts_between_two_employees()
    {
        $emp1 = $this->createEmployee([
            'full_name' => 'محمد باقر',
            'job_title' => 'طبيب',
        ]);

        $emp2 = $this->createEmployee([
            'full_name' => 'علي رضا',
            'job_title' => 'طبيب',
        ]);

        $testDate = now()->format('Y-m-d');

        HrSchedule::create(['hr_employee_id' => $emp1->id, 'shift_date' => $testDate, 'hr_shift_id' => $this->morningShift->id, 'is_off_day' => false]);
        HrSchedule::create(['hr_employee_id' => $emp2->id, 'shift_date' => $testDate, 'hr_shift_id' => $this->nightShift->id, 'is_off_day' => false]);

        $response = $this->actingAs($this->admin)->post(route('hr.schedules.swap'), [
            'swap_date' => $testDate,
            'employee1_id' => $emp1->id,
            'employee2_id' => $emp2->id,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hr_schedules', [
            'hr_employee_id' => $emp1->id,
            'shift_date' => $testDate,
            'hr_shift_id' => $this->nightShift->id,
        ]);

        $this->assertDatabaseHas('hr_schedules', [
            'hr_employee_id' => $emp2->id,
            'shift_date' => $testDate,
            'hr_shift_id' => $this->morningShift->id,
        ]);
    }
}
