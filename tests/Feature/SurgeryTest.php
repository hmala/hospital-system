<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Surgery;
use App\Models\SurgicalOperation;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_surgery_details(): void
    {
        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Seed hospital and departments
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $department = Department::where('type', 'surgery')->first();

        // Create doctor user and doctor profile
        $doctorUser = User::create([
            'name' => 'Dr. Ahmad',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctorUser->assignRole('doctor');

        $doctor = new Doctor;
        $doctor->user_id = $doctorUser->id;
        $doctor->department_id = $department->id;
        $doctor->phone = '12345678';
        $doctor->specialization = 'General Surgery';
        $doctor->qualification = 'MBBS';
        $doctor->license_number = 'LIC123';
        $doctor->experience_years = 10;
        $doctor->bio = 'Surgeon';
        $doctor->consultation_fee = 50000;
        $doctor->max_patients_per_day = 10;
        $doctor->is_active = true;
        $doctor->save();

        // Create patient user and profile
        $patientUser = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 30,
            'gender' => 'male',
            'blood_group' => 'A+',
        ]);

        // Create an admin user to access the pages
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        // Create a visit
        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'doctor_id' => $doctor->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => now()->toTimeString(),
            'visit_type' => 'surgery',
            'status' => 'pending_payment',
            'chief_complaint' => 'Normal',
        ]);

        // Create a surgery
        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'visit_id' => $visit->id,
            'surgery_type' => 'Appendectomy',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now()->toTimeString(),
            'status' => 'scheduled',
            'surgery_fee' => 100000,
        ]);

        // Access the page as admin
        $response = $this->actingAs($admin)->get(route('surgeries.show', $surgery));
        $response->assertStatus(200);
        $response->assertSee('تفاصيل العملية');
    }

    public function test_can_update_surgery_details(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        // Seed hospital and departments
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $department = Department::where('type', 'surgery')->first();

        $doctorUser = User::create([
            'name' => 'Dr. Ahmad',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctorUser->assignRole('doctor');

        $doctor = new Doctor;
        $doctor->user_id = $doctorUser->id;
        $doctor->department_id = $department->id;
        $doctor->phone = '12345678';
        $doctor->specialization = 'General Surgery';
        $doctor->qualification = 'MBBS';
        $doctor->license_number = 'LIC123';
        $doctor->experience_years = 10;
        $doctor->bio = 'Surgeon';
        $doctor->consultation_fee = 50000;
        $doctor->max_patients_per_day = 10;
        $doctor->is_active = true;
        $doctor->save();

        $patientUser = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 30,
            'gender' => 'male',
            'blood_group' => 'A+',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'doctor_id' => $doctor->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => now()->toTimeString(),
            'visit_type' => 'surgery',
            'status' => 'pending_payment',
            'chief_complaint' => 'Normal',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'visit_id' => $visit->id,
            'surgery_type' => 'Appendectomy',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => now()->toTimeString(),
            'status' => 'scheduled',
            'surgery_fee' => 100000,
        ]);

        $response = $this->actingAs($admin)->patch(route('surgeries.updateDetails', $surgery), [
            'diagnosis' => 'Acute Appendicitis',
            'anesthesia_type' => 'general',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'estimated_duration_minutes' => 90,
            'post_op_notes' => 'Patient stable, recovery initiated.',
            'prescribed_medications' => [
                'surgery_treatments' => [
                    $surgery->id => [
                        [
                            'description' => 'Paracetamol',
                            'dosage' => '500mg',
                            'timing' => 'Every 6 hours',
                            'duration_value' => 3,
                            'duration_unit' => 'days',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('surgeries.show', $surgery));

        $surgery->refresh();
        $this->assertEquals('Acute Appendicitis', $surgery->diagnosis);
        $this->assertEquals('general', $surgery->anesthesia_type);
        $this->assertEquals(90, $surgery->estimated_duration);
        $this->assertCount(1, $surgery->surgeryTreatments);
        $this->assertEquals('Paracetamol', $surgery->surgeryTreatments->first()->description);
    }

    public function test_can_book_surgery_with_multiple_operations_and_verify_fees(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $department = Department::where('type', 'surgery')->first();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_multi_ops@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $doctorUser = User::create([
            'name' => 'Dr. Ali',
            'email' => 'ali@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctorUser->assignRole('doctor');

        $doctor = new Doctor;
        $doctor->user_id = $doctorUser->id;
        $doctor->department_id = $department->id;
        $doctor->phone = '12345678';
        $doctor->specialization = 'General Surgery';
        $doctor->qualification = 'Board';
        $doctor->license_number = 'LIC456';
        $doctor->experience_years = 12;
        $doctor->is_active = true;
        $doctor->save();

        $patientUser = User::create([
            'name' => 'Patient Multi Ops',
            'email' => 'multi_ops@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 45,
            'gender' => 'female',
            'blood_group' => 'B+',
        ]);

        $room = Room::create([
            'room_number' => 'OP-101',
            'room_type' => 'regular',
            'department_id' => $department->id,
            'status' => 'available',
        ]);

        $primaryOp = SurgicalOperation::create([
            'name' => 'Cholecystectomy',
            'category' => 'General Surgery',
            'fee' => 500000,
        ]);

        $secondaryOp = SurgicalOperation::create([
            'name' => 'Hernia Repair',
            'category' => 'General Surgery',
            'fee' => 250000,
        ]);

        // اختبار عرض صفحة حجز العملية والتحقق من عدم وجود أي خطأ بليد أو جافاسكربت
        $createPageResponse = $this->actingAs($admin)->get(route('surgeries.create', [
            'department_id' => $department->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'referring_doctor_name' => 'د. قمر سعد',
            'visit_id' => 161,
        ]));
        $createPageResponse->assertStatus(200);
        $createPageResponse->assertSee('إضافة عملية أخرى');

        $response = $this->actingAs($admin)->post(route('surgeries.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'room_id' => $room->id,
            'expected_stay_days' => 2,
            'surgery_category' => 'General Surgery',
            'surgical_operation_id' => $primaryOp->id,
            'custom_surgery_fee' => '500,000',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '10:00',
            'referring_doctor_name' => 'د. أحمد الاستشاري',
            'additional_operations' => [
                [
                    'surgical_operation_id' => $secondaryOp->id,
                    'fee' => '250,000',
                    'notes' => 'تداخل جراحي تكميلي في نفس الجلسة',
                ],
            ],
        ]);

        $response->assertRedirect(route('surgeries.index'));

        $surgery = Surgery::where('patient_id', $patient->id)->first();
        $this->assertNotNull($surgery);
        $this->assertEquals('Cholecystectomy', $surgery->surgery_type);
        $this->assertEquals(500000, $surgery->surgery_fee);

        $this->assertCount(1, $surgery->additionalOperations);
        $addOp = $surgery->additionalOperations->first();
        $this->assertEquals($secondaryOp->id, $addOp->surgical_operation_id);
        $this->assertEquals(250000, $addOp->fee);
        $this->assertEquals('تداخل جراحي تكميلي في نفس الجلسة', $addOp->notes);

        // فحص احتساب الكاشير وإجمالي العمليات
        $totalCombinedFee = ($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee');
        $this->assertEquals(750000, $totalCombinedFee);

        // فحص فتح شاشة التعديل واسترجاع العمليات المتعددة
        $editResponse = $this->actingAs($admin)->get(route('surgeries.edit', $surgery));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('surgicalOperationsTable');
        $editResponse->assertSee('جدول العمليات الجراحية المطلوبة');

        // فحص تعديل وتحديث العمليات المتعددة
        $thirdOp = \App\Models\SurgicalOperation::create([
            'name' => 'Hernioplasty',
            'category' => 'General Surgery',
            'fee' => 150000,
            'is_active' => true,
        ]);

        $updateResponse = $this->actingAs($admin)->put(route('surgeries.update', $surgery), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'room_id' => $room->id,
            'expected_stay_days' => 1,
            'surgery_category' => 'General Surgery',
            'surgical_operation_id' => $primaryOp->id,
            'custom_surgery_fee' => '600,000',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '11:00',
            'referring_doctor_name' => 'د. أحمد الاستشاري',
            'additional_operations' => [
                [
                    'surgical_operation_id' => $thirdOp->id,
                    'fee' => '180,000',
                    'notes' => 'تم استبدال العملية التكميلية',
                ],
            ],
        ]);

        $updateResponse->assertRedirect(route('surgeries.index'));

        $surgery->refresh();
        $this->assertEquals(600000, $surgery->surgery_fee);
        $this->assertCount(1, $surgery->additionalOperations);
        $this->assertEquals($thirdOp->id, $surgery->additionalOperations->first()->surgical_operation_id);
        $this->assertEquals(180000, $surgery->additionalOperations->first()->fee);
        $this->assertEquals('تم استبدال العملية التكميلية', $surgery->additionalOperations->first()->notes);
    }

    public function test_surgery_insurance_vs_cash_reservation_mode_on_create_and_update(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $department = Department::where('type', 'surgery')->first();

        $doctorUser = User::create([
            'name' => 'Dr. Surgeon',
            'email' => 'surgeon@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $department->id,
            'phone' => '12345678',
            'specialization' => 'General Surgery',
            'qualification' => 'MBBS',
            'license_number' => 'LIC999',
            'experience_years' => 10,
            'consultation_fee' => 50000,
            'max_patients_per_day' => 10,
            'is_active' => true,
        ]);

        $patientUser = User::create([
            'name' => 'Insured Patient',
            'email' => 'insured@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 45,
            'gender' => 'male',
            'blood_group' => 'B+',
            'insurance_type' => 'hi',
            'insurance_card_no' => 'HI-12345',
            'copay_percentage' => 25.0,
        ]);

        $admin = User::create([
            'name' => 'Admin User 2',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $room = Room::create([
            'room_number' => '305',
            'room_type' => 'regular',
            'status' => 'available',
        ]);

        $operation = SurgicalOperation::create([
            'name' => 'Cholecystectomy',
            'category' => 'General Surgery',
            'fee' => 1000000,
            'is_active' => true,
        ]);

        // 1. Create surgery as CASH mode (apply_insurance = 0) even though patient has HI
        $createRes = $this->actingAs($admin)->post(route('surgeries.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'room_id' => $room->id,
            'expected_stay_days' => 1,
            'surgery_category' => 'General Surgery',
            'surgical_operation_id' => $operation->id,
            'custom_surgery_fee' => '1,000,000',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'referring_doctor_name' => 'د. خالد',
            'apply_insurance' => '0', // Cash reservation
        ]);
        $createRes->assertRedirect(route('surgeries.index'));

        $surgery = Surgery::latest()->first();
        $this->assertEquals('none', $surgery->insurance_type);

        // 2. Index should show Cash badge and omit header booking button
        $indexRes = $this->actingAs($admin)->get(route('surgeries.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('نقدي (كاش)');
        $indexRes->assertDontSee('حجز عملية جديدة');
        $indexRes->assertDontSee(route('surgeries.create'), false);

        // 3. Edit view should render patient insurance card with cash radio selected
        $editRes = $this->actingAs($admin)->get(route('surgeries.edit', $surgery));
        $editRes->assertStatus(200);
        $editRes->assertSee('تحديد مسار حجز هذه العملية:');
        $editRes->assertSee('حجز نقدي خاص (كاش كامل خارج الضمان)');

        // 4. Update surgery to Insurance mode (apply_insurance = 1)
        $updateRes = $this->actingAs($admin)->put(route('surgeries.update', $surgery), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'room_id' => $room->id,
            'expected_stay_days' => 1,
            'surgery_category' => 'General Surgery',
            'surgical_operation_id' => $operation->id,
            'custom_surgery_fee' => '1,000,000',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'referring_doctor_name' => 'د. خالد',
            'apply_insurance' => '1', // Toggle to Insurance
        ]);
        $updateRes->assertRedirect(route('surgeries.index'));

        $surgery->refresh();
        $this->assertEquals('hi', $surgery->insurance_type);
    }

    public function test_surgery_staff_can_view_and_save_surgery_details_without_nested_forms(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $department = Department::where('type', 'surgery')->first();

        $surgeryStaff = User::create([
            'name' => 'موظف العمليات',
            'email' => 'surgery_staff_save@example.com',
            'password' => bcrypt('password'),
            'role' => 'surgery_staff',
        ]);
        $surgeryStaff->assignRole('surgery_staff');

        $surgeonUser = User::create([
            'name' => 'د. الجراح',
            'email' => 'surgeon_save@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $surgeonUser->assignRole('doctor');

        $surgeon = new Doctor;
        $surgeon->user_id = $surgeonUser->id;
        $surgeon->department_id = $department->id;
        $surgeon->phone = '07701234567';
        $surgeon->specialization = 'General Surgery';
        $surgeon->qualification = 'Board';
        $surgeon->license_number = 'LIC-SURG-14';
        $surgeon->is_active = true;
        $surgeon->save();

        $anesthUser = User::create([
            'name' => 'د. التخدير',
            'email' => 'anesth_save@example.com',
            'password' => bcrypt('password'),
            'role' => 'anesthesia',
        ]);

        $anesthesiologist = new Doctor;
        $anesthesiologist->user_id = $anesthUser->id;
        $anesthesiologist->department_id = $department->id;
        $anesthesiologist->phone = '07707654321';
        $anesthesiologist->specialization = 'التخدير';
        $anesthesiologist->qualification = 'Board';
        $anesthesiologist->license_number = 'LIC-ANES-14';
        $anesthesiologist->is_active = true;
        $anesthesiologist->save();

        $patientUser = User::create([
            'name' => 'مريض اختبار موظف العمليات',
            'email' => 'patient_staff_save@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 35,
            'gender' => 'male',
            'blood_group' => 'O+',
        ]);

        $location = \App\Models\Location::create([
            'name' => 'صالة عمليات الطابق الثالث',
            'type' => 'sub',
        ]);

        $orStore = \App\Models\Location::create([
            'name' => 'مخزن عمليات',
            'type' => 'sub',
        ]);

        $orProduct = \App\Models\Product::create([
            'name' => 'قفازات جراحية معقمة 7.5',
            'code' => 'OR-GLV-75',
            'category' => 'مستلزمات طبية',
            'unit' => 'زوج',
        ]);

        \App\Models\StockBatch::create([
            'product_id' => $orProduct->id,
            'location_id' => $orStore->id,
            'internal_barcode' => 'OR-BATCH-001',
            'cost_price' => 1500,
            'initial_qty' => 20,
            'current_qty' => 20,
            'received_at' => now(),
            'expiry_date' => now()->addYear(),
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $surgeon->id,
            'department_id' => $department->id,
            'surgery_type' => 'Cholecystectomy',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => 'in_progress',
            'surgery_fee' => 500000,
        ]);

        // 1. Render /surgeries/{id} as surgery_staff and guarantee modular per-tab forms have NO nested <form> tags
        $showRes = $this->actingAs($surgeryStaff)->get(route('surgeries.show', $surgery));
        $showRes->assertStatus(200);

        $html = $showRes->getContent();
        foreach (['detailTeamTimingForm', 'detailSuppliesForm'] as $formId) {
            $formStart = strpos($html, 'id="'.$formId.'"');
            $this->assertNotFalse($formStart, "Expected form #{$formId} to exist");
            $formEnd = strpos($html, '</form>', $formStart);
            $this->assertNotFalse($formEnd, "Expected closing </form> for #{$formId}");

            $innerFormHtml = substr($html, $formStart, $formEnd - $formStart);
            $this->assertStringNotContainsString('<form', $innerFormHtml, "Nested <form> found inside #{$formId}");
        }

        $showRes->assertSee('حفظ الفريق الطبي والتوقيت');
        $showRes->assertSee('صرف من مخزن العمليات');
        $showRes->assertSee('قفازات جراحية معقمة 7.5');
        $showRes->assertDontSee('حفظ التشخيص والعلاجات');
        $showRes->assertDontSee('حفظ تفاصيل التخدير');
        $showRes->assertSee('id="addDeviceForm"', false);

        // 2. Submit updateDetails as surgery_staff and assert persistence of allowed fields + computed duration
        $suppliesPayload = json_encode([
            ['name' => 'شاش معقم', 'qty' => '5', 'unit' => 'قطعة', 'notes' => 'تم التجهيز'],
        ], JSON_UNESCAPED_UNICODE);

        $saveRes = $this->actingAs($surgeryStaff)->patch(route('surgeries.updateDetails', $surgery), [
            'location_id' => $location->id,
            'anesthesiologist_id' => $anesthesiologist->id,
            'surgical_assistant_name' => 'م. علي الجراح',
            'start_time' => '09:00',
            'end_time' => '11:30',
            'estimated_duration_minutes' => 150,
            'supplies' => $suppliesPayload,
        ]);

        $saveRes->assertRedirect(route('surgeries.show', $surgery));
        $saveRes->assertSessionHas('success');

        $surgery->refresh();
        $this->assertEquals($location->id, $surgery->location_id);
        $this->assertEquals($anesthesiologist->id, $surgery->anesthesiologist_id);
        $this->assertEquals('م. علي الجراح', $surgery->surgical_assistant_name);
        $this->assertEquals(150, $surgery->estimated_duration);
        $this->assertEquals($suppliesPayload, $surgery->supplies);
    }

    public function test_unified_surgery_detail_tabs_enforce_anesthesiologist_and_surgeon_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $department = Department::where('type', 'surgery')->first();

        $surgeonUser = User::create([
            'name' => 'د. الجراح الموحد',
            'email' => 'surgeon_unified@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $surgeonUser->assignRole('doctor');

        $surgeon = Doctor::create([
            'user_id' => $surgeonUser->id,
            'department_id' => $department->id,
            'type' => 'surgeon',
            'phone' => '07701112233',
            'specialization' => 'جراحة عامة',
            'qualification' => 'Board',
            'license_number' => 'LIC-SURG-UNI',
            'is_active' => true,
        ]);

        // Anesthesiologist modeled as role 'doctor' + Doctor.type = 'anesthesiologist'
        $anesthDocUser = User::create([
            'name' => 'د. التخدير الموحد',
            'email' => 'anesth_doc_unified@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $anesthDocUser->assignRole('doctor');

        $anesthDoctor = Doctor::create([
            'user_id' => $anesthDocUser->id,
            'department_id' => $department->id,
            'type' => 'anesthesiologist',
            'phone' => '07704445566',
            'specialization' => 'التخدير والعناية المركزة',
            'qualification' => 'Board',
            'license_number' => 'LIC-ANES-UNI',
            'is_active' => true,
        ]);

        // Role-based anesthesia account
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'التخدير', 'guard_name' => 'web']);
        $anesthRoleUser = User::create([
            'name' => 'مخدر بصلاحية الدور',
            'email' => 'anesth_role_unified@example.com',
            'password' => bcrypt('password'),
            'role' => 'التخدير',
        ]);
        $anesthRoleUser->assignRole('التخدير');

        $patientUser = User::create([
            'name' => 'مريض التوحيد',
            'email' => 'patient_unified@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 40,
            'gender' => 'male',
            'blood_group' => 'A+',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $surgeon->id,
            'anesthesiologist_id' => $anesthDoctor->id,
            'department_id' => $department->id,
            'surgery_type' => 'Laparoscopic Cholecystectomy',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'in_progress',
            'surgery_fee' => 750000,
        ]);

        // 1. Both Anesthesiologist accounts default to detail_anesthesia and ONLY have save button there
        foreach ([$anesthDocUser, $anesthRoleUser] as $aUser) {
            $res = $this->actingAs($aUser)->get(route('surgeries.show', $surgery));
            $res->assertStatus(200);
            $res->assertSee('id="tab-btn-detail_anesthesia"', false);
            $res->assertSee('id="detailAnesthesiaForm"', false);
            $res->assertSee('حفظ تفاصيل التخدير');
            $res->assertDontSee('حفظ التشخيص والعلاجات');
            $res->assertDontSee('حفظ الفريق الطبي والتوقيت');
            $res->assertDontSee('حفظ المستلزمات الطبية');

            // Unauthorized scoped/unscoped writes return 403
            $this->actingAs($aUser)->patch(route('surgeries.updateDetails', $surgery), [
                'tab' => 'detail_diagnosis',
                'diagnosis' => 'Unauthorized Diagnosis Edit',
            ])->assertStatus(403);

            $this->actingAs($aUser)->patch(route('surgeries.updateDetails', $surgery), [
                'tab' => 'detail_team_timing',
                'surgical_assistant_name' => 'Unauthorized Assistant',
            ])->assertStatus(403);

            $this->actingAs($aUser)->patch(route('surgeries.updateDetails', $surgery), [
                'diagnosis' => 'Unscoped Forbidden Diagnosis',
            ])->assertStatus(403);

            $this->actingAs($aUser)->patchJson(route('surgeries.updateSurgeryType', $surgery), [
                'surgery_type' => 'Another Surgery',
            ])->assertStatus(403);
        }

        // 2. Anesthesiologist saves anesthesia details cleanly (without being able to reassign team doctors)
        $otherAnesthUser = User::create([
            'name' => 'مخدر آخر',
            'email' => 'other_anesth_unified@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $otherAnesthDoctor = Doctor::create([
            'user_id' => $otherAnesthUser->id,
            'department_id' => $department->id,
            'type' => 'anesthesiologist',
            'phone' => '07705556677',
            'specialization' => 'تخدير',
            'qualification' => 'Board',
            'license_number' => 'LIC-ANES-OTH',
            'is_active' => true,
        ]);

        $saveAnesth = $this->actingAs($anesthDocUser)->patch(route('surgeries.updateDetails', $surgery), [
            'tab' => 'detail_anesthesia',
            'anesthesiologist_id' => $otherAnesthDoctor->id,
            'anesthesiologist_2_id' => $otherAnesthDoctor->id,
            'anesthesia_type' => 'تخدير عام (General Anesthesia)',
            'anesthesia_notes' => 'تم التخدير واستقرت العلامات الحيوية دون مضاعفات.',
        ]);

        $saveAnesth->assertRedirect(route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_anesthesia']));
        $saveAnesth->assertSessionHas('success', 'تم حفظ تفاصيل التخدير بنجاح');

        // Consume flashed session notification cleanly
        $anesthDocView = $this->actingAs($anesthDocUser)->get(route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_anesthesia']));
        $anesthDocView->assertSee('تم حفظ تفاصيل التخدير بنجاح');
        $anesthDocView->assertDontSee('id="anesthesia_tab_anesthesiologist_id"', false);
        $anesthDocView->assertDontSee('id="anesthesia_start_time"', false);

        $surgery->refresh();
        $this->assertEquals($anesthDoctor->id, $surgery->anesthesiologist_id);
        $this->assertNull($surgery->anesthesiologist_2_id);
        $this->assertEquals('تخدير عام (General Anesthesia)', $surgery->anesthesia_type);
        $this->assertNotNull($surgery->anesthesiaStation);
        $this->assertEquals('تم التخدير واستقرت العلامات الحيوية دون مضاعفات.', $surgery->anesthesiaStation->notes);

        // 3. Surgeon sees diagnosis write button, cannot save anesthesia tab
        $surgeonRes = $this->actingAs($surgeonUser)->get(route('surgeries.show', $surgery));
        $surgeonRes->assertStatus(200);
        $surgeonRes->assertSee('id="detailDiagnosisForm"', false);
        $surgeonRes->assertSee('حفظ التشخيص والعلاجات');
        $surgeonRes->assertDontSee('id="detailAnesthesiaForm"', false);
        $surgeonRes->assertDontSee('حفظ تفاصيل التخدير');
        $surgeonRes->assertDontSee('حفظ الفريق الطبي والتوقيت');

        $this->actingAs($surgeonUser)->patch(route('surgeries.updateDetails', $surgery), [
            'tab' => 'detail_anesthesia',
            'anesthesia_notes' => 'Surgeon should not overwrite anesthesia',
        ])->assertStatus(403);

        $this->actingAs($surgeonUser)->patch(route('surgeries.updateDetails', $surgery), [
            'tab' => 'detail_diagnosis',
            'diagnosis' => 'Cholelithiasis',
            'treatment_plan' => 'NPO 6h then soft diet',
        ])->assertRedirect(route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_diagnosis']));

        $surgery->refresh();
        $this->assertEquals('Cholelithiasis', $surgery->diagnosis);

        // Surgeon saves surgical_notes directly in detail_type; anesthesiologist gets 403
        $this->actingAs($anesthDocUser)->patch(route('surgeries.updateDetails', $surgery), [
            'tab' => 'detail_type',
            'surgical_notes' => 'Unauthorized type note',
        ])->assertStatus(403);

        $this->actingAs($surgeonUser)->patch(route('surgeries.updateDetails', $surgery), [
            'tab' => 'detail_type',
            'surgical_notes' => 'ملاحظة خاصة بنوع العملية الجراحية',
        ])->assertRedirect(route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_type']));

        $surgery->refresh();
        $this->assertEquals('ملاحظة خاصة بنوع العملية الجراحية', $surgery->surgical_notes);

        $this->actingAs($surgeonUser)->get(route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_type']))
            ->assertSee('id="detailTypeNotesForm"', false)
            ->assertSee('ملاحظة خاصة بنوع العملية الجراحية');

        // 4. Admin can customize detail_* matrix via Tab Permissions Settings
        $admin = User::create([
            'name' => 'أدمن المصفوفة',
            'email' => 'admin_matrix_unified@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('surgery-stations.tab-settings.index'))
            ->assertStatus(200)
            ->assertSee('مصفوفة صلاحيات ملف العملية وصالة العمليات');

        $this->actingAs($admin)->post(route('surgery-stations.tab-settings.update'), [
            'permissions' => [
                'anesthesia' => [
                    'detail_team_timing' => 'write',
                ],
            ],
        ])->assertRedirect(route('surgery-stations.tab-settings.index'));

        $this->actingAs($anesthDocUser)->patch(route('surgeries.updateDetails', $surgery), [
            'tab' => 'detail_team_timing',
            'surgical_assistant_name' => 'مساعد مخول من المصفوفة',
        ])->assertRedirect(route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_team_timing']));

        $surgery->refresh();
        $this->assertEquals('مساعد مخول من المصفوفة', $surgery->surgical_assistant_name);
    }

    public function test_surgery_resolves_operation_sub_store_without_breaking_floor_sub_store(): void
    {
        $hospital = \App\Models\Hospital::create([
            'name' => 'مستشفى الاختبار',
            'owner_name' => 'الإدارة',
            'license_number' => 'LIC-OR-01',
            'phone' => '07700000001',
            'email' => 'or-test@example.com',
            'address' => 'بغداد',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '101',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 20,
            'is_active' => true,
        ]);

        $floor3Store = \App\Models\Location::create(['name' => 'ردهةالطابق الثالث', 'type' => 'sub']);
        $orMainSubStore = \App\Models\Location::create(['name' => 'مخزن عمليات رئيسي', 'type' => 'sub']);
        $orTheaterStore = \App\Models\Location::create(['name' => 'صالة عمليات 1', 'type' => 'sub']);

        $orCustodianUser = User::create([
            'name' => 'أمين عهدة العمليات',
            'email' => 'or_custodian_resolve@example.com',
            'password' => bcrypt('password'),
            'role' => 'surgery_staff',
            'location_id' => $orMainSubStore->id,
        ]);

        $patientUser = User::create([
            'name' => 'مريض عزل المخازن',
            'email' => 'patient_store_isolation@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);
        $patient = Patient::create(['user_id' => $patientUser->id, 'age' => 42, 'gender' => 'male']);

        $room301 = Room::create(['room_number' => '301', 'floor' => 'الطابق الثالث', 'room_type' => 'regular']);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'room_id' => $room301->id,
            'location_id' => $orTheaterStore->id,
            'surgery_type' => 'Appendectomy',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'in_progress',
            'surgery_fee' => 300000,
        ]);

        // Assigning OR theater location_id resolves OR store to "مخزن عمليات رئيسي" and keeps ward Floor 3 intact for nursing
        $this->assertEquals($orMainSubStore->id, $surgery->getOperationSubStoreLocation($orCustodianUser)?->id);
        $this->assertEquals($orMainSubStore->id, $surgery->getOperationSubStoreLocation()?->id);
        $this->assertEquals($floor3Store->id, $surgery->getFloorSubStoreLocation()?->id);
    }

    public function test_operation_sub_store_dispense_and_reverse_and_or_store_tab_workflow(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $hospital = \App\Models\Hospital::create([
            'name' => 'مستشفى العمليات',
            'owner_name' => 'الإدارة',
            'license_number' => 'LIC-OR-02',
            'phone' => '07700000002',
            'email' => 'or-test2@example.com',
            'address' => 'بغداد',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'الجراحة والتخدير',
            'type' => 'surgery',
            'room_number' => '102',
            'consultation_fee' => 25000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 20,
            'is_active' => true,
        ]);

        $orStore = \App\Models\Location::create([
            'name' => 'مخزن عمليات رئيسي',
            'type' => 'sub',
        ]);

        $floorStore = \App\Models\Location::create([
            'name' => 'ردهة الطابق الثاني',
            'type' => 'sub',
        ]);

        $surgeryStaff = User::create([
            'name' => 'موظف عهدة العمليات',
            'email' => 'or_custodian_test@example.com',
            'password' => bcrypt('password'),
            'role' => 'surgery_staff',
            'location_id' => $orStore->id,
        ]);
        $surgeryStaff->assignRole('surgery_staff');

        // Another user (even admin or surgery_staff with NO OR location_id) MUST NOT see the OR store tab on /surgeries
        $adminWithoutLocation = User::create([
            'name' => 'أدمن بدون عهدة عمليات',
            'email' => 'admin_no_or_loc@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'location_id' => null,
        ]);
        $adminWithoutLocation->assignRole('admin');

        $staffWithWardLocation = User::create([
            'name' => 'موظف بمخزن ردهة',
            'email' => 'staff_ward_loc@example.com',
            'password' => bcrypt('password'),
            'role' => 'surgery_staff',
            'location_id' => $floorStore->id,
        ]);
        $staffWithWardLocation->assignRole('surgery_staff');

        $anesthUser = User::create([
            'name' => 'د. مخدر العمليات',
            'email' => 'anesth_custodian_block@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $anesthUser->assignRole('doctor');

        Doctor::create([
            'user_id' => $anesthUser->id,
            'department_id' => $department->id,
            'type' => 'anesthesiologist',
            'phone' => '07709998877',
            'specialization' => 'التخدير',
            'qualification' => 'Board',
            'license_number' => 'LIC-ANES-BLK',
            'is_active' => true,
        ]);

        $patientUser = User::create([
            'name' => 'مريض صرف العمليات',
            'email' => 'patient_or_dispense@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);
        $patient = Patient::create(['user_id' => $patientUser->id, 'age' => 29, 'gender' => 'female']);

        $supplyProduct = \App\Models\Product::create([
            'name' => 'خيوط جراحية Vicryl 2-0',
            'code' => 'OR-SUT-20',
            'category' => 'مستلزمات طبية',
            'unit' => 'قطعة',
        ]);
        $medProduct = \App\Models\Product::create([
            'name' => 'Propofol 1% Vial',
            'code' => 'OR-ANES-01',
            'category' => 'أدوية التخدير',
            'unit' => 'فيال',
        ]);

        $supplyBatch = \App\Models\StockBatch::create([
            'product_id' => $supplyProduct->id,
            'location_id' => $orStore->id,
            'internal_barcode' => 'OR-LOT-SUT',
            'cost_price' => 3000,
            'initial_qty' => 10,
            'current_qty' => 10,
            'received_at' => now(),
            'expiry_date' => now()->addMonths(8),
        ]);
        $medBatch = \App\Models\StockBatch::create([
            'product_id' => $medProduct->id,
            'location_id' => $orStore->id,
            'internal_barcode' => 'OR-LOT-PROP',
            'cost_price' => 9000,
            'initial_qty' => 8,
            'current_qty' => 8,
            'received_at' => now(),
            'expiry_date' => now()->addMonths(6),
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'surgery_type' => 'Thyroidectomy',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '11:00:00',
            'status' => 'in_progress',
            'surgery_fee' => 450000,
        ]);

        // 0. Users WITHOUT OR store assigned in User Management MUST NOT see #btnTabOrStore / #paneOrStore
        $this->actingAs($adminWithoutLocation)->get(route('surgeries.index', ['tab' => 'or_store']))
            ->assertStatus(200)
            ->assertDontSee('id="btnTabOrStore"', false)
            ->assertDontSee('id="paneOrStore"', false);

        $this->actingAs($staffWithWardLocation)->get(route('surgeries.index', ['tab' => 'or_store']))
            ->assertStatus(200)
            ->assertDontSee('id="btnTabOrStore"', false)
            ->assertDontSee('id="paneOrStore"', false);

        // 1. User WITH OR location_id ("مخزن عمليات رئيسي") sees #btnTabOrStore + both supplies & OR meds on /surgeries?tab=or_store
        $indexRes = $this->actingAs($surgeryStaff)->get(route('surgeries.index', ['tab' => 'or_store']));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('id="btnTabOrStore"', false);
        $indexRes->assertSee('مخزن عمليات رئيسي');
        $indexRes->assertSee('خيوط جراحية Vicryl 2-0');
        $indexRes->assertSee('Propofol 1% Vial');

        // 2. /surgeries/{id}?tab=detail_supplies exposes all OR store batches to surgery_staff
        $showRes = $this->actingAs($surgeryStaff)->get(route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_supplies']));
        $showRes->assertStatus(200);
        $showRes->assertSee('خيوط جراحية Vicryl 2-0');
        $showRes->assertSee('Propofol 1% Vial');
        $showRes->assertSee('صرف من مخزن العمليات');

        // 3. Unauthorized role (anesthesia) gets 403 on dispense
        $this->actingAs($anesthUser)->post(route('surgeries.supplies.dispense', $surgery), [
            'batch_id' => $medBatch->id,
            'quantity' => 2,
        ])->assertStatus(403);

        // 4. surgery_staff dispenses Propofol (anesthesia med in OR store) directly to patient
        $dispenseRes = $this->actingAs($surgeryStaff)->post(route('surgeries.supplies.dispense', $surgery), [
            'batch_id' => $medBatch->id,
            'quantity' => 3,
            'admin_notes' => 'جرعة تحريضية أثناء العملية',
        ]);
        $dispenseRes->assertRedirect(route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_supplies']));
        $dispenseRes->assertSessionHas('success');

        $medBatch->refresh();
        $this->assertEquals(5, $medBatch->current_qty);

        $movement = \App\Models\StockMovement::where('batch_id', $medBatch->id)
            ->where('type', 'issue_to_patient')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals($orStore->id, $movement->from_location_id);
        $this->assertEquals($surgery->id, $movement->surgery_id);
        $this->assertEquals(3, $movement->quantity);

        $treatment = \App\Models\SurgeryTreatment::where('surgery_id', $surgery->id)
            ->where('batch_id', $medBatch->id)
            ->first();
        $this->assertNotNull($treatment);
        $this->assertEquals(\App\Models\SurgeryTreatment::TYPE_SUPPLY, $treatment->item_type);
        $this->assertEquals('صرف من مخزن العمليات', $treatment->timing);
        $this->assertEquals(3, $treatment->quantity);

        // Movement appears in OR store recent feed
        $indexFeedRes = $this->actingAs($surgeryStaff)->get(route('surgeries.index', ['tab' => 'or_store']));
        $indexFeedRes->assertStatus(200);
        $indexFeedRes->assertSee('-3');
        $indexFeedRes->assertSee('عملية #'.$surgery->id);

        // 5. Unauthorized role gets 403 on reversal
        $this->actingAs($anesthUser)->delete(route('surgeries.supplies.reverse', [$surgery, $treatment]))
            ->assertStatus(403);

        // 6. surgery_staff reverses dispensed item back to OR store
        $reverseRes = $this->actingAs($surgeryStaff)->delete(route('surgeries.supplies.reverse', [$surgery, $treatment]));
        $reverseRes->assertRedirect(route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_supplies']));
        $reverseRes->assertSessionHas('success');

        $medBatch->refresh();
        $this->assertEquals(8, $medBatch->current_qty);
        $this->assertNull(\App\Models\SurgeryTreatment::find($treatment->id));
        $this->assertDatabaseHas('stock_movements', [
            'batch_id' => $medBatch->id,
            'type' => 'return',
            'quantity' => 3,
            'surgery_id' => $surgery->id,
        ]);

        // Unreferenced supply batch still intact
        $this->assertEquals(10, $supplyBatch->fresh()->current_qty);
    }

    public function test_hidden_detail_tabs_disappear_for_arabic_and_multi_role_accounts(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        \App\Models\SurgeryTabPermission::seedDefaults();

        $department = Department::where('type', 'surgery')->first();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'الجراح', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'التخدير', 'guard_name' => 'web']);

        $admin = User::create([
            'name' => 'أدمن إخفاء التبويبات',
            'email' => 'admin_hide_detail_tabs@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $multiSurgeon = User::create([
            'name' => 'د. جراح متعدد الأدوار',
            'email' => 'multi_surgeon_hide@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $multiSurgeon->assignRole(['doctor', 'الجراح', 'patient']);

        Doctor::create([
            'user_id' => $multiSurgeon->id,
            'department_id' => $department->id,
            'type' => 'surgeon',
            'phone' => '07701239991',
            'specialization' => 'جراحة عامة',
            'qualification' => 'Board',
            'license_number' => 'LIC-HIDE-1',
            'is_active' => true,
        ]);

        $arabicSurgeon = User::create([
            'name' => 'د. جراح بدور عربي فقط',
            'email' => 'arabic_surgeon_hide@example.com',
            'password' => bcrypt('password'),
            'role' => 'الجراح',
        ]);
        $arabicSurgeon->assignRole('الجراح');

        $arabicSurgeonDoc = Doctor::create([
            'user_id' => $arabicSurgeon->id,
            'department_id' => $department->id,
            'type' => 'surgeon',
            'phone' => '07701239992',
            'specialization' => 'جراحة عامة',
            'qualification' => 'Board',
            'license_number' => 'LIC-HIDE-2',
            'is_active' => true,
        ]);

        $anesthRoleUser = User::create([
            'name' => 'د. تخدير بدور عربي',
            'email' => 'arabic_anesth_hide@example.com',
            'password' => bcrypt('password'),
            'role' => 'Anesthesia',
        ]);
        $anesthRoleUser->assignRole('التخدير');

        $patientUser = User::create([
            'name' => 'مريض اختبار الإخفاء',
            'email' => 'patient_hide_tabs@example.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
        ]);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'age' => 33,
            'gender' => 'male',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $arabicSurgeonDoc->id,
            'department_id' => $department->id,
            'surgery_type' => 'Hernia Repair',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'in_progress',
            'surgery_fee' => 400000,
        ]);

        $this->actingAs($admin)->post(route('surgery-stations.tab-settings.update'), [
            'permissions' => [
                'doctor' => [
                    'detail_anesthesia' => 'hidden',
                    'detail_team_timing' => 'hidden',
                ],
                'anesthesia' => [
                    'detail_diagnosis' => 'hidden',
                ],
            ],
        ])->assertRedirect(route('surgery-stations.tab-settings.index'));

        foreach ([$multiSurgeon, $arabicSurgeon] as $surgeonAccount) {
            $res = $this->actingAs($surgeonAccount)->get(route('surgeries.show', $surgery));
            $res->assertStatus(200);
            $res->assertDontSee('id="tab-btn-detail_anesthesia"', false);
            $res->assertDontSee('id="pane-detail_anesthesia"', false);
            $res->assertDontSee('id="tab-btn-detail_team_timing"', false);
            $res->assertDontSee('id="pane-detail_team_timing"', false);
            $res->assertSee('id="tab-btn-detail_diagnosis"', false);
        }

        $anesthRes = $this->actingAs($anesthRoleUser)->get(route('surgeries.show', $surgery));
        $anesthRes->assertStatus(200);
        $anesthRes->assertDontSee('id="tab-btn-detail_diagnosis"', false);
        $anesthRes->assertDontSee('id="pane-detail_diagnosis"', false);
        $anesthRes->assertSee('id="tab-btn-detail_anesthesia"', false);
    }
}
