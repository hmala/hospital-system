<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Room;
use App\Models\SurgicalOperation;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\HospitalSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryCreateModernWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_surgery_create_page_renders_patient_executive_banner_and_split_workspace(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(HospitalSeeder::class);
        $this->seed(DepartmentSeeder::class);

        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        $patientUser = User::factory()->create([
            'name' => 'منير سمير يوسف',
            'phone' => '07701234567',
        ]);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'ذكر',
            'birth_date' => now()->subYears(45)->toDateString(),
            'insurance_type' => 'hi',
            'insurance_card_no' => 'HI-998877',
        ]);

        $department = Department::first();

        $doctorUser = User::factory()->create(['name' => 'د. فراس البياتي']);
        $doctor = new Doctor;
        $doctor->user_id = $doctorUser->id;
        $doctor->department_id = $department ? $department->id : 1;
        $doctor->phone = '07709876543';
        $doctor->specialization = 'جراحة عامة';
        $doctor->qualification = 'MBBS';
        $doctor->license_number = 'LIC-999';
        $doctor->type = 'surgeon';
        $doctor->consultation_fee = 25000;
        $doctor->is_active = true;
        $doctor->save();

        Room::create([
            'room_number' => '101',
            'floor' => 'الطابق الأول',
            'room_type' => 'regular',
            'room_purpose' => 'beds',
            'daily_fee' => 0,
            'status' => 'available',
            'is_active' => true,
        ]);

        SurgicalOperation::create([
            'name' => 'استئصال المرارة بالناظور',
            'category' => 'جراحة المنظار',
            'fee' => 1250000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminUser)->get(route('surgeries.create', ['patient_id' => $patient->id]));

        $response->assertStatus(200);
        $response->assertSee('<form action="'.route('surgeries.store').'" method="POST" id="surgeryForm" enctype="multipart/form-data">', false);
        $response->assertSee('منير سمير يوسف');
        $response->assertSee('HI-998877');
        $response->assertSee('هيئة الضمان الصحي الوطني');
        $response->assertSee('بيانات المريض، الفريق الطبي، والمسار');
        $response->assertSee('جدول العمليات الجراحية المطلوبة');
        $response->assertSee('اختيار الغرفة والإقامة السريرية');
        $response->assertSee('تأكيد وحجز العملية الجراحية');
        $response->assertDontSee('لوحة الاعتماد والملخص');

        // Verify room selection state is declared before early #patient_id change trigger (no JS TDZ crash)
        $html = $response->getContent();
        $roomDeclPos = strpos($html, "let selectedRoomType = '';");
        $patientTriggerPos = strpos($html, "$('#patient_id').trigger('change');");
        $this->assertNotFalse($roomDeclPos);
        $this->assertNotFalse($patientTriggerPos);
        $this->assertLessThan($patientTriggerPos, $roomDeclPos);

        // Verify /surgeries/create without patient_id renders cleanly too
        $this->actingAs($adminUser)->get(route('surgeries.create'))->assertStatus(200);

        // اختبار شاشة التعديل للعمليات
        $surgery = \App\Models\Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department ? $department->id : 1,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'surgery_type' => 'استئصال المرارة بالناظور',
            'surgery_category' => 'جراحة المنظار',
            'surgery_fee' => 1250000,
            'room_id' => 1,
            'insurance_type' => 'hi',
            'status' => 'pending',
            'referring_doctor_name' => 'د. فراس البياتي',
        ]);

        $editResponse = $this->actingAs($adminUser)->get(route('surgeries.edit', $surgery->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('منير سمير يوسف');
        $editResponse->assertSee('تعديل حجز العملية الجراحية');
        $editResponse->assertSee('جدول العمليات الجراحية المطلوبة');
        $editResponse->assertSee('اختيار الغرفة والإقامة السريرية');
        $editResponse->assertSee('حفظ وتحديث حجز العملية الجراحية');
    }
}
