<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Emergency;
use App\Models\EmergencyRadiologyRequest;
use App\Models\Patient;
use App\Models\RadiologyType;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RadiologyPriorPendingRequestsTabTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $patient;

    protected $department;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Hospital::firstOrCreate(
            ['name' => 'مستشفى الشفاء'],
            ['address' => 'بغداد', 'phone' => '07700000000', 'email' => 'info@example.com', 'owner_name' => 'المدير العام', 'license_number' => 'HOSP-12345']
        );

        Permission::firstOrCreate(['name' => 'view radiology', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process radiology requests', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'radiology_staff', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole('radiology_staff');
        $this->user->givePermissionTo(['view radiology', 'process radiology requests']);

        $patientUser = User::factory()->create(['name' => 'أحمد عبد الله التميمي']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'birth_date' => '1990-05-15',
            'medical_number' => 'MED-998877',
            'phone' => '07712345678',
        ]);

        $this->department = Department::create([
            'hospital_id' => 1,
            'name' => 'الاستعلامات',
            'type' => 'other',
            'room_number' => 'REC-01',
            'consultation_fee' => 0,
            'working_hours_start' => '08:00',
            'working_hours_end' => '16:00',
        ]);
    }

    public function test_radiology_index_displays_prior_pending_tab_and_badge()
    {
        // 1. طلب من يومين سابقين (معلق)
        $priorVisit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->subDays(2)->toDateString(),
            'visit_time' => '10:00:00',
            'visit_type' => 'radiology',
            'chief_complaint' => 'ألم بالصدر',
            'status' => 'pending',
            'created_at' => now()->subDays(2),
        ]);

        $priorRequest = MedicalRequest::create([
            'visit_id' => $priorVisit->id,
            'type' => 'radiology',
            'subtype' => 'radiology',
            'description' => 'أشعة سينية للصدر Chest X-Ray',
            'status' => 'pending',
            'payment_status' => 'paid',
        ]);
        $priorRequest->created_at = now()->subDays(2);
        $priorRequest->save();

        // 2. طلب من اليوم
        $todayVisit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->toDateString(),
            'visit_time' => '09:00:00',
            'visit_type' => 'radiology',
            'chief_complaint' => 'ألم بالركبة',
            'status' => 'pending',
        ]);

        MedicalRequest::create([
            'visit_id' => $todayVisit->id,
            'type' => 'radiology',
            'subtype' => 'radiology',
            'description' => 'أشعة للركبة Knee X-Ray',
            'status' => 'pending',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('radiology-staff.index'));

        $response->assertStatus(200);
        $response->assertSee('الطلبات المعلقة (أيام سابقة)');
        $response->assertSee('id="badge-tab-pending-count"', false);
        $response->assertSee('id="pending-pane"', false);

        // يجب أن يظهر طلب الأيام السابقة داخل تبويب المعلقة
        $response->assertSee('#'.$priorRequest->id);
        $response->assertSee('أحمد عبد الله التميمي');
    }

    public function test_queue_status_returns_accurate_prior_pending_count()
    {
        $priorVisit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->subDays(3)->toDateString(),
            'visit_time' => '11:00:00',
            'visit_type' => 'radiology',
            'chief_complaint' => 'مراجعة معلقة',
            'status' => 'pending',
            'created_at' => now()->subDays(3),
        ]);

        $priorReq = MedicalRequest::create([
            'visit_id' => $priorVisit->id,
            'type' => 'radiology',
            'subtype' => 'radiology',
            'description' => 'فحص معلق قديم',
            'status' => 'pending',
            'payment_status' => 'paid',
        ]);
        $priorReq->created_at = now()->subDays(3);
        $priorReq->save();

        $response = $this->actingAs($this->user)
            ->getJson(route('radiology-staff.queue-status', ['category' => 'radiology']));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals(1, $response->json('stats.prior_pending'));
    }

    public function test_emergency_tab_renders_with_unified_table_styling()
    {
        $doctorUser = User::factory()->create(['name' => 'طبيب الطوارئ المقيم']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'hospital_id' => 1,
            'department_id' => $this->department->id,
            'specialization' => 'طوارئ',
            'qualification' => 'بكالوريوس طب وجراحة',
            'license_number' => 'DOC-EM-999',
            'phone' => '07700000001',
        ]);

        $emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'active',
            'priority' => 'critical',
            'emergency_type' => 'medical',
            'admission_time' => now(),
            'symptoms' => 'ألم حاد واشتباه كسر',
            'payment_status' => 'paid',
            'is_active' => true,
        ]);

        $emReq = EmergencyRadiologyRequest::create([
            'emergency_id' => $emergency->id,
            'patient_id' => $this->patient->id,
            'status' => 'pending',
            'priority' => 'critical',
            'payment_status' => 'paid',
            'notes' => 'اشتباه كسر عاجل',
            'requested_at' => now(),
        ]);

        $radType = RadiologyType::create([
            'hospital_id' => 1,
            'name' => 'أشعة طارئة للكتف',
            'code' => 'RAD-EM-01',
            'price' => 25000,
            'base_price' => 25000,
            'category' => 'x-ray',
            'subcategory' => 'أشعة',
            'status' => 'active',
            'is_active' => true,
        ]);

        $emReq->radiologyTypes()->attach($radType->id);

        $response = $this->actingAs($this->user)
            ->get(route('radiology-staff.index'));

        $response->assertStatus(200);
        $response->assertSee('id="emergency-tab"', false);
        $response->assertSee('id="emergency-pane"', false);
        $response->assertSee('سجل طلبات فحوصات قسم الطوارئ');
        $response->assertSee('طبيب الطوارئ المقيم');
        $response->assertSee('أشعة طارئة للكتف');
        $response->assertSee('اشتباه كسر عاجل');
        $response->assertSee('حرج');
        $response->assertSee('بدء الفحص');
    }

    public function test_radiology_index_displays_lab_style_department_cards_and_tabs()
    {
        $response = $this->actingAs($this->user)
            ->get(route('radiology-staff.index'));

        $response->assertStatus(200);
        $response->assertSee('طلبات العيادات الاستشارية');
        $response->assertSee('طلبات الأشعة المباشرة');
        $response->assertSee('أشعة وفحوصات قسم الطوارئ');
        $response->assertSee('كافة طلبات القسم');
        $response->assertSee('id="radiology-department-cards"', false);
        $response->assertSee('id="station-tab"', false);
        $response->assertSee('id="direct-tab"', false);
        $response->assertSee('id="emergency-tab"', false);
        $response->assertSee('id="pending-tab"', false);
        $response->assertSee('id="all-tab"', false);
        $response->assertSee('id="direct-pane"', false);
    }

    public function test_radiology_index_date_filter_keeps_consultation_tab_active_and_displays_results()
    {
        $doctorUser = User::factory()->create(['name' => 'د. حيدر الاستشاري']);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'باطنية',
            'qualification' => 'استشاري',
            'license_number' => 'DOC-999',
            'is_active' => true,
        ]);

        $yesterdayVisit = Visit::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->department->id,
            'visit_date' => now()->subDay()->toDateString(),
            'visit_time' => '10:30:00',
            'visit_type' => 'radiology',
            'chief_complaint' => 'ألم صدري',
            'status' => 'completed',
            'created_at' => now()->subDay(),
        ]);

        $yesterdayReq = MedicalRequest::create([
            'visit_id' => $yesterdayVisit->id,
            'type' => 'radiology',
            'subtype' => 'radiology',
            'description' => 'أشعة رئة سابقة Chest X-Ray Yesterday',
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
        $yesterdayReq->created_at = now()->subDay();
        $yesterdayReq->save();

        // 1. طلب الصفحة بفلتر الأمس
        $response = $this->actingAs($this->user)
            ->get(route('radiology-staff.index', ['date' => 'yesterday']));

        $response->assertStatus(200);

        // يجب أن يكون تبويب الاستشارية وبانيل الاستشارية نشطين فوراً
        $response->assertSee('class="tab-pane fade show active" id="station-pane"', false);
        $response->assertSee('id="station-tab"', false);

        // يجب أن يظهر طلب الأمس فوراً في الجدول دون تنقل
        $response->assertSee('أشعة رئة سابقة Chest X-Ray Yesterday');
        $response->assertSee('د. حيدر الاستشاري');
        $response->assertSee('أحمد عبد الله التميمي');
    }
}
