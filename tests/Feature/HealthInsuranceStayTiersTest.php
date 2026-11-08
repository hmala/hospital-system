<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\HealthInsuranceStayTier;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HealthInsuranceStayTiersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $cashier;

    protected Department $department;

    protected Patient $insurancePatient;

    protected Doctor $doctor;

    protected Room $regularRoom;

    protected Room $vipRoom;

    protected Room $vvipRoom;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'process surgery payments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage health insurance', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات التخصصي',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-STAY-01',
            'phone' => '07700000000',
            'email' => 'stay@hospital.com',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '301',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'code' => 'SURG-STAY',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);
        $this->admin->givePermissionTo(['manage health insurance', 'process surgery payments']);

        $this->cashier = User::factory()->create();
        $this->cashier->givePermissionTo('process surgery payments');

        $doctorUser = User::factory()->create(['name' => 'د. جراح الإقامة']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'qualification' => 'بورد جراحة عامة',
            'license_number' => 'DOC-STAY-'.uniqid(),
            'status' => 'active',
        ]);

        $patientUser = User::factory()->create(['name' => 'مريض ضمان إقامة']);
        $this->insurancePatient = Patient::create([
            'user_id' => $patientUser->id,
            'insurance_type' => 'hi',
            'insurance_card_no' => 'HI-STAY-9988',
            'copay_percentage' => 15.0,
            'gender' => 'male',
            'date_of_birth' => '1985-05-15',
        ]);

        $this->regularRoom = Room::create([
            'room_number' => '101',
            'department_id' => $this->department->id,
            'room_type' => 'regular',
            'daily_fee' => 200000,
            'status' => 'available',
        ]);

        $this->vipRoom = Room::create([
            'room_number' => 'VIP-201',
            'department_id' => $this->department->id,
            'room_type' => 'vip',
            'daily_fee' => 350000,
            'status' => 'available',
        ]);

        $this->vvipRoom = Room::create([
            'room_number' => 'VVIP-301',
            'department_id' => $this->department->id,
            'room_type' => 'vvip',
            'daily_fee' => 450000,
            'status' => 'available',
        ]);

        // Seed tiers if not already present
        $this->artisan('migrate');
    }

    public function test_default_stay_tiers_seeded_correctly()
    {
        $this->assertDatabaseHas('health_insurance_stay_tiers', [
            'day_from' => 1,
            'day_to' => 1,
            'copay_percentage' => 0.00,
            'standard_room_daily_price' => 200000.00,
        ]);

        $this->assertDatabaseHas('health_insurance_stay_tiers', [
            'day_from' => 2,
            'day_to' => 2,
            'copay_percentage' => 10.00,
        ]);

        $this->assertDatabaseHas('health_insurance_stay_tiers', [
            'day_from' => 3,
            'day_to' => 8,
            'copay_percentage' => 25.00,
        ]);

        $this->assertDatabaseHas('health_insurance_stay_tiers', [
            'day_from' => 9,
            'day_to' => 13,
            'copay_percentage' => 50.00,
        ]);

        $this->assertDatabaseHas('health_insurance_stay_tiers', [
            'day_from' => 14,
            'day_to' => 999,
            'copay_percentage' => 75.00,
        ]);

        $this->assertEquals(200000.0, HealthInsuranceStayTier::getStandardRoomPrice());
    }

    public function test_stay_tier_copay_percentages_for_days()
    {
        $this->assertEquals(0.0, HealthInsuranceStayTier::getCopayPercentageForDay(1));
        $this->assertEquals(10.0, HealthInsuranceStayTier::getCopayPercentageForDay(2));
        $this->assertEquals(25.0, HealthInsuranceStayTier::getCopayPercentageForDay(3));
        $this->assertEquals(25.0, HealthInsuranceStayTier::getCopayPercentageForDay(8));
        $this->assertEquals(50.0, HealthInsuranceStayTier::getCopayPercentageForDay(9));
        $this->assertEquals(50.0, HealthInsuranceStayTier::getCopayPercentageForDay(13));
        $this->assertEquals(75.0, HealthInsuranceStayTier::getCopayPercentageForDay(14));
        $this->assertEquals(75.0, HealthInsuranceStayTier::getCopayPercentageForDay(20));
    }

    public function test_regular_room_calculation_for_insurance()
    {
        // Day 1: 0% copay, free within surgery (0 on patient, 0 on insurance)
        $day1 = HealthInsuranceStayTier::calculateNightFigures(1, $this->regularRoom);
        $this->assertEquals(0.0, $day1['upgrade_diff']);
        $this->assertEquals(0.0, $day1['regular_patient_share']);
        $this->assertEquals(0.0, $day1['regular_insurance_share']);

        // Day 2: 10% copay = 20,000 IQD, insurance = 180,000 IQD
        $day2 = HealthInsuranceStayTier::calculateNightFigures(2, $this->regularRoom);
        $this->assertEquals(10.0, $day2['copay_percentage']);
        $this->assertEquals(20000.0, $day2['regular_patient_share']);
        $this->assertEquals(180000.0, $day2['regular_insurance_share']);
        $this->assertEquals(0.0, $day2['upgrade_diff']);

        // Day 3: 25% copay = 50,000 IQD, insurance = 150,000 IQD
        $day3 = HealthInsuranceStayTier::calculateNightFigures(3, $this->regularRoom);
        $this->assertEquals(25.0, $day3['copay_percentage']);
        $this->assertEquals(50000.0, $day3['regular_patient_share']);
        $this->assertEquals(150000.0, $day3['regular_insurance_share']);

        // Day 9: 50% copay = 100,000 IQD, insurance = 100,000 IQD
        $day9 = HealthInsuranceStayTier::calculateNightFigures(9, $this->regularRoom);
        $this->assertEquals(50.0, $day9['copay_percentage']);
        $this->assertEquals(100000.0, $day9['regular_patient_share']);
        $this->assertEquals(100000.0, $day9['regular_insurance_share']);

        // Day 14: 75% copay = 150,000 IQD, insurance = 50,000 IQD
        $day14 = HealthInsuranceStayTier::calculateNightFigures(14, $this->regularRoom);
        $this->assertEquals(75.0, $day14['copay_percentage']);
        $this->assertEquals(150000.0, $day14['regular_patient_share']);
        $this->assertEquals(50000.0, $day14['regular_insurance_share']);
    }

    public function test_vip_room_calculation_with_upgrade_difference_for_all_days()
    {
        // VIP daily fee = 350,000, standard = 200,000 => upgrade difference = 150,000 IQD
        // Day 1: Patient pays 0 (standard) + 150,000 (upgrade diff) = 150,000 IQD. Insurance pays 0.
        $day1 = HealthInsuranceStayTier::calculateNightFigures(1, $this->vipRoom);
        $this->assertEquals(150000.0, $day1['upgrade_diff']);
        $this->assertEquals(150000.0, $day1['total_patient_share']);
        $this->assertEquals(0.0, $day1['regular_insurance_share']);

        // Day 2: Patient pays 20,000 (10%) + 150,000 (upgrade diff) = 170,000 IQD. Insurance pays 180,000 IQD.
        $day2 = HealthInsuranceStayTier::calculateNightFigures(2, $this->vipRoom);
        $this->assertEquals(150000.0, $day2['upgrade_diff']);
        $this->assertEquals(20000.0, $day2['regular_patient_share']);
        $this->assertEquals(170000.0, $day2['total_patient_share']);
        $this->assertEquals(180000.0, $day2['regular_insurance_share']);

        // Day 3: Patient pays 50,000 (25%) + 150,000 (upgrade diff) = 200,000 IQD. Insurance pays 150,000 IQD.
        $day3 = HealthInsuranceStayTier::calculateNightFigures(3, $this->vipRoom);
        $this->assertEquals(150000.0, $day3['upgrade_diff']);
        $this->assertEquals(50000.0, $day3['regular_patient_share']);
        $this->assertEquals(200000.0, $day3['total_patient_share']);
        $this->assertEquals(150000.0, $day3['regular_insurance_share']);
    }

    public function test_vvip_room_calculation_with_upgrade_difference_for_all_days()
    {
        // VVIP daily fee = 450,000, standard = 200,000 => upgrade difference = 250,000 IQD
        // Day 1: Patient pays 0 (standard) + 250,000 (upgrade diff) = 250,000 IQD. Insurance pays 0.
        $day1 = HealthInsuranceStayTier::calculateNightFigures(1, $this->vvipRoom);
        $this->assertEquals(250000.0, $day1['upgrade_diff']);
        $this->assertEquals(250000.0, $day1['total_patient_share']);
        $this->assertEquals(0.0, $day1['regular_insurance_share']);

        // Day 2: Patient pays 20,000 (10%) + 250,000 (upgrade diff) = 270,000 IQD. Insurance pays 180,000 IQD.
        $day2 = HealthInsuranceStayTier::calculateNightFigures(2, $this->vvipRoom);
        $this->assertEquals(250000.0, $day2['upgrade_diff']);
        $this->assertEquals(20000.0, $day2['regular_patient_share']);
        $this->assertEquals(270000.0, $day2['total_patient_share']);
        $this->assertEquals(180000.0, $day2['regular_insurance_share']);
    }

    public function test_admin_can_update_stay_tier_copay()
    {
        $tier = HealthInsuranceStayTier::where('day_from', 2)->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->put(route('health-insurance-categories.stay-tiers.update', $tier), [
                'name' => 'اليوم الثاني المعدل',
                'day_from' => $tier->day_from,
                'day_to' => $tier->day_to,
                'copay_percentage' => 12.5,
                'notes' => 'معدل بطلب الإدارة',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('health_insurance_stay_tiers', [
            'id' => $tier->id,
            'copay_percentage' => 12.5,
            'name' => 'اليوم الثاني المعدل',
        ]);
    }

    public function test_admin_can_update_standard_room_price()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('health-insurance-categories.stay-tiers.standard-price'), [
                'standard_room_daily_price' => 220000,
            ]);

        $response->assertRedirect();
        $this->assertEquals(220000.0, HealthInsuranceStayTier::getStandardRoomPrice());
    }

    public function test_surgery_stay_breakdown_and_cashier_payment_for_insurance()
    {
        // Surgery with VIP room: Checkin yesterday 10:00 AM, checkout today 14:00 (Total 2 nights)
        $tz = 'Asia/Baghdad';
        $checkin = Carbon::now($tz)->subDay()->setTime(10, 0, 0);
        $checkout = Carbon::now($tz)->setTime(14, 0, 0); // Past 12:00 cutoff => 2 nights

        $surgery = Surgery::create([
            'patient_id' => $this->insurancePatient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'room_id' => $this->vipRoom->id,
            'surgery_type' => 'عملية استئصال مرارة بالناظور',
            'surgery_fee' => 1000000,
            'room_fee' => 350000,
            'surgery_fee_paid' => 'pending',
            'status' => 'completed',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'started_at' => $checkin,
            'discharged_at' => $checkout,
            'insurance_type' => 'hi',
        ]);

        $stayDetails = $surgery->calculateStayDetails();
        $this->assertTrue($stayDetails['is_insurance_calculated']);
        $this->assertCount(2, $stayDetails['nights_breakdown']);

        // Night 1 VIP: 0 standard + 150,000 upgrade diff = 150,000 IQD. Ins: 0.
        // Night 2 VIP: 20,000 standard (10%) + 150,000 upgrade diff = 170,000 IQD. Ins: 180,000.
        // Total Patient Share = 150,000 + 170,000 = 320,000 IQD.
        // Total Insurance Share = 0 + 180,000 = 180,000 IQD.
        $this->assertEquals(320000.0, $stayDetails['patient_stay_share']);
        $this->assertEquals(180000.0, $stayDetails['insurance_stay_share']);

        // Check Cashier Form
        $formResponse = $this->actingAs($this->cashier)
            ->get(route('cashier.surgeries.payment.form', $surgery));

        $formResponse->assertStatus(200);
        $formResponse->assertSee('استقطاع متدرج (ضمان)');
        $formResponse->assertSee('عرض تفكيك استقطاع الليالي');
        $formResponse->assertSee('320,000'); // Patient share
        $formResponse->assertSee('180,000'); // Insurance share

        // Process Payment from Cashier: pay room & pay surgery
        $postData = [
            'payment_method' => 'cash',
            'insurance_type' => 'hi',
            'copay_percentage' => 15.0, // for surgery
            'amount' => 470000, // 150,000 (surgery 15%) + 320,000 (room stay)
            'pay_surgery' => 1,
            'pay_room' => 1,
            'surgery_payment_type' => 'full',
            'room_payment_type' => 'full',
            'package_included' => [
                'extra' => [],
            ],
        ];

        $paymentResponse = $this->actingAs($this->cashier)
            ->post(route('cashier.surgeries.payment.process', $surgery), $postData);

        $paymentResponse->assertRedirect(route('cashier.surgeries.index'));

        // Check Payment record
        $payment = Payment::where('surgery_id', $surgery->id)->latest()->first();
        $this->assertNotNull($payment);

        // Surgery Patient: 1,000,000 * 15% = 150,000. Insurance: 850,000.
        // Room Patient: 320,000. Insurance: 180,000.
        // Total Patient Share: 150,000 + 320,000 = 470,000 IQD.
        // Total Insurance Share: 850,000 + 180,000 = 1,030,000 IQD.
        $this->assertEquals(470000.0, (float) $payment->amount);
        $this->assertEquals(470000.0, (float) $payment->patient_share);
        $this->assertEquals(1030000.0, (float) $payment->insurance_share);
        $this->assertEquals('pending', $payment->claim_status);
        $this->assertEquals('hi', $payment->insurance_type);

        // Verify surgery room paid amount updated
        $surgery->refresh();
        $this->assertEquals(320000.0, (float) $surgery->room_fee_paid_amount);
    }
}
