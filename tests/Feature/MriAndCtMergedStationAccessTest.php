<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\RadiologyType;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MriAndCtMergedStationAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $hospital;

    protected $mriUser;

    protected $doctor;

    protected $patient;

    protected $visit;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'radiology_mri', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'view radiology', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process radiology requests', 'guard_name' => 'web']);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-RAD-'.rand(100, 999),
        ]);

        $this->mriUser = User::factory()->create([
            'name' => 'فني الرنين والمفراس',
            'email' => 'mri_test@hospital.com',
        ]);
        $this->mriUser->assignRole('radiology_mri');
        $this->mriUser->givePermissionTo(['view radiology', 'process radiology requests']);

        $dept = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'القلبية والباطنية',
            'type' => 'cardiology',
            'room_number' => '201',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
        ]);

        $docUser = User::factory()->create(['name' => 'د. محمد فارس']);
        $this->doctor = Doctor::create([
            'user_id' => $docUser->id,
            'hospital_id' => $this->hospital->id,
            'department_id' => $dept->id,
            'specialization' => 'أمراض القلب',
            'qualification' => 'استشاري',
            'license_number' => 'DOC-12345',
        ]);

        $patientUser = User::factory()->create(['name' => 'احمد حسين سلمان الواسطي']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => $this->hospital->id,
            'gender' => 'male',
            'age' => 50,
            'phone' => '07712345678',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $dept->id,
            'status' => 'completed',
            'visit_type' => 'consultation',
            'chief_complaint' => 'ألم في الصدر',
            'visit_date' => today(),
            'visit_time' => now()->format('H:i:s'),
        ]);
    }

    public function test_mri_user_can_view_both_mri_and_ct_requests_in_workstation()
    {
        $ctType = RadiologyType::firstOrCreate(
            ['code' => 'RAD-2026-032'],
            [
                'name' => 'CT heart (coronary) / Coronary CTA',
                'base_price' => 175000,
                'is_active' => true,
            ]
        );

        $mriType = RadiologyType::firstOrCreate(
            ['code' => 'RAD-2026-001'],
            [
                'name' => 'Brain MRI with contrast',
                'base_price' => 150000,
                'is_active' => true,
            ]
        );

        $ctRequest = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'subtype' => 'ct',
            'description' => 'طلب أشعة: CT heart (coronary) / Coronary CTA',
            'status' => 'pending',
            'payment_status' => 'paid',
            'details' => [
                'radiology_type_id' => $ctType->id,
            ],
            'created_at' => today(),
        ]);

        $mriRequest = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'radiology',
            'subtype' => 'mri',
            'description' => 'طلب أشعة: Brain MRI with contrast',
            'status' => 'pending',
            'payment_status' => 'paid',
            'details' => [
                'radiology_type_id' => $mriType->id,
            ],
            'created_at' => today(),
        ]);

        $response = $this->actingAs($this->mriUser)
            ->get(route('radiology-staff.index', ['date' => 'today']));

        $response->assertStatus(200);
        $response->assertSee('احمد حسين سلمان الواسطي');
        $response->assertSee('CT heart (coronary)');
        $response->assertSee('Brain MRI with contrast');

        // Can access show page for CT request without 403
        $showResponse = $this->actingAs($this->mriUser)
            ->get(route('radiology-staff.show', $ctRequest));
        $showResponse->assertStatus(200);
    }
}
