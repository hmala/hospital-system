<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurgeonStationAccessAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $surgeonUser;

    protected Doctor $surgeonDoctor;

    protected Department $department;

    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'الجراح', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view surgeon station', 'guard_name' => 'web']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-001',
            'phone' => '07700000000',
            'email' => 'info@alkafaat.com',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
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

        $this->surgeonUser = User::factory()->create([
            'name' => 'د. احمد اسامة',
            'email' => 'surgeon_ahmed@hospital.com',
        ]);
        $this->surgeonUser->assignRole('الجراح');
        $this->surgeonUser->givePermissionTo('view surgeon station');

        $this->surgeonDoctor = Doctor::create([
            'user_id' => $this->surgeonUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'phone' => '07712345678',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-SURG-01',
            'type' => 'surgeon',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي جراحي']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'file_number' => 'SURG-001',
            'gender' => 'male',
            'age' => 40,
        ]);
    }

    public function test_surgery_appears_in_surgeon_station_when_operation_theater_completes(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->surgeonDoctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'استئصال الزائدة الدودية',
            'status' => 'completed',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
        ]);

        $otStation = $surgery->operationTheaterStation()->create([
            'status' => 'completed',
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);

        $surgery->surgeonStation()->create([
            'surgeon_id' => $this->surgeonDoctor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->surgeonUser)
            ->get(route('surgeon-station.index'));

        $response->assertStatus(200);
        $response->assertSee('محطة الطبيب الجراح');
        $response->assertSee('استئصال الزائدة الدودية');
        $response->assertSee('مريض تجريبي جراحي');
        $response->assertSee(route('surgeon-station.show', $surgery), false);
    }

    public function test_surgeon_resolves_duplicate_doctor_records_by_clean_name(): void
    {
        // محاكاة وجود سجل طبيب ثانٍ مستورد لنفس الجراح (بدون البادئة "د.")
        $duplicateUser = User::factory()->create([
            'name' => 'احمد اسامة',
            'email' => 'imported_ahmed@hospital.com',
        ]);

        $duplicateDoctor = Doctor::create([
            'user_id' => $duplicateUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة وتجميل',
            'phone' => '07799999999',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-SURG-02',
            'type' => 'surgeon',
            'is_active' => true,
        ]);

        // تم ربط العملية بالمعرّف المستورد
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $duplicateDoctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية جيوب انفية دقيقة',
            'status' => 'completed',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '11:00:00',
        ]);

        $surgery->operationTheaterStation()->create([
            'status' => 'completed',
        ]);

        $surgery->surgeonStation()->create([
            'surgeon_id' => $duplicateDoctor->id,
            'status' => 'pending',
        ]);

        // عندما يسجل الجراح بحسابه الأساسي "د. احمد اسامة"، يجب أن تظهر له العملية
        $response = $this->actingAs($this->surgeonUser)
            ->get(route('surgeon-station.index'));

        $response->assertStatus(200);
        $response->assertSee('عملية جيوب انفية دقيقة');

        // وأيضاً يُسمح له بفتح المحطة دون أي حجب 403
        $showResponse = $this->actingAs($this->surgeonUser)
            ->get(route('surgeon-station.show', $surgery));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('محطة الجراح - العملية رقم '.$surgery->id);
    }

    public function test_other_surgeon_cannot_access_unauthorized_surgery(): void
    {
        $otherUser = User::factory()->create(['name' => 'د. خالد فرحان']);
        $otherUser->assignRole('الجراح');
        $otherUser->givePermissionTo('view surgeon station');

        $otherDoctor = Doctor::create([
            'user_id' => $otherUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة',
            'qualification' => 'MBBS',
            'license_number' => 'LIC-SURG-03',
            'type' => 'surgeon',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->surgeonDoctor->id,
            'department_id' => $this->department->id,
            'surgery_type' => 'عملية خاصة بالطبيب احمد',
            'status' => 'completed',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '12:00:00',
        ]);

        $surgery->operationTheaterStation()->create([
            'status' => 'completed',
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('surgeon-station.show', $surgery));

        $response->assertStatus(403);
    }
}
