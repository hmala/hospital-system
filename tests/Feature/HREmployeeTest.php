<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\HrFieldRequirement;
use App\Models\HrLookupOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\HospitalSeeder;
use Database\Seeders\HrSettingsSeeder;

class HREmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(HospitalSeeder::class);
        $this->seed(HrSettingsSeeder::class);
        Storage::fake('public');
    }

    public function test_admin_can_view_employees_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('hr.employees.index'));
        $response->assertStatus(200);
        $response->assertSee('سجل الموظفين');
    }

    public function test_can_create_medical_employee_with_license_credentials_and_documents()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $hospital = Hospital::first();
        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'max_patients_per_day' => 30,
        ]);

        $dummyPdf = UploadedFile::fake()->create('national_id.pdf', 500, 'application/pdf');

        $employeeData = [
            'employee_code' => 'EMP-TEST-001',
            'full_name' => 'د. حيدر جاسم الكعبي',
            'gender' => 'male',
            'phone' => '07701234567',
            'staff_type' => 'medical',
            'job_title' => 'طبيب اختصاص جراحة عامة',
            'department_id' => $department->id,
            'employment_type' => 'full_time',
            'hire_date' => now()->subYear()->format('Y-m-d'),
            'basic_salary' => 2500000,
            'status' => 'active',
            'medical_license_number' => 'MOH-IQ-98765',
            'license_expiry_date' => now()->addMonths(6)->format('Y-m-d'),
            'syndicate_card_number' => 'SYN-4455',
            'qualification' => 'بورد عربي في الجراحة',
            'sub_specialty' => 'جراحة المنظار',
            'documents' => [
                [
                    'type' => 'البطاقة الوطنية الموحدة',
                    'file' => $dummyPdf,
                    'notes' => 'نسخة ملونة واضحة',
                ],
            ],
        ];

        $response = $this->actingAs($admin)->post(route('hr.employees.store'), $employeeData);

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP-TEST-001',
            'full_name' => 'د. حيدر جاسم الكعبي',
            'medical_license_number' => 'MOH-IQ-98765',
            'staff_type' => 'medical',
        ]);

        $employee = Employee::where('employee_code', 'EMP-TEST-001')->first();
        $this->assertNotNull($employee);
        $this->assertTrue($employee->isMedicalStaff());
        $this->assertFalse($employee->isLicenseExpired());

        // التحقق من رفع المستمسك وتسميته المنظمة
        $document = $employee->documents()->first();
        $this->assertNotNull($document);
        $this->assertEquals('EMP-TEST-001', $document->employee_code);
        $this->assertEquals('البطاقة الوطنية الموحدة', $document->document_type);
        $this->assertStringContainsString('EMP-TEST-001', $document->file_name);
        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_can_upload_document_from_employee_dossier()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $employee = Employee::create([
            'employee_code' => 'EMP-TEST-DOC',
            'full_name' => 'زينب كريم',
            'gender' => 'female',
            'phone' => '07709999999',
            'staff_type' => 'administrative',
            'job_title' => 'مسؤولة شؤون الموظفين',
            'employment_type' => 'full_time',
            'hire_date' => now()->format('Y-m-d'),
            'status' => 'active',
        ]);

        $dummyFile = UploadedFile::fake()->create('residence_card.pdf', 300, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('hr.employees.documents.upload', $employee->id), [
            'document_type' => 'بطاقة السكن',
            'document_file' => $dummyFile,
            'notes' => 'مستمسك أصلي مصدق',
        ]);

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'employee_code' => 'EMP-TEST-DOC',
            'document_type' => 'بطاقة السكن',
        ]);

        $doc = EmployeeDocument::where('employee_id', $employee->id)->first();
        $this->assertStringContainsString('EMP-TEST-DOC', $doc->file_name);
        Storage::disk('public')->assertExists($doc->file_path);

        // تجربة حذف المستمسك
        $deleteResponse = $this->actingAs($admin)->delete(route('hr.employees.documents.destroy', $doc->id));
        $this->assertDatabaseMissing('employee_documents', ['id' => $doc->id]);
        Storage::disk('public')->assertMissing($doc->file_path);
    }

    public function test_can_manage_hr_field_requirements()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        // جعل حقل national_id إجبارياً
        $response = $this->actingAs($admin)->post(route('hr.settings.update-fields'), [
            'required_fields' => ['national_id', 'phone', 'full_name'],
        ]);

        $this->assertTrue(HrFieldRequirement::isFieldRequired('national_id'));

        // محاولة حفظ موظف بدون national_id يجب أن تفشل
        $employeeData = [
            'employee_code' => 'EMP-FAIL-01',
            'full_name' => 'علي حسن',
            'gender' => 'male',
            'phone' => '07701111111',
            'staff_type' => 'administrative',
            'job_title' => 'إداري',
            'employment_type' => 'full_time',
            'hire_date' => now()->format('Y-m-d'),
            'status' => 'active',
            // national_id is missing!
        ];

        $postResponse = $this->actingAs($admin)->post(route('hr.employees.store'), $employeeData);
        $postResponse->assertSessionHasErrors(['national_id']);
    }

    public function test_can_manage_lookup_options()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        // إضافة نوع تعاقد جديد
        $response = $this->actingAs($admin)->post(route('hr.settings.store-option'), [
            'category' => 'employment_type',
            'name' => 'عقد زمالة تدريبية',
            'code' => 'training_fellowship',
        ]);

        $this->assertDatabaseHas('hr_lookup_options', [
            'category' => 'employment_type',
            'name' => 'عقد زمالة تدريبية',
        ]);

        $opt = HrLookupOption::where('name', 'عقد زمالة تدريبية')->first();

        // تعطيل الخيار
        $this->actingAs($admin)->patch(route('hr.settings.toggle-option', $opt->id));
        $this->assertFalse($opt->fresh()->is_active);

        // حذف الخيار
        $this->actingAs($admin)->delete(route('hr.settings.destroy-option', $opt->id));
        $this->assertDatabaseMissing('hr_lookup_options', ['id' => $opt->id]);
    }
}
