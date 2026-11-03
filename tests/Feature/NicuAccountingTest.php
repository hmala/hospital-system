<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuConsultation;
use App\Models\NicuPayment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NicuAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $cashierUser;

    protected Doctor $doctor;

    protected Patient $patient;

    protected Incubator $incubator;

    protected IncubatorReservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'hospital_admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);

        // إنشاء مستخدم المدير مع كافة الصلاحيات
        $this->admin = User::factory()->create(['name' => 'مدير الحسابات']);
        $this->admin->assignRole('admin');

        $hospital = \App\Models\Hospital::create([
            'name' => 'مستشفى الفيحاء',
            'owner_name' => 'د. حسام',
            'license_number' => 'LIC-100',
            'phone' => '07700000000',
            'address' => 'بغداد',
        ]);

        $dept = Department::firstOrCreate([
            'name' => 'قسم الأطفال والخدج',
            'hospital_id' => $hospital->id,
            'type' => 'pediatrics',
            'room_number' => '201',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. حيدر اختصاص أطفال']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'أطفال وحديثي ولادة',
            'qualification' => 'بورد أطفال',
            'license_number' => 'DOC-LIC-999',
            'consultation_fee' => 20000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'أم المولود']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'phone' => '07700000001',
            'gender' => 'female',
        ]);

        $this->incubator = Incubator::create([
            'incubator_number' => 'INC-99',
            'room_number' => '101',
            'status' => 'occupied',
            'daily_fee' => 100000,
        ]);

        $this->reservation = IncubatorReservation::create([
            'patient_id' => $this->patient->id,
            'baby_name' => 'طفل سارة',
            'incubator_id' => $this->incubator->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $dept->id,
            'admission_date' => now()->subDays(3)->toDateString(),
            'admission_time' => '10:00:00',
            'status' => 'admitted',
            'total_cost' => 300000,
            'insurance_type' => 'none',
        ]);
    }

    public function test_admin_can_access_nicu_analytics(): void
    {
        $response = $this->actingAs($this->admin)->get(route('accountant.nicu.analytics'));

        $response->assertStatus(200);
        $response->assertSee('لوحة تحليلات وإحصاءات الخدج والحاضنات (NICU)');
        $response->assertSee('حالات دخول الخدج');
        $response->assertSee('نسبة إشغال الحاضنات');
    }

    public function test_admin_can_access_nicu_financial_movements(): void
    {
        // تسجيل دفعة أمانات للطفل
        NicuPayment::create([
            'incubator_reservation_id' => $this->reservation->id,
            'patient_id' => $this->patient->id,
            'cashier_id' => $this->admin->id,
            'receipt_number' => 'NICU-TEST-001',
            'payment_type' => NicuPayment::TYPE_DEPOSIT,
            'amount' => 150000,
            'payment_method' => 'cash',
            'financial_status' => 'paid',
            'notes' => 'أمانات وسلفة دخول',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('cashier.nicu.financial-movements'));

        $response->assertStatus(200);
        $response->assertSee('الحركات المالية لقسم الخدج والحاضنات');
        $response->assertSee('NICU-TEST-001');
        $response->assertSee('150,000');
    }

    public function test_admin_can_access_nicu_statements(): void
    {
        $response = $this->actingAs($this->admin)->get(route('cashier.nicu.statements'));

        $response->assertStatus(200);
        $response->assertSee('كشوفات حسابات الخدج والحاضنات');
        $response->assertSee($this->reservation->baby_title);
    }

    public function test_admin_can_view_nicu_doctor_accounts_and_payout(): void
    {
        // إضافة استشارة منجزة للطبيب بقيمة 50000
        NicuConsultation::create([
            'reservation_id' => $this->reservation->id,
            'consultation_type' => NicuConsultation::TYPE_INTERNAL,
            'doctor_id' => $this->doctor->id,
            'specialty' => 'أطفال وحديثي ولادة',
            'reason' => 'فحص ومتابعة سريرية دورية',
            'fee' => 50000,
            'status' => NicuConsultation::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // عرض قائمة حسابات الأطباء
        $response = $this->actingAs($this->admin)->get(route('cashier.nicu.doctor-accounts'));
        $response->assertStatus(200);
        $response->assertSee('حسابات أطباء الخدج والأطفال');
        $response->assertSee($this->doctor->user->name);
        $response->assertSee('50,000');

        // عرض كشف حساب الطبيب المفرد
        $detailResponse = $this->actingAs($this->admin)->get(route('cashier.nicu.doctor-account', $this->doctor));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('كشف حساب الخدج والحاضنات للطبيب');
        $detailResponse->assertSee('50,000');

        // صرف مستحقات الطبيب
        $payoutResponse = $this->actingAs($this->admin)->post(route('cashier.nicu.doctor-payout', $this->doctor), [
            'amount' => 25000,
            'notes' => 'صرف دفعة مستحقات خدج',
        ]);

        $payoutResponse->assertRedirect(route('cashier.nicu.doctor-account', $this->doctor));
        $this->assertDatabaseHas('doctor_dues', [
            'doctor_id' => $this->doctor->id,
            'amount' => 25000,
            'notes' => 'صرف دفعة مستحقات خدج',
        ]);
    }
}
