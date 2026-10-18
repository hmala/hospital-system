<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LabTest;
use App\Models\RadiologyType;
use App\Imports\LabTestsImport;
use App\Imports\RadiologyTypesImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LabAndRadiologyImportTest extends TestCase
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
        $perms = ['view lab tests', 'edit lab tests', 'manage radiology types'];
        foreach ($perms as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $adminRole->givePermissionTo($perm);
        }
    }

    public function test_lab_tests_import_updates_prices_and_status()
    {
        $test = LabTest::create([
            'name' => 'Lipid Profile',
            'code' => 'LIPID',
            'main_category' => 'المختبر',
            'subcategory' => 'Biochemistry',
            'price' => 10000,
            'hi_price' => 4000,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $import = new LabTestsImport();
        $rows = collect([
            // Header row
            ['المعرف (# ID)', 'اسم الفحص', 'الرمز الطبي', 'القسم', 'التصنيف الفرعي', 'سعر الكاش', 'سعر الضمان', 'حالة الضمان', 'فحوصات', 'حالة التفعيل'],
            // Data row
            [$test->id, 'Lipid Profile', 'LIPID', 'المختبر', 'Biochemistry', '25,000 د.ع', '12,500', 'مشمول', '-', 'نشط'],
        ]);

        $import->collection($rows);

        $this->assertEquals(1, $import->updatedCount);

        $test->refresh();
        $this->assertEquals(25000, (float) $test->price);
        $this->assertEquals(12500, (float) $test->hi_price);
        $this->assertTrue((bool) $test->is_hi_active);
    }

    public function test_radiology_types_import_updates_prices_and_contrast()
    {
        $rad = RadiologyType::create([
            'name' => 'MRI Brain',
            'code' => 'MRI_BRAIN',
            'main_category' => 'رنين',
            'base_price' => 120000,
            'hi_price' => 50000,
            'is_hi_active' => true,
            'requires_contrast' => false,
            'is_active' => true,
        ]);

        $import = new RadiologyTypesImport();
        $rows = collect([
            // Header row
            ['المعرف (# ID)', 'اسم الفحص', 'القسم', 'الرمز الطبي', 'سعر الكاش', 'سعر الضمان', 'حالة الضمان', 'صبغة', 'حالة التفعيل'],
            // Data row with updated name
            [$rad->id, 'MRI Brain with Spectroscopy', 'رنين', 'MRI_BRAIN', '160,000', '70,000', 'مشمول بالضمان', 'نعم', 'نشط'],
            // New data row not in DB
            ['9999', 'CT Abdomen New', 'مفراس', 'CT_ABD_NEW', '85,000', '35,000', 'مشمول بالضمان', 'لا', 'نشط'],
        ]);

        $import->collection($rows);

        $this->assertEquals(1, $import->updatedCount);
        $this->assertEquals(1, $import->createdCount);

        $rad->refresh();
        $this->assertEquals('MRI Brain with Spectroscopy', $rad->name);
        $this->assertEquals(160000, (float) $rad->base_price);
        $this->assertEquals(70000, (float) $rad->hi_price);
        $this->assertTrue((bool) $rad->requires_contrast);

        $created = RadiologyType::where('name', 'CT Abdomen New')->first();
        $this->assertNotNull($created);
        $this->assertEquals(85000, (float) $created->base_price);
        $this->assertEquals(35000, (float) $created->hi_price);
    }

    public function test_admin_can_upload_lab_tests_csv_import()
    {
        $admin = User::factory()->create(['email' => 'admin_lab_imp@hospital.com']);
        $admin->assignRole('admin');

        $test = LabTest::create([
            'name' => 'Glucose Fasting',
            'code' => 'FBS',
            'main_category' => 'المختبر',
            'subcategory' => 'Biochemistry',
            'price' => 5000,
            'hi_price' => 2000,
            'is_hi_active' => true,
            'is_active' => true,
        ]);

        $csvContent = "ID,Name,Code,Category,Subcategory,Price,HI_Price,HI_Status,Subtests,Active\n";
        $csvContent .= "{$test->id},Glucose Fasting,FBS,المختبر,Biochemistry,8000,3000,مشمول,-,نشط\n";

        $file = UploadedFile::fake()->createWithContent('lab_tests.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('lab-tests.import'), [
            'excel_file' => $file,
        ]);

        $response->assertSessionHas('success');
        $test->refresh();
        $this->assertEquals(8000, (float) $test->price);
        $this->assertEquals(3000, (float) $test->hi_price);
    }

    public function test_admin_can_upload_radiology_types_csv_import()
    {
        $admin = User::factory()->create(['email' => 'admin_rad_imp@hospital.com']);
        $admin->assignRole('admin');

        $rad = RadiologyType::create([
            'name' => 'Chest X-Ray',
            'code' => 'CXR',
            'main_category' => 'أشعة',
            'base_price' => 15000,
            'hi_price' => 5000,
            'is_hi_active' => true,
            'requires_contrast' => false,
            'is_active' => true,
        ]);

        $csvContent = "ID,Name,Category,Code,BasePrice,HIPrice,HIStatus,Contrast,Active\n";
        $csvContent .= "{$rad->id},Chest X-Ray,أشعة,CXR,20000,8000,مشمول,لا,نشط\n";

        $file = UploadedFile::fake()->createWithContent('radiology.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('radiology.types.import'), [
            'excel_file' => $file,
        ]);

        $response->assertSessionHas('success');
        $rad->refresh();
        $this->assertEquals(20000, (float) $rad->base_price);
        $this->assertEquals(8000, (float) $rad->hi_price);
    }

    public function test_radiology_types_import_with_replace_all_deletes_stale_records()
    {
        $admin = User::factory()->create(['email' => 'admin_rad_replace@hospital.com']);
        $admin->assignRole('admin');

        $keep = RadiologyType::create([
            'name' => 'Brain MRI',
            'code' => 'RAD-MRI-01',
            'main_category' => 'الرنين',
            'subcategory' => 'الرنين',
            'base_price' => 200000,
            'is_active' => true,
        ]);

        $stale = RadiologyType::create([
            'name' => 'Old Unused Arabic Exam',
            'code' => 'RAD-OLD-99',
            'main_category' => 'أشعة',
            'subcategory' => 'أشعة',
            'base_price' => 10000,
            'is_active' => true,
        ]);

        $csvContent = "ID,Name,Category,Code,BasePrice,HIPrice,HIStatus,Contrast,Active\n";
        $csvContent .= "{$keep->id},Brain MRI,رنين,RAD-MRI-01,220000,100000,مشمول,لا,نشط\n";

        $file = UploadedFile::fake()->createWithContent('radiology_replace.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('radiology.types.import'), [
            'excel_file' => $file,
            'replace_all' => '1',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('radiology_types', ['id' => $keep->id, 'base_price' => 220000]);
        $this->assertDatabaseMissing('radiology_types', ['id' => $stale->id]);
    }

    public function test_unauthenticated_user_cannot_import()
    {
        $file = UploadedFile::fake()->create('tests.xlsx', 100);

        $response1 = $this->post(route('lab-tests.import'), ['excel_file' => $file]);
        $response1->assertRedirect('/login');

        $response2 = $this->post(route('radiology.types.import'), ['excel_file' => $file]);
        $response2->assertRedirect('/login');
    }
}
