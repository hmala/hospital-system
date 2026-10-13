<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorDue;
use App\Models\DoctorFinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HospitalTreasuryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'view account statements', 'guard_name' => 'web']);
    }

    public function test_unauthorized_user_cannot_access_treasury()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('treasury.index'));
        $response->assertStatus(403);
    }

    public function test_accountant_can_view_treasury_and_kpis()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view account statements');

        // Create some sample transactions
        FinancialTransaction::create([
            'transaction_type' => 'hospital_revenue',
            'voucher_type'     => 'inflow',
            'category'         => 'consultation',
            'voucher_number'   => 'REV-TEST-1',
            'amount'           => 50000,
            'currency'         => 'IQD',
            'performed_by_id'  => $user->id,
            'performed_at'     => now(),
        ]);

        FinancialTransaction::create([
            'transaction_type' => 'expense',
            'voucher_type'     => 'outflow',
            'category'         => 'operational',
            'voucher_number'   => 'EXP-TEST-1',
            'amount'           => 15000,
            'currency'         => 'IQD',
            'performed_by_id'  => $user->id,
            'performed_at'     => now(),
        ]);

        $response = $this->actingAs($user)->get(route('treasury.index'));
        $response->assertStatus(200);
        $response->assertSee('خزينة المستشفى المركزية');
        $response->assertSee('50,000');
        $response->assertSee('15,000');
        $response->assertSee('35,000'); // Net balance: 50,000 - 15,000 = 35,000
    }

    public function test_accountant_can_store_expense_voucher()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view account statements');

        $response = $this->actingAs($user)->post(route('treasury.expense.store'), [
            'category'       => 'operational',
            'amount'         => 25000,
            'description'    => 'شراء وقود للمولدات',
            'payment_method' => 'cash',
            'performed_at'   => now()->format('Y-m-d\TH:i'),
            'notes'          => 'فاتورة محطة الوقود',
        ]);

        $response->assertRedirect(route('treasury.index'));
        $this->assertDatabaseHas('financial_transactions', [
            'voucher_type' => 'outflow',
            'category'     => 'operational',
            'amount'       => 25000,
            'description'  => 'شراء وقود للمولدات',
        ]);
    }

    public function test_accountant_can_store_doctor_payout_and_sync_account()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view account statements');

        $hospital = \App\Models\Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);
        $dept = \App\Models\Department::create([
            'name'                => 'باطنية',
            'type'                => 'internal',
            'room_number'         => '101',
            'consultation_fee'    => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end'   => '16:00:00',
            'hospital_id'         => $hospital->id,
        ]);
        $doctorUser = User::factory()->create(['name' => 'د. حسام العراقي']);
        $doctor = Doctor::create([
            'user_id'          => $doctorUser->id,
            'department_id'    => $dept->id,
            'specialization'   => 'استشاري باطنية',
            'qualification'    => 'دكتوراه / بورد',
            'license_number'   => 'DOC-9912',
            'consultation_fee' => 25000,
            'is_active'        => true,
            'type'             => 'consultant',
        ]);

        // Prior earning of 100,000
        DoctorDue::create([
            'doctor_id' => $doctor->id,
            'amount'    => 100000,
            'status'    => 'pending',
            'notes'     => 'مستحقات كشفية',
        ]);
        DoctorFinancialAccount::create([
            'doctor_id'    => $doctor->id,
            'total_earned' => 100000,
            'total_paid'   => 0,
            'balance'      => 100000,
        ]);

        // Pay doctor 40,000 from Treasury
        $response = $this->actingAs($user)->post(route('treasury.expense.store'), [
            'category'       => 'doctor_payout',
            'doctor_id'      => $doctor->id,
            'amount'         => 40000,
            'description'    => 'صرف دفعة للطبيب حسام',
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('treasury.index'));

        // Verify transaction created
        $this->assertDatabaseHas('financial_transactions', [
            'voucher_type' => 'outflow',
            'category'     => 'doctor_payout',
            'related_type' => Doctor::class,
            'related_id'   => $doctor->id,
            'amount'       => 40000,
        ]);

        // Verify doctor account updated: balance should now be 60,000
        $account = DoctorFinancialAccount::where('doctor_id', $doctor->id)->first();
        $this->assertEquals(60000, $account->balance);
        $this->assertEquals(40000, $account->total_paid);
    }

    public function test_accountant_can_store_income_voucher()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view account statements');

        $response = $this->actingAs($user)->post(route('treasury.income.store'), [
            'category'       => 'general_income',
            'amount'         => 80000,
            'description'    => 'تحصيل مستحقات ضمان صحي',
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertRedirect(route('treasury.index'));
        $this->assertDatabaseHas('financial_transactions', [
            'voucher_type' => 'inflow',
            'category'     => 'general_income',
            'amount'       => 80000,
        ]);
    }
}
