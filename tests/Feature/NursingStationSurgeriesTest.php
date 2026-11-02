<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgicalOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NursingStationSurgeriesTest extends TestCase
{
    use RefreshDatabase;

    protected Department $dept;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view nursing station', 'guard_name' => 'web']);

        $hospital = Hospital::firstOrCreate(
            ['name' => 'مستشفى الفرات'],
            [
                'owner_name' => 'د. صاحب المستشفى',
                'phone' => '07700000000',
                'address' => 'بغداد',
                'license_number' => 'HOSP-123',
            ]
        );
        $this->dept = Department::firstOrCreate(
            ['name' => 'قسم الجراحة العامة'],
            [
                'hospital_id' => $hospital->id,
                'type' => 'surgery',
                'room_number' => '101',
                'consultation_fee' => 25000,
                'working_hours_start' => '08:00',
                'working_hours_end' => '16:00',
            ]
        );
    }

    public function test_nurse_maryam_sees_booked_surgery_in_room_302_in_pre_op_tab(): void
    {
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');
        $nurseMaryam->givePermissionTo('view nursing station');

        $room302 = Room::create([
            'room_number' => '302',
            'room_type' => 'vip',
            'floor' => 'الطابق الثالث',
            'daily_fee' => 100000,
            'status' => 'occupied',
            'beds_count' => 1,
            'has_bathroom' => true,
            'has_tv' => true,
            'has_ac' => true,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'علي حسين الفت']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '12345678901234',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. جراح الاختبار']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'specialization' => 'جراحة عامة',
            'department_id' => $this->dept->id,
            'qualification' => 'MBChB',
            'license_number' => 'DOC-'.uniqid(),
        ]);

        $operation = SurgicalOperation::create([
            'name' => 'استئصال المرارة بالمنظار',
            'code' => 'SURG-001',
            'category' => 'جراحة عامة',
            'fee' => 500000,
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->dept->id,
            'room_id' => $room302->id,
            'surgery_type' => $operation->name,
            'surgery_category' => 'جراحة عامة',
            'surgical_operation_id' => $operation->id,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'referring_doctor_name' => 'د. محول',
            'custom_surgery_fee' => 500000,
            'status' => 'scheduled',
            'expected_stay_days' => 1,
        ]);

        $response = $this->actingAs($nurseMaryam)->get(route('nursing-station.index'));

        $response->assertStatus(200);
        $response->assertSee('علي حسين الفت');
        $response->assertSee('غرفة 302');
        $response->assertSee('الطابق الثالث');
        $this->assertTrue($response->viewData('preOpSurgeries')->contains('id', $surgery->id));
    }

    public function test_surgery_in_waiting_status_still_appears_in_pre_op_tab(): void
    {
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');
        $nurseMaryam->givePermissionTo('view nursing station');

        $room302 = Room::create([
            'room_number' => '302',
            'room_type' => 'vip',
            'floor' => 'الطابق الثالث',
            'daily_fee' => 100000,
            'status' => 'occupied',
            'beds_count' => 1,
            'has_bathroom' => true,
            'has_tv' => true,
            'has_ac' => true,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'علي حسين الفت']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '12345678901235',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. جراح']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'specialization' => 'جراحة عامة',
            'department_id' => $this->dept->id,
            'qualification' => 'MBChB',
            'license_number' => 'DOC-'.uniqid(),
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->dept->id,
            'room_id' => $room302->id,
            'surgery_type' => 'عملية جراحية',
            'surgery_category' => 'جراحة عامة',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'referring_doctor_name' => 'د. محول',
            'custom_surgery_fee' => 500000,
            'status' => 'waiting', // checked-in on waiting page
            'expected_stay_days' => 1,
        ]);

        $response = $this->actingAs($nurseMaryam)->get(route('nursing-station.index'));

        $response->assertStatus(200);
        $response->assertSee('علي حسين الفت');
        $response->assertSee('بانتظار الصالة');
        $this->assertTrue($response->viewData('preOpSurgeries')->contains('id', $surgery->id));
    }

    public function test_surgery_moves_to_post_op_when_operation_theater_is_completed(): void
    {
        $subFloor3 = Location::create(['name' => 'ردهة الطابق الثالث', 'type' => 'sub']);

        $nurseMaryam = User::factory()->create([
            'name' => 'مريم الممرضة',
            'location_id' => $subFloor3->id,
        ]);
        $nurseMaryam->assignRole('nurse');
        $nurseMaryam->givePermissionTo('view nursing station');

        $room302 = Room::create([
            'room_number' => '302',
            'room_type' => 'vip',
            'floor' => 'الطابق الثالث',
            'daily_fee' => 100000,
            'status' => 'occupied',
            'beds_count' => 1,
            'has_bathroom' => true,
            'has_tv' => true,
            'has_ac' => true,
            'is_active' => true,
        ]);

        $patientUser = User::factory()->create(['name' => 'علي حسين الفت']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '12345678901236',
        ]);

        $doctorUser = User::factory()->create(['name' => 'د. جراح']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'specialization' => 'جراحة عامة',
            'department_id' => $this->dept->id,
            'qualification' => 'MBChB',
            'license_number' => 'DOC-'.uniqid(),
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->dept->id,
            'room_id' => $room302->id,
            'surgery_type' => 'عملية جراحية',
            'surgery_category' => 'جراحة عامة',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now(),
            'referring_doctor_name' => 'د. محول',
            'custom_surgery_fee' => 500000,
            'status' => 'in_progress',
            'expected_stay_days' => 1,
        ]);

        // Complete operation theater station
        $surgery->operationTheaterStation()->create([
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($nurseMaryam)->get(route('nursing-station.index'));

        $response->assertStatus(200);
        $this->assertFalse($response->viewData('preOpSurgeries')->contains('id', $surgery->id));
        $this->assertTrue($response->viewData('postOpSurgeries')->contains('id', $surgery->id));
        $response->assertSee('خرج من صالة العمليات');
    }
}
