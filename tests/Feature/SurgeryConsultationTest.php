<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\OperationTheater;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgeryConsultation;
use App\Models\SurgeryTabPermission;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\HospitalSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryConsultationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $doctorUser;

    protected Doctor $doctor;

    protected User $consultantUser;

    protected Doctor $consultantDoctor;

    protected Surgery $surgery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(HospitalSeeder::class);
        $this->seed(DepartmentSeeder::class);
        SurgeryTabPermission::seedDefaults();

        $department = Department::first();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->doctorUser = User::factory()->create(['name' => 'د. أحمد جراح']);
        $this->doctorUser->assignRole('doctor');
        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $department->id,
            'specialization' => 'General Surgery',
            'qualification' => 'FRCS',
            'license_number' => 'LIC-SURG-'.uniqid(),
            'type' => 'surgeon',
            'is_active' => true,
        ]);

        $this->consultantUser = User::factory()->create(['name' => 'د. محمود استشاري قلب']);
        $this->consultantUser->assignRole('doctor');
        $this->consultantDoctor = Doctor::create([
            'user_id' => $this->consultantUser->id,
            'department_id' => $department->id,
            'specialization' => 'Cardiology',
            'qualification' => 'MD, PhD',
            'license_number' => 'LIC-CONS-'.uniqid(),
            'type' => 'consultant',
            'consultation_fee' => 35000,
            'is_available_today' => true,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'مريض تجريبي']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 45,
            'gender' => 'male',
        ]);

        $room = Room::create([
            'room_number' => 'SURG-401',
            'type' => 'surgery',
            'status' => 'available',
            'price_per_day' => 120,
        ]);

        $theater = OperationTheater::create([
            'name' => 'صالة الجراحة الكبرى',
            'code' => 'OR-MAJ-1',
            'is_active' => true,
        ]);

        $this->surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $department->id,
            'room_id' => $room->id,
            'operation_theater_id' => $theater->id,
            'surgery_name' => 'عملية استئصال المرارة بالمنظار',
            'surgery_type' => 'major',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'room_checkin_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    public function test_clinical_station_consultations_tab_renders_properly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('clinical-station.show', [
            'surgery' => $this->surgery->id,
            'tab' => 'consultations',
        ]));

        $response->assertStatus(200);
        $response->assertSee('طلبات الاستشارات الطبية التخصصية');
        $response->assertSee('طلب استشارة طبية جديدة');
        $response->assertSee('consultation_doctor_id', false);
        $response->assertSee('consultation_doctor_name', false);
        $response->assertSee('د. محمود استشاري قلب');
        $response->assertSee('Cardiology');
        $response->assertSee('35,000 د.ع');
    }

    public function test_can_store_consultation_request_successfully(): void
    {
        $response = $this->actingAs($this->doctorUser)->post(route('clinical-station.consultations.store', $this->surgery), [
            'doctor_id' => $this->consultantDoctor->id,
            'specialty' => 'Cardiology',
            'reason' => 'تقييم كفاءة القلب قبل التخدير العام للمريض لوجود خفقان مفاجئ',
            'priority' => 'urgent',
            'fee' => 35000,
        ]);

        $response->assertRedirect(route('clinical-station.show', ['surgery' => $this->surgery->id, 'tab' => 'consultations']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('surgery_consultations', [
            'surgery_id' => $this->surgery->id,
            'doctor_id' => $this->consultantDoctor->id,
            'specialty' => 'Cardiology',
            'priority' => 'urgent',
            'fee' => 35000,
            'status' => 'pending',
            'requested_by' => $this->doctorUser->id,
        ]);
    }

    public function test_consultant_can_respond_to_consultation(): void
    {
        $consultation = SurgeryConsultation::create([
            'surgery_id' => $this->surgery->id,
            'doctor_id' => $this->consultantDoctor->id,
            'specialty' => 'Cardiology',
            'reason' => 'فحص وظائف القلب والتخطيط',
            'priority' => 'stat',
            'fee' => 35000,
            'status' => 'pending',
            'requested_by' => $this->doctorUser->id,
        ]);

        $response = $this->actingAs($this->consultantUser)->post(route('clinical-station.consultations.respond', [
            'surgery' => $this->surgery->id,
            'consultation' => $consultation->id,
        ]), [
            'consultant_response' => 'تم فحص المريض وعمل تخطيط قلب، الحالة مستقرة وتسمح بإجراء التداخل الجراحي تحت مراقبة العلامات الحيوية.',
            'consultant_recommendations' => 'يوصى باستمرار دواء البيتا بلوكر وتجنب انخفاض ضغط الدم الحاد أثناء العملية.',
        ]);

        $response->assertRedirect(route('clinical-station.show', ['surgery' => $this->surgery->id, 'tab' => 'consultations']));
        $response->assertSessionHas('success');

        $consultation->refresh();
        $this->assertEquals('completed', $consultation->status);
        $this->assertStringContainsString('تم فحص المريض وعمل تخطيط قلب', $consultation->consultant_response);
        $this->assertStringContainsString('يوصى باستمرار دواء البيتا بلوكر', $consultation->consultant_recommendations);
        $this->assertEquals($this->consultantUser->id, $consultation->responded_by);
        $this->assertNotNull($consultation->responded_at);
    }

    public function test_can_cancel_pending_consultation(): void
    {
        $consultation = SurgeryConsultation::create([
            'surgery_id' => $this->surgery->id,
            'doctor_id' => $this->consultantDoctor->id,
            'specialty' => 'Cardiology',
            'reason' => 'استشارة ملغاة تجريبية',
            'priority' => 'routine',
            'fee' => 0,
            'status' => 'pending',
            'requested_by' => $this->doctorUser->id,
        ]);

        $response = $this->actingAs($this->doctorUser)->delete(route('clinical-station.consultations.cancel', [
            'surgery' => $this->surgery->id,
            'consultation' => $consultation->id,
        ]));

        $response->assertRedirect(route('clinical-station.show', ['surgery' => $this->surgery->id, 'tab' => 'consultations']));
        $response->assertSessionHas('success');

        $consultation->refresh();
        $this->assertEquals('cancelled', $consultation->status);
    }

    public function test_cannot_cancel_already_completed_consultation(): void
    {
        $consultation = SurgeryConsultation::create([
            'surgery_id' => $this->surgery->id,
            'doctor_id' => $this->consultantDoctor->id,
            'specialty' => 'Cardiology',
            'reason' => 'استشارة منتهية',
            'priority' => 'routine',
            'fee' => 0,
            'status' => 'completed',
            'consultant_response' => 'تم الفحص',
            'requested_by' => $this->doctorUser->id,
            'responded_by' => $this->consultantUser->id,
            'responded_at' => now(),
        ]);

        $response = $this->actingAs($this->doctorUser)->delete(route('clinical-station.consultations.cancel', [
            'surgery' => $this->surgery->id,
            'consultation' => $consultation->id,
        ]));

        $response->assertRedirect(route('clinical-station.show', ['surgery' => $this->surgery->id, 'tab' => 'consultations']));
        $response->assertSessionHas('error');

        $consultation->refresh();
        $this->assertEquals('completed', $consultation->status);
    }

    public function test_surgery_show_page_renders_consultations_tab(): void
    {
        SurgeryConsultation::create([
            'surgery_id' => $this->surgery->id,
            'doctor_id' => $this->consultantDoctor->id,
            'specialty' => 'Cardiology',
            'reason' => 'استشارة لعرضها في صفحة تفاصيل العملية',
            'priority' => 'urgent',
            'fee' => 35000,
            'status' => 'completed',
            'consultant_response' => 'تم المعاينة بنجاح ولا يوجد مانع جراحي.',
            'requested_by' => $this->doctorUser->id,
            'responded_by' => $this->consultantUser->id,
            'responded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('surgeries.show', $this->surgery));

        $response->assertStatus(200);
        $response->assertSee('الاستشارات الطبية');
        $response->assertSee('pane-detail_consultations', false);
        $response->assertSee('د. محمود استشاري قلب');
        $response->assertSee('تم المعاينة بنجاح ولا يوجد مانع جراحي.');
    }
}
