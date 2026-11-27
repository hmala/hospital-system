<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NicuSidebarAndReceptionistPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'create incubator reservations',
            'view incubator reservations',
            'manage incubator reservations',
            'view nicu delivery radar',
            'manage nicu delivery observations',
            'view nicu archive',
            'manage nicu store',
            'view medicine groups',
            'manage nicu services',
            'manage nicu care plans',
            'manage nicu prescriptions',
        ] as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }
    }

    public function test_receptionist_with_only_create_incubator_reservations_sees_only_booking_tab_and_cannot_bypass_other_nicu_routes(): void
    {
        $role = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $role->syncPermissions(['create incubator reservations']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('incubator-reservations.create'));
        $response->assertOk();

        // يظهر تبويب الحجز فقط في القائمة الجانبية
        $response->assertSee('حجز ودخول خديج جديد');
        $response->assertDontSee('لوحة الحاضنات اللحظية');
        $response->assertDontSee('رادار صالات العمليات والولادة');
        $response->assertDontSee('معاينات الوليد الحية');
        $response->assertDontSee('سجل وإضبارات الخدج');
        $response->assertDontSee('السجلات وأرشفة المرضى');
        $response->assertDontSee('مخزن وعُهدة الخدج');

        // منع الوصول المباشر عبر الرابط بدون صلاحية صريحة
        $this->actingAs($user)->get(route('nicu.index'))->assertForbidden();
        $this->actingAs($user)->get(route('nicu.delivery-radar.index'))->assertForbidden();
        $this->actingAs($user)->get(route('nicu.reservations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('nicu.reservations.index', ['view' => 'archived', 'status' => 'discharged']))->assertForbidden();
    }

    public function test_booking_with_print_redirects_directly_to_print_and_does_not_expose_child_file_to_unauthorized_user(): void
    {
        $role = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $role->syncPermissions(['create incubator reservations']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $patientUser = User::factory()->create();
        $patient = \App\Models\Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
        ]);
        $incubator = \App\Models\Incubator::create([
            'incubator_number' => 'INC-RECEPT-1',
            'incubator_type' => 'normal',
            'status' => 'available',
            'daily_fee' => 100000,
        ]);

        // 1. الحجز مع طباعة التعهد: تحويل مباشر لصفحة الطباعة
        $response = $this->actingAs($user)->post(route('incubator-reservations.store'), [
            'patient_id' => $patient->id,
            'incubator_id' => $incubator->id,
            'admission_date' => now()->toDateString(),
            'admission_time' => '10:00',
            'print_consent_after_save' => '1',
        ]);

        $reservation = \App\Models\IncubatorReservation::latest('id')->first();
        $this->assertNotNull($reservation);

        $response->assertRedirect(route('nicu.print.admission-consent', ['reservation' => $reservation, 'autoprint' => 1]));

        // 2. صفحة الطباعة تظهر خيار حجز خديج جديد ولا تظهر رابط ملف الطفل للمستعلم الذي لا يملك صلاحية عرض ملف الخدج
        $printResponse = $this->actingAs($user)->get(route('nicu.print.admission-consent', ['reservation' => $reservation, 'autoprint' => 1]));
        $printResponse->assertOk();
        $printResponse->assertSee('حجز خديج جديد');
        $printResponse->assertDontSee('العودة لملف الطفل');

        // 3. منع المستعلم من فتح ملف الطفل السريري (403)
        $this->actingAs($user)->get(route('nicu.show', $reservation))->assertForbidden();

        // 4. الحجز بدون طباعة التعهد: يعود لشاشة الحجز ولا يذهب لملف الطفل السريري
        $incubator2 = \App\Models\Incubator::create([
            'incubator_number' => 'INC-RECEPT-2',
            'incubator_type' => 'normal',
            'status' => 'available',
            'daily_fee' => 100000,
        ]);
        $patientUser2 = User::factory()->create();
        $patient2 = \App\Models\Patient::create([
            'user_id' => $patientUser2->id,
            'gender' => 'male',
        ]);

        $responseNoPrint = $this->actingAs($user)->post(route('incubator-reservations.store'), [
            'patient_id' => $patient2->id,
            'incubator_id' => $incubator2->id,
            'admission_date' => now()->toDateString(),
            'admission_time' => '11:00',
        ]);
        $responseNoPrint->assertRedirect(route('incubator-reservations.create'));
    }

    public function test_receptionist_with_view_incubator_reservations_sees_board_and_reservations_tabs(): void
    {
        $role = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $role->syncPermissions(['create incubator reservations', 'view incubator reservations']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('incubator-reservations.create'));
        $response->assertOk();

        $response->assertSee('حجز ودخول خديج جديد');
        $response->assertSee('لوحة الحاضنات اللحظية');
        $response->assertSee('سجل وإضبارات الخدج');
        $response->assertSee('السجلات وأرشفة المرضى');
        $response->assertDontSee('رادار صالات العمليات والولادة');

        $this->actingAs($user)->get(route('nicu.index'))->assertOk();
        $this->actingAs($user)->get(route('nicu.reservations.index'))->assertOk();
        $this->actingAs($user)->get(route('nicu.delivery-radar.index'))->assertForbidden();
    }
}
