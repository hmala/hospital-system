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
    }
}
