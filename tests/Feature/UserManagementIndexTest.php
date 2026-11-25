<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Hospital::firstOrCreate(
            ['name' => 'مستشفى الكفاءات الأهلي'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'pharmacy_staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
    }

    public function test_admin_can_view_modern_users_index_with_stats_and_instant_filters(): void
    {
        $admin = User::factory()->create(['name' => 'مدير النظام', 'email' => 'admin@hospital.com']);
        $admin->assignRole('admin');

        $pharmacyUser = User::factory()->create([
            'name' => 'أحمد صيدلي',
            'email' => 'ahmed_pharmacy@hospital.com',
            'phone' => '07712345678',
            'is_active' => true,
        ]);
        $pharmacyUser->assignRole('pharmacy_staff');

        $nurseUser = User::factory()->create([
            'name' => 'زينب ممرضة',
            'email' => 'zainab_nurse@hospital.com',
            'is_active' => false,
        ]);
        $nurseUser->assignRole('nurse');

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('إدارة حسابات الكادر والمستخدمين');
        $response->assertSee('إجمالي الحسابات المسجلة');
        $response->assertSee('الحسابات النشطة والمفعلة');
        $response->assertSee('الحسابات المعطلة مؤقتاً');
        $response->assertSee('أحمد صيدلي');
        $response->assertSee('زينب ممرضة');
        $response->assertSee('id="search"', false);
        $response->assertSee('onchange="this.form.submit()"', false);
    }

    public function test_filter_by_role_returns_matching_users_only(): void
    {
        $admin = User::factory()->create(['email' => 'admin@hospital.com']);
        $admin->assignRole('admin');

        $pharmacyUser = User::factory()->create(['name' => 'محمد كادر صيدلية']);
        $pharmacyUser->assignRole('pharmacy_staff');

        $doctorUser = User::factory()->create(['name' => 'حيدر طبيب جراح']);
        $doctorUser->assignRole('doctor');

        $response = $this->actingAs($admin)->get(route('users.index', ['role' => 'pharmacy_staff']));

        $response->assertStatus(200);
        $response->assertSee('محمد كادر صيدلية');
        $response->assertDontSee('حيدر طبيب جراح');
    }

    public function test_filter_by_search_query_returns_matching_users(): void
    {
        $admin = User::factory()->create(['email' => 'admin@hospital.com']);
        $admin->assignRole('admin');

        $user1 = User::factory()->create(['name' => 'سارة كريم علي', 'email' => 'sara@hospital.com']);
        $user1->assignRole('nurse');

        $user2 = User::factory()->create(['name' => 'عمر خالد حسن', 'email' => 'omar@hospital.com']);
        $user2->assignRole('nurse');

        $response = $this->actingAs($admin)->get(route('users.index', ['search' => 'سارة']));

        $response->assertStatus(200);
        $response->assertSee('سارة كريم علي');
        $response->assertDontSee('عمر خالد حسن');
    }

    public function test_filter_by_location_returns_floor_users(): void
    {
        $admin = User::factory()->create(['email' => 'admin@hospital.com']);
        $admin->assignRole('admin');

        $locationA = Location::create(['name' => 'ردهة الطابق الرابع', 'type' => 'sub']);
        $locationB = Location::create(['name' => 'مخزن العمليات الكبرى', 'type' => 'sub']);

        $userA = User::factory()->create(['name' => 'مستخدم طابق 4', 'location_id' => $locationA->id]);
        $userA->assignRole('nurse');

        $userB = User::factory()->create(['name' => 'مستخدم العمليات', 'location_id' => $locationB->id]);
        $userB->assignRole('nurse');

        $response = $this->actingAs($admin)->get(route('users.index', ['location_id' => $locationA->id]));

        $response->assertStatus(200);
        $response->assertSee('مستخدم طابق 4');
        $response->assertDontSee('مستخدم العمليات');
    }
}
