<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorVisitCancellationRefundTest extends TestCase
{
    use RefreshDatabase;

    protected Hospital $hospital;
    protected Department $department;
    protected Doctor $doctor;
    protected User $cashierUser;
    protected User $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الفيحاء',
            'email' => 'info@test.com',
            'owner_name' => 'المدير العام',
            'phone' => '0770000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-' . uniqid(),
        ]);

        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'عيادة الباطنية',
            'type' => 'internal',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
            'is_active' => true,
        ]);

        $this->doctorUser = User::factory()->create(['name' => 'د. قمر سعد']);
        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'باطنية',
            'qualification' => 'بورد عربي',
            'license_number' => 'DOC-' . uniqid(),
            'phone' => '07701234567',
            'consultation_fee' => 25000,
            'is_active' => true,
        ]);

        $roleCashier = Role::firstOrCreate(['name' => 'cashier']);
        $perm = Permission::firstOrCreate(['name' => 'process payments']);
        $roleCashier->givePermissionTo($perm);

        $this->cashierUser = User::factory()->create();
        $this->cashierUser->assignRole('cashier');
    }

    public function test_medical_request_payment_does_not_overwrite_doctor_appointment_payment_id(): void
    {
        $patientUser = User::factory()->create(['name' => 'محمد سهم']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);

        // 1. إنشاء موعد ودفع كشفية الطبيب
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => now(),
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'consultation_fee' => 25000,
        ]);

        $consultationPayment = Payment::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'cashier_id' => $this->cashierUser->id,
            'receipt_number' => 'REC-APP-' . uniqid(),
            'amount' => 2500, // نسبة تحمل 10%
            'total_amount' => 25000,
            'payment_method' => 'cash',
            'payment_type' => 'appointment',
            'paid_at' => now(),
        ]);

        $appointment->update(['payment_id' => $consultationPayment->id]);

        // 2. إنشاء زيارة وطلب فحص إيكو/أشعة
        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_id' => $appointment->id,
            'visit_date' => now(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص دوري',
            'status' => 'in_progress',
        ]);

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'radiology',
            'description' => 'فحص سونار',
            'status' => 'pending',
            'payment_status' => 'pending',
            'details' => ['test_name' => 'ECHO'],
        ]);

        // 3. دفع رسوم الفحص عبر الكاشير
        $response = $this->actingAs($this->cashierUser)->post(route('cashier.request.payment.process', $medicalRequest), [
            'amount' => 6250,
            'payment_method' => 'cash',
            'patient_share' => 6250,
            'insurance_share' => 18750,
        ]);

        $response->assertSessionHasNoErrors();

        // تأكد من أن payment_id الخاص بالموعد لم يتم استبداله بدفعة الفحص الطبي
        $appointment->refresh();
        $this->assertEquals($consultationPayment->id, $appointment->payment_id, 'Appointment payment_id must remain linked to the consultation payment!');
        
        // تأكد من أن حالة زيارة الطبيب لم تتغير إلى scheduled بسبب دفع الفحص
        $visit->refresh();
        $this->assertEquals('in_progress', $visit->status, 'Doctor visit in_progress status must not be overridden by cashier request payment!');
    }

    public function test_cancelling_appointment_refunds_consultation_fee_not_radiology_payment(): void
    {
        $patientUser = User::factory()->create(['name' => 'حسام عبد الستار']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1985-05-05',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => now(),
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'consultation_fee' => 25000,
        ]);

        $consultationPayment = Payment::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'cashier_id' => $this->cashierUser->id,
            'receipt_number' => 'REC-APP-' . uniqid(),
            'amount' => 2500,
            'payment_method' => 'cash',
            'payment_type' => 'appointment',
            'paid_at' => now(),
        ]);
        $appointment->update(['payment_id' => $consultationPayment->id]);

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_id' => $appointment->id,
            'visit_date' => now(),
            'visit_time' => '10:00:00',
            'visit_type' => 'consultation',
            'chief_complaint' => 'فحص دوري',
            'status' => 'in_progress',
        ]);

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => 'radiology',
            'description' => 'فحص سونار',
            'status' => 'pending',
            'payment_status' => 'paid',
            'details' => ['test_name' => 'Ultrasound'],
        ]);

        // دفعة الفحص 6,250
        $requestPayment = Payment::create([
            'patient_id' => $patient->id,
            'request_id' => $medicalRequest->id,
            'cashier_id' => $this->cashierUser->id,
            'receipt_number' => 'REC-RAD-' . uniqid(),
            'amount' => 6250,
            'payment_method' => 'cash',
            'payment_type' => 'radiology',
            'paid_at' => now(),
        ]);

        // إلغاء الموعد
        $this->actingAs($this->doctorUser);
        $appointment->cancel('إلغاء من قبل الطبيب');

        // التحقق من أن الاسترجاع تم بقيمة كشفية الطبيب 2500 فقط وليس 6250
        $refundPayment = Payment::where('appointment_id', $appointment->id)
            ->where('amount', '<', 0)
            ->first();

        $this->assertNotNull($refundPayment, 'Refund payment should exist');
        $this->assertEquals(-2500.00, (float)$refundPayment->amount, 'Refund amount must be -2500 (consultation copay), not -6250 (radiology fee)!');

        // التحقق من بقاء فحص الأشعة المسدد مدفوعاً
        $medicalRequest->refresh();
        $this->assertEquals('paid', $medicalRequest->payment_status);
    }
}
