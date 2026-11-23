<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserEditModernViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'anesthesia', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);
    }

    public function test_admin_can_view_user_edit_screen_with_modern_layout_and_categories(): void
    {
        $admin = User::factory()->create(['email' => 'admin_user_edit@hospital.com']);
        $admin->assignRole('admin');

        $location = Location::create([
            'name' => 'مخزن الطابق الثاني',
            'code' => 'FL-02',
            'type' => 'sub',
            'is_active' => true,
        ]);

        $targetUser = User::factory()->create([
            'name' => 'علي حسن موظف الاستقبال',
            'email' => 'ali_reception@hospital.com',
            'location_id' => $location->id,
        ]);
        $targetUser->assignRole('receptionist');

        $response = $this->actingAs($admin)->get(route('users.edit', $targetUser));

        $response->assertStatus(200);
        $response->assertSee('تعديل ملف المستخدم');
        $response->assertSee('علي حسن موظف الاستقبال');
        $response->assertSee('ali_reception@hospital.com');
        $response->assertSee('#ID: '.$targetUser->id);
        $response->assertSee('مخزن الطابق الثاني');
        $response->assertSee('الكوادر الطبية والجراحية');
        $response->assertSee('الاستقبال والتمريض والطوارئ');
        $response->assertSee('حفظ وتحديث بيانات المستخدم');
    }

    public function test_admin_can_update_user_details_and_roles_successfully(): void
    {
        $admin = User::factory()->create(['email' => 'admin_user_update@hospital.com']);
        $admin->assignRole('admin');

        $targetUser = User::factory()->create([
            'name' => 'محمد كادر تمريضي',
            'email' => 'mohammed_nurse@hospital.com',
        ]);
        $targetUser->assignRole('nurse');

        $response = $this->actingAs($admin)->put(route('users.update', $targetUser), [
            'name' => 'محمد أحمد مشرف التمريض',
            'email' => 'mohammed_headnurse@hospital.com',
            'roles' => ['nurse', 'anesthesia'],
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'تم تحديث المستخدم بنجاح');

        $targetUser->refresh();
        $this->assertEquals('محمد أحمد مشرف التمريض', $targetUser->name);
        $this->assertEquals('mohammed_headnurse@hospital.com', $targetUser->email);
        $this->assertTrue($targetUser->hasRole('nurse'));
        $this->assertTrue($targetUser->hasRole('anesthesia'));
    }
}
