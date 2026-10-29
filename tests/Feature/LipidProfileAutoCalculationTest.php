<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\LabTestSubTest;
use App\Models\Patient;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LipidProfileAutoCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected $labStaffUser;

    protected $patient;

    protected $visit;

    protected $lipidProfile;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('lab_staff', 'web');

        Permission::findOrCreate('view lab tests', 'web');
        Permission::findOrCreate('process lab tests', 'web');

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-001',
        ]);

        $department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'المختبر الرئيسي',
            'type' => 'laboratory',
            'room_number' => '1',
            'consultation_fee' => 0,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
        ]);

        $this->labStaffUser = User::create([
            'name' => 'فني المختبر الأول',
            'email' => 'lab_tech@hospital.test',
            'password' => bcrypt('password'),
            'role' => 'lab_staff',
            'hospital_id' => $hospital->id,
        ]);
        $this->labStaffUser->assignRole('lab_staff');
        $this->labStaffUser->givePermissionTo(['view lab tests', 'process lab tests']);

        $patientUser = User::create([
            'name' => 'أحمد علي حسن',
            'email' => 'ahmed_patient@hospital.test',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'hospital_id' => $hospital->id,
        ]);

        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'أحمد علي حسن',
            'gender' => 'male',
            'date_of_birth' => '1985-05-15',
            'phone' => '07712345678',
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'hospital_id' => $hospital->id,
            'department_id' => $department->id,
            'visit_date' => now()->toDateString(),
            'visit_type' => 'checkup',
            'visit_time' => now(),
            'chief_complaint' => 'فحص روتيني شامل',
            'status' => 'pending',
        ]);

        // إعداد فحص Lipid Profile وفحوصاته الفرعية
        $this->lipidProfile = LabTest::create([
            'name' => 'Lipid Profile',
            'code' => 'LIPID_PROFILE',
            'main_category' => 'Biochemistry',
            'subcategory' => 'LIPID PROFILE',
            'unit' => 'mg/dL',
            'result_entry_type' => LabTest::ENTRY_TYPE_SUBTESTS,
            'is_active' => true,
            'price' => 35000,
        ]);

        $subTests = [
            ['name' => 'Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '100 - 200', 'sort_order' => 1],
            ['name' => 'Triglyceride', 'unit' => 'mg/dL', 'reference_range' => '60 - 160', 'sort_order' => 2],
            ['name' => 'High Density Lipoprotein', 'unit' => 'mg/dL', 'reference_range' => '> 35', 'sort_order' => 3],
            ['name' => 'Low Density Lipoprotein (Calculated)', 'unit' => 'mg/dL', 'reference_range' => '< 130', 'sort_order' => 4],
            ['name' => 'Very Low Density Lipoprotein (Calculated)', 'unit' => 'mg/dL', 'reference_range' => '5 - 30', 'sort_order' => 5],
        ];

        foreach ($subTests as $st) {
            LabTestSubTest::create([
                'lab_test_id' => $this->lipidProfile->id,
                'name' => $st['name'],
                'unit' => $st['unit'],
                'reference_range' => $st['reference_range'],
                'result_type' => 'numeric',
                'sort_order' => $st['sort_order'],
            ]);
        }
    }

    /**
     * اختبار: عند إدخال TC=210 و HDL=45 و TG=150، يقوم النظام تلقائياً بحساب VLDL=30 و LDL=135
     */
    public function test_auto_calculates_vldl_and_ldl_via_friedewald_equation(): void
    {
        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'Lipid Profile',
            'status' => 'in_progress',
            'payment_status' => 'paid',
            'details' => json_encode([
                'tests' => ['Lipid Profile'],
                'lab_test_ids' => [$this->lipidProfile->id],
            ]),
        ]);

        $subChol = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'Cholesterol')->first();
        $subTg = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'Triglyceride')->first();
        $subHdl = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'High Density Lipoprotein')->first();
        $subLdl = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'Low Density Lipoprotein (Calculated)')->first();
        $subVldl = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'Very Low Density Lipoprotein (Calculated)')->first();

        // إرسال النتائج مع ترك خانتي LDL و VLDL فارغتين ليقوم السيرفر والواجهة بحسابهما
        $postData = [
            'action' => 'complete',
            'status' => 'completed',
            'test_results' => [
                'sub_'.$subChol->id => [
                    'test_name' => $subChol->name,
                    'parent_test_name' => 'Lipid Profile',
                    'sub_test_id' => $subChol->id,
                    'lab_test_id' => $this->lipidProfile->id,
                    'value' => '210',
                    'unit' => 'mg/dL',
                ],
                'sub_'.$subTg->id => [
                    'test_name' => $subTg->name,
                    'parent_test_name' => 'Lipid Profile',
                    'sub_test_id' => $subTg->id,
                    'lab_test_id' => $this->lipidProfile->id,
                    'value' => '150',
                    'unit' => 'mg/dL',
                ],
                'sub_'.$subHdl->id => [
                    'test_name' => $subHdl->name,
                    'parent_test_name' => 'Lipid Profile',
                    'sub_test_id' => $subHdl->id,
                    'lab_test_id' => $this->lipidProfile->id,
                    'value' => '45',
                    'unit' => 'mg/dL',
                ],
                'sub_'.$subLdl->id => [
                    'test_name' => $subLdl->name,
                    'parent_test_name' => 'Lipid Profile',
                    'sub_test_id' => $subLdl->id,
                    'lab_test_id' => $this->lipidProfile->id,
                    'value' => '', // تركت فارغة للحساب الآلي
                    'unit' => 'mg/dL',
                ],
                'sub_'.$subVldl->id => [
                    'test_name' => $subVldl->name,
                    'parent_test_name' => 'Lipid Profile',
                    'sub_test_id' => $subVldl->id,
                    'lab_test_id' => $this->lipidProfile->id,
                    'value' => '', // تركت فارغة للحساب الآلي
                    'unit' => 'mg/dL',
                ],
            ],
        ];

        $response = $this->actingAs($this->labStaffUser)->put(route('lab.update', $request), $postData);

        $response->assertRedirect(route('lab.index'));

        // التأكد من حفظ VLDL = 150 / 5 = 30
        $vldlResult = LabResult::where('request_id', $request->id)
            ->where('test_name', 'Very Low Density Lipoprotein (Calculated)')
            ->first();
        $this->assertNotNull($vldlResult, 'VLDL result must be automatically calculated and saved');
        $this->assertEquals('30', $vldlResult->value);
        $this->assertStringContainsString('TG / 5', $vldlResult->notes);

        // التأكد من حفظ LDL = 210 - 45 - 30 = 135
        $ldlResult = LabResult::where('request_id', $request->id)
            ->where('test_name', 'Low Density Lipoprotein (Calculated)')
            ->first();
        $this->assertNotNull($ldlResult, 'LDL result must be automatically calculated and saved');
        $this->assertEquals('135', $ldlResult->value);
        $this->assertStringContainsString('Friedewald', $ldlResult->notes);

        // التأكد من تحديث النتيجة في كائن الطلب الطبي للعرض عند الطبيب
        $request->refresh();
        $resJson = json_decode($request->result, true);
        $this->assertEquals('30', $resJson['test_results']['sub_'.$subVldl->id]['value']);
        $this->assertEquals('135', $resJson['test_results']['sub_'.$subLdl->id]['value']);
    }

    /**
     * اختبار: عندما تكون الدهون الثلاثية TG >= 400، لا يتم حساب LDL قسراً ويظهر التنبيه السريري
     */
    public function test_when_triglycerides_ge_400_friedewald_ldl_is_not_force_calculated(): void
    {
        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'Lipid Profile',
            'status' => 'in_progress',
            'payment_status' => 'paid',
            'details' => json_encode(['tests' => ['Lipid Profile']]),
        ]);

        $subChol = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'Cholesterol')->first();
        $subTg = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'Triglyceride')->first();
        $subHdl = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'High Density Lipoprotein')->first();
        $subLdl = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'Low Density Lipoprotein (Calculated)')->first();
        $subVldl = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'Very Low Density Lipoprotein (Calculated)')->first();

        // إرسال TG = 450 (أعلى من 400)
        $postData = [
            'action' => 'complete',
            'status' => 'completed',
            'test_results' => [
                'sub_'.$subChol->id => ['test_name' => $subChol->name, 'value' => '260', 'unit' => 'mg/dL'],
                'sub_'.$subTg->id => ['test_name' => $subTg->name, 'value' => '450', 'unit' => 'mg/dL'],
                'sub_'.$subHdl->id => ['test_name' => $subHdl->name, 'value' => '35', 'unit' => 'mg/dL'],
                'sub_'.$subLdl->id => ['test_name' => $subLdl->name, 'value' => '', 'unit' => 'mg/dL'],
                'sub_'.$subVldl->id => ['test_name' => $subVldl->name, 'value' => '', 'unit' => 'mg/dL'],
            ],
        ];

        $response = $this->actingAs($this->labStaffUser)->put(route('lab.update', $request), $postData);
        $response->assertRedirect(route('lab.index'));

        // VLDL يُحسب دائماً (450 / 5 = 90)
        $vldlResult = LabResult::where('request_id', $request->id)
            ->where('test_name', 'Very Low Density Lipoprotein (Calculated)')
            ->first();
        $this->assertNotNull($vldlResult);
        $this->assertEquals('90', $vldlResult->value);

        // LDL لم يُحسب قسراً لأن TG >= 400
        $ldlResult = LabResult::where('request_id', $request->id)
            ->where('test_name', 'Low Density Lipoprotein (Calculated)')
            ->first();
        $this->assertNull($ldlResult);
    }

    /**
     * اختبار: الإدخال اليدوي المباشر للـ LDL يتم احترامه ولا يتم طمسه
     */
    public function test_respects_manual_ldl_entry(): void
    {
        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'Lipid Profile',
            'status' => 'in_progress',
            'payment_status' => 'paid',
            'details' => json_encode(['tests' => ['Lipid Profile']]),
        ]);

        $subChol = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'Cholesterol')->first();
        $subTg = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'Triglyceride')->first();
        $subHdl = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'High Density Lipoprotein')->first();
        $subLdl = LabTestSubTest::where('lab_test_id', $this->lipidProfile->id)->where('name', 'Low Density Lipoprotein (Calculated)')->first();

        // قام الفني بإدخال LDL مقاس يدوياً أو مباشر = 120
        $postData = [
            'action' => 'complete',
            'status' => 'completed',
            'test_results' => [
                'sub_'.$subChol->id => ['test_name' => $subChol->name, 'value' => '210', 'unit' => 'mg/dL'],
                'sub_'.$subTg->id => ['test_name' => $subTg->name, 'value' => '150', 'unit' => 'mg/dL'],
                'sub_'.$subHdl->id => ['test_name' => $subHdl->name, 'value' => '45', 'unit' => 'mg/dL'],
                'sub_'.$subLdl->id => ['test_name' => $subLdl->name, 'value' => '120', 'unit' => 'mg/dL'], // مدخل يدوياً
            ],
        ];

        $response = $this->actingAs($this->labStaffUser)->put(route('lab.update', $request), $postData);
        $response->assertRedirect(route('lab.index'));

        $ldlResult = LabResult::where('request_id', $request->id)
            ->where('test_name', 'Low Density Lipoprotein (Calculated)')
            ->first();
        $this->assertNotNull($ldlResult);
        $this->assertEquals('120', $ldlResult->value, 'Manual entry should be preserved');
    }

    /**
     * اختبار: الحساب الآلي عندما تكون الفحوصات مسجلة كفحوصات مفردة مستقلة (Single Tests)
     */
    public function test_auto_calculates_individual_single_tests(): void
    {
        $testChol = LabTest::create(['name' => 'Cholesterol', 'code' => 'CHOL_TOT', 'unit' => 'mg/dL', 'is_active' => true, 'price' => 10000]);
        $testTg = LabTest::create(['name' => 'Triglyceride', 'code' => 'TRIG', 'unit' => 'mg/dL', 'is_active' => true, 'price' => 10000]);
        $testHdl = LabTest::create(['name' => 'High Density Lipoprotein', 'code' => 'HDL', 'unit' => 'mg/dL', 'is_active' => true, 'price' => 10000]);
        $testLdl = LabTest::create(['name' => 'Low Density Lipoprotein', 'code' => 'LDL', 'unit' => 'mg/dL', 'is_active' => true, 'price' => 10000]);
        $testVldl = LabTest::create(['name' => 'Very Low Density Lipoprotein', 'code' => 'VLDL', 'unit' => 'mg/dL', 'is_active' => true, 'price' => 10000]);

        $request = MedicalRequest::create([
            'visit_id' => $this->visit->id,
            'type' => 'lab',
            'description' => 'Individual Lipid Tests',
            'status' => 'in_progress',
            'payment_status' => 'paid',
            'details' => json_encode(['tests' => ['Cholesterol', 'Triglyceride', 'High Density Lipoprotein', 'Low Density Lipoprotein', 'Very Low Density Lipoprotein']]),
        ]);

        $postData = [
            'action' => 'complete',
            'status' => 'completed',
            'test_results' => [
                'Cholesterol' => ['test_name' => 'Cholesterol', 'value' => '200', 'unit' => 'mg/dL'],
                'Triglyceride' => ['test_name' => 'Triglyceride', 'value' => '100', 'unit' => 'mg/dL'],
                'High Density Lipoprotein' => ['test_name' => 'High Density Lipoprotein', 'value' => '50', 'unit' => 'mg/dL'],
                'Low Density Lipoprotein' => ['test_name' => 'Low Density Lipoprotein', 'value' => '', 'unit' => 'mg/dL'], // فارغ للحساب الآلي
                'Very Low Density Lipoprotein' => ['test_name' => 'Very Low Density Lipoprotein', 'value' => '', 'unit' => 'mg/dL'], // فارغ للحساب الآلي
            ],
        ];

        $response = $this->actingAs($this->labStaffUser)->put(route('lab.update', $request), $postData);
        $response->assertRedirect(route('lab.index'));

        // VLDL = 100 / 5 = 20
        $vldlResult = LabResult::where('request_id', $request->id)->where('test_name', 'Very Low Density Lipoprotein')->first();
        $this->assertNotNull($vldlResult);
        $this->assertEquals('20', $vldlResult->value);

        // LDL = 200 - 50 - 20 = 130
        $ldlResult = LabResult::where('request_id', $request->id)->where('test_name', 'Low Density Lipoprotein')->first();
        $this->assertNotNull($ldlResult);
        $this->assertEquals('130', $ldlResult->value);
    }
}
