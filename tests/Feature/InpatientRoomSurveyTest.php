<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\InpatientRoomSurvey;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InpatientRoomSurveyTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);

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
    }

    protected function createPatient(string $name = 'مريض تجريبي'): Patient
    {
        $user = User::factory()->create(['name' => $name]);

        return Patient::create([
            'user_id' => $user->id,
            'age' => 30,
            'gender' => 'male',
        ]);
    }

    protected function createSurgery(Patient $patient, ?Room $room = null): Surgery
    {
        return Surgery::create([
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'room_id' => $room?->id,
            'surgery_type' => 'جراحة عامة',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'scheduled',
            'surgery_fee' => 100000,
        ]);
    }

    public function test_public_user_can_view_survey_by_uuid(): void
    {
        $patient = $this->createPatient('سارة أحمد');
        $room = Room::create(['room_number' => '305', 'type' => 'vip', 'floor' => 'الطابق الثالث']);
        $surgery = $this->createSurgery($patient, $room);

        $survey = InpatientRoomSurvey::create([
            'surgery_id' => $surgery->id,
            'patient_id' => $patient->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);

        $response = $this->get(route('inpatient-surveys.public.show', ['uuid' => $survey->uuid]));

        $response->assertStatus(200);
        $response->assertSee('مستشفى الكفاءات الأهلي');
        $response->assertSee('استبيان تقييم غرفة الرقود');
        $response->assertSee('305');
        $response->assertSee('النظافة العامة للغرفة');
    }

    public function test_patient_can_submit_survey_from_mobile_and_score_is_calculated(): void
    {
        $patient = $this->createPatient('علي حسن');
        $room = Room::create(['room_number' => '402', 'type' => 'regular', 'floor' => 'الطابق الرابع']);
        $surgery = $this->createSurgery($patient, $room);

        $survey = InpatientRoomSurvey::create([
            'surgery_id' => $surgery->id,
            'patient_id' => $patient->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);

        $payload = [
            'cleanliness_room' => 5,
            'cleanliness_bathroom' => 5,
            'temperature_ac' => 4,
            'lighting' => 5,
            'quietness_privacy' => 4,
            'bed_comfort' => 5,
            'control_panel' => 5,
            'storage_space' => 4,
            'furniture_safety' => 5,
            'entertainment' => 4,
            'doctor_communication' => 5,
            'room_notes' => 'الغرفة نظيفة جداً والخدمة راقية',
            'top_three_improvements' => ['تحديث قائمة التلفاز', 'زيادة وسائد السرير'],
            'patient_suggestions' => 'استمروا بهذا الأداء الممتاز',
            'recommend_score_nps' => 10,
        ];

        $response = $this->post(route('inpatient-surveys.public.submit', ['uuid' => $survey->uuid]), $payload);

        $response->assertRedirect(route('inpatient-surveys.public.thank-you', ['uuid' => $survey->uuid]));

        $survey->refresh();
        $this->assertEquals('completed', $survey->status);
        $this->assertEquals('patient_qr', $survey->submitted_via);
        $this->assertNotNull($survey->completed_at);
        $this->assertGreaterThan(85.0, $survey->overall_score);
        $this->assertEquals(10, $survey->recommend_score_nps);
        $this->assertEquals('الغرفة نظيفة جداً والخدمة راقية', $survey->room_notes);
    }

    public function test_already_completed_survey_renders_thank_you_view(): void
    {
        $patient = $this->createPatient();
        $survey = InpatientRoomSurvey::create([
            'patient_id' => $patient->id,
            'status' => 'completed',
            'overall_score' => 95.0,
            'completed_at' => now(),
        ]);

        $response = $this->get(route('inpatient-surveys.public.show', ['uuid' => $survey->uuid]));

        $response->assertStatus(200);
        $response->assertSee('تم استلام تقييمك مسبقاً');
    }

    public function test_nurse_can_manually_submit_survey_from_patient_chart(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $patient = $this->createPatient();
        $room = Room::create(['room_number' => '201', 'type' => 'vip', 'floor' => 'الطابق الثاني']);
        $surgery = $this->createSurgery($patient, $room);

        $payload = [
            'cleanliness_room' => 5,
            'cleanliness_bathroom' => 4,
            'temperature_ac' => 5,
            'lighting' => 4,
            'quietness_privacy' => 5,
            'bed_comfort' => 4,
            'control_panel' => 5,
            'storage_space' => 5,
            'furniture_safety' => 4,
            'entertainment' => 4,
            'doctor_communication' => 5,
            'patient_suggestions' => 'المريض يشيد بالهدوء وسرعة استجابة التمريض',
        ];

        $response = $this->actingAs($nurse)
            ->post(route('inpatient-surveys.manual.store', $surgery), $payload);

        $response->assertSessionHas('success');

        $savedSurvey = InpatientRoomSurvey::where('surgery_id', $surgery->id)->first();
        $this->assertNotNull($savedSurvey);
        $this->assertEquals('completed', $savedSurvey->status);
        $this->assertEquals('staff_manual', $savedSurvey->submitted_via);
        $this->assertEquals($nurse->id, $savedSurvey->submitted_by_user_id);
    }

    public function test_staff_can_view_printable_qr_slip(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $patient = $this->createPatient('محمود كريم');
        $room = Room::create(['room_number' => '108', 'type' => 'regular', 'floor' => 'الطابق الأول']);
        $surgery = $this->createSurgery($patient, $room);

        $response = $this->actingAs($nurse)
            ->get(route('inpatient-surveys.print-qr', $surgery));

        $response->assertStatus(200);
        $response->assertSee('مستشفى الكفاءات الأهلي');
        $response->assertSee('استبيان تقييم غرفة الرقود');
        $response->assertSee('محمود كريم');
        $response->assertSee('108');
        $response->assertSee('create-qr-code');
    }

    public function test_staff_can_view_and_print_a4_survey_report(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $patient = $this->createPatient('زينب كاظم');
        $room = Room::create(['room_number' => '501', 'type' => 'vip', 'floor' => 'الطابق الخامس']);
        $surgery = $this->createSurgery($patient, $room);

        $survey = InpatientRoomSurvey::create([
            'surgery_id' => $surgery->id,
            'patient_id' => $patient->id,
            'room_id' => $room->id,
            'status' => 'completed',
            'cleanliness_room' => 5,
            'overall_score' => 96.0,
            'room_notes' => 'خدمة ممتازة جداً',
        ]);

        $response = $this->actingAs($nurse)
            ->get(route('inpatient-surveys.print-a4', $surgery));

        $response->assertStatus(200);
        $response->assertSee('مستشفى الكفاءات الأهلي');
        $response->assertSee('استبيان تقييم غرفة الرقود في المستشفى');
        $response->assertSee('القسم الأول: المعلومات الأساسية');
        $response->assertSee('القسم الثاني: تقييم غرفة الرقود');
        $response->assertSee('القسم الثالث: أسئلة مفتوحة لمزيد من التفاصيل');
        $response->assertSee('تقييم الفريق الطبي (زيارة الطبيب المعالج)');
        $response->assertSee('خدمة ممتازة جداً');
        $response->assertSee('501');
    }

    public function test_staff_can_view_and_print_blank_a4_survey_report(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $response = $this->actingAs($nurse)
            ->get(route('inpatient-surveys.print-blank'));

        $response->assertStatus(200);
        $response->assertSee('مستشفى الكفاءات الأهلي');
        $response->assertSee('استبيان تقييم غرفة الرقود في المستشفى');
        $response->assertSee('القسم الأول: المعلومات الأساسية');
        $response->assertSee('القسم الثاني: تقييم غرفة الرقود');
        $response->assertSee('القسم الثالث: أسئلة مفتوحة لمزيد من التفاصيل');
        $response->assertSee('تقييم الفريق الطبي (زيارة الطبيب المعالج)');
    }

    public function test_only_admin_can_reset_completed_survey(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $patient = $this->createPatient('حسام عبد الستار');
        $room = Room::create(['room_number' => '102', 'type' => 'regular', 'floor' => 'الطابق الأول']);
        $surgery = $this->createSurgery($patient, $room);

        $survey = InpatientRoomSurvey::create([
            'surgery_id' => $surgery->id,
            'patient_id' => $patient->id,
            'room_id' => $room->id,
            'status' => 'completed',
            'cleanliness_room' => 5,
            'overall_score' => 95.0,
            'completed_at' => now(),
        ]);

        // 1. Nurse is forbidden (403)
        $nurseResponse = $this->actingAs($nurse)
            ->post(route('inpatient-surveys.reset', $surgery));
        $nurseResponse->assertStatus(403);

        // 2. Admin can reset successfully
        $adminResponse = $this->actingAs($admin)
            ->post(route('inpatient-surveys.reset', $surgery));
        $adminResponse->assertSessionHas('success');

        $survey->refresh();
        $this->assertEquals('pending', $survey->status);
        $this->assertNull($survey->overall_score);
        $this->assertNull($survey->completed_at);
        $this->assertNull($survey->cleanliness_room);
    }
}
