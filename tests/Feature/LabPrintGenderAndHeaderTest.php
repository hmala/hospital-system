<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\Request as MedicalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class LabPrintGenderAndHeaderTest extends TestCase
{
    use RefreshDatabase;

    protected User $labStaff;
    protected User $femaleUser;
    protected Patient $femalePatient;
    protected User $maleUser;
    protected Patient $malePatient;
    protected \App\Models\Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'lab_staff', 'guard_name' => 'web']);

        $hospital = \App\Models\Hospital::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'مستشفى الكفاءات الأهلي',
                'owner_name' => 'Admin',
                'phone' => '07700000000',
                'address' => 'Baghdad',
                'license_number' => 'LIC-12345',
            ]
        );

        $this->department = \App\Models\Department::firstOrCreate(
            ['name' => 'المختبر'],
            [
                'hospital_id' => $hospital->id,
                'type' => 'laboratory',
                'room_number' => '101',
                'consultation_fee' => 0,
                'working_hours_start' => '08:00:00',
                'working_hours_end' => '20:00:00',
                'is_active' => true,
            ]
        );

        $this->labStaff = User::factory()->create([
            'name' => 'فني المختبر الأول',
            'role' => 'lab_staff',
        ]);
        $this->labStaff->assignRole('lab_staff');
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view lab tests']);
        $this->labStaff->givePermissionTo('view lab tests');

        // مريضة أنثى (هيفاء)
        $this->femaleUser = User::factory()->create([
            'name' => 'هيفاء مدلول لفته',
            'gender' => 'female',
            'role' => 'patient',
        ]);
        $this->femalePatient = Patient::create([
            'user_id' => $this->femaleUser->id,
        ]);

        // مريض ذكر (أحمد)
        $this->maleUser = User::factory()->create([
            'name' => 'أحمد سمير علي',
            'gender' => 'male',
            'role' => 'patient',
        ]);
        $this->malePatient = Patient::create([
            'user_id' => $this->maleUser->id,
        ]);
    }

    public function test_female_patient_prints_as_female_not_male(): void
    {
        $visit = Visit::create([
            'patient_id' => $this->femalePatient->id,
            'department_id' => $this->department->id,
            'visit_type' => 'laboratory',
            'chief_complaint' => 'فحوصات مخبرية',
            'visit_date' => now(),
            'visit_time' => now(),
            'status' => 'in_progress',
        ]);

        $request = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبرية',
            'status' => 'completed',
            'details' => ['tests' => ['TSH']],
        ]);

        $response = $this->actingAs($this->labStaff)->get(route('lab.print', $request));

        $response->assertStatus(200);
        // التحقق من ظهور Female وليس Male
        $response->assertSee('Sex');
        $response->assertSee('Female');
        $response->assertDontSee('<span class="info-val-text">Male</span>', false);

        // التحقق من ظهور ترويسة الطوارئ والأشعة الموحدة
        $response->assertSee('مستشفى الكفاءات الأهلي');
        $response->assertSee('قسم المختبر والتحاليل الطبية');
        $response->assertSee("Al-Kafa'at", false);
        $response->assertSee('Clinical Laboratory Dept.');
        $response->assertSee('document-title-banner');
        $response->assertSee('LAB-#' . $request->id);
    }

    public function test_male_patient_prints_as_male(): void
    {
        $visit = Visit::create([
            'patient_id' => $this->malePatient->id,
            'department_id' => $this->department->id,
            'visit_type' => 'laboratory',
            'chief_complaint' => 'فحوصات مخبرية',
            'visit_date' => now(),
            'visit_time' => now(),
            'status' => 'in_progress',
        ]);

        $request = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبرية',
            'status' => 'completed',
            'details' => ['tests' => ['CBC']],
        ]);

        $response = $this->actingAs($this->labStaff)->get(route('lab.print', $request));

        $response->assertStatus(200);
        $response->assertSee('Sex');
        $response->assertSee('Male');
    }

    public function test_patient_model_gender_accessor_delegates_to_user_gender(): void
    {
        $this->assertEquals('female', $this->femalePatient->gender);
        $this->assertEquals('أنثى', $this->femalePatient->gender_display);

        $this->assertEquals('male', $this->malePatient->gender);
        $this->assertEquals('ذكر', $this->malePatient->gender_display);
    }

    public function test_lab_print_displays_previous_results_for_same_patient(): void
    {
        // 1. الزيارة الأولى للمريضة (قبل شهرين)
        $visit1 = Visit::create([
            'patient_id' => $this->femalePatient->id,
            'department_id' => $this->department->id,
            'visit_type' => 'laboratory',
            'chief_complaint' => 'تحليل سابق',
            'visit_date' => now()->subMonths(2),
            'visit_time' => now(),
            'status' => 'completed',
        ]);
        $req1 = MedicalRequest::create([
            'visit_id' => $visit1->id,
            'type' => 'lab',
            'description' => 'طلب مختبر سابق 1',
            'status' => 'completed',
            'created_at' => now()->subMonths(2),
        ]);
        \App\Models\LabResult::create([
            'visit_id' => $visit1->id,
            'request_id' => $req1->id,
            'test_name' => 'Hemoglobin',
            'value' => '10.5',
            'unit' => 'g/dL',
            'status' => 'low',
            'reference_range' => '12-16',
            'created_at' => now()->subMonths(2),
        ]);

        // 2. الزيارة الثانية للمريضة (قبل أسبوعين)
        $visit2 = Visit::create([
            'patient_id' => $this->femalePatient->id,
            'department_id' => $this->department->id,
            'visit_type' => 'laboratory',
            'chief_complaint' => 'تحليل متابعة',
            'visit_date' => now()->subWeeks(2),
            'visit_time' => now(),
            'status' => 'completed',
        ]);
        $req2 = MedicalRequest::create([
            'visit_id' => $visit2->id,
            'type' => 'lab',
            'description' => 'طلب مختبر سابق 2',
            'status' => 'completed',
            'created_at' => now()->subWeeks(2),
        ]);
        \App\Models\LabResult::create([
            'visit_id' => $visit2->id,
            'request_id' => $req2->id,
            'test_name' => 'Hemoglobin',
            'value' => '11.8',
            'unit' => 'g/dL',
            'status' => 'low',
            'reference_range' => '12-16',
            'created_at' => now()->subWeeks(2),
        ]);

        // 3. الزيارة الحالية للمريضة (اليوم)
        $visitCurrent = Visit::create([
            'patient_id' => $this->femalePatient->id,
            'department_id' => $this->department->id,
            'visit_type' => 'laboratory',
            'chief_complaint' => 'فحص اليوم',
            'visit_date' => now(),
            'visit_time' => now(),
            'status' => 'completed',
        ]);
        $reqCurrent = MedicalRequest::create([
            'visit_id' => $visitCurrent->id,
            'type' => 'lab',
            'description' => 'طلب مختبر حالي',
            'status' => 'completed',
            'created_at' => now(),
        ]);
        \App\Models\LabResult::create([
            'visit_id' => $visitCurrent->id,
            'request_id' => $reqCurrent->id,
            'test_name' => 'Hemoglobin',
            'value' => '12.6',
            'unit' => 'g/dL',
            'status' => 'normal',
            'reference_range' => '12-16',
            'created_at' => now(),
        ]);

        // اختبار دالة getPreviousLabResultsMap على نموذج الطلب
        $prevMap = $reqCurrent->getPreviousLabResultsMap();
        $this->assertArrayHasKey('hemoglobin', $prevMap);
        $this->assertCount(2, $prevMap['hemoglobin']);
        $this->assertEquals('11.8', $prevMap['hemoglobin'][0]['value']);
        $this->assertEquals('10.5', $prevMap['hemoglobin'][1]['value']);

        // اختبار استعراض صفحة التقرير
        $response = $this->actingAs($this->labStaff)->get(route('lab.print', $reqCurrent));
        $response->assertStatus(200);
        $response->assertSee('Previous (آخر 3 نتائج)');
        $response->assertSee('11.8');
        $response->assertSee('10.5');
        $response->assertSee('12.6'); // النتيجة الحالية
    }

    public function test_lab_print_shows_dash_when_no_previous_results(): void
    {
        $visit = Visit::create([
            'patient_id' => $this->malePatient->id,
            'department_id' => $this->department->id,
            'visit_type' => 'laboratory',
            'chief_complaint' => 'أول فحص',
            'visit_date' => now(),
            'visit_time' => now(),
            'status' => 'completed',
        ]);
        $req = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'أول طلب مختبر',
            'status' => 'completed',
        ]);
        \App\Models\LabResult::create([
            'visit_id' => $visit->id,
            'request_id' => $req->id,
            'test_name' => 'Serum Ferritin',
            'value' => '85',
            'unit' => 'ng/mL',
            'status' => 'normal',
            'reference_range' => '30-400',
        ]);

        $response = $this->actingAs($this->labStaff)->get(route('lab.print', $req));
        $response->assertStatus(200);
        $response->assertSee('Previous (آخر 3 نتائج)');
        $response->assertSee('prev-empty-dash');
    }
}

