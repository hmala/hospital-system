<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Emergency;
use App\Models\EmergencyRadiologyRequest;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\RadiologyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnifiedEmergencyRadiologyStationAndReportTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;

    private EmergencyRadiologyRequest $emergencyRadiology;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'radiology_staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'view emergencies', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view radiology', 'guard_name' => 'web']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'النجف',
            'license_number' => 'LIC-EM-'.rand(100, 999),
        ]);

        $this->staffUser = User::factory()->create([
            'role' => 'radiology_staff',
        ]);
        $this->staffUser->assignRole('radiology_staff');
        $this->staffUser->givePermissionTo(['view emergencies', 'view radiology']);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'Emergency Department',
            'type' => 'emergency',
            'room_number' => 'ER-01',
            'consultation_fee' => 0,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'Emergency',
            'qualification' => 'MBChB, FICMS',
            'experience_years' => 10,
            'license_number' => 'DOC-EM-999',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'شاكر فوزي شاكر', 'phone' => '07805224052']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'medical_number' => 'MED-12345',
            'gender' => 'male',
            'age' => 45,
            'insurance_type' => 'hi',
        ]);

        $emergency = Emergency::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'emergency_type' => 'cardiac',
            'admission_time' => now(),
            'priority' => 'urgent',
            'status' => 'in_progress',
            'symptoms' => 'Severe chest pain radiating to left arm',
        ]);

        $radType = RadiologyType::firstOrCreate(
            ['code' => 'RAD-TEST-001'],
            [
                'name' => 'Chest - Single-section standard x-ray (AP)',
                'main_category' => 'أشعة',
                'subcategory' => 'أشعة',
                'base_price' => 25000,
                'is_active' => true,
            ]
        );

        $this->emergencyRadiology = EmergencyRadiologyRequest::create([
            'emergency_id' => $emergency->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'priority' => 'urgent',
            'notes' => 'Clinical correlation recommended',
            'requested_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->emergencyRadiology->radiologyTypes()->attach($radType->id, [
            'result' => 'CXR: Bilateral clear lung fields without consolidation',
            'image_path' => 'emergency_radiology_results/sample.jpg',
        ]);
    }

    public function test_staff_can_view_unified_emergency_radiology_workstation(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.emergency-radiology.show', $this->emergencyRadiology));

        $response->assertStatus(200);
        $response->assertSee('محطة إدخال تقرير طوارئ الأشعة');
        $response->assertSee('شاكر فوزي شاكر');
        $response->assertSee('Chest - Single-section standard x-ray (AP)');
        $response->assertSee('النتائج والمشاهدات التشخيصية (Clinical Findings)');
        $response->assertSee('قوالب سريرية سريعة بنقرة واحدة:');
        $response->assertSee('الاستنتاج الطبي (Impression / Conclusion)');
        $response->assertSee('التوصيات والمتابعة (Recommendations)');
        $response->assertSee('حفظ واعتماد تقرير الأشعة');
    }

    public function test_staff_can_save_and_complete_emergency_radiology_report(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->put(route('staff.emergency-radiology.complete', $this->emergencyRadiology), [
                'findings' => 'Normal study with no acute bony abnormality or consolidation.',
                'impression' => 'Normal Chest X-Ray',
                'recommendations' => 'No further immediate radiological imaging required.',
                'status' => 'completed',
                'notes' => 'Follow up in clinic',
            ]);

        $response->assertSessionHas('success');

        $this->emergencyRadiology->refresh();
        $this->assertEquals('completed', $this->emergencyRadiology->status);
        $this->assertNotNull($this->emergencyRadiology->results_data);
        $this->assertEquals('Normal Chest X-Ray', $this->emergencyRadiology->results_data['impression']);
        $this->assertEquals('Normal study with no acute bony abnormality or consolidation.', $this->emergencyRadiology->results_data['findings']);
    }

    public function test_staff_can_view_unified_emergency_radiology_print_report(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.emergency-radiology.print', $this->emergencyRadiology));

        $response->assertStatus(200);
        $response->assertSee('مستشفى الكفاءات الأهلي');
        $response->assertSee("Al-Kafa'at Private Hospital", false);
        $response->assertSee('شاكر فوزي شاكر');
        $response->assertSee('Chest - Single-section standard x-ray (AP)');
        $response->assertSee('Clinical Findings');
        $response->assertSee('طبيب / فني الأشعة المعتمد');
        $response->assertSee('الختم الرسمي');
        $response->assertSee('ERAD-'.$this->emergencyRadiology->id);
        $response->assertSee('تقرير كامل');
        $response->assertSee('ورق مروس');
    }
}
