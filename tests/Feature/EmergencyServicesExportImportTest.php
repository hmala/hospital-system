<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EmergencyService;
use App\Imports\EmergencyServicesImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmergencyServicesExportImportTest extends TestCase
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
        $perm = Permission::firstOrCreate(['name' => 'manage emergency services', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($perm);
    }

    public function test_admin_can_export_emergency_services_to_excel()
    {
        $admin = User::factory()->create(['email' => 'admin_emg_exp@hospital.com']);
        $admin->assignRole('admin');

        EmergencyService::create([
            'name' => 'خياطة جرح طارئ',
            'category' => 'إجراءات جراحية صغرى',
            'price' => 25000,
            'hi_price' => 10000,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('emergency-services.export'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-type') ?? '', 'spreadsheetml') ||
            str_contains($response->headers->get('content-disposition') ?? '', '.xlsx')
        );
    }

    public function test_emergency_services_import_updates_prices_and_status()
    {
        $service = EmergencyService::create([
            'name' => 'تخطيط قلب طوارئ',
            'category' => 'فحوصات',
            'price' => 15000,
            'hi_price' => 5000,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $import = new EmergencyServicesImport();
        $rows = collect([
            ['المعرف (# ID)', 'اسم خدمة الطوارئ', 'القسم / التصنيف', 'سعر الكاش (د.ع)', 'سعر الضمان الصحي (د.ع)', 'حالة الضمان الصحي', 'حالة التفعيل'],
            [$service->id, 'تخطيط قلب طوارئ', 'فحوصات', '20,000 د.ع', '8,000 د.ع', 'مشمول', 'نشط'],
        ]);

        $import->collection($rows);

        $this->assertEquals(1, $import->updatedCount);

        $service->refresh();
        $this->assertEquals(20000, (float) $service->price);
        $this->assertEquals(8000, (float) $service->hi_price);
        $this->assertTrue((bool) $service->is_hi_active);
    }

    public function test_admin_can_upload_emergency_services_csv_import()
    {
        $admin = User::factory()->create(['email' => 'admin_emg_imp@hospital.com']);
        $admin->assignRole('admin');

        $service = EmergencyService::create([
            'name' => 'إنعاش قلبي رئوي CPR',
            'category' => 'عناية فائقة',
            'price' => 50000,
            'hi_price' => 20000,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $csvContent = "ID,Name,Category,Price,HI_Price,HI_Status,Active\n";
        $csvContent .= "{$service->id},إنعاش قلبي رئوي CPR,عناية فائقة,60000,25000,مشمول,نشط\n";

        $file = UploadedFile::fake()->createWithContent('emergency_services.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('emergency-services.import'), [
            'excel_file' => $file,
        ]);

        $response->assertSessionHas('success');
        $service->refresh();
        $this->assertEquals(60000, (float) $service->price);
        $this->assertEquals(25000, (float) $service->hi_price);
    }

    public function test_unauthenticated_user_cannot_export_or_import_emergency_services()
    {
        $response1 = $this->get(route('emergency-services.export'));
        $response1->assertRedirect('/login');

        $file = UploadedFile::fake()->create('test.xlsx', 100);
        $response2 = $this->post(route('emergency-services.import'), ['excel_file' => $file]);
        $response2->assertRedirect('/login');
    }
}
