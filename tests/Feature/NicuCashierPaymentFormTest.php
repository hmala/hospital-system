<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuPayment;
use App\Models\NicuService;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NicuCashierPaymentFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $cashier;

    protected User $unauthorizedUser;

    protected IncubatorReservation $reservation;

    protected Incubator $incubator;

    protected NicuService $service;

    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $hospital = Hospital::create([
            'name' => 'مستشفى الفيحاء',
            'owner_name' => 'د. حسام',
            'license_number' => 'LIC-100',
            'phone' => '07700000000',
            'address' => 'بغداد',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الأطفال والخدج',
            'description' => 'NICU',
            'type' => 'pediatrics',
            'room_number' => '601',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
        ]);

        $room = Room::create([
            'room_number' => 'NICU-101',
            'type' => 'nicu',
            'status' => 'available',
            'current_occupancy' => 0,
            'capacity' => 5,
        ]);

        $this->incubator = Incubator::create([
            'incubator_number' => 'INC-01',
            'room_id' => $room->id,
            'status' => 'occupied',
            'daily_fee' => 75000,
        ]);

        $this->service = NicuService::create([
            'name' => 'باقة رعاية حاضنة متقدمة',
            'code' => 'NICU-ADV',
            'daily_fee' => 100000,
            'is_active' => true,
        ]);

        $parentUser = User::factory()->create([
            'name' => 'أحمد العبيدي',
            'phone' => '07711112222',
        ]);

        $this->patient = Patient::create([
            'user_id' => $parentUser->id,
            'name' => 'أحمد العبيدي',
            'phone' => '07711112222',
            'national_id' => '199012345678',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
        ]);

        $this->reservation = IncubatorReservation::create([
            'patient_id' => $this->patient->id,
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'baby_name' => 'طفل أحمد الصغير',
            'admission_date' => now()->subDays(2)->toDateString(),
            'admission_time' => '10:00:00',
            'status' => IncubatorReservation::STATUS_ADMITTED,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->cashier = User::factory()->create();
        $this->cashier->assignRole('cashier');

        $this->unauthorizedUser = User::factory()->create();
        $this->unauthorizedUser->assignRole('nurse');
    }

    public function test_cashier_can_view_nicu_payment_form_matching_surgery_style()
    {
        $response = $this->actingAs($this->cashier)->get(route('cashier.nicu.payment.form', $this->reservation->id));

        $response->assertStatus(200);
        $response->assertSee('تسوية وتسديد رسوم رعاية وحاضنة الخدج');
        $response->assertSee('طفل أحمد الصغير');
        $response->assertSee('INC-01');
        $response->assertSee('باقة رعاية حاضنة متقدمة');
        $response->assertSee('صندوق تسديد رسوم الحاضنة وتصفية الحساب');
        $response->assertSee('تسديد كامل المبلغ المتبقي');
        $response->assertSee('تسديد جزئي (دفعة مقبوضة الآن)');
        $response->assertDontSee('nicuDepositModal');
    }

    public function test_cashier_index_links_directly_to_nicu_payment_form()
    {
        $response = $this->actingAs($this->cashier)->get(route('cashier.index'));

        $response->assertStatus(200);
        $response->assertSee(route('cashier.nicu.payment.form', $this->reservation->id));
        $response->assertSee('تسديد الرسوم');
        $response->assertDontSee('nicuDepositModal');
    }

    public function test_cashier_can_process_full_payment_successfully()
    {
        $response = $this->actingAs($this->cashier)->post(route('cashier.nicu.payment', $this->reservation->id), [
            'amount' => 200000,
            'payment_method' => 'cash',
            'notes' => 'تسديد كامل الحساب نقداً',
        ]);

        $this->assertDatabaseHas('nicu_payments', [
            'incubator_reservation_id' => $this->reservation->id,
            'amount' => 200000,
            'payment_method' => 'cash',
            'financial_status' => 'paid',
        ]);

        $payment = NicuPayment::where('incubator_reservation_id', $this->reservation->id)->first();
        $response->assertRedirect(route('cashier.nicu.receipt', $payment->id));
    }

    public function test_cashier_can_process_partial_payment_with_custom_amount()
    {
        $response = $this->actingAs($this->cashier)->post(route('cashier.nicu.payment', $this->reservation->id), [
            'payment_mode' => 'partial',
            'custom_paid_amount' => 50000,
            'amount' => 200000, // should be overridden by custom_paid_amount
            'payment_method' => 'card',
            'notes' => 'دفعة تسديد جزئية بالبطاقة',
        ]);

        $this->assertDatabaseHas('nicu_payments', [
            'incubator_reservation_id' => $this->reservation->id,
            'amount' => 50000,
            'payment_method' => 'card',
            'financial_status' => 'paid',
        ]);
    }

    public function test_cashier_can_process_refund_when_excess_deposit_exists()
    {
        // First, record a big payment to create an excess deposit
        NicuPayment::create([
            'incubator_reservation_id' => $this->reservation->id,
            'patient_id' => $this->patient->id,
            'cashier_id' => $this->cashier->id,
            'receipt_number' => NicuPayment::generateReceiptNumber(),
            'payment_type' => NicuPayment::TYPE_INTERIM,
            'amount' => 500000, // large payment exceeds calculated stay
            'payment_method' => 'cash',
            'financial_status' => 'paid',
            'notes' => 'دفعة مسبقة',
            'paid_at' => now(),
        ]);

        // Access the payment form, should see refund section
        $response = $this->actingAs($this->cashier)->get(route('cashier.nicu.payment.form', $this->reservation->id));
        $response->assertStatus(200);
        $response->assertSee('معالجة إرجاع الفائض المالي للمريض (Refund)');

        // Process refund
        $refundResponse = $this->actingAs($this->cashier)->post(route('cashier.nicu.payment', $this->reservation->id), [
            'payment_type' => 'refund',
            'amount' => 50000,
            'payment_method' => 'cash',
            'notes' => 'إرجاع جزء من الفائض نقداً',
        ]);

        $this->assertDatabaseHas('nicu_payments', [
            'incubator_reservation_id' => $this->reservation->id,
            'payment_type' => NicuPayment::TYPE_REFUND,
            'amount' => 50000,
            'financial_status' => 'paid',
        ]);
    }

    public function test_unauthorized_user_cannot_access_payment_form()
    {
        $response = $this->actingAs($this->unauthorizedUser)->get(route('cashier.nicu.payment.form', $this->reservation->id));
        $response->assertStatus(403);
    }
}
