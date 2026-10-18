<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Hospital;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ThamanLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected User $thamanUser;

    protected function setUp(): void
    {
        parent::setUp();

        Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'email' => 'info@alkafaat.test',
            'owner_name' => 'المدير العام',
            'phone' => '0770000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-' . uniqid(),
        ]);

        $role = Role::firstOrCreate(['name' => 'thaman', 'guard_name' => 'web']);
        $this->thamanUser = User::factory()->create(['name' => 'موظف شعبة الضمان']);
        $this->thamanUser->assignRole($role);
    }

    public function test_login_redirect_returns_patients_index_for_thaman_user(): void
    {
        $loginController = new \App\Http\Controllers\Auth\LoginController();
        $this->actingAs($this->thamanUser);

        $this->assertEquals(route('patients.index'), $loginController->redirectTo());
    }

    public function test_dashboard_redirects_thaman_user_to_patients_index(): void
    {
        $response = $this->actingAs($this->thamanUser)->get('/dashboard');

        $response->assertRedirect(route('patients.index'));
    }

    public function test_root_route_redirects_thaman_user_to_patients_index(): void
    {
        $response = $this->actingAs($this->thamanUser)->get('/');

        $response->assertRedirect(route('patients.index'));
    }

    public function test_sidebar_hides_dashboard_and_shows_patients_for_thaman_user(): void
    {
        // إعطاء صلاحية view patients لعرض صفحة المرضى
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view patients', 'guard_name' => 'web']);
        $this->thamanUser->givePermissionTo('view patients');

        $response = $this->actingAs($this->thamanUser)->get(route('patients.index'));

        $response->assertStatus(200);
        // التحقق من ظهور رابط سجل وملفات المرضى في الشريط الجانبي
        $response->assertSee('سجل وملفات المرضى');
        // التحقق من عدم وجود رابط الرئيسية في شريط التنقل
        $response->assertDontSee('<i class="fas fa-home me-2"></i>الرئيسية', false);
    }

    public function test_patients_index_displays_urgent_alert_banner_above_table(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view patients', 'guard_name' => 'web']);
        $this->thamanUser->givePermissionTo('view patients');

        $patientUser = User::factory()->create(['name' => 'جاسم محمد عاجل']);
        $patient = \App\Models\Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => 1,
            'insurance_type' => 'hi',
        ]);

        // إرسال إشعار نقص بيانات
        $this->thamanUser->notify(new \App\Notifications\InsuranceIncompleteAlertNotification(
            $patient,
            ['رقم الهاتف', 'تاريخ الميلاد'],
            $this->thamanUser
        ));

        $response = $this->actingAs($this->thamanUser)->get(route('patients.index'));

        $response->assertStatus(200);
        // التحقق من ظهور بانر التنبيه العاجل أعلى الجدول
        $response->assertSee('تنبيه عاجل من الاستعلامات');
        $response->assertSee('مرضى بالاستعلامات بانتظار إكمال النواقص لاعتماد حجز الضمان الصحي');
        $response->assertSee('جاسم محمد عاجل');
        $response->assertSee('إكمال النواقص فوراً');
    }

    public function test_quick_update_automatically_marks_incomplete_alert_as_read(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'edit patients', 'guard_name' => 'web']);
        $this->thamanUser->givePermissionTo('edit patients');

        $patientUser = User::factory()->create(['name' => 'سندس علي']);
        $patient = \App\Models\Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => 1,
            'insurance_type' => 'hi',
        ]);

        $this->thamanUser->notify(new \App\Notifications\InsuranceIncompleteAlertNotification(
            $patient,
            ['رقم الهاتف'],
            $this->thamanUser
        ));

        $this->assertEquals(1, $this->thamanUser->unreadNotifications()->count());

        // تنفيذ الإكمال السريع للمريض
        $response = $this->actingAs($this->thamanUser)->patchJson(route('patients.quick-update', $patient->id), [
            'phone' => '07701234567',
            'gender' => 'female',
            'national_id' => '1234567890',
            'insurance_number' => 'HI-999',
            'date_of_birth' => '1995-01-01',
        ]);

        $response->assertStatus(200);
        // تمييز الإشعار كمقروء تلقائياً
        $this->assertEquals(0, $this->thamanUser->fresh()->unreadNotifications()->count());
    }

    public function test_insurance_alerts_poll_endpoint_returns_active_unread_alerts(): void
    {
        $patientUser = User::factory()->create(['name' => 'مريض الاستعلامات الفوري']);
        $patient = \App\Models\Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => 1,
            'insurance_type' => 'hi',
            'insurance_card_no' => null,
            'insurance_number' => null,
            'national_id' => null,
        ]);

        $this->thamanUser->notify(new \App\Notifications\InsuranceIncompleteAlertNotification(
            $patient,
            ['رقم بطاقة الضمان', 'البطاقة الموحدة'],
            $this->thamanUser
        ));

        $response = $this->actingAs($this->thamanUser)->getJson(route('patients.insurance-alerts-poll'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);
        $response->assertJsonFragment([
            'patient_name' => 'مريض الاستعلامات الفوري',
        ]);
    }
}
