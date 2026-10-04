<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Patient;
use App\Models\RadiologyType;
use App\Models\Request as MedicalRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UltrasoundInquiryBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء الأدوار المطلوبة
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'radiology_ultrasound', 'guard_name' => 'web']);

        // إنشاء المستشفى وقسم الاستعلامات
        $hospital = \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        Department::firstOrCreate(
            ['name' => 'الاستعلامات'],
            ['hospital_id' => $hospital->id, 'type' => 'other', 'room_number' => 'REC-1', 'consultation_fee' => 0, 'working_hours_start' => '08:00:00', 'working_hours_end' => '17:00:00', 'is_active' => true]
        );

        // إنشاء نوع الخدمة
        ServiceType::firstOrCreate(
            ['name' => 'radiology'],
            ['label' => 'أشعة وسونار', 'icon' => 'fa-x-ray', 'color' => 'info', 'is_active' => true, 'order' => 2]
        );
    }

    public function test_can_book_ultrasound_with_specific_scan_type_and_sets_pending_payment()
    {
        // 1. إنشاء موظف استعلامات
        $receptionist = User::factory()->create();
        $receptionist->assignRole('receptionist');
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'inquiry.create.radiology.ultrasound', 'guard_name' => 'web']);
        $receptionist->givePermissionTo('inquiry.create.radiology.ultrasound');

        // 2. موظف سونار
        $sonarStaff = User::factory()->create(['name' => 'د. أحمد السونار']);
        $sonarStaff->assignRole('radiology_ultrasound');

        // 3. نوع فحص السونار
        $sonarType = RadiologyType::create([
            'main_category' => 'سونار',
            'subcategory' => 'سونار',
            'name' => 'سونار البطن والحوض',
            'code' => 'US-031',
            'base_price' => 50000,
            'estimated_duration' => 20,
            'is_active' => true,
        ]);

        // 4. مريض
        $patientUser = User::factory()->create(['name' => 'علي حسن']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'blood_group' => 'O+',
        ]);

        // 5. حجز السونار من الاستعلامات
        $response = $this->actingAs($receptionist)->post(route('inquiry.store'), [
            'patient_id' => $patient->id,
            'request_type' => ['radiology'],
            'radiology_category' => 'ultrasound',
            'ultrasound_type_id' => $sonarType->id,
            'ultrasound_staff_id' => $sonarStaff->id,
        ]);

        $response->assertRedirect(route('consultant-availability.index'));

        // 6. التحقق من إنشاء الطلب الطبي
        $medicalRequest = MedicalRequest::where('type', 'radiology')
            ->where('subtype', 'ultrasound')
            ->latest()
            ->first();

        $this->assertNotNull($medicalRequest);
        $this->assertEquals('pending', $medicalRequest->status);
        $this->assertEquals('pending', $medicalRequest->payment_status); // جاهز للدفع في الكاشير

        $details = json_decode($medicalRequest->details, true);
        $this->assertEquals($sonarType->id, $details['ultrasound_type_id']);
        $this->assertEquals([$sonarType->id], $details['radiology_type_ids']);
        $this->assertTrue($details['services_selected']);

        // 7. التحقق من زيارة المريض والموعد المرتبط
        $this->assertEquals('pending_payment', $medicalRequest->visit->status);
        $this->assertNotNull($medicalRequest->visit->appointment_id);

        $appointment = \App\Models\Appointment::find($medicalRequest->visit->appointment_id);
        $this->assertNotNull($appointment);
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertEquals('pending', $appointment->payment_status);
        $this->assertStringContainsString('سونار', $appointment->reason);
        $this->assertNotNull($appointment->queue_number);

        // 8. التحقق من ظهور الحجز في شاشة توفر الاستشاريين
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage consultant availability', 'guard_name' => 'web']);
        $receptionist->givePermissionTo('manage consultant availability');
        $consultantPage = $this->actingAs($receptionist)->get(route('consultant-availability.index'));
        $consultantPage->assertStatus(200);
        $consultantPage->assertSee('سونار');
        $consultantPage->assertSee($patientUser->name);
    }
}
