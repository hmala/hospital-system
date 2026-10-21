<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Emergency;
use App\Models\LabTest;
use App\Models\RadiologyType;
use App\Models\EmergencyLabRequest;
use App\Models\EmergencyRadiologyRequest;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class EmergencyRequestItemModificationAndAutoMergeTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $doctorUser;
    protected $doctor;
    protected $patient;
    protected $emergency;
    protected $testCbc;
    protected $testRbs;
    protected $radCXR;
    protected $radAbdomen;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('doctor', 'web');
        Role::findOrCreate('patient', 'web');

        Permission::findOrCreate('manage emergencies', 'web');
        Permission::findOrCreate('create medical requests', 'web');

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الطوارئ',
            'type' => 'emergency',
            'room_number' => 'ER-1',
            'consultation_fee' => 10000,
            'working_hours_start' => '00:00:00',
            'working_hours_end' => '23:59:59',
        ]);

        $this->doctorUser = User::create([
            'name' => 'د. حسام طوارئ',
            'username' => 'dr_hossam_er',
            'email' => 'dr_hossam_er@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->doctorUser->assignRole('doctor');
        $this->doctorUser->givePermissionTo(['manage emergencies', 'create medical requests']);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'department_id' => $department->id,
            'type' => 'general',
            'specialization' => 'emergency_medicine',
            'qualification' => 'MBChB',
            'license_number' => 'DOC-ER-01',
            'consultation_fee' => 10000,
            'is_active' => true,
        ]);

        $patientUser = User::create([
            'name' => 'كرار سلام',
            'username' => 'karrar_salam',
            'email' => 'karrar@test.com',
            'password' => bcrypt('password'),
        ]);
        $patientUser->assignRole('patient');

        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'date_of_birth' => '1995-05-15',
            'phone' => '07712345678',
        ]);

        $this->emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'admission_time' => now(),
            'priority' => 'yellow',
            'emergency_type' => 'medical',
            'symptoms' => 'ألم حاد في الصدر',
            'chief_complaint' => 'ألم حاد في الصدر',
            'status' => 'in_progress',
            'payment_status' => 'pending',
        ]);

        $this->testCbc = LabTest::create([
            'name' => 'CBC (صورة دم كاملة)',
            'code' => 'CBC',
            'price' => 15000,
            'is_active' => true,
        ]);

        $this->testRbs = LabTest::create([
            'name' => 'RBS (سكر عشوائي)',
            'code' => 'RBS',
            'price' => 5000,
            'is_active' => true,
        ]);

        $this->radCXR = RadiologyType::create([
            'name' => 'أشعة الصدر (Chest X-Ray)',
            'code' => 'CXR',
            'base_price' => 20000,
            'is_active' => true,
        ]);

        $this->radAbdomen = RadiologyType::create([
            'name' => 'سونار البطن (Abdominal Ultrasound)',
            'code' => 'US-ABD',
            'base_price' => 25000,
            'is_active' => true,
        ]);
    }

    public function test_emergency_lab_request_auto_merges_into_pending_unpaid_request()
    {
        $this->actingAs($this->doctorUser);

        // الطلب الأول
        $response1 = $this->post(route('emergency.request-lab', $this->emergency), [
            'lab_test_ids' => [$this->testCbc->id],
            'priority' => 'urgent',
            'notes' => 'فحص أولي',
        ]);
        $response1->assertRedirect();

        $this->assertDatabaseCount('emergency_lab_requests', 1);
        $labRequest = EmergencyLabRequest::first();
        $this->assertCount(1, $labRequest->labTests);

        // الطلب الثاني لنفس الحالة قبل السداد
        $response2 = $this->post(route('emergency.request-lab', $this->emergency), [
            'lab_test_ids' => [$this->testRbs->id],
            'priority' => 'critical',
            'notes' => 'إضافة فحص سكر عاجل',
        ]);
        $response2->assertRedirect();

        // يجب أن يظل عدد الطلبات 1 مع دمج الفحص الثاني فيه
        $this->assertDatabaseCount('emergency_lab_requests', 1);
        $labRequest->refresh();
        $this->assertCount(2, $labRequest->labTests);
        $this->assertEquals('critical', $labRequest->priority);
        $this->assertStringContainsString('إضافة فحص سكر عاجل', $labRequest->notes);
    }

    public function test_emergency_radiology_request_auto_merges_into_pending_unpaid_request()
    {
        $this->actingAs($this->doctorUser);

        // طلب الأشعة الأول
        $response1 = $this->post(route('emergency.request-radiology', $this->emergency), [
            'radiology_type_ids' => [$this->radCXR->id],
            'priority' => 'urgent',
            'notes' => 'فحص صدر',
        ]);
        $response1->assertRedirect();

        $this->assertDatabaseCount('emergency_radiology_requests', 1);
        $radReq = EmergencyRadiologyRequest::first();
        $this->assertCount(1, $radReq->radiologyTypes);

        // طلب سونار إضافي لنفس الحالة قبل السداد
        $response2 = $this->post(route('emergency.request-radiology', $this->emergency), [
            'radiology_type_ids' => [$this->radAbdomen->id],
            'priority' => 'critical',
            'notes' => 'إضافة سونار بطن',
        ]);
        $response2->assertRedirect();

        // يجب أن يظل عدد الطلبات 1 مع دمج الفحصين فيه
        $this->assertDatabaseCount('emergency_radiology_requests', 1);
        $radReq->refresh();
        $this->assertCount(2, $radReq->radiologyTypes);
        $this->assertEquals('critical', $radReq->priority);
    }

    public function test_emergency_doctor_can_remove_individual_lab_test_and_recalculate_fees()
    {
        $this->actingAs($this->doctorUser);

        $labReq = EmergencyLabRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'status' => 'pending',
            'priority' => 'urgent',
            'requested_at' => now(),
        ]);
        $labReq->labTests()->attach([$this->testCbc->id, $this->testRbs->id]);

        $this->assertCount(2, $labReq->labTests);

        // حذف فحص RBS
        $response = $this->deleteJson(route('emergency.remove-request-item', $this->emergency), [
            'type' => 'lab',
            'request_id' => $labReq->id,
            'item_id' => $this->testRbs->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'remaining_count' => 1,
            'is_cancelled' => false,
        ]);

        $labReq->refresh();
        $this->assertCount(1, $labReq->labTests);
        $this->assertEquals($this->testCbc->id, $labReq->labTests->first()->id);
    }

    public function test_when_last_item_is_removed_request_becomes_cancelled()
    {
        $this->actingAs($this->doctorUser);

        $labReq = EmergencyLabRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'status' => 'pending',
            'priority' => 'urgent',
            'requested_at' => now(),
        ]);
        $labReq->labTests()->attach([$this->testCbc->id]);

        // حذف الفحص الوحيد
        $response = $this->deleteJson(route('emergency.remove-request-item', $this->emergency), [
            'type' => 'lab',
            'request_id' => $labReq->id,
            'item_id' => $this->testCbc->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'remaining_count' => 0,
            'is_cancelled' => true,
        ]);

        $labReq->refresh();
        $this->assertCount(0, $labReq->labTests);
        $this->assertEquals('cancelled', $labReq->status);
    }

    public function test_emergency_doctor_can_remove_individual_radiology_type()
    {
        $this->actingAs($this->doctorUser);

        $radReq = EmergencyRadiologyRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'status' => 'pending',
            'priority' => 'urgent',
            'requested_at' => now(),
        ]);
        $radReq->radiologyTypes()->attach([$this->radCXR->id, $this->radAbdomen->id]);

        $this->assertCount(2, $radReq->radiologyTypes);

        // حذف سونار البطن
        $response = $this->deleteJson(route('emergency.remove-request-item', $this->emergency), [
            'type' => 'radiology',
            'request_id' => $radReq->id,
            'item_id' => $this->radAbdomen->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'remaining_count' => 1,
            'is_cancelled' => false,
        ]);

        $radReq->refresh();
        $this->assertCount(1, $radReq->radiologyTypes);
        $this->assertEquals($this->radCXR->id, $radReq->radiologyTypes->first()->id);
    }

    public function test_removal_is_blocked_if_request_is_already_paid()
    {
        $this->actingAs($this->doctorUser);

        $payment = Payment::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'amount' => 15000,
            'payment_type' => 'emergency',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $labReq = EmergencyLabRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'payment_id' => $payment->id,
            'status' => 'pending',
            'priority' => 'urgent',
            'requested_at' => now(),
        ]);
        $labReq->labTests()->attach([$this->testCbc->id]);

        // محاولة الحذف بعد السداد
        $response = $this->deleteJson(route('emergency.remove-request-item', $this->emergency), [
            'type' => 'lab',
            'request_id' => $labReq->id,
            'item_id' => $this->testCbc->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('لا يمكن حذف أو تعديل الفحص بعد سداد الرسوم', $response->json('message'));
    }

    public function test_emergency_show_blade_displays_stepper_mini_table_and_delete_buttons()
    {
        $this->actingAs($this->doctorUser);

        // طلب معلق غير مسدد (مرحلة طلب الطبيب وعند الكاشير)
        $labReq = EmergencyLabRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'status' => 'pending',
            'priority' => 'urgent',
            'requested_at' => now(),
        ]);
        $labReq->labTests()->attach([$this->testCbc->id]);

        $radReq = EmergencyRadiologyRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'status' => 'pending',
            'priority' => 'urgent',
            'requested_at' => now(),
        ]);
        $radReq->radiologyTypes()->attach([$this->radCXR->id]);

        // طلب مكتمل (مرحلة النتيجة جاهزة)
        $completedLabReq = EmergencyLabRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'status' => 'completed',
            'priority' => 'urgent',
            'requested_at' => now(),
        ]);
        $completedLabReq->labTests()->attach([$this->testRbs->id => ['result' => json_encode(['value' => '120', 'unit' => 'mg/dL', 'status' => 'normal'])]]);

        // طلب ملغي (أرشيف)
        $cancelledRadReq = EmergencyRadiologyRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'status' => 'cancelled',
            'priority' => 'urgent',
            'notes' => 'ألغي بواسطة الطبيب',
            'requested_at' => now(),
        ]);
        $cancelledRadReq->radiologyTypes()->attach([$this->radAbdomen->id]);

        $response = $this->get(route('emergency.show', $this->emergency));

        $response->assertOk();
        // التأكد من مراحل شريط التقدم (Stepper)
        $response->assertSee('طلب الطبيب');
        $response->assertSee('عند الكاشير');
        $response->assertSee('النتيجة جاهزة');
        // التأكد من استدعاء دالة الحذف للأطباء
        $response->assertSee('emergencyRemoveRequestItem');
        // التأكد من ظهور أرشيف الطلبات الملغاة
        $response->assertSee('أرشيف طلبات الأشعة الملغاة');
    }
}
