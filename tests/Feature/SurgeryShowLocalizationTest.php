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

class SurgeryShowLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_surgery_show_renders_translated_english_labels_in_english_session(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin_i18n@hospital.com']);
        $admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'General Hospital',
            'owner_name' => 'Dr. Director',
            'license_number' => 'LIC-I18N-01',
            'phone' => '07700000001',
            'email' => 'i18n@hospital.com',
            'address' => 'Baghdad',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'Surgery Department',
            'type' => 'surgery',
            'room_number' => '201',
            'consultation_fee' => 45000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 20,
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'Surgeon One']);
        $doctorUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'General Surgery',
            'phone' => '07712345679',
            'qualification' => 'FRCS',
            'license_number' => 'LIC-DOC-02',
            'type' => 'surgeon',
        ]);

        $patientUser = User::factory()->create(['name' => 'Patient John']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'patient_id' => 'P-I18N-100',
            'name' => 'Patient John',
            'gender' => 'male',
            'age' => 38,
            'blood_group' => 'O+',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'surgery_name' => 'Appendectomy',
            'surgery_type' => 'Major',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'waiting',
        ]);

        // 1. In English session
        $enResponse = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('surgeries.show', $surgery));
        $enResponse->assertOk();
        $enResponse->assertSee('dir="ltr"', false);
        $enResponse->assertSee('Surgery Details');
        $enResponse->assertSee('Medical Diagnosis &amp; Surgeon Orders', false);
        $enResponse->assertSee('Surgical Team Members (5 Fields)');
        $enResponse->assertSee('Anesthesia Plan &amp; Details', false);
        $enResponse->assertSee('Surgery Supplies &amp; Consumables', false);
        $enResponse->assertSee('Periodic Follow-Up Log');
        $enResponse->assertSee('Medical Consultations Log');
        $enResponse->assertSee('Save Surgery Details');

        // 2. In Arabic session (default)
        $arResponse = $this->actingAs($admin)->withSession(['locale' => 'ar'])->get(route('surgeries.show', $surgery));
        $arResponse->assertOk();
        $arResponse->assertSee('dir="rtl"', false);
        $arResponse->assertSee('تفاصيل العملية');
        $arResponse->assertSee('التشخيص الطبي وتوصيات الجراح');
        $arResponse->assertSee('أعضاء الفريق الطبي المشارك في العملية (5 قوائم)');
        $arResponse->assertSee('خطة وتفاصيل التخدير');
        $arResponse->assertSee('مستلزمات ومواد العملية');
        $arResponse->assertSee('سجل المتابعات الدورية');
        $arResponse->assertSee('سجل الاستشارات الطبية');
        $arResponse->assertSee('حفظ بيانات العملية');
    }
}
