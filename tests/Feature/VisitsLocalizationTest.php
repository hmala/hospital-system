<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitsLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_visits_index_and_details_render_translated_labels_in_both_locales(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin_visits_i18n@hospital.com']);
        $admin->assignRole('admin');

        $hospital = Hospital::create([
            'name' => 'General Hospital',
            'owner_name' => 'Dr. Director',
            'license_number' => 'LIC-VISITS-01',
            'phone' => '07700000003',
            'email' => 'visits@hospital.com',
            'address' => 'Baghdad',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'Internal Medicine Clinic',
            'type' => 'internal',
            'room_number' => '102',
            'consultation_fee' => 30000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '18:00',
            'max_patients_per_day' => 25,
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'Clinic Doctor']);
        $doctorUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'Internal Medicine',
            'phone' => '07712345681',
            'qualification' => 'MBChB',
            'license_number' => 'LIC-DOC-04',
            'type' => 'consultant',
        ]);

        $patientUser = User::factory()->create(['name' => 'Patient Mark']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'patient_id' => 'P-VISIT-100',
            'name' => 'Patient Mark',
            'gender' => 'male',
            'age' => 45,
            'blood_group' => 'B+',
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:30:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'Severe headache and fatigue',
            'status' => 'in_progress',
        ]);

        // 1. Visits Index in English
        $enIndexResponse = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('visits.index'));
        $enIndexResponse->assertOk();
        $enIndexResponse->assertSee('dir="ltr"', false);
        $enIndexResponse->assertSee('Outpatient &amp; Doctor Visits Log', false);
        $enIndexResponse->assertSee('Export Excel');
        $enIndexResponse->assertSee('New Visit Registration');
        $enIndexResponse->assertSee('Total Visits');

        // 2. Visits Index in Arabic
        $arIndexResponse = $this->actingAs($admin)->withSession(['locale' => 'ar'])->get(route('visits.index'));
        $arIndexResponse->assertOk();
        $arIndexResponse->assertSee('dir="rtl"', false);
        $arIndexResponse->assertSee('سجل زيارات العيادات والاستشارية');
        $arIndexResponse->assertSee('تصدير إكسل');
        $arIndexResponse->assertSee('تسجيل زيارة جديدة');
        $arIndexResponse->assertSee('إجمالي الزيارات');

        // 3. Visits Show in English
        $enShowResponse = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('visits.show', $visit));
        $enShowResponse->assertOk();
        $enShowResponse->assertSee('dir="ltr"', false);
        $enShowResponse->assertSee('Visit Details');
        $enShowResponse->assertSee('Medical Report');
        $enShowResponse->assertSee('Patient Information');

        // 4. Visits Show in Arabic
        $arShowResponse = $this->actingAs($admin)->withSession(['locale' => 'ar'])->get(route('visits.show', $visit));
        $arShowResponse->assertOk();
        $arShowResponse->assertSee('dir="rtl"', false);
        $arShowResponse->assertSee('تفاصيل الزيارة');
        $arShowResponse->assertSee('التقرير الطبي');
        $arShowResponse->assertSee('معلومات المريض');

        // 5. Visits Create in English & Arabic
        $enCreateResponse = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('visits.create'));
        $enCreateResponse->assertOk();
        $enCreateResponse->assertSee('New Visit Registration');
        $enCreateResponse->assertSee('Visit Information');

        $arCreateResponse = $this->actingAs($admin)->withSession(['locale' => 'ar'])->get(route('visits.create'));
        $arCreateResponse->assertOk();
        $arCreateResponse->assertSee('تسجيل زيارة جديدة');
        $arCreateResponse->assertSee('بيانات الزيارة');
    }

    public function test_doctor_station_visits_index_renders_translated_labels_in_both_locales(): void
    {
        $hospital = Hospital::create([
            'name' => 'General Hospital',
            'owner_name' => 'Dr. Director',
            'license_number' => 'LIC-DOC-STATION-01',
            'phone' => '07700000004',
            'email' => 'docstation@hospital.com',
            'address' => 'Baghdad',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'Surgery Clinic',
            'type' => 'surgery',
            'room_number' => '103',
            'consultation_fee' => 35000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '18:00',
            'max_patients_per_day' => 20,
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'Doctor Beta', 'email' => 'doctor_beta@hospital.com']);
        $doctorUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'General Surgery',
            'phone' => '07712345682',
            'qualification' => 'FRCS',
            'license_number' => 'LIC-DOC-05',
            'type' => 'surgeon',
        ]);

        // English session
        $enResponse = $this->actingAs($doctorUser)->withSession(['locale' => 'en'])->get(route('doctor.visits.index'));
        $enResponse->assertOk();
        $enResponse->assertSee('dir="ltr"', false);
        $enResponse->assertSee('Doctor Dashboard');
        $enResponse->assertSee('Live Clinic &amp; Calling Station', false);
        $enResponse->assertSee("Today's Visits");
        $enResponse->assertSee('Pending Visits');
        $enResponse->assertSee('All Visits Archive');

        // Arabic session
        $arResponse = $this->actingAs($doctorUser)->withSession(['locale' => 'ar'])->get(route('doctor.visits.index'));
        $arResponse->assertOk();
        $arResponse->assertSee('dir="rtl"', false);
        $arResponse->assertSee('لوحة تحكم الطبيب');
        $arResponse->assertSee('محطة العيادة والمناداة الحية');
        $arResponse->assertSee('زيارات اليوم');
        $arResponse->assertSee('زيارات معلقة');
        $arResponse->assertSee('أرشيف جميع الزيارات');
    }

    public function test_patient_history_and_pending_investigations_render_in_both_locales(): void
    {
        $hospital = Hospital::create([
            'name' => 'General Hospital',
            'owner_name' => 'Dr. Director',
            'license_number' => 'LIC-DOC-STATION-02',
            'phone' => '07700000005',
            'email' => 'docstation2@hospital.com',
            'address' => 'Baghdad',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'Cardiology Clinic',
            'type' => 'internal',
            'room_number' => '104',
            'consultation_fee' => 40000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '18:00',
            'max_patients_per_day' => 20,
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'Doctor Cardio']);
        $doctorUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'Cardiology',
            'phone' => '07712345683',
            'qualification' => 'Board',
            'license_number' => 'LIC-DOC-06',
            'type' => 'consultant',
        ]);

        $patientUser = User::factory()->create(['name' => 'John Doe']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'patient_id' => 'P-HIST-101',
            'name' => 'John Doe',
            'gender' => 'male',
            'age' => 50,
            'blood_group' => 'A+',
        ]);

        // 1. Test Patient Medical History in English
        $enHistory = $this->actingAs($doctorUser)->withSession(['locale' => 'en'])->get(route('doctor.patient.history', $patient));
        $enHistory->assertOk();
        $enHistory->assertSee('Patient Medical Journey');
        $enHistory->assertSee('Filter Medical History');
        $enHistory->assertSee('Quick Access');
        $enHistory->assertSee('Print');
        $enHistory->assertSee('Back');

        // 2. Test Patient Medical History in Arabic
        $arHistory = $this->actingAs($doctorUser)->withSession(['locale' => 'ar'])->get(route('doctor.patient.history', $patient));
        $arHistory->assertOk();
        $arHistory->assertSee('الرحلة الطبية للمريض');
        $arHistory->assertSee('فلتر السجل الطبي');
        $arHistory->assertSee('الوصول السريع');
        $arHistory->assertSee('طباعة');
        $arHistory->assertSee('رجوع');

        // 3. Test Staff Requests Log in English
        $admin = User::factory()->create(['name' => 'Admin Officer']);
        $admin->assignRole('admin');
        $enStaff = $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('staff.requests.index'));
        $enStaff->assertOk();
        $enStaff->assertSee('Medical Orders Management');
        $enStaff->assertSee('Filter Requests');
        $enStaff->assertSee('Live');

        // 4. Test Staff Requests Log in Arabic
        $arStaff = $this->actingAs($admin)->withSession(['locale' => 'ar'])->get(route('staff.requests.index'));
        $arStaff->assertOk();
        $arStaff->assertSee('إدارة الطلبات الطبية');
        $arStaff->assertSee('فلترة الطلبات');
        $arStaff->assertSee('مباشر');
    }

    public function test_doctor_workstation_left_column_and_pending_tests_alert_in_both_locales(): void
    {
        $hospital = Hospital::create([
            'name' => 'General Hospital',
            'owner_name' => 'Dr. Director',
            'license_number' => 'LIC-DOC-STATION-03',
            'phone' => '07700000006',
            'email' => 'docstation3@hospital.com',
            'address' => 'Baghdad',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'Neurology Clinic',
            'type' => 'internal',
            'room_number' => '105',
            'consultation_fee' => 45000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '18:00',
            'max_patients_per_day' => 20,
            'is_active' => true,
        ]);

        $doctorUser = User::factory()->create(['name' => 'Doctor Neuro']);
        $doctorUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $dept->id,
            'specialization' => 'Neurology',
            'phone' => '07712345684',
            'qualification' => 'PhD',
            'license_number' => 'LIC-DOC-07',
            'type' => 'consultant',
        ]);

        $patientUser = User::factory()->create(['name' => 'Alice Wonder']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'patient_id' => 'P-DOC-200',
            'name' => 'Alice Wonder',
            'gender' => 'female',
            'age' => 38,
            'blood_group' => 'O+',
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '11:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'Frequent migraines and dizziness',
            'status' => 'in_progress',
        ]);

        \App\Models\Request::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'type' => 'lab',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'Complete Blood Count (CBC)',
            'details' => ['tests' => ['CBC', 'ESR']],
        ]);

        // 1. Doctor Workstation in English
        $enDoc = $this->actingAs($doctorUser)->withSession(['locale' => 'en'])->get(route('doctor.visits.show', $visit));
        $enDoc->assertOk();
        $enDoc->assertSee('Pending medical tests awaiting results (count: 1)');
        $enDoc->assertSee('Ending visit is restricted until all lab and radiology results are issued');
        $enDoc->assertSee('Suspend &amp; Call Next', false);
        $enDoc->assertSee('Medical Record &amp; Test Results', false);
        $enDoc->assertSee("Today's Requests");
        $enDoc->assertSee('Previous Visits');
        $enDoc->assertSee('Appointments &amp; Reviews', false);

        // 2. Doctor Workstation in Arabic
        $arDoc = $this->actingAs($doctorUser)->withSession(['locale' => 'ar'])->get(route('doctor.visits.show', $visit));
        $arDoc->assertOk();
        $arDoc->assertSee('توجد فحوصات طبية معلقة بانتظار صدور النتائج (عددها: 1)');
        $arDoc->assertSee('يُمنع إنهاء الزيارة حتى تكتمل وتصدر نتائج كافة الفحوصات (المخبرية والشعاعية)');
        $arDoc->assertSee('تعليق واستدعاء التالي');
        $arDoc->assertSee('السجل الطبي ونتائج الفحوصات');
        $arDoc->assertSee('طلبات اليوم');
        $arDoc->assertSee('الزيارات السابقة');
        $arDoc->assertSee('المواعيد والمراجعات');
    }
}
