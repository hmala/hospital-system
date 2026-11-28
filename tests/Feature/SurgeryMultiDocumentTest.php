<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\Surgery;
use App\Models\SurgeryDocumentType;
use App\Models\SurgicalOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurgeryMultiDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Patient $patient;

    protected Doctor $doctor;

    protected SurgicalOperation $operation;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        Storage::fake('public');

        $hospital = \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الكفاءات'],
            [
                'owner_name' => 'إدارة المستشفى',
                'phone' => '07700000000',
                'address' => 'بغداد',
                'license_number' => 'HOSP-12345',
            ]
        );

        $this->department = Department::firstOrCreate(
            ['name' => 'قسم الجراحة العامة'],
            [
                'is_active' => true,
                'hospital_id' => $hospital->id,
                'type' => 'surgery',
                'room_number' => '101',
                'consultation_fee' => 25000,
                'working_hours_start' => '08:00',
                'working_hours_end' => '16:00',
                'max_patients_per_day' => 30,
            ]
        );

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $doctorUser = User::factory()->create(['name' => 'د. محمد علي']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'جراحة عامة',
            'qualification' => 'بكالوريوس طب وجراحة عامة',
            'license_number' => 'DOC-'.uniqid(),
            'consultation_fee' => 25000,
            'type' => 'surgeon',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي للاختبار']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'مريض تجريبي للاختبار',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'phone' => '07700000000',
            'insurance_type' => 'none',
        ]);

        $this->operation = SurgicalOperation::firstOrCreate(
            ['name' => 'استئصال المرارة بالناظور'],
            [
                'category' => 'جراحة عامة',
                'fee' => 500000,
                'is_active' => true,
            ]
        );
    }

    public function test_document_types_can_be_listed_and_created(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('surgery-document-types.index'));
        $response->assertOk();
        $response->assertJsonStructure(['success', 'types']);

        $createResponse = $this->actingAs($this->admin)->postJson(route('surgery-document-types.store'), [
            'name' => 'تقرير فحص وظائف الكبد الخاص',
        ]);

        $createResponse->assertOk();
        $createResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('surgery_document_types', [
            'name' => 'تقرير فحص وظائف الكبد الخاص',
        ]);
    }

    public function test_surgery_can_be_created_with_multiple_documents(): void
    {
        $docType1 = SurgeryDocumentType::firstOrCreate(['name' => 'استمارة موافقة وتعهد العملية']);
        $docType2 = SurgeryDocumentType::firstOrCreate(['name' => 'تقرير تخطيط القلب / إيكو']);

        $file1 = UploadedFile::fake()->create('consent.pdf', 150, 'application/pdf');
        $fakeBase64Img = 'data:image/jpeg;base64,'.base64_encode('fake-image-binary-data');

        $payload = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'surgical_operation_id' => $this->operation->id,
            'scheduled_date' => now()->format('Y-m-d'),
            'referring_doctor_name' => 'د. محمد علي',
            'custom_surgery_fee' => 500000,
            'expected_stay_days' => 1,
            'surgery_category' => 'general',
            'surgery_documents' => [
                1 => [
                    'document_type_id' => $docType1->id,
                    'title' => 'موافقة المريض الخطية الموقعة',
                    'file' => $file1,
                ],
                2 => [
                    'document_type_id' => $docType2->id,
                    'title' => 'تخطيط القلب ECG قبل العملية',
                    'scanned_data' => $fakeBase64Img,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('surgeries.store'), $payload);

        $response->assertRedirect(route('surgeries.index'));

        $surgery = Surgery::latest('id')->first();
        $this->assertNotNull($surgery);

        // Verify two documents created in surgery_documents
        $this->assertEquals(2, $surgery->documents()->count());

        $doc1 = $surgery->documents()->where('title', 'موافقة المريض الخطية الموقعة')->first();
        $this->assertNotNull($doc1);
        $this->assertEquals($docType1->id, $doc1->surgery_document_type_id);
        $this->assertEquals('consent.pdf', $doc1->file_name);
        Storage::disk('public')->assertExists($doc1->file_path);

        $doc2 = $surgery->documents()->where('title', 'تخطيط القلب ECG قبل العملية')->first();
        $this->assertNotNull($doc2);
        $this->assertEquals($docType2->id, $doc2->surgery_document_type_id);
        Storage::disk('public')->assertExists($doc2->file_path);

        // Verify auto-archived in PatientDocument
        $this->assertTrue(PatientDocument::where('patient_id', $this->patient->id)->where('file_path', $doc1->file_path)->exists());
        $this->assertTrue(PatientDocument::where('patient_id', $this->patient->id)->where('file_path', $doc2->file_path)->exists());
    }

    public function test_surgery_edit_screen_loads_and_new_document_can_be_added(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => now(),
            'referring_doctor_name' => 'د. محمد علي',
            'custom_surgery_fee' => 500000,
            'surgery_fee' => 500000,
            'expected_stay_days' => 1,
            'surgery_category' => 'general',
            'status' => 'scheduled',
            'payment_status' => 'pending',
            'referral_letter_path' => 'referrals/legacy_referral.jpg',
        ]);

        Storage::disk('public')->put('referrals/legacy_referral.jpg', 'fake-image');

        // Test GET /surgeries/{id}/edit loads successfully and loads documents
        $response = $this->actingAs($this->admin)->get(route('surgeries.edit', $surgery));
        $response->assertOk();
        $response->assertSee('مستندات ومرفقات العملية الجراحية');

        // The legacy referral letter should have been converted/loaded as a document
        $this->assertEquals(1, $surgery->fresh()->documents()->count());

        // Now test adding another document via update
        $docType = SurgeryDocumentType::firstOrCreate(['name' => 'فحص واستشارة التخدير / الباطنية']);
        $newFile = UploadedFile::fake()->create('anesthesia_clearance.pdf', 200, 'application/pdf');

        $updatePayload = [
            'patient_id' => $surgery->patient_id,
            'doctor_id' => $this->doctor->id,
            'surgical_operation_id' => $this->operation->id,
            'scheduled_date' => now()->format('Y-m-d'),
            'referring_doctor_name' => 'د. محمد علي',
            'custom_surgery_fee' => 500000,
            'expected_stay_days' => 1,
            'surgery_category' => 'general',
            'surgery_documents' => [
                1 => [
                    'document_type_id' => $docType->id,
                    'title' => 'تقرير طبيب التخدير الاستشاري',
                    'file' => $newFile,
                ],
            ],
        ];

        $updateResponse = $this->actingAs($this->admin)->put(route('surgeries.update', $surgery), $updatePayload);
        $updateResponse->assertRedirect(route('surgeries.index'));

        $this->assertEquals(2, $surgery->fresh()->documents()->count());
    }

    public function test_document_can_be_deleted_from_surgery(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgical_operation_id' => $this->operation->id,
            'surgery_type' => $this->operation->name,
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => now(),
            'referring_doctor_name' => 'د. محمد علي',
            'custom_surgery_fee' => 500000,
            'surgery_fee' => 500000,
            'expected_stay_days' => 1,
            'surgery_category' => 'general',
            'status' => 'scheduled',
            'payment_status' => 'pending',
        ]);

        $fakePath = 'surgery_documents/'.$surgery->id.'/test_doc.pdf';
        Storage::disk('public')->put($fakePath, 'dummy-pdf-content');

        $doc = $surgery->documents()->create([
            'patient_id' => $this->patient->id,
            'title' => 'مستند للتجربة والحذف',
            'file_path' => $fakePath,
            'file_name' => 'test_doc.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
            'uploaded_by' => $this->admin->id,
        ]);

        $deleteResponse = $this->actingAs($this->admin)->deleteJson(
            route('surgeries.documents.delete', [$surgery->id, $doc->id])
        );

        $deleteResponse->assertOk();
        $deleteResponse->assertJson(['success' => true]);

        $this->assertDatabaseMissing('surgery_documents', ['id' => $doc->id]);
        Storage::disk('public')->assertMissing($fakePath);
    }
}
