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
}
