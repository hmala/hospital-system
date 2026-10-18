<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LabTest;
use App\Models\RadiologyType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LabTestsExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perms = ['view lab tests', 'manage radiology types'];
        foreach ($perms as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $adminRole->givePermissionTo($perm);
        }
    }

    public function test_admin_can_export_lab_tests_to_excel()
    {
        $admin = User::factory()->create(['email' => 'admin_lab_exp@hospital.com']);
        $admin->assignRole('admin');

        LabTest::create([
            'name' => 'CBC Test',
            'main_category' => 'المختبر',
            'subcategory' => 'Hematology',
            'price' => 15000,
            'hi_price' => 8500,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('lab-tests.export'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-type') ?? '', 'spreadsheetml') ||
            str_contains($response->headers->get('content-disposition') ?? '', '.xlsx')
        );
    }

    public function test_admin_can_export_radiology_types_to_excel()
    {
        $admin = User::factory()->create(['email' => 'admin_rad_exp@hospital.com']);
        $admin->assignRole('admin');

        RadiologyType::create([
            'name' => 'CT Brain',
            'code' => 'CT_BRAIN',
            'main_category' => 'مفراس',
            'base_price' => 125000,
            'hi_price' => 40000,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('radiology.types.export'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-type') ?? '', 'spreadsheetml') ||
            str_contains($response->headers->get('content-disposition') ?? '', '.xlsx')
        );
    }

    public function test_unauthenticated_user_cannot_export_lab_tests()
    {
        $response = $this->get(route('lab-tests.export'));
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_user_cannot_export_radiology_types()
    {
        $response = $this->get(route('radiology.types.export'));
        $response->assertRedirect('/login');
    }
}
