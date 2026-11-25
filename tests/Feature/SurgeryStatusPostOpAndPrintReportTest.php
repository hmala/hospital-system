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

class SurgeryStatusPostOpAndPrintReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createTestData(string $status = 'completed'): array
    {
        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات التخصصي',
            'owner_name' => 'د. حسام العراقي',
            'license_number' => 'LIC-OR-TEST',
            'phone' => '07700000000',
            'email' => 'info@alkafaat.com',
            'address' => 'بغداد - المنصور',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '101',
            'consultation_fee' => 50000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 30,
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create(['name' => 'Surgery Coordinator', 'email' => 'coordinator@hospital.com']);
        $adminUser->assignRole('admin');

        $docUser = User::factory()->create(['name' => 'Dr. Ahmed Surgeon', 'email' => 'dr_ahmed@hospital.com']);
        $docUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'General Surgery',
            'phone' => '07711112222',
            'qualification' => 'FICMS General Surgery',
            'license_number' => 'LIC-SURG-77',
            'type' => 'surgeon',
        ]);

        $patientUser = User::factory()->create(['name' => 'Ali Patient', 'phone' => '07733334444']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-05-15',
            'blood_type' => 'O+',
            'address' => 'بغداد',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'surgery_type' => 'استئصال الزائدة الدودية بالمنظار',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00',
            'status' => $status,
            'anesthesia_type' => 'تخدير عام',
            'diagnosis' => 'التهاب الزائدة الدودية الحاد مع وذمة موضعية',
            'description' => 'تم إجراء الاستئصال بنجاح دون أي مضاعفات نزفية',
            'doctor_recommendation' => 'مراقبة العلامات الحيوية وصرف المسكنات الوريدية لمدة 24 ساعة',
            'surgical_assistant_name' => 'د. محمد المساعد',
            'anesthesia_assistant_name' => 'أحمد مساعد التخدير',
            'end_time' => $status === 'completed' ? now() : null,
        ]);

        return [$adminUser, $doctor, $patient, $surgery, $hospital];
    }

    public function test_surgery_show_allows_status_dropdown_even_when_completed_and_shows_top_save_button(): void
    {
        [$adminUser, , , $surgery] = $this->createTestData('completed');

        $response = $this->actingAs($adminUser)->get(route('surgeries.show', $surgery));

        $response->assertStatus(200);

        // 1. Status dropdown is present and clickable
        $response->assertSee('patientOrStatusDropdownBtn');
        $response->assertSee('Post-Op (خرج من الصالة)');

        // 2. Options to re-start or return to waiting from completed are present
        $response->assertSee('formDropdownRestartSurgery');
        $response->assertSee('استئناف العملية (إعادة دخول الصالة)');
        $response->assertSee('formDropdownReturnFromCompleted');
        $response->assertSee('إرجاعه لقائمة الانتظار (Pre-Op)');

        // 3. Top save button is present right beside print report
        $response->assertSee('btnUnifiedSaveSurgeryTop');
        $response->assertSee('حفظ بيانات العملية');
        $response->assertSee('طباعة التقرير');
    }

    public function test_can_change_status_after_completed_back_to_in_progress_and_waiting(): void
    {
        [$adminUser, , , $surgery] = $this->createTestData('completed');

        // Restart surgery from completed back to in_progress
        $startResponse = $this->actingAs($adminUser)->post(route('surgeries.start', $surgery));
        $startResponse->assertRedirect();

        $surgery->refresh();
        $this->assertEquals('in_progress', $surgery->status);
        $this->assertNull($surgery->end_time);

        // Complete again
        $this->actingAs($adminUser)->post(route('surgeries.complete', $surgery));
        $surgery->refresh();
        $this->assertEquals('completed', $surgery->status);
        $this->assertNotNull($surgery->end_time);

        // Return from completed back to waiting
        $waitingResponse = $this->actingAs($adminUser)->post(route('surgeries.return-to-waiting', $surgery));
        $waitingResponse->assertRedirect();

        $surgery->refresh();
        $this->assertEquals('waiting', $surgery->status);
        $this->assertNull($surgery->end_time);
    }

    public function test_surgery_print_report_renders_with_official_hospital_header_logo_and_details(): void
    {
        [$adminUser, , , $surgery, $hospital] = $this->createTestData('completed');

        $response = $this->actingAs($adminUser)->get(route('surgeries.print', $surgery));

        $response->assertStatus(200);

        // Hospital header & details
        $response->assertSee($hospital->name);
        $response->assertSee('قسم العمليات الجراحية والتخدير');
        $response->assertSee('جناح العمليات الكبرى • التقرير الجراحي المعتمد');
        $response->assertSee('Department of Surgery & Anesthesia', false);
        $response->assertSee('تقرير العملية الجراحية المعتمد');

        // Patient & Doctor metadata
        $response->assertSee('Ali Patient');
        $response->assertSee('Dr. Ahmed Surgeon');
        $response->assertSee('استئصال الزائدة الدودية بالمنظار');

        // Clinical sections
        $response->assertSee('التهاب الزائدة الدودية الحاد مع وذمة موضعية');
        $response->assertSee('تخدير عام');
        $response->assertSee('د. محمد المساعد');

        // Official signature & stamp block
        $response->assertSee('توقيع الطبيب الجراح');
        $response->assertSee('توقيع طبيب التخدير');
        $response->assertSee('ختم صالة العمليات');
        $response->assertSee('OR-#'.$surgery->id);
    }

    public function test_surgery_print_report_displays_all_anesthesiologists_and_assistants_in_single_page_layout(): void
    {
        [$adminUser, , , $surgery] = $this->createTestData('completed');

        // Create secondary anesthesiologist and assign both
        $anesth1User = User::factory()->create(['name' => 'زينب سمير']);
        $anesth1 = Doctor::create([
            'user_id' => $anesth1User->id,
            'department_id' => $surgery->department_id,
            'specialization' => 'Anesthesia',
            'phone' => '07711223344',
            'qualification' => 'FICMS Anesthesia',
            'license_number' => 'LIC-AN-1',
            'type' => 'anesthesiologist',
        ]);

        $anesth2User = User::factory()->create(['name' => 'رند عبدالرحمن']);
        $anesth2 = Doctor::create([
            'user_id' => $anesth2User->id,
            'department_id' => $surgery->department_id,
            'specialization' => 'Anesthesia',
            'phone' => '07722334455',
            'qualification' => 'CABAMS Anesthesia',
            'license_number' => 'LIC-AN-2',
            'type' => 'anesthesiologist',
        ]);

        $surgery->update([
            'anesthesiologist_id' => $anesth1->id,
            'anesthesiologist_2_id' => $anesth2->id,
            'surgical_assistant_name' => 'حسين ناهي',
            'anesthesia_assistant_name' => 'محمد حلبي',
        ]);

        $response = $this->actingAs($adminUser)->get(route('surgeries.print', $surgery));

        $response->assertStatus(200);

        // 1. Verify all team members are simultaneously rendered without overwriting
        $response->assertSee('د. Dr. Ahmed Surgeon');
        $response->assertSee('حسين ناهي');
        $response->assertSee('طبيب التخدير الأول:');
        $response->assertSee('زينب سمير');
        $response->assertSee('طبيب التخدير الثاني:');
        $response->assertSee('رند عبدالرحمن');
        $response->assertSee('مساعد / تقني التخدير:');
        $response->assertSee('محمد حلبي');

        // 2. Verify dual anesthesiologist signatures
        $response->assertSee('• د. رند عبدالرحمن');

        // 3. Verify A4 single page CSS directives
        $response->assertSee('@page');
        $response->assertSee('size: A4 portrait');
    }
}
