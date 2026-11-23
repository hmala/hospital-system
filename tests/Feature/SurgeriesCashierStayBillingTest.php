<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgicalOperation;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeriesCashierStayBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_surgeries_cashier_calculates_dynamic_stay_nights_correctly(): void
    {
        $admin = User::factory()->create(['name' => 'Cashier Admin', 'email' => 'cashier_stay@hospital.com']);
        $admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'مستشفى الفيحاء',
            'owner_name' => 'د. محمد',
            'phone' => '07701234567',
            'address' => 'البصرة',
            'license_number' => 'LIC-TEST-001',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'General Surgery Department',
            'type' => 'surgery',
            'room_number' => '301',
            'consultation_fee' => 50000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 20,
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. محمد علي']);
        $doctorUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'جراحة عامة',
            'phone' => '07711223344',
            'qualification' => 'Board',
            'license_number' => 'LIC-DOC-77',
            'type' => 'surgeon',
        ]);

        $patientUser = User::factory()->create(['name' => 'معد يوسف مغير']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'patient_id' => 'P-SURG-1002',
            'name' => 'معد يوسف مغير',
            'gender' => 'male',
            'age' => 40,
            'blood_group' => 'O+',
        ]);

        $room = Room::create([
            'room_number' => '501',
            'room_type' => 'regular',
            'room_purpose' => 'beds',
            'floor' => 'الطابق الخامس',
            'daily_fee' => 250000,
            'status' => 'occupied',
            'beds_count' => 1,
            'is_active' => true,
        ]);

        $operation = SurgicalOperation::create([
            'name' => 'رتق فتق فوق الصرة',
            'code' => 'OP-01',
            'category' => 'major',
            'department_id' => $dept->id,
            'fee' => 3000000,
            'is_active' => true,
        ]);

        // Surgery admitted 10 days ago (1 night included + 9 extra nights = 9 * 250,000 = 2,250,000)
        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'surgical_operation_id' => $operation->id,
            'room_id' => $room->id,
            'surgery_type' => 'رتق فتق فوق الصرة',
            'status' => 'scheduled',
            'scheduled_date' => Carbon::now()->subDays(10)->toDateString(),
            'scheduled_time' => Carbon::now()->subDays(10),
            'admission_date' => Carbon::now()->subDays(10),
            'room_checkin_at' => Carbon::now()->subDays(10),
            'surgery_fee' => 3000000,
            'surgery_fee_paid_amount' => 3000000,
            'room_fee' => 0,
            'room_fee_paid_amount' => 0,
            'payment_status' => 'partial',
            'billing_status' => 'ready',
        ]);

        // Request surgeries cashier index
        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('cashier.surgeries.index'));

        $response->assertStatus(200);
        $response->assertSee('معد يوسف مغير');
        $stayTotal = $surgery->calculateStayDetails()['total_fee'];
        $response->assertSee(number_format($stayTotal, 0));
        $response->assertSee('تسديد الرسوم');
    }

    public function test_main_cashier_has_link_to_advanced_surgeries_cashier(): void
    {
        $admin = User::factory()->create(['name' => 'Cashier Admin 2', 'email' => 'cashier_link@hospital.com']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('cashier.index'));

        $response->assertStatus(200);
        $response->assertSee(route('cashier.surgeries.index'));
        $response->assertSee('كاشير العمليات المتقدم');
        $response->assertDontSee('id="tab-surgeries"', false);
    }
}
