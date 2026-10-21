<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\ICD10Code;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DoctorIcd10SearchTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;
    protected $doctor;
    protected $visit;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('doctor', 'web');

        Permission::findOrCreate('manage own visits', 'web');
        Permission::findOrCreate('view own visits', 'web');

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        if (!\Illuminate\Support\Facades\Schema::hasTable('icd10_codes')) {
            \Illuminate\Support\Facades\Schema::create('icd10_codes', function ($table) {
                $table->id();
                $table->string('code')->index();
                $table->text('description')->nullable();
                $table->text('description_ar')->nullable();
                $table->string('category')->nullable();
                $table->timestamps();
            });
        }

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'is_active' => true,
        ]);

        $this->doctorUser = User::create([
            'name' => 'د. أحمد علي',
            'email' => 'doctor_icd10@hospital.test',
            'password' => bcrypt('password'),
            'role' => 'doctor',
            'is_active' => true,
        ]);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo(['manage own visits', 'view own visits']);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'hospital_id' => $hospital->id,
            'department_id' => $department->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد',
            'license_number' => 'DOC-1234',
            'consultation_fee' => 15000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $patientUser = User::create([
            'name' => 'علي حسن',
            'email' => 'ali_patient@hospital.test',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'gender' => 'male',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => $hospital->id,
            'national_id' => '1234567890',
            'phone' => '07711223344',
            'age' => 45,
            'blood_type' => 'O+',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص سريري',
            'status' => 'in_progress',
        ]);

        // إدخال رموز ICD-10 تجريبية
        ICD10Code::create([
            'code' => 'J00',
            'description' => 'Acute nasopharyngitis (common cold)',
            'description_ar' => 'التهاب الخيشوم الحاد - زكام -',
            'category' => 'Diseases of the Respiratory system',
        ]);

        ICD10Code::create([
            'code' => 'K29.7',
            'description' => 'Gastritis, unspecified',
            'description_ar' => 'التهاب المعدة غير المحدد',
            'category' => 'Diseases of the Digestive system',
        ]);

        ICD10Code::create([
            'code' => 'E11.9',
            'description' => 'Type 2 diabetes mellitus without complications',
            'description_ar' => 'داء السكري من النوع 2 بدون مضاعفات',
            'category' => 'Endocrine, Nutritional and Metabolic Diseases',
        ]);
    }

    /** @test */
    public function icd10_search_returns_empty_when_query_is_blank()
    {
        $response = $this->actingAs($this->doctorUser)
            ->getJson(route('doctor.icd10.search', ['q' => '']));

        $response->assertOk();
        $this->assertCount(0, $response->json());
    }

    /** @test */
    public function icd10_search_matches_by_code()
    {
        $response = $this->actingAs($this->doctorUser)
            ->getJson(route('doctor.icd10.search', ['q' => 'J00']));

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals('J00', $data[0]['code']);
        $this->assertEquals('التهاب الخيشوم الحاد - زكام -', $data[0]['description_ar']);
    }

    /** @test */
    public function icd10_search_matches_by_arabic_description()
    {
        $response = $this->actingAs($this->doctorUser)
            ->getJson(route('doctor.icd10.search', ['q' => 'المعدة']));

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals('K29.7', $data[0]['code']);
        $this->assertStringContainsString('التهاب المعدة', $data[0]['description_ar']);
    }

    /** @test */
    public function icd10_search_matches_by_english_description()
    {
        $response = $this->actingAs($this->doctorUser)
            ->getJson(route('doctor.icd10.search', ['q' => 'diabetes']));

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals('E11.9', $data[0]['code']);
        $this->assertStringContainsString('diabetes', strtolower($data[0]['description']));
    }

    /** @test */
    public function doctor_can_save_diagnosis_with_code_and_description()
    {
        $response = $this->actingAs($this->doctorUser)
            ->put(route('doctor.visits.update', $this->visit), [
                'diagnosis' => [
                    'code' => 'K29.7 - التهاب المعدة غير المحدد (Gastritis, unspecified)',
                    'actual_code' => 'K29.7',
                    'description' => 'التهاب المعدة غير المحدد - Gastritis',
                ],
            ]);

        $response->assertRedirect();

        $this->visit->refresh();
        $this->assertNotNull($this->visit->diagnosis);
        $this->assertEquals('K29.7', $this->visit->diagnosis['actual_code'] ?? null);
        $this->assertStringContainsString('K29.7', $this->visit->diagnosis['code']);
    }

    /** @test */
    public function doctor_visit_page_renders_new_autocomplete_wrapper()
    {
        $response = $this->actingAs($this->doctorUser)
            ->get(route('doctor.visits.show', $this->visit));

        $response->assertOk();
        $response->assertSee('icd10-autocomplete-wrapper');
        $response->assertSee('icd10_dropdown');
        $response->assertSee('يقبل: الرمز • العربي • English');
        $response->assertSee('selected_diagnosis_card');
        $response->assertDontSee('<datalist id="icd10-list">', false);
    }

    /** @test */
    public function doctor_can_save_diagnosis_with_empty_description()
    {
        $response = $this->actingAs($this->doctorUser)
            ->put(route('doctor.visits.update', $this->visit), [
                'diagnosis' => [
                    'code' => 'J00 - التهاب الخيشوم الحاد (Acute nasopharyngitis)',
                    'actual_code' => 'J00',
                    'description' => '', // فارغ
                ],
            ]);

        $response->assertRedirect();

        $this->visit->refresh();
        $this->assertNotNull($this->visit->diagnosis);
        $this->assertEquals('J00', $this->visit->diagnosis['actual_code'] ?? null);
        $this->assertNotEmpty($this->visit->diagnosis['description']);
    }
}

