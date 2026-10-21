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
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RadiologyPrintReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'radiology_ultrasound', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'radiology_staff', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view radiology', 'guard_name' => 'web']);
    }

    public function test_radiology_print_report_displays_findings_and_empty_signature_block()
    {
        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'address' => 'بغداد',
            'phone' => '07700000000',
            'email' => 'info@example.com',
            'owner_name' => 'المدير',
            'license_number' => '123'
        ]);

        $dept = Department::create([
            'name' => 'عيادة الباطنية',
            'hospital_id' => $hospital->id,
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '15:00',
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. حسام الباطنية']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'باطنية',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-12345',
            'consultation_fee' => 15000,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'منير سمير يوسف', 'phone' => '07712345678']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1985-05-15',
            'insurance_type' => 'hi',
            'insurance_card_number' => 'TH-998822',
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $dept->id,
            'doctor_id' => $doctor->id,
            'visit_date' => now(),
            'visit_time' => now(),
            'visit_type' => 'checkup',
            'chief_complaint' => 'ألم في البطن',
            'status' => 'completed',
        ]);

        $sonarType = RadiologyType::create([
            'name' => 'سونار البطن والحوض الشامل',
            'code' => 'US-101',
            'base_price' => 45000,
            'hi_price' => 10000,
            'is_hi_active' => true,
            'subcategory' => 'سونار',
            'main_category' => 'السونار',
            'is_active' => true,
        ]);

        // إنشاء طلب السونار مع المشاهدات والنتائج
        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'radiology',
            'subtype' => 'ultrasound',
            'description' => 'طلب سونار: سونار البطن والحوض الشامل',
            'details' => json_encode([
                'radiology_type_ids' => [$sonarType->id],
                'radiology_type_names' => [$sonarType->name],
            ]),
            'status' => 'completed',
            'payment_status' => 'paid',
            'insurance_type' => 'hi',
            'result' => [
                'findings' => 'الكبد سليم والحجم طبيعي مع عدم وجود أي كتل أو ارتشاح شحمي. المرارة سليمة وخالية من الحصى. الكليتان بحجم وشكل طبيعي.',
                'impression' => 'فحص سونار بطن وحوض سليم ولا توجد أي علامات مرضية واضحة.',
                'recommendations' => 'متابعة دورية سنوية.',
                'radiologist' => 'د. سنان أخصائي السونار',
                'reported_at' => now()->format('Y-m-d H:i:s'),
            ],
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('radiology-staff.print', $medicalRequest));

        $response->assertStatus(200);
        // التحقق من ظهور اسم المريض
        $response->assertSee('منير سمير يوسف');
        // التحقق من ظهور المشاهدات والنتائج
        $response->assertSee('الكبد سليم والحجم طبيعي مع عدم وجود أي كتل أو ارتشاح شحمي');
        $response->assertSee('فحص سونار بطن وحوض سليم ولا توجد أي علامات مرضية واضحة');
        $response->assertSee('متابعة دورية سنوية');
        // التحقق من ظهور شارة الضمان الصحي
        $response->assertSee('الضمان الصحي');
        // التحقق من وجود مكان التوقيع فارغاً للاختبار
        $response->assertSee('(مكان التوقيع والاعتماد)');
        $response->assertSee('أخصائي الأشعة والتصوير الطبي / فاحص السونار');
    }
}
