<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\HospitalExtraService;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuExtraService;
use App\Models\NicuService;
use App\Models\Patient;
use App\Models\User;
use App\Services\NicuBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NicuExtraServicesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $cashier;

    protected User $unauthorizedUser;

    protected IncubatorReservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = [
            'view cashier',
            'process payments',
            'view payments',
            'manage users',
            'manage settings',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($perms);

        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $cashierRole->givePermissionTo(['view cashier', 'process payments', 'view payments']);

        $this->admin = User::factory()->create(['name' => 'مدير النظام', 'role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->cashier = User::factory()->create(['name' => 'أمين الصندوق', 'role' => 'cashier']);
        $this->cashier->assignRole('cashier');

        $this->unauthorizedUser = User::factory()->create(['name' => 'مستخدم بدون صلاحية', 'role' => 'patient']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'د. المدير',
            'license_number' => 'LIC-EXTRA-01',
            'phone' => '07700000000',
            'address' => 'بغداد',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الخدج',
            'type' => 'pediatrics',
            'room_number' => '601',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
            'is_active' => true,
        ]);

        $docUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'أطفال وخدج',
            'qualification' => 'بورد أطفال',
            'license_number' => 'DOC-EXTRA-01',
            'consultation_fee' => 25000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'أم الرضيع', 'role' => 'patient']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'phone' => '07700000009',
            'gender' => 'female',
        ]);

        $service = NicuService::create([
            'name' => 'حاضنة عناية متوسطة',
            'code' => 'NICU-EXTRA-1',
            'daily_fee' => 100000,
            'is_active' => true,
        ]);

        $incubator = Incubator::create([
            'incubator_number' => 'INC-99',
            'room_number' => '601',
            'incubator_type' => 'normal',
            'daily_fee' => 100000,
            'status' => 'available',
        ]);

        $this->reservation = IncubatorReservation::create([
            'patient_id' => $patient->id,
            'baby_name' => 'طفل اختبار الخدمات الإضافية',
            'incubator_id' => $incubator->id,
            'nicu_service_id' => $service->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'admission_date' => now()->toDateString(),
            'admission_time' => '09:00',
            'status' => IncubatorReservation::STATUS_ADMITTED,
        ]);
    }

    public function test_nicu_payment_form_displays_nicu_extra_services_box_and_isolates_surgery_only_catalog(): void
    {
        HospitalExtraService::create([
            'name' => 'خدمة عمليات حصرية فقط',
            'category' => 'decorating',
            'department' => 'general',
            'default_price' => 50000,
            'is_active' => true,
        ]);

        HospitalExtraService::create([
            'name' => 'خدمة مشتركة لكل الأقسام',
            'category' => 'administrative',
            'department' => 'all',
            'default_price' => 12000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('cashier.nicu.payment.form', $this->reservation));

        $response->assertOk();
        $response->assertSee('إضافة خدمات إضافية وفندقية للخدج والحاضنة');
        $response->assertSee('تصنيف ذكي وبحث فوري مثل ICD-10');
        $response->assertSee('علاج ضوئي إضافي (Phototherapy)');
        $response->assertSee('خدمة مشتركة لكل الأقسام');
        $response->assertDontSee('خدمة عمليات حصرية فقط');
        $response->assertSee('الخدمات المتفرقة للمستشفى');
    }

    public function test_cashier_can_add_update_and_remove_nicu_extra_services_and_billing_updates(): void
    {
        $catalogItem = HospitalExtraService::forNicu()->first();
        $this->assertNotNull($catalogItem);

        // 1. Add catalog service
        $addResponse = $this->actingAs($this->cashier)
            ->post(route('cashier.nicu.extra-services.add', $this->reservation), [
                'service_id' => $catalogItem->id,
                'service_name' => $catalogItem->name,
                'service_price' => 25000,
                'service_quantity' => 2,
                'service_notes' => 'جلستان ضوئيتان',
            ]);

        $addResponse->assertRedirect(route('cashier.nicu.payment.form', $this->reservation));

        $extra = NicuExtraService::where('incubator_reservation_id', $this->reservation->id)->first();
        $this->assertNotNull($extra);
        $this->assertEquals(50000.0, (float) $extra->total_amount);

        // Verify NicuBillingService reflects extra service in grand_total & net_balance
        $billing = app(NicuBillingService::class)->calculateStayDetails($this->reservation->fresh());
        $this->assertEquals(50000.0, (float) $billing['total_extra_services_cost']);
        $this->assertEquals(150000.0, (float) $billing['grand_total']);
        $this->assertEquals(150000.0, (float) $billing['net_balance']);

        // 2. Update price and quantity
        $updateResponse = $this->actingAs($this->cashier)
            ->patch(route('cashier.nicu.extra-services.update', [$this->reservation, $extra]), [
                'price' => 30000,
                'quantity' => 3,
            ]);

        $updateResponse->assertRedirect(route('cashier.nicu.payment.form', $this->reservation));
        $extra->refresh();
        $this->assertEquals(90000.0, (float) $extra->total_amount);

        // 3. Remove service
        $deleteResponse = $this->actingAs($this->cashier)
            ->delete(route('cashier.nicu.extra-services.remove', [$this->reservation, $extra]));

        $deleteResponse->assertRedirect(route('cashier.nicu.payment.form', $this->reservation));
        $this->assertDatabaseMissing('nicu_extra_services', ['id' => $extra->id]);

        $billingAfter = app(NicuBillingService::class)->calculateStayDetails($this->reservation->fresh());
        $this->assertEquals(0.0, (float) $billingAfter['total_extra_services_cost']);
        $this->assertEquals(100000.0, (float) $billingAfter['net_balance']);
    }

    public function test_custom_nicu_extra_service_auto_saves_to_nicu_catalog_and_renders_on_prints(): void
    {
        $response = $this->actingAs($this->cashier)
            ->post(route('cashier.nicu.extra-services.add', $this->reservation), [
                'service_name' => 'خدمة رعاية مخصصة جديدة للخديج',
                'service_price' => 18000,
                'service_quantity' => 1,
                'service_category' => 'care',
                'service_notes' => 'حسب طلب الطبيب',
            ]);

        $response->assertRedirect(route('cashier.nicu.payment.form', $this->reservation));

        $this->assertDatabaseHas('hospital_extra_services', [
            'name' => 'خدمة رعاية مخصصة جديدة للخديج',
            'department' => 'nicu',
            'category' => 'care',
            'default_price' => 18000.00,
        ]);

        // Thermal itemized print includes the added service
        $this->actingAs($this->cashier)
            ->get(route('cashier.nicu.itemized-receipt.print', $this->reservation))
            ->assertOk()
            ->assertSee('خدمة رعاية مخصصة جديدة للخديج')
            ->assertSee('18,000');

        // A4 statement includes the added service
        $this->actingAs($this->admin)
            ->get(route('nicu.statement', $this->reservation))
            ->assertOk()
            ->assertSee('خدمة رعاية مخصصة جديدة للخديج')
            ->assertSee('18,000');
    }

    public function test_insured_nicu_extra_services_are_billed_100_percent_cash_to_patient(): void
    {
        $this->reservation->update([
            'insurance_type' => 'hi',
            'policy_category' => 'E',
            'gestational_age' => '32 أسبوع',
        ]);

        $billingBefore = app(NicuBillingService::class)->calculateStayDetails($this->reservation->fresh());

        $this->actingAs($this->cashier)
            ->post(route('cashier.nicu.extra-services.add', $this->reservation), [
                'service_name' => 'مبيت أم / مرافق طفل',
                'service_price' => 25000,
                'service_quantity' => 1,
                'service_category' => 'accommodation',
            ]);

        $billingAfter = app(NicuBillingService::class)->calculateStayDetails($this->reservation->fresh());

        $this->assertEquals(
            (float) $billingBefore['insurance_grand_total'],
            (float) $billingAfter['insurance_grand_total']
        );
        $this->assertEquals(
            (float) $billingBefore['patient_grand_total'] + 25000.0,
            (float) $billingAfter['patient_grand_total']
        );
    }

    public function test_admin_can_manage_hospital_miscellaneous_services_and_unauthorized_is_blocked(): void
    {
        // Unauthorized user gets 403
        $this->actingAs($this->unauthorizedUser)
            ->get(route('admin.extra-services.index'))
            ->assertForbidden();

        // Admin views index
        $this->actingAs($this->admin)
            ->get(route('admin.extra-services.index'))
            ->assertOk()
            ->assertSee('الخدمات المتفرقة للمستشفى');

        // Admin creates a new service
        $this->actingAs($this->admin)
            ->post(route('admin.extra-services.store'), [
                'name' => 'خدمة فحص سمع متقدم',
                'code' => 'NICU-HEAR-01',
                'department' => 'nicu',
                'category' => 'clinical',
                'default_price' => 22000,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $srv = HospitalExtraService::where('name', 'خدمة فحص سمع متقدم')->first();
        $this->assertNotNull($srv);
        $this->assertEquals('nicu', $srv->department);

        // Admin quick-updates price via AJAX
        $this->actingAs($this->admin)
            ->patchJson(route('admin.extra-services.quick-price', $srv), [
                'default_price' => 27500,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'default_price' => 27500]);

        $this->assertEquals(27500.0, (float) $srv->fresh()->default_price);

        // Admin toggles status
        $this->actingAs($this->admin)
            ->post(route('admin.extra-services.toggle', $srv))
            ->assertRedirect();
        $this->assertFalse($srv->fresh()->is_active);

        // Admin full updates
        $this->actingAs($this->admin)
            ->put(route('admin.extra-services.update', $srv), [
                'name' => 'فحص سمع محدث',
                'code' => 'NICU-HEAR-02',
                'department' => 'all',
                'category' => 'clinical',
                'default_price' => 30000,
                'is_active' => 1,
            ])
            ->assertRedirect();
        $srv->refresh();
        $this->assertEquals('فحص سمع محدث', $srv->name);
        $this->assertEquals('all', $srv->department);
        $this->assertTrue($srv->is_active);

        // Admin deletes service
        $this->actingAs($this->admin)
            ->delete(route('admin.extra-services.destroy', $srv))
            ->assertRedirect();
        $this->assertDatabaseMissing('hospital_extra_services', ['id' => $srv->id]);
    }
}
