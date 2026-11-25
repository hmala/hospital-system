<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuAttachment;
use App\Models\NicuService;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NicuAdmissionConsentTest extends TestCase
{
    use RefreshDatabase;

    protected User $nicuStaff;

    protected User $unauthorizedUser;

    protected Patient $motherPatient;

    protected Doctor $doctor;

    protected Incubator $incubator;

    protected NicuService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $perms = [
            'view incubator reservations',
            'create incubator reservations',
            'print nicu cards',
            'manage nicu attachments',
            'manage nicu nursing',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $staffRole = Role::firstOrCreate(['name' => 'nicu_staff', 'guard_name' => 'web']);
        $staffRole->givePermissionTo($perms);

        $this->nicuStaff = User::factory()->create(['name' => 'موظف استقبال الخدج', 'role' => 'nicu_staff']);
        $this->nicuStaff->assignRole('nicu_staff');

        $this->unauthorizedUser = User::factory()->create(['name' => 'مستخدم بدون صلاحية', 'role' => 'cashier']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'إدارة المستشفى',
            'license_number' => 'LIC-HOSP-101',
            'address' => 'بغداد - الحارثية شارع الكندي',
            'phone' => '07760507060',
            'email' => 'alkafaathospital@gmail.com',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الخدج',
            'type' => 'pediatrics',
            'room_number' => '601',
            'consultation_fee' => 25000,
            'is_active' => true,
        ]);

        $docUser = User::factory()->create(['name' => 'أنور خليل ابراهيم', 'role' => 'doctor']);
        $this->doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'أخصائي أطفال وخدج',
            'qualification' => 'بورد أطفال',
            'license_number' => 'LIC-NICU-101',
            'consultation_fee' => 25000,
            'type' => 'consultant',
            'is_active' => true,
        ]);

        $motherUser = User::factory()->create(['name' => 'مروى عدنان شنيف', 'role' => 'patient']);
        $this->motherPatient = Patient::create([
            'user_id' => $motherUser->id,
            'phone' => '07700000011',
            'age' => 27,
            'gender' => 'female',
        ]);

        $this->incubator = Incubator::create([
            'incubator_number' => 'INC-101',
            'room_number' => '601',
            'incubator_type' => Incubator::TYPE_NORMAL,
            'status' => Incubator::STATUS_AVAILABLE,
            'daily_fee' => 150000,
        ]);

        $this->service = NicuService::create([
            'name' => 'رعاية خديج قياسية',
            'code' => 'NICU-CONSENT-1',
            'daily_fee' => 150000,
            'is_active' => true,
        ]);
    }

    public function test_booking_without_consent_scan_succeeds_and_triggers_post_booking_prompt(): void
    {
        $response = $this->actingAs($this->nicuStaff)->post(route('incubator-reservations.store'), [
            'patient_id' => $this->motherPatient->id,
            'guardian_name' => 'علي حسن محمد',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'admission_date' => '2026-11-25',
            'admission_time' => '10:30',
            'print_consent_after_save' => '1',
        ]);

        $reservation = IncubatorReservation::firstOrFail();

        $response->assertRedirect(route('nicu.show', $reservation));
        $response->assertSessionHas('show_consent_prompt', true);
        $response->assertSessionHas('auto_open_consent_print', true);

        $this->assertSame('علي حسن محمد', $reservation->guardian_name);
        $this->assertNull($reservation->admissionConsentAttachment);
    }

    public function test_booking_with_scanned_consent_archives_admission_consent_attachment(): void
    {
        $tinyPngBase64 = 'data:image/png;base64,'.base64_encode(
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );

        $response = $this->actingAs($this->nicuStaff)->post(route('incubator-reservations.store'), [
            'patient_id' => $this->motherPatient->id,
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'admission_date' => '2026-11-25',
            'admission_time' => '11:00',
            'scanned_admission_consent' => $tinyPngBase64,
        ]);

        $reservation = IncubatorReservation::firstOrFail();
        $response->assertRedirect(route('nicu.show', $reservation));

        // يجب أن يكون قد أخذ اسم الأم كاسم ولي أمر افتراضي
        $this->assertSame('مروى عدنان شنيف', $reservation->guardian_name);

        $consentAtt = $reservation->admissionConsentAttachment;
        $this->assertNotNull($consentAtt);
        $this->assertSame(NicuAttachment::TYPE_ADMISSION_CONSENT, $consentAtt->document_type);
        Storage::disk('public')->assertExists($consentAtt->file_path);
    }

    public function test_admission_consent_print_renders_all_required_details_and_supports_guardian_override(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->motherPatient->id,
            'baby_name' => 'طفل مروى عدنان شنيف',
            'guardian_name' => 'مروى عدنان شنيف',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'admission_date' => '2026-11-25',
            'admission_time' => '09:15',
            'status' => IncubatorReservation::STATUS_ADMITTED,
        ]);

        $response = $this->actingAs($this->nicuStaff)
            ->get(route('nicu.print.admission-consent', $reservation));

        $response->assertOk();
        $response->assertSee('مستشفى الكفاءات الأهلي');
        $response->assertSee('Al-Kafaat Hospital');
        $response->assertSee('تعهد خطي بموافقة ولي أمر المريض على التدخلات العلاجية لمرضى دخول الخدج');
        $response->assertSee('مروى عدنان شنيف');
        $response->assertSee('الدكتور أنور خليل ابراهيم');
        $response->assertSee('CPAP, Chest Tube, Ventilator');
        $response->assertSee('25/11/2026');
        $response->assertSee('07760507060');
        $response->assertSee('07860507060');

        // دعم تمرير اسم الأب عند الطباعة
        $overrideResponse = $this->actingAs($this->nicuStaff)
            ->get(route('nicu.print.admission-consent', ['reservation' => $reservation->id, 'guardian_name' => 'كرار محمد']));
        $overrideResponse->assertOk();
        $overrideResponse->assertSee('كرار محمد');
    }

    public function test_optional_consent_can_be_uploaded_or_scanned_later_from_patient_chart(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->motherPatient->id,
            'baby_name' => 'طفل مروى عدنان شنيف',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'admission_date' => '2026-11-25',
            'admission_time' => '09:15',
            'status' => IncubatorReservation::STATUS_ADMITTED,
        ]);

        // قبل الرفع: صفحة الطفل تعرض زر الطباعة وزر الرفع الاختياري
        $chartBefore = $this->actingAs($this->nicuStaff)->get(route('nicu.show', $reservation));
        $chartBefore->assertOk();
        $chartBefore->assertSee('طباعة تعهد الخدج');
        $chartBefore->assertSee('مسح/رفع التعهد (اختياري)');

        // رفع ملف موقع مع تحديث اسم الأب
        $file = UploadedFile::fake()->create('signed_consent.pdf', 120, 'application/pdf');
        $uploadRes = $this->actingAs($this->nicuStaff)->post(route('nicu.admission-consent.upload', $reservation), [
            'guardian_name' => 'علي كاظم',
            'consent_file' => $file,
        ]);

        $uploadRes->assertRedirect();
        $uploadRes->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame('علي كاظم', $reservation->guardian_name);
        $this->assertNotNull($reservation->admissionConsentAttachment);
        Storage::disk('public')->assertExists($reservation->admissionConsentAttachment->file_path);

        // بعد الرفع: تظهر شارة الأرشفة
        $chartAfter = $this->actingAs($this->nicuStaff)->get(route('nicu.show', $reservation));
        $chartAfter->assertOk();
        $chartAfter->assertSee('التعهد مؤرشف');

        // عند خروج الطفل: يمنع الرفع الجديد بينما تبقى الطباعة متاحة
        $reservation->update(['status' => IncubatorReservation::STATUS_DISCHARGED]);

        $tinyPngBase64 = 'data:image/png;base64,'.base64_encode(
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );
        $blockedUpload = $this->actingAs($this->nicuStaff)->post(route('nicu.admission-consent.upload', $reservation), [
            'scanned_consent' => $tinyPngBase64,
        ]);
        $blockedUpload->assertSessionHas('error');

        $this->actingAs($this->nicuStaff)
            ->get(route('nicu.print.admission-consent', $reservation))
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_print_or_upload_admission_consent(): void
    {
        $reservation = IncubatorReservation::create([
            'patient_id' => $this->motherPatient->id,
            'baby_name' => 'طفل مروى عدنان شنيف',
            'incubator_id' => $this->incubator->id,
            'nicu_service_id' => $this->service->id,
            'admission_date' => '2026-11-25',
            'admission_time' => '09:15',
            'status' => IncubatorReservation::STATUS_ADMITTED,
        ]);

        $this->actingAs($this->unauthorizedUser)
            ->get(route('nicu.print.admission-consent', $reservation))
            ->assertForbidden();

        $this->actingAs($this->unauthorizedUser)
            ->post(route('nicu.admission-consent.upload', $reservation), [
                'consent_file' => UploadedFile::fake()->image('consent.jpg'),
            ])
            ->assertForbidden();
    }
}
