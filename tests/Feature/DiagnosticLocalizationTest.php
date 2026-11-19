<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_laboratory_workstation_renders_in_both_locales(): void
    {
        $admin = User::factory()->create(['name' => 'Lab Admin', 'email' => 'lab_admin@hospital.com']);
        $admin->assignRole('admin');

        // 1. English Locale
        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('lab.index'));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('Laboratory Requests &amp; Tests', false);
        $responseEn->assertSee('Live');
        $responseEn->assertSee('Surgery Lab Tests');
        $responseEn->assertSee('Tests &amp; Prices Catalog', false);

        // 2. Arabic Locale
        $responseAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('lab.index'));

        $responseAr->assertStatus(200);
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('طلبات وفحوصات المختبر');
        $responseAr->assertSee('مباشر');
        $responseAr->assertSee('تحاليل العمليات');
        $responseAr->assertSee('دليل وأسعار التحاليل');
    }

    public function test_radiology_staff_workstation_renders_in_both_locales(): void
    {
        $admin = User::factory()->create(['name' => 'Rad Staff Admin', 'email' => 'rad_admin@hospital.com']);
        $admin->assignRole('admin');

        // 1. English Locale
        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('radiology-staff.index'));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('Radiology &amp; Medical Imaging Department', false);
        $responseEn->assertSee('General Imaging Center');
        $responseEn->assertSee('Call Next');

        // 2. Arabic Locale
        $responseAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('radiology-staff.index'));

        $responseAr->assertStatus(200);
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('قسم الأشعة والتصوير الطبي');
        $responseAr->assertSee('المركز العام');
        $responseAr->assertSee('استدعاء التالي');
    }

    public function test_radiology_types_directory_renders_in_both_locales(): void
    {
        $admin = User::factory()->create(['name' => 'Rad Types Admin', 'email' => 'rad_types@hospital.com']);
        $admin->assignRole('admin');

        // English
        $typesEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('radiology.types.index'));

        $typesEn->assertStatus(200);
        $typesEn->assertSee('dir="ltr"', false);
        $typesEn->assertSee('Radiology Types &amp; Pricing Directory', false);
        $typesEn->assertSee('Cash Price (IQD)');
        $typesEn->assertSee('Covered by Health Insurance (HI)');

        // Arabic
        $typesAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('radiology.types.index'));

        $typesAr->assertStatus(200);
        $typesAr->assertSee('dir="rtl"', false);
        $typesAr->assertSee('دليل وتسعير أنواع الأشعة والتصوير');
        $typesAr->assertSee('سعر الكاش (د.ع)');
        $typesAr->assertSee('مشمولة بالضمان (HI)');
    }

    public function test_lab_tests_directory_renders_in_both_locales(): void
    {
        $admin = User::factory()->create(['name' => 'Lab Tests Admin', 'email' => 'lab_tests_admin@hospital.com']);
        $admin->assignRole('admin');

        // English
        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('lab-tests.index'));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('Laboratory Tests Directory &amp; Management', false);
        $responseEn->assertSee('Total Studies');
        $responseEn->assertSee('General Status');
        $responseEn->assertSee('Health Insurance');

        // Arabic
        $responseAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('lab-tests.index'));

        $responseAr->assertStatus(200);
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('إدارة ودليل الفحوصات المختبرية');
        $responseAr->assertSee('إجمالي الفحوصات');
        $responseAr->assertSee('الحالة العامة');
        $responseAr->assertSee('الضمان الصحي');
    }

    public function test_surgery_radiology_tests_renders_in_both_locales(): void
    {
        $admin = User::factory()->create(['name' => 'Surgery Rad Admin', 'email' => 'surg_rad_admin@hospital.com']);
        $admin->assignRole('admin');

        // 1. Index in English
        $indexEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('staff.surgery-radiology-tests.index'));

        $indexEn->assertStatus(200);
        $indexEn->assertSee('dir="ltr"', false);
        $indexEn->assertSee('Surgery Radiology Orders');
        $indexEn->assertSee('Surgeries Requiring Radiology Selection');
        $indexEn->assertSee('Search &amp; Filter', false);

        // Index in Arabic
        $indexAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('staff.surgery-radiology-tests.index'));

        $indexAr->assertStatus(200);
        $indexAr->assertSee('dir="rtl"', false);
        $indexAr->assertSee('طلبات الأشعة للعمليات الجراحية');
        $indexAr->assertSee('العمليات التي تحتاج اختيار أشعة');
        $indexAr->assertSee('البحث والتصفية');

        // 2. Selection in English
        $selectionEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('staff.surgery-radiology-tests.selection'));

        $selectionEn->assertStatus(200);
        $selectionEn->assertSee('dir="ltr"', false);
        $selectionEn->assertSee('Surgeries Requiring Radiology Selection');
        $selectionEn->assertSee('Back to Radiology Orders');

        // Selection in Arabic
        $selectionAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('staff.surgery-radiology-tests.selection'));

        $selectionAr->assertStatus(200);
        $selectionAr->assertSee('dir="rtl"', false);
        $selectionAr->assertSee('العمليات التي تحتاج اختيار أشعة');
        $selectionAr->assertSee('عودة إلى طلبات الأشعة');
    }

    public function test_surgery_lab_tests_renders_in_both_locales(): void
    {
        $admin = User::factory()->create(['name' => 'Surgery Lab Admin', 'email' => 'surg_lab_admin@hospital.com']);
        $admin->assignRole('admin');

        // 1. Index in English
        $indexEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('staff.surgery-lab-tests.index'));

        $indexEn->assertStatus(200);
        $indexEn->assertSee('dir="ltr"', false);
        $indexEn->assertSee('Surgery Laboratory Tests &amp; Orders', false);
        $indexEn->assertSee('Go to General Laboratory');
        $indexEn->assertSee('Live');

        // Index in Arabic
        $indexAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('staff.surgery-lab-tests.index'));

        $indexAr->assertStatus(200);
        $indexAr->assertSee('dir="rtl"', false);
        $indexAr->assertSee('تحاليل وفحوصات العمليات الجراحية');
        $indexAr->assertSee('الانتقال للمختبر العام');
        $indexAr->assertSee('مباشر');

        // 2. Selection in English
        $selectionEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('staff.surgery-lab-tests.selection'));

        $selectionEn->assertStatus(200);
        $selectionEn->assertSee('dir="ltr"', false);
        $selectionEn->assertSee('Surgeries Requiring Lab Tests Selection');
        $selectionEn->assertSee('Back to Laboratory Orders');

        // Selection in Arabic
        $selectionAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('staff.surgery-lab-tests.selection'));

        $selectionAr->assertStatus(200);
        $selectionAr->assertSee('dir="rtl"', false);
        $selectionAr->assertSee('العمليات التي تحتاج اختيار تحاليل');
        $selectionAr->assertSee('عودة إلى طلبات المختبر');
    }
}
