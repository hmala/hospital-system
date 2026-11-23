<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\HospitalExtraService;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\SurgeryExtraService;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhysiotherapySurgeryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createSurgeryFixture(): array
    {
        $admin = User::factory()->create(['name' => 'Surgery Admin', 'email' => 'surg_admin@hospital.com']);
        $admin->assignRole('admin');

        $residentUser = User::factory()->create(['name' => 'Dr. Resident User', 'email' => 'resident@hospital.com']);
        $residentUser->assignRole('resident');

        $hospital = Hospital::create([
            'name' => 'Central Surgical Hospital',
            'owner_name' => 'Director',
            'license_number' => 'LIC-SURG-PHY-01',
            'phone' => '07700000030',
            'email' => 'surg_phy@hospital.com',
            'address' => 'Baghdad',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'Surgery Ward',
            'type' => 'surgery',
            'room_number' => '401',
            'consultation_fee' => 50000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 20,
            'is_active' => true,
        ]);

        $docUser = User::factory()->create(['name' => 'Dr. Lead Surgeon']);
        $docUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'General Surgery',
            'phone' => '07711223388',
            'qualification' => 'Board',
            'license_number' => 'DOC-SURG-77',
            'type' => 'surgeon',
        ]);

        $patUser = User::factory()->create(['name' => 'Ahmed Patient']);
        $patient = Patient::create([
            'user_id' => $patUser->id,
            'patient_id' => 'P-PHY-101',
            'name' => 'Ahmed Patient',
            'gender' => 'male',
            'age' => 40,
            'blood_group' => 'O+',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'surgery_name' => 'Cholecystectomy',
            'surgery_type' => 'Major',
            'surgery_fee' => 500000,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => 'scheduled',
            'payment_status' => 'pending',
        ]);

        // Ensure HospitalExtraService exists
        HospitalExtraService::updateOrInsert(
            ['name' => 'علاج فيزيائي'],
            [
                'code' => 'PHYS-01',
                'category' => 'medical',
                'default_price' => 60000.00,
                'is_active' => true,
            ]
        );

        return [$admin, $residentUser, $surgery];
    }

    public function test_clinical_station_consultations_tab_displays_physiotherapy_section(): void
    {
        [$admin, $resident, $surgery] = $this->createSurgeryFixture();

        $response = $this->actingAs($resident)
            ->get(route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => 'post_op', 'tab' => 'consultations']));

        $response->assertStatus(200);
        $response->assertSee('طلب خدمة علاج فيزيائي وتأهيل سريري');
        $response->assertSee('60,000 د.ع');
        $response->assertSee('عدد الجلسات:');
        $response->assertSee('إرسال للكاشير');
    }

    public function test_resident_can_store_physiotherapy_request_and_appears_in_cashier_payment_form(): void
    {
        [$admin, $resident, $surgery] = $this->createSurgeryFixture();

        // 1. Resident requests 1 session of physiotherapy
        $response = $this->actingAs($resident)
            ->post(route('clinical-station.physiotherapy.store', $surgery->id), [
                'sessions' => 1,
                'unit_price' => 60000,
                'notes' => 'تمارين تنفسية وتأهيل حركي بعد استئصال المرارة',
            ]);

        $response->assertRedirect(route('clinical-station.show', ['surgery' => $surgery->id, 'tab' => 'consultations']));
        $response->assertSessionHas('success');

        // Check DB
        $this->assertDatabaseHas('surgery_extra_services', [
            'surgery_id' => $surgery->id,
            'name' => 'علاج فيزيائي',
            'price' => 60000,
            'quantity' => 1,
            'total_amount' => 60000,
            'payment_status' => 'pending',
            'created_by' => $resident->id,
        ]);

        // 2. Cashier opens surgery payment form
        $cashierResponse = $this->actingAs($admin)
            ->get(route('cashier.surgeries.payment.form', $surgery->id));

        $cashierResponse->assertStatus(200);
        $cashierResponse->assertSee('علاج فيزيائي');
        $cashierResponse->assertSee('60000');
        $cashierResponse->assertSee('سعر الخدمة (يمكن تعديله)');
        $cashierResponse->assertSee('extra_service_prices');
    }

    public function test_cashier_can_edit_physiotherapy_price_and_process_payment(): void
    {
        [$admin, $resident, $surgery] = $this->createSurgeryFixture();

        // Resident requests physiotherapy
        $this->actingAs($resident)
            ->post(route('clinical-station.physiotherapy.store', $surgery->id), [
                'sessions' => 1,
                'unit_price' => 60000,
                'notes' => 'جلسة علاج طبيعي',
            ]);

        $extraService = SurgeryExtraService::where('surgery_id', $surgery->id)->first();
        $this->assertNotNull($extraService);

        // Cashier edits price to 55,000 IQD and pays
        $payResponse = $this->actingAs($admin)
            ->post(route('cashier.surgeries.payment.process', $surgery->id), [
                'amount' => 555000,
                'pay_surgery' => '1',
                'pay_extra_services' => [$extraService->id],
                'extra_service_prices' => [
                    $extraService->id => 55000,
                ],
                'payment_method' => 'cash',
                'notes' => 'تم تعديل سعر العلاج الطبيعي إلى 55 ألف',
            ]);

        $payResponse->assertSessionHasNoErrors();
        $payResponse->assertRedirect();

        // Check updated price and paid status
        $extraService->refresh();
        $this->assertEquals('paid', $extraService->payment_status);
        $this->assertEquals(55000, $extraService->total_amount);
    }

    public function test_resident_can_cancel_pending_physiotherapy_request(): void
    {
        [$admin, $resident, $surgery] = $this->createSurgeryFixture();

        $this->actingAs($resident)
            ->post(route('clinical-station.physiotherapy.store', $surgery->id), [
                'sessions' => 1,
                'unit_price' => 60000,
                'notes' => 'جلسة قبل الإلغاء',
            ]);

        $extraService = SurgeryExtraService::where('surgery_id', $surgery->id)->first();
        $this->assertNotNull($extraService);

        // Cancel
        $cancelResponse = $this->actingAs($resident)
            ->delete(route('clinical-station.physiotherapy.cancel', [$surgery->id, $extraService->id]));

        $cancelResponse->assertRedirect();
        $this->assertDatabaseMissing('surgery_extra_services', ['id' => $extraService->id]);
    }
}
