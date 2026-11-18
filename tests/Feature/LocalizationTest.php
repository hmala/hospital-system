<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test-locale', function () {
            return view('layouts.app');
        })->middleware('web');
    }

    public function test_can_switch_locale_to_english(): void
    {
        $response = $this->get('/lang/en');

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    public function test_can_switch_locale_to_arabic(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/lang/ar');

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'ar');
    }

    public function test_invalid_locale_defaults_to_arabic_in_middleware(): void
    {
        $response = $this->withSession(['locale' => 'fr'])->get('/_test-locale');

        $this->assertEquals('ar', App::getLocale());
    }

    public function test_english_locale_renders_ltr_direction_and_translations(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/_test-locale');

        $response->assertOk();
        $this->assertEquals('en', App::getLocale());
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('lang="en"', false);
        $this->assertEquals('Hospital Management System', __('نظام المستشفى'));
        $this->assertEquals('Logout', __('تسجيل الخروج'));
    }

    public function test_arabic_locale_renders_rtl_direction(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/_test-locale');

        $response->assertOk();
        $this->assertEquals('ar', App::getLocale());
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('القائمة الرئيسية');
        $response->assertSee('الإشعارات');
        $response->assertSee('"rtl": true', false);
    }

    public function test_english_layout_renders_translated_navigation_and_ltr_toastr(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/_test-locale');

        $response->assertOk();
        $response->assertSee('Hospital Management System');
        $response->assertSee('Main Menu');
        $response->assertSee('Notifications');
        $response->assertSee('No new notifications');
        $response->assertSee('Home');
        $response->assertSee('Loading');
        $response->assertSee('"rtl": false', false);
        $response->assertSee('direction: ltr !important;', false);
    }

    public function test_login_page_renders_in_english_and_arabic_with_switcher(): void
    {
        $enResponse = $this->withSession(['locale' => 'en'])->get('/login');
        $enResponse->assertOk();
        $enResponse->assertSee('dir="ltr"', false);
        $enResponse->assertSee('lang="en"', false);
        $enResponse->assertSee('Sign In to System');
        $enResponse->assertSee('Remember me');
        $enResponse->assertSee('Email Address or Username');
        $enResponse->assertSee('fa-arrow-right', false);
        $enResponse->assertSee(route('lang.switch', 'ar'), false);

        $arResponse = $this->withSession(['locale' => 'ar'])->get('/login');
        $arResponse->assertOk();
        $arResponse->assertSee('dir="rtl"', false);
        $arResponse->assertSee('lang="ar"', false);
        $arResponse->assertSee('دخول إلى المنظومة');
        $arResponse->assertSee('تذكرني');
        $arResponse->assertSee('fa-arrow-left', false);
        $arResponse->assertSee(route('lang.switch', 'en'), false);
    }

    public function test_english_dictionary_covers_core_modules_and_is_valid_json(): void
    {
        $raw = file_get_contents(lang_path('en.json'));
        $dict = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($dict);
        $this->assertGreaterThanOrEqual(500, count($dict));

        App::setLocale('en');
        $this->assertEquals('Dashboard', __('لوحة التحكم'));
        $this->assertEquals('Surgeries Registry', __('سجل العمليات الجراحية'));
        $this->assertEquals('Operating Room Schedule & Waiting List', __('جدول وقائمة انتظار العمليات الجراحية'));
        $this->assertEquals('Unified Clinical Station', __('المحطة السريرية الموحدة'));
        $this->assertEquals('Surgeon Station', __('محطة الطبيب الجراح'));
        $this->assertEquals('Anesthesia Station', __('محطة طبيب التخدير'));
        $this->assertEquals('Nursing & Inpatient Station', __('محطة التمريض والرقود'));
        $this->assertEquals('Daily Nurse Duty & Availability Schedule', __('جدول توفر ودوام التمريض اليومي'));
        $this->assertEquals('Off Official Duty Schedule Today', __('خارج جدول الدوام الرسمي لليوم'));
        $this->assertEquals('Surgery Details', __('تفاصيل العملية'));
        $this->assertEquals('Medical Diagnosis & Surgeon Orders', __('التشخيص الطبي وتوصيات الجراح'));
        $this->assertEquals('Surgical Team Members (5 Fields)', __('أعضاء الفريق الطبي المشارك في العملية (5 قوائم)'));
        $this->assertEquals('Anesthesia Plan & Details', __('خطة وتفاصيل التخدير'));
        $this->assertEquals('Surgery Supplies & Consumables', __('مستلزمات ومواد العملية'));
        $this->assertEquals('Periodic Follow-Up Log', __('سجل المتابعات الدورية'));
        $this->assertEquals('Medical Consultations Log', __('سجل الاستشارات الطبية'));
    }
}
