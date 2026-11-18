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

class ClinicalStationLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_clinical_station_show_renders_translated_english_labels_in_english_session(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin_clinical@hospital.com']);
        $admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'General Hospital',
            'owner_name' => 'Dr. Director',
            'license_number' => 'LIC-CLINICAL-01',
            'phone' => '07700000002',
            'email' => 'clinical@hospital.com',
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

        $doctorUser = User::factory()->create(['name' => 'Surgeon Alpha']);
        $doctorUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'General Surgery',
            'phone' => '07712345680',
            'qualification' => 'FRCS',
            'license_number' => 'LIC-DOC-03',
            'type' => 'surgeon',
        ]);

        $patientUser = User::factory()->create(['name' => 'Patient Jane']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'patient_id' => 'P-CLINICAL-100',
            'name' => 'Patient Jane',
            'gender' => 'female',
            'age' => 42,
            'blood_group' => 'A+',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'surgery_name' => 'Cholecystectomy',
            'surgery_type' => 'Major',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => 'in_progress',
        ]);

        // 1. In English session
        $enResponse = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('clinical-station.show', $surgery));
        $enResponse->assertOk();
        $enResponse->assertSee('dir="ltr"', false);
        $enResponse->assertSee('Unified Clinical Station');
        $enResponse->assertSee('Medical History');
        $enResponse->assertSee('Vitals &amp; Fluids', false);
        $enResponse->assertSee('Read &amp; Write Enabled', false);

        // 2. In Arabic session (default)
        $arResponse = $this->actingAs($admin)->withSession(['locale' => 'ar'])->get(route('clinical-station.show', $surgery));
        $arResponse->assertOk();
        $arResponse->assertSee('dir="rtl"', false);
        $arResponse->assertSee('المحطة السريرية الموحدة');
        $arResponse->assertSee('السيرة المرضية');
        $arResponse->assertSee('العلامات والسوائل');
        $arResponse->assertSee('إدخال وتعديل متاح بحسابك');
    }
}
