<?php

namespace Tests\Feature;

use App\Models\CcuAttachment;
use App\Models\CcuConsultation;
use App\Models\CcuDoctorNote;
use App\Models\CcuMedicationSchedule;
use App\Models\Doctor;
use App\Models\IcuAdmission;
use App\Models\IcuBed;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CcuClinicalSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Doctor $doctor;

    protected Doctor $consultantDoctor;

    protected Patient $patient;

    protected IcuBed $bed;

    protected IcuAdmission $admission;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'view icu radar',
            'manage icu beds',
            'admit icu patients',
            'record icu vitals',
            'record icu fluid balance',
            'manage icu medical orders',
            'manage icu care plans',
            'discharge icu patients',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($permissions);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $hospital = \App\Models\Hospital::create([
            'name' => 'مستشفى القلب والعناية المركزة التخصصي',
            'owner_name' => 'المدير العام',
            'license_number' => 'CCU-HOSP-1',
            'phone' => '07700000000',
            'address' => 'بغداد',
        ]);

        $dept = \App\Models\Department::firstOrCreate(
            ['name' => 'وحدة العناية المركزة والحرجة CCU'],
            [
                'hospital_id' => $hospital->id,
                'type' => 'other',
                'room_number' => 'CCU-200',
                'consultation_fee' => 30000,
                'working_hours_start' => '08:00',
                'working_hours_end' => '20:00',
                'is_active' => true,
            ]
        );

        $docUser1 = User::factory()->create(['name' => 'د. فراس التميمي']);
        $this->doctor = Doctor::create([
            'user_id' => $docUser1->id,
            'department_id' => $dept->id,
            'specialization' => 'عناية مركزة وتخدير',
            'qualification' => 'استشاري عناية حرجة',
            'license_number' => 'CCU-DOC-01',
            'is_active' => true,
        ]);

        $docUser2 = User::factory()->create(['name' => 'د. سمير الباطني']);
        $this->consultantDoctor = Doctor::create([
            'user_id' => $docUser2->id,
            'department_id' => $dept->id,
            'specialization' => 'أمراض باطنية وقلبية',
            'qualification' => 'استشاري قلب وقسطرة',
            'license_number' => 'CARDIO-DOC-02',
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'أحمد جاسم محمد']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'phone' => '07711112222',
            'gender' => 'male',
        ]);

        $this->bed = IcuBed::create([
            'bed_number' => '5',
            'bed_type' => IcuBed::TYPE_CCU,
            'room_number' => 'CCU-BED-05',
            'floor' => 'طابق العناية المركزة',
            'status' => IcuBed::STATUS_OCCUPIED,
            'daily_price' => 175000.00,
            'is_active' => true,
        ]);

        $this->admission = IcuAdmission::create([
            'patient_id' => $this->patient->id,
            'icu_bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'admission_source' => 'emergency',
            'admission_date' => now(),
            'status' => IcuAdmission::STATUS_ACTIVE,
            'primary_diagnosis' => 'Acute Coronary Syndrome with Cardiogenic Shock',
        ]);
    }

    public function test_can_create_doctor_directive_and_pin_it_to_head_of_dossier()
    {
        $payload = [
            'note_text' => 'NPO صيام تام، الحفاظ على الضغط الشرياني الوسطي MAP > 65، ممنوع تحريك الرقبة',
            'priority' => 'stat',
            'is_pinned' => 1,
            '_active_tab' => 'ordersTabPane',
        ];

        $response = $this->actingAs($this->admin)->post(route('ccu.doctor-notes.store', $this->admission->id), $payload);

        $response->assertRedirect(route('icu.show', $this->admission->id));
        $response->assertSessionHas('active_tab', 'ordersTabPane');

        $this->assertDatabaseHas('ccu_doctor_notes', [
            'icu_admission_id' => $this->admission->id,
            'priority' => 'stat',
            'is_pinned' => true,
        ]);

        // التحقق من ظهور التوجيه المثبت في رأس الإضبارة
        $viewResponse = $this->actingAs($this->admin)->get(route('icu.show', $this->admission->id));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('توجيهات وملاحظات الطبيب الاختصاص المثبتة');
        $viewResponse->assertSee('NPO صيام تام');
    }

    public function test_can_toggle_doctor_note_pin()
    {
        $note = CcuDoctorNote::create([
            'icu_admission_id' => $this->admission->id,
            'doctor_id' => $this->doctor->id,
            'note_text' => 'مراقبة إدرار البول بالساعة',
            'priority' => 'urgent',
            'is_pinned' => true,
            'pinned_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        // إلغاء التثبيت
        $response = $this->actingAs($this->admin)->post(route('ccu.doctor-notes.toggle-pin', $note->id));
        $response->assertRedirect(route('icu.show', $this->admission->id));
        $this->assertFalse($note->fresh()->is_pinned);

        // إعادة التثبيت
        $response2 = $this->actingAs($this->admin)->post(route('ccu.doctor-notes.toggle-pin', $note->id));
        $this->assertTrue($note->fresh()->is_pinned);
    }

    public function test_can_create_medication_schedule_and_administer_quick()
    {
        $payload = [
            'type' => 'infusion',
            'title' => 'Noradrenaline Infusion',
            'dose_or_amount' => '4 mg in 50 ml D5W',
            'route' => 'تسريب وريدي مستمر (IV Infusion)',
            'interval_hours' => 'continuous',
            'infusion_rate' => '10 mcg/min',
            'instructions' => 'ضبط الجرعة للحفاظ على الضغط الوسطي MAP > 65',
            '_active_tab' => 'medicationTabPane',
        ];

        $response = $this->actingAs($this->admin)->post(route('ccu.medication-schedules.store', $this->admission->id), $payload);

        $response->assertRedirect(route('icu.show', $this->admission->id));
        $response->assertSessionHas('active_tab', 'medicationTabPane');

        $this->assertDatabaseHas('ccu_medication_schedules', [
            'icu_admission_id' => $this->admission->id,
            'title' => 'Noradrenaline Infusion',
            'infusion_rate' => '10 mcg/min',
            'is_active' => true,
        ]);

        $schedule = CcuMedicationSchedule::where('icu_admission_id', $this->admission->id)->first();
        $this->assertNotNull($schedule);

        // توثيق الإعطاء السريع بنقرة زر
        $administerResponse = $this->actingAs($this->admin)->post(route('ccu.medication-schedules.administer-quick', $schedule->id));
        $administerResponse->assertRedirect(route('icu.show', $this->admission->id));

        $this->assertNotNull($schedule->fresh()->last_administered_at);
        $this->assertEquals($this->admin->id, $schedule->fresh()->last_administered_by);
    }

    public function test_can_request_internal_consultation_and_respond()
    {
        $payload = [
            'consultation_type' => 'internal',
            'specialty' => 'باطنية وقلبية',
            'doctor_id' => $this->consultantDoctor->id,
            'priority' => 'stat',
            'reason' => 'تقييم كفاءة العضلة القلبية بعد الصدمة القلبية وبدء مضخات الدعم',
            'fee' => 25000,
            '_active_tab' => 'consultationsTabPane',
        ];

        $response = $this->actingAs($this->admin)->post(route('ccu.consultations.store', $this->admission->id), $payload);

        $response->assertRedirect(route('icu.show', $this->admission->id));
        $response->assertSessionHas('active_tab', 'consultationsTabPane');

        $this->assertDatabaseHas('ccu_consultations', [
            'icu_admission_id' => $this->admission->id,
            'consultation_type' => 'internal',
            'doctor_id' => $this->consultantDoctor->id,
            'status' => 'pending',
            'fee' => 25000,
        ]);

        $consultation = CcuConsultation::where('icu_admission_id', $this->admission->id)->first();

        // تدوين واعتماد رد الاستشاري
        $replyPayload = [
            'response' => 'تم فحص المريض وإجراء إيكو بجانب السرير. يُنصح بالاستمرار على نورادرينالين وإضافة دوبوتامين بمعدل 5 ميكروغرام/كغ/دقيقة.',
        ];

        $replyResponse = $this->actingAs($this->admin)->post(route('ccu.consultations.respond', $consultation->id), $replyPayload);
        $replyResponse->assertRedirect(route('icu.show', $this->admission->id));

        $this->assertEquals('completed', $consultation->fresh()->status);
        $this->assertNotNull($consultation->fresh()->response);
        $this->assertNotNull($consultation->fresh()->responded_at);
    }

    public function test_can_upload_and_delete_ccu_attachment()
    {
        Storage::fake('public');

        $fakeFile = UploadedFile::fake()->create('ecg_admission_strip.pdf', 1024, 'application/pdf');

        $payload = [
            'title' => 'تخطيط قلب ECG عند دخول العناية',
            'attachment_type' => 'ecg',
            'file' => $fakeFile,
            'notes' => 'ST Elevation in Anterior Leads V1-V4',
            '_active_tab' => 'attachmentsTabPane',
        ];

        $response = $this->actingAs($this->admin)->post(route('ccu.attachments.store', $this->admission->id), $payload);

        $response->assertRedirect(route('icu.show', $this->admission->id));
        $response->assertSessionHas('active_tab', 'attachmentsTabPane');

        $this->assertDatabaseHas('ccu_attachments', [
            'icu_admission_id' => $this->admission->id,
            'attachment_type' => 'ecg',
            'title' => 'تخطيط قلب ECG عند دخول العناية',
        ]);

        $attachment = CcuAttachment::where('icu_admission_id', $this->admission->id)->first();
        $this->assertNotNull($attachment);
        Storage::disk('public')->assertExists($attachment->file_path);

        // حذف المرفق
        $delResponse = $this->actingAs($this->admin)->delete(route('ccu.attachments.destroy', $attachment->id));
        $delResponse->assertRedirect(route('icu.show', $this->admission->id));

        $this->assertDatabaseMissing('ccu_attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertMissing($attachment->file_path);
    }

    public function test_icu_show_renders_all_ccu_tabs_cleanly()
    {
        $response = $this->actingAs($this->admin)->get(route('icu.show', $this->admission->id));

        $response->assertStatus(200);
        $response->assertSee('medicationTabPane');
        $response->assertSee('consultationsTabPane');
        $response->assertSee('attachmentsTabPane');
        $response->assertSee('ordersTabPane');
        $response->assertSee('جدول الأدوية والمضخات (MAR)');
        $response->assertSee('الاستشارات التخصصية');
        $response->assertSee('المرفقات وتخطيط القلب (ECG)');
    }
}
