<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeriesLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function createSurgeryFixture(): array
    {
        $admin = User::factory()->create(['name' => 'Surgery Admin', 'email' => 'surgery_admin@hospital.com']);
        $admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'Central Hospital',
            'owner_name' => 'Dr. Director',
            'license_number' => 'LIC-SURG-01',
            'phone' => '07700000010',
            'email' => 'surg@hospital.com',
            'address' => 'Baghdad',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'General Surgery Department',
            'type' => 'surgery',
            'room_number' => '301',
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
            'phone' => '07711223344',
            'qualification' => 'Board',
            'license_number' => 'LIC-DOC-77',
            'type' => 'surgeon',
        ]);

        $patUser = User::factory()->create(['name' => 'Patient John']);
        $patient = Patient::create([
            'user_id' => $patUser->id,
            'patient_id' => 'P-SURG-777',
            'name' => 'Patient John',
            'gender' => 'male',
            'age' => 45,
            'blood_group' => 'B+',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'surgery_name' => 'Appendectomy',
            'surgery_type' => 'Major',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'scheduled',
            'pre_op_diagnosis' => 'Acute Appendicitis',
        ]);

        return [$admin, $surgery];
    }

    public function test_surgeries_index_renders_in_both_locales(): void
    {
        [$admin] = $this->createSurgeryFixture();

        // 1. English Locale
        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('surgeries.index'));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('Surgeries Registry');
        $responseEn->assertSee('Active Surgeries (In Pathway)');
        $responseEn->assertSee('Completed &amp; Cancelled Surgeries', false);
        $responseEn->assertSee('OR Waiting List &amp; Theaters', false);

        // 2. Arabic Locale
        $responseAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('surgeries.index'));

        $responseAr->assertStatus(200);
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('سجل العمليات الجراحية');
        $responseAr->assertSee('العمليات النشطة (داخل المسار)');
        $responseAr->assertSee('العمليات المكتملة والملغاة (خرجت من الصالة)');
        $responseAr->assertSee('قائمة الانتظار والصالات');
    }

    public function test_surgeries_waiting_list_renders_in_both_locales(): void
    {
        [$admin] = $this->createSurgeryFixture();

        // 1. English Locale
        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('surgeries.waiting'));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('Operating Room Schedule &amp; Waiting List', false);
        $responseEn->assertSee('OR Flight Board');
        $responseEn->assertSee('Add New Theater');

        // 2. Arabic Locale
        $responseAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('surgeries.waiting'));

        $responseAr->assertStatus(200);
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('جدول وقائمة انتظار العمليات الجراحية');
        $responseAr->assertSee('OR Flight Board');
        $responseAr->assertSee('إضافة صالة جديدة');
    }

    public function test_surgeries_create_renders_in_both_locales(): void
    {
        [$admin] = $this->createSurgeryFixture();

        // 1. English Locale
        $responseEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('surgeries.create'));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('Book New Surgery');
        $responseEn->assertSee('Select Patient');
        $responseEn->assertSee('Surgeon');
        $responseEn->assertSee('Required Surgical Operations Schedule');

        // 2. Arabic Locale
        $responseAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('surgeries.create'));

        $responseAr->assertStatus(200);
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('حجز عملية جراحية جديدة');
        $responseAr->assertSee('اختر المريض');
        $responseAr->assertSee('الطبيب الجراح');
        $responseAr->assertSee('جدول العمليات الجراحية المطلوبة');
    }

    public function test_surgeries_show_and_edit_render_in_both_locales(): void
    {
        [$admin, $surgery] = $this->createSurgeryFixture();

        // Show - English & Arabic
        $showEn = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('surgeries.show', $surgery));
        $showEn->assertStatus(200);
        $showEn->assertSee('dir="ltr"', false);

        $showAr = $this->actingAs($admin)->withSession(['locale' => 'ar'])->get(route('surgeries.show', $surgery));
        $showAr->assertStatus(200);
        $showAr->assertSee('dir="rtl"', false);

        // Edit - English
        $editEn = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('surgeries.edit', $surgery));
        $editEn->assertStatus(200);
        $editEn->assertSee('dir="ltr"', false);
        $editEn->assertSee('Edit Surgical Operation Booking');
        $editEn->assertSee('Surgeon');
        $editEn->assertSee('Required Surgical Operations Schedule');
        $editEn->assertSee('Save &amp; Update Surgical Operation', false);

        // Edit - Arabic
        $editAr = $this->actingAs($admin)->withSession(['locale' => 'ar'])->get(route('surgeries.edit', $surgery));
        $editAr->assertStatus(200);
        $editAr->assertSee('dir="rtl"', false);
        $editAr->assertSee('تعديل حجز العملية الجراحية');
        $editAr->assertSee('الطبيب الجراح');
        $editAr->assertSee('جدول العمليات الجراحية المطلوبة');
        $editAr->assertSee('حفظ وتحديث حجز العملية الجراحية');
    }

    public function test_surgeries_print_renders_in_both_locales(): void
    {
        [$admin, $surgery] = $this->createSurgeryFixture();

        // 1. English
        $printEn = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('surgeries.print', $surgery));
        $printEn->assertStatus(200);
        $printEn->assertSee('dir="ltr"', false);
        $printEn->assertSee('Surgery Report');
        $printEn->assertSee('Surgeon Signature');
        $printEn->assertSee('Operating Theater Stamp');

        // 2. Arabic
        $printAr = $this->actingAs($admin)->withSession(['locale' => 'ar'])->get(route('surgeries.print', $surgery));
        $printAr->assertStatus(200);
        $printAr->assertSee('dir="rtl"', false);
        $printAr->assertSee('تقرير العملية الجراحية');
        $printAr->assertSee('توقيع الطبيب الجراح');
        $printAr->assertSee('ختم صالة العمليات');
    }

    public function test_surgery_tab_permissions_matrix_renders_in_both_locales(): void
    {
        [$admin] = $this->createSurgeryFixture();

        // 1. English
        $matrixEn = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('surgery-stations.tab-settings.index'));

        $matrixEn->assertStatus(200);
        $matrixEn->assertSee('dir="ltr"', false);
        $matrixEn->assertSee('Tab Permissions Matrix');
        $matrixEn->assertSee('Clinical Station');
        $matrixEn->assertSee('Add Role or Station to Permissions');

        // 2. Arabic
        $matrixAr = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('surgery-stations.tab-settings.index'));

        $matrixAr->assertStatus(200);
        $matrixAr->assertSee('dir="rtl"', false);
        $matrixAr->assertSee('مصفوفة صلاحيات التبويبات');
        $matrixAr->assertSee('المحطة السريرية');
        $matrixAr->assertSee('إضافة دور أو محطة للصلاحيات');
    }

    public function test_surgeries_control_view_renders_in_both_locales(): void
    {
        [$admin, $surgery] = $this->createSurgeryFixture();

        // English session
        app()->setLocale('en');
        app('translator')->setLocale('en');
        session(['locale' => 'en']);
        $htmlEn = view('surgeries.control', ['surgeries' => collect([$surgery])])->render();
        $this->assertStringContainsString('Surgical Operations Control Board', $htmlEn);
        $this->assertStringContainsString('Waiting List', $htmlEn);
        $this->assertStringContainsString('All Surgeries', $htmlEn);

        // Arabic session
        app()->setLocale('ar');
        app('translator')->setLocale('ar');
        session(['locale' => 'ar']);
        $htmlAr = view('surgeries.control', ['surgeries' => collect([$surgery])])->render();
        $this->assertStringContainsString('لوحة تحكم العمليات الجراحية', $htmlAr);
        $this->assertStringContainsString('قائمة الانتظار', $htmlAr);
        $this->assertStringContainsString('جميع العمليات', $htmlAr);
    }
}
