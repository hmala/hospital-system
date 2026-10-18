<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LabWhatsAppShareAndPublicReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;
    protected Patient $patient;
    protected MedicalRequest $labRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $hospital = Hospital::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'مستشفى الكفاءات الأهلي',
                'owner_name' => 'Admin',
                'phone' => '07700000000',
                'address' => 'Baghdad',
                'license_number' => 'LIC-12345',
            ]
        );

        $dept = Department::firstOrCreate(
            ['name' => 'المختبر'],
            [
                'hospital_id' => $hospital->id,
                'type' => 'laboratory',
                'room_number' => '101',
                'consultation_fee' => 0,
                'working_hours_start' => '08:00:00',
                'working_hours_end' => '20:00:00',
                'is_active' => true,
            ]
        );

        $role = Role::firstOrCreate(['name' => 'lab_staff']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view lab tests']);
        $this->staffUser = User::factory()->create(['name' => 'فني المختبر']);
        $this->staffUser->assignRole($role);
        $this->staffUser->givePermissionTo('view lab tests');

        $patientUser = User::factory()->create([
            'name' => 'عبدالله أحمد كاظم',
            'phone' => '07701234567',
            'gender' => 'male',
        ]);

        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'hospital_id' => 1,
            'insurance_type' => 'cash',
        ]);

        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $dept->id,
            'visit_date' => now(),
            'visit_time' => now(),
            'visit_type' => 'laboratory',
            'chief_complaint' => 'فحوصات مخبرية',
            'status' => 'completed',
        ]);

        $this->labRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب فحوصات مخبرية',
            'status' => 'completed',
            'payment_status' => 'paid',
            'details' => [
                'tests' => ['CBC', 'Lipid Profile'],
            ],
            'result' => json_encode([
                'test_results' => [
                    'CBC' => ['value' => 'Normal', 'unit' => ''],
                ],
                'notes' => 'فحص معتمد',
            ]),
        ]);
    }

    public function test_patient_phone_is_formatted_to_international_format_for_whatsapp(): void
    {
        $formattedPhone = $this->labRequest->getPatientPhone();
        $this->assertEquals('9647701234567', $formattedPhone);
    }

    public function test_whatsapp_share_url_contains_wa_me_and_public_report_link(): void
    {
        $waUrl = $this->labRequest->getWhatsAppShareUrl();

        $this->assertNotNull($waUrl);
        $this->assertStringStartsWith('https://wa.me/9647701234567?text=', $waUrl);

        $publicUrl = $this->labRequest->getPublicReportUrl();
        $this->assertStringContainsString(rawurlencode($publicUrl), $waUrl);
        $this->assertStringContainsString(rawurlencode('مستشفى الكفاءات الأهلي'), $waUrl);
    }

    public function test_public_report_accessible_without_authentication_using_token(): void
    {
        $publicUrl = $this->labRequest->getPublicReportUrl();

        // دخول المريض بدون أي تسجيل دخول (Guest)
        $response = $this->get($publicUrl);

        $response->assertStatus(200);
        $response->assertSee('عبدالله أحمد كاظم');
        $response->assertSee('مستشفى الكفاءات الأهلي');
        $response->assertSee('تحميل / حفظ كـ PDF');
    }

    public function test_public_report_denied_without_valid_token_for_unauthenticated_user(): void
    {
        // محاولة فتح التقرير بدون توكن أو بتوكن خاطئ
        $invalidUrl = route('lab.public-report', ['request' => $this->labRequest->id, 'token' => 'invalid_token']);

        $response = $this->get($invalidUrl);

        $response->assertStatus(403);
    }

    public function test_lab_index_displays_whatsapp_button_for_completed_request(): void
    {
        $response = $this->actingAs($this->staffUser)->get(route('lab.index'));

        $response->assertStatus(200);
        $response->assertSee('واتساب');
        $response->assertSee('https://wa.me/9647701234567', false);
    }

    public function test_lab_show_displays_whatsapp_button_in_toolbar_and_beside_phone(): void
    {
        $response = $this->actingAs($this->staffUser)->get(route('lab.show', $this->labRequest));

        $response->assertStatus(200);
        $response->assertSee('إرسال النتيجة عبر واتساب');
        $response->assertSee('https://wa.me/9647701234567', false);
    }
}
