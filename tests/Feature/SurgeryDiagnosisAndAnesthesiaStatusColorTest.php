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

class SurgeryDiagnosisAndAnesthesiaStatusColorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_surgery_show_displays_red_for_undiagnosed_and_orange_for_undocumented_anesthesia(): void
    {
        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-001',
            'phone' => '07700000000',
            'email' => 'info@hospital.com',
            'address' => 'بغداد',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة',
            'type' => 'surgery',
            'room_number' => '101',
            'consultation_fee' => 50000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 30,
            'is_active' => true,
        ]);

        $staffUser = User::factory()->create(['name' => 'Surgery Staff Member', 'email' => 'or_staff@hospital.com']);
        $staffUser->assignRole('surgery_staff');

        $docUser = User::factory()->create(['name' => 'Dr. Surgeon', 'email' => 'dr_surgeon1@hospital.com']);
        $docUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'General Surgery',
            'phone' => '07712345678',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-SURG-01',
            'type' => 'surgeon',
        ]);

        $patientUser = User::factory()->create(['name' => 'Patient Ahmed']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'Patient Ahmed',
            'patient_id' => 'P-101',
            'gender' => 'male',
        ]);

        // Surgery with NO diagnosis and NO anesthesia
        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'surgery_name' => 'Appendectomy',
            'surgery_type' => 'Major',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'waiting',
            'diagnosis' => null,
            'anesthesia_type' => null,
        ]);

        $response = $this->actingAs($staffUser)->get(route('surgeries.show', $surgery));

        $response->assertStatus(200);

        // Assert Red Indicator for Diagnosis
        $response->assertSee('التشخيص: لم يُشخص بعد', false);
        $response->assertSee('#fee2e2', false); // Red background
        $response->assertSee('لم يتم إدخال التشخيص الطبي للعملية بعد', false);
        $response->assertDontSee('التشخيص: تم التشخيص', false);

        // Assert Orange/Amber Indicator for Anesthesia
        $response->assertSee('التخدير: لم يُدخل بعد', false);
        $response->assertSee('#ffedd5', false); // Warm amber background
        $response->assertSee('لم يتم إدخال نوع وخطة التخدير بعد', false);
        $response->assertDontSee('التخدير: تم التوثيق', false);
    }

    public function test_surgery_show_displays_green_for_diagnosed_and_blue_for_documented_anesthesia(): void
    {
        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-002',
            'phone' => '07700000001',
            'email' => 'info2@hospital.com',
            'address' => 'بغداد',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة',
            'type' => 'surgery',
            'room_number' => '102',
            'consultation_fee' => 50000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 30,
            'is_active' => true,
        ]);

        $staffUser = User::factory()->create(['name' => 'Surgery Staff Member 2', 'email' => 'or_staff2@hospital.com']);
        $staffUser->assignRole('surgery_staff');

        $docUser = User::factory()->create(['name' => 'Dr. Ali', 'email' => 'dr_ali2@hospital.com']);
        $docUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'General Surgery',
            'phone' => '07712345679',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-SURG-02',
            'type' => 'surgeon',
        ]);

        $anesthUser = User::factory()->create(['name' => 'Dr. Anesthesiologist', 'email' => 'dr_anesth2@hospital.com']);
        $anesthUser->assignRole('doctor');
        $anesthesiologist = Doctor::create([
            'user_id' => $anesthUser->id,
            'department_id' => $dept->id,
            'specialization' => 'Anesthesia',
            'phone' => '07712345680',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-ANES-01',
            'type' => 'anesthesiologist',
        ]);

        $patientUser = User::factory()->create(['name' => 'Patient Sarah']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'Patient Sarah',
            'patient_id' => 'P-102',
            'gender' => 'female',
        ]);

        // Surgery WITH diagnosis and WITH documented anesthesia
        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'anesthesiologist_id' => $anesthesiologist->id,
            'surgery_name' => 'Cholecystectomy',
            'surgery_type' => 'Major',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'waiting',
            'diagnosis' => 'Acute Cholecystitis with Gallstones',
            'anesthesia_type' => 'تخدير عام (General Anesthesia)',
        ]);

        $response = $this->actingAs($staffUser)->get(route('surgeries.show', $surgery));

        $response->assertStatus(200);

        // Assert Green Indicator for Diagnosis
        $response->assertSee('التشخيص: تم التشخيص', false);
        $response->assertSee('#dcfce7', false); // Green background
        $response->assertSee('تم توثيق التشخيص الطبي للعملية بنجاح', false);
        $response->assertDontSee('التشخيص: لم يُشخص بعد', false);

        // Assert Blue/Cyan Indicator for Anesthesia
        $response->assertSee('التخدير: تم التوثيق', false);
        $response->assertSee('#e0f2fe', false); // Cyan/blue background
        $response->assertSee('تم توثيق تفاصيل وخطة التخدير بنجاح', false);
        $response->assertDontSee('التخدير: لم يُدخل بعد', false);
    }
}
