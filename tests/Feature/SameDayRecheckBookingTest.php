<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SameDayRecheckBookingTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorUser;

    protected $doctor;

    protected $patientUser;

    protected $patient;

    protected $department;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('doctor', 'web');
        Role::findOrCreate('cashier', 'web');
        Role::findOrCreate('lab_staff', 'web');
        Role::findOrCreate('radiology_staff', 'web');
        Role::findOrCreate('consultation_receptionist', 'web');

        Permission::findOrCreate('manage own visits', 'web');
        Permission::findOrCreate('view own visits', 'web');
        Permission::findOrCreate('create visits', 'web');
        Permission::findOrCreate('manage appointments', 'web');
        Permission::findOrCreate('process payments', 'web');
        Permission::findOrCreate('view cashier', 'web');

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '3',
            'consultation_fee' => 35000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->doctorUser = User::create([
            'name' => 'د. علي حسين',
            'email' => 'dr.ali@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo(['manage own visits', 'view own visits']);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'باطنية',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-123',
            'consultation_fee' => 35000,
            'is_active' => true,
        ]);

        $this->patientUser = User::create([
            'name' => 'سلمى راضي علي',
            'email' => 'salma@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->patient = Patient::create([
            'user_id' => $this->patientUser->id,
            'gender' => 'female',
            'birth_date' => '1980-01-01',
            'blood_type' => 'O+',
        ]);
    }

    public function test_booking_same_day_for_same_doctor_is_strictly_blocked(): void
    {
        // 1. إنشاء زيارة سابقة للمريض في نفس اليوم
        Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص عام',
            'status' => 'in_progress',
        ]);

        $admin = User::create([
            'name' => 'الاستعلامات',
            'email' => 'reception@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        // 2. محاولة حجز موعد ثانٍ لنفس المريض ونفس الطبيب في نفس اليوم
        $response = $this->actingAs($admin)->post(route('appointments.store'), [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '12:30',
            'reason' => 'كشف طبي عام',
            'consultation_fee' => 35000,
        ]);

        // 3. التأكد من رفض الحجز برسالة خطأ وعدم إنشاء أي موعد جديد
        $response->assertSessionHasErrors(['patient_id']);
        $this->assertEquals(0, Appointment::where('patient_id', $this->patient->id)->count());
    }

    public function test_doctor_workstation_blocks_completing_visit_when_tests_are_pending(): void
    {
        // 1. زيارة قيد الكشف
        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص عام',
            'status' => 'in_progress',
        ]);

        // 2. طلب تحليل قيد الانتظار لم يكتمل
        $labRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'lab',
            'status' => 'pending',
            'description' => 'CBC test',
            'details' => ['tests' => ['CBC']],
        ]);

        // 3. محاولة إنهاء الزيارة أثناء تعليق الفحص -> يجب أن تُرفض ويُمنع الإغلاق
        $response = $this->actingAs($this->doctorUser)->put(route('doctor.visits.update', $visit), [
            'status' => 'completed',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('in_progress', $visit->fresh()->status);

        // 4. بعد اكتمال التحليل من قبل المختبر
        $labRequest->update(['status' => 'completed']);

        // 5. محاولة إنهاء الزيارة مجدداً -> يجب أن تنجح
        $responseSuccess = $this->actingAs($this->doctorUser)->put(route('doctor.visits.update', $visit), [
            'status' => 'completed',
        ]);

        $responseSuccess->assertSessionHas('success');
        $this->assertEquals('completed', $visit->fresh()->status);
    }

    public function test_doctor_workstation_merges_all_same_day_requests_for_patient(): void
    {
        // زيارة أولى بها فحصين
        $visit1 = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص عام',
            'status' => 'completed',
        ]);

        $req1 = MedicalRequest::create([
            'visit_id' => $visit1->id,
            'patient_id' => $this->patient->id,
            'type' => 'lab',
            'status' => 'completed',
            'description' => 'CBC test',
            'details' => ['tests' => ['CBC']],
        ]);

        // زيارة ثانية لنفس اليوم فارغة
        $visit2 = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '12:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص عام ثان',
            'status' => 'in_progress',
        ]);

        // فتح الزيارة الثانية بالطبيب
        $response = $this->actingAs($this->doctorUser)->get(route('doctor.visits.show', $visit2));

        $response->assertStatus(200);
        // التحقق من ظهور فحص الزيارة الأولى داخل الزيارة الثانية
        $loadedVisit = $response->viewData('visit');
        $this->assertCount(1, $loadedVisit->requests);
        $this->assertEquals($req1->id, $loadedVisit->requests->first()->id);
    }

    public function test_cashier_splits_postponed_tests_into_separate_request_and_releases_visit_lock(): void
    {
        Permission::findOrCreate('process medical requests payments', 'web');
        $cashierUser = User::factory()->create();
        $cashierUser->assignRole('cashier');
        $cashierUser->givePermissionTo('process medical requests payments');

        $test1 = \App\Models\LabTest::create([
            'name' => 'Fasting Blood Sugar',
            'code' => 'FBS-99',
            'price' => 15000,
            'is_active' => true,
        ]);

        $test2 = \App\Models\LabTest::create([
            'name' => 'Complete Blood Count',
            'code' => 'CBC-99',
            'price' => 10000,
            'is_active' => true,
        ]);

        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'checkup',
            'chief_complaint' => 'فحص عام',
            'status' => 'in_progress',
        ]);

        $originalReq = MedicalRequest::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'lab',
            'status' => 'pending',
            'payment_status' => 'pending',
            'description' => 'فحوصات دم',
            'details' => [
                'lab_test_ids' => [$test1->id, $test2->id],
            ],
        ]);

        // الكاشير يسدد CBC (test2) ويؤجل فحص السكر الصائم FBS (test1) لعدم الصيام
        $response = $this->actingAs($cashierUser)->post(route('cashier.request.payment.process', $originalReq), [
            'amount' => 10000,
            'payment_method' => 'cash',
            'insurance_type' => 'none',
            'item_actions' => [
                $originalReq->id => [
                    'lab' => [
                        $test1->id => 'postpone',
                        $test2->id => 'pay_today',
                    ],
                ],
            ],
            'postpone_reason' => 'عدم الصيام 12 ساعة',
        ]);

        // 1. التحقق من تسديد فحص CBC بنجاح
        $this->assertEquals('paid', $originalReq->fresh()->payment_status);
        $this->assertEquals([$test2->id], $originalReq->fresh()->details['lab_test_ids']);

        // 2. التحقق من إنشاء طلب فرعي جديد للفحص المؤجل (FBS) بحالة postponed
        $splitPostponedReq = MedicalRequest::where('visit_id', $visit->id)
            ->where('status', 'postponed')
            ->first();

        $this->assertNotNull($splitPostponedReq);
        $this->assertEquals('pending', $splitPostponedReq->payment_status);
        $this->assertEquals([$test1->id], $splitPostponedReq->details['lab_test_ids']);
        $this->assertTrue($splitPostponedReq->details['is_postponed_split']);

        // 3. التحقق من وصول إشعار للطبيب المعالج
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->doctorUser->id,
            'type' => 'request_items_postponed',
        ]);

        // 4. المختبر يكمل الفحص المدفوع اليوم
        $originalReq->update(['status' => 'completed']);

        // 5. الطبيب ينهي الزيارة بنجاح دون أن يعيقه الفحص المؤجل للغد
        $visitCompleteResponse = $this->actingAs($this->doctorUser)->put(route('doctor.visits.update', $visit), [
            'status' => 'completed',
        ]);

        $visitCompleteResponse->assertSessionHas('success');
        $this->assertEquals('completed', $visit->fresh()->status);
    }
}
