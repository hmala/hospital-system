<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Emergency;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $doctorUser;
    protected $doctor;
    protected $department;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->doctorUser = User::factory()->create(['name' => 'د. حسام عادل']);
        $this->doctorUser->assignRole('doctor');

        $hospitalId = DB::table('hospitals')->insertGetId([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deptId = DB::table('departments')->insertGetId([
            'hospital_id' => $hospitalId,
            'name' => 'قسم الطوارئ',
            'type' => 'emergency',
            'room_number' => 'ER-1',
            'consultation_fee' => 10000,
            'working_hours_start' => '00:00:00',
            'working_hours_end' => '23:59:59',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $deptId,
            'type' => 'emergency',
            'phone' => '07712345678',
            'specialization' => 'طب طوارئ',
            'qualification' => 'بكالوريوس طب وجراحة عامة',
            'license_number' => 'DOC-' . rand(1000, 9999),
            'consultation_fee' => 10000,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'working_days' => ['sunday', 'monday'],
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        // تنظيف أي توقيع تم إنشاؤه أثناء الاختبار
        $filePath = public_path('images/signatures/doctor_' . $this->doctor->id . '.png');
        if (File::exists($filePath)) {
            @unlink($filePath);
        }

        parent::tearDown();
    }

    /**
     * Create a dummy image simulating a dark pen stroke on white paper
     */
    protected function createDummySignatureImage(): UploadedFile
    {
        $im = imagecreatetruecolor(100, 50);
        $white = imagecolorallocate($im, 255, 255, 255);
        $blue = imagecolorallocate($im, 10, 40, 180);

        imagefilledrectangle($im, 0, 0, 100, 50, $white);
        // Draw simulated signature stroke
        imageline($im, 10, 25, 90, 25, $blue);
        imageline($im, 20, 15, 80, 35, $blue);

        $tempPath = tempnam(sys_get_temp_dir(), 'sig_test_') . '.jpg';
        imagejpeg($im, $tempPath, 90);
        imagedestroy($im);

        return new UploadedFile($tempPath, 'signature.jpg', 'image/jpeg', null, true);
    }

    public function test_can_upload_and_auto_extract_signature_via_doctor_update(): void
    {
        $dummyImage = $this->createDummySignatureImage();

        $response = $this->actingAs($this->admin)->put(route('doctors.update', $this->doctor), [
            'name' => 'د. حسام عادل',
            'email' => $this->doctorUser->email,
            'phone' => '07712345678',
            'department_id' => $this->doctor->department_id,
            'type' => 'emergency',
            'specialization' => 'طب طوارئ',
            'consultation_fee' => 10000,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'working_days' => ['sunday', 'monday'],
            'is_active' => 1,
            'signature_file' => $dummyImage,
            'signature_threshold' => 200,
        ]);

        $response->assertRedirect(route('doctors.index'));
        $response->assertSessionHas('success');

        $this->doctor->refresh();
        $this->assertNotNull($this->doctor->signature);
        $this->assertStringContainsString('doctor_' . $this->doctor->id . '.png', $this->doctor->signature);
        $this->assertTrue(file_exists(public_path($this->doctor->signature)));
    }

    public function test_ajax_upload_signature_endpoint_returns_json_and_saves_signature(): void
    {
        $dummyImage = $this->createDummySignatureImage();

        $response = $this->actingAs($this->admin)->postJson(route('doctors.upload-signature', $this->doctor), [
            'signature_file' => $dummyImage,
            'signature_threshold' => 200,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->doctor->refresh();
        $this->assertNotNull($this->doctor->signature);
        $this->assertTrue(file_exists(public_path($this->doctor->signature)));
    }

    public function test_emergency_report_displays_doctor_specific_signature(): void
    {
        // 1. Assign doctor's signature
        $dummyImage = $this->createDummySignatureImage();
        \App\Services\SignatureProcessingService::processAndSave($dummyImage, $this->doctor->id);
        $this->doctor->update(['signature' => 'images/signatures/doctor_' . $this->doctor->id . '.png']);

        // 2. Create emergency case with this doctor
        $patientUser = User::factory()->create(['name' => 'علي قاسم']);
        $patient = Patient::create(['user_id' => $patientUser->id]);

        $emergency = Emergency::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'admission_time' => now(),
            'emergency_type' => 'medical',
            'priority' => 'urgent',
            'status' => 'discharged',
            'discharge_type' => 'recovered',
            'diagnosis' => 'نزلة معوية حادة',
            'symptoms' => 'ألم حاد في البطن وغثيان',
        ]);

        // 3. View medical report
        $response = $this->actingAs($this->admin)->get(route('emergency.medical-report', $emergency));
        $response->assertStatus(200);
        $response->assertSee('د. حسام عادل');
        $response->assertSee('images/signatures/doctor_' . $this->doctor->id . '.png');
    }

    public function test_can_upload_and_auto_extract_signature_when_creating_new_doctor(): void
    {
        $dummyImage = $this->createDummySignatureImage();

        $response = $this->actingAs($this->admin)->post(route('doctors.store'), [
            'name' => 'د. سمير كمال',
            'email' => 'samir.kamal.' . uniqid() . '@example.com',
            'phone' => '07709876543',
            'department_id' => $this->doctor->department_id,
            'type' => 'emergency',
            'specialization' => 'طب طوارئ وإنعاش',
            'consultation_fee' => 15000,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'working_days' => ['sunday', 'monday', 'tuesday'],
            'signature_file' => $dummyImage,
            'signature_threshold' => 200,
        ]);

        $response->assertRedirect(route('doctors.index'));
        $response->assertSessionHas('success');

        $createdDoctor = Doctor::whereHas('user', function($q) {
            $q->where('name', 'د. سمير كمال');
        })->first();

        $this->assertNotNull($createdDoctor);
        $this->assertNotNull($createdDoctor->signature);
        $this->assertStringContainsString('doctor_' . $createdDoctor->id . '.png', $createdDoctor->signature);
        $this->assertTrue(file_exists(public_path($createdDoctor->signature)));

        // Clean up created file
        if (file_exists(public_path($createdDoctor->signature))) {
            @unlink(public_path($createdDoctor->signature));
        }
    }
}

