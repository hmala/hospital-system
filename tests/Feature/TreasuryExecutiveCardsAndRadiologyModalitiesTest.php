<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\FinancialTransaction;
use App\Models\Payment;
use App\Models\Request as MedRequest;
use App\Models\Patient;
use App\Models\Department;
use App\Models\Visit;
use App\Models\Doctor;
use App\Models\DoctorDue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TreasuryExecutiveCardsAndRadiologyModalitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $hosp = \App\Models\Hospital::firstOrCreate(
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

    public function test_treasury_displays_six_executive_cards_including_insurance_claims_and_pending_dues()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Cash Inflow
        FinancialTransaction::create([
            'transaction_type' => 'hospital_revenue',
            'category' => 'consultation',
            'voucher_type' => 'inflow',
            'voucher_number' => 'V-IN-1',
            'amount' => 100000,
            'currency' => 'IQD',
            'payment_method' => 'cash',
            'description' => 'إيراد نقدي',
            'performed_at' => now(),
        ]);

        // Insurance share payment (Accrual receivable)
        Payment::create([
            'payment_type' => 'lab',
            'amount' => 50000,
            'insurance_share' => 150000,
            'paid_at' => now(),
        ]);

        $dept = Department::create([
            'hospital_id' => \App\Models\Hospital::first()->id,
            'name' => 'قسم الجراحة',
            'type' => 'surgery',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
        ]);

        // Pending doctor liabilities
        $docUser = User::factory()->create();
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'جراحة',
            'qualification' => 'بورد',
            'license_number' => 'DOC-DUE-' . uniqid(),
            'is_active' => true,
        ]);
        DoctorDue::create([
            'doctor_id' => $doctor->id,
            'amount' => 85000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('treasury.index'));

        $response->assertStatus(200);
        // Check 6 executive cards
        $response->assertSee('الرصيد الفعلي بالخزينة');
        $response->assertSee('المقبوض نقداً (الفترة)');
        $response->assertSee('مطالبات الضمان (الفترة)');
        $response->assertSee('المسترجع للمرضى');
        $response->assertSee('المصروفات المنفذة');
        $response->assertSee('صافي سيولة الفترة');

        // Assert values appear
        $response->assertSee('150,000'); // Insurance claim
        $response->assertSee('85,000'); // Pending doctor due
    }

    public function test_treasury_decomposes_radiology_into_modalities_in_departments_table()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $patient = Patient::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'مريض فحص',
            'phone' => '07700112233',
            'gender' => 'male',
        ]);

        $dept = Department::create([
            'hospital_id' => \App\Models\Hospital::first()->id,
            'name' => 'قسم الأشعة',
            'type' => 'internal',
            'room_number' => '102',
            'consultation_fee' => 20000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $dept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص روتيني',
            'status' => 'completed',
        ]);

        // Ultrasound request & payment
        $sonarReq = MedRequest::create([
            'visit_id' => $visit->id,
            'type' => 'radiology',
            'subtype' => 'ultrasound',
            'description' => 'فحص سونار بطن وحوض',
            'status' => 'completed',
        ]);
        Payment::create([
            'payment_type' => 'radiology',
            'request_id' => $sonarReq->id,
            'amount' => 45000,
            'insurance_share' => 15000,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('treasury.index'));

        $response->assertStatus(200);
        $response->assertSee('السونار (Ultrasound)');
        $response->assertSee('45,000');
        $response->assertSee('15,000');
        $response->assertSee('60,000'); // Accrual gross = 45k + 15k
    }
}
