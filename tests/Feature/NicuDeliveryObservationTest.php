<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Incubator;
use App\Models\NicuDeliveryObservation;
use App\Models\NicuSetting;
use App\Models\OperationTheater;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\SurgicalOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NicuDeliveryObservationTest extends TestCase
{
    use RefreshDatabase;

    protected User $nicuNurse;

    protected User $admin;

    protected User $plainUser;

    protected Patient $motherPatient;

    protected Department $department;

    protected Doctor $doctor;

    protected SurgicalOperation $cSectionOp;

    protected OperationTheater $theater;

    protected function setUp(): void
    {
        parent::setUp();

        $viewRadarPerm = Permission::firstOrCreate(['name' => 'view nicu delivery radar', 'guard_name' => 'web']);
        $manageObsPerm = Permission::firstOrCreate(['name' => 'manage nicu delivery observations', 'guard_name' => 'web']);
        $manageSettingsPerm = Permission::firstOrCreate(['name' => 'manage nicu delivery radar settings', 'guard_name' => 'web']);
        $createResPerm = Permission::firstOrCreate(['name' => 'create incubator reservations', 'guard_name' => 'web']);
        $viewResPerm = Permission::firstOrCreate(['name' => 'view incubator reservations', 'guard_name' => 'web']);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([$viewRadarPerm, $manageObsPerm, $manageSettingsPerm, $createResPerm, $viewResPerm]);

        $nicuNurseRole = Role::firstOrCreate(['name' => 'nicu_nurse', 'guard_name' => 'web']);
        $nicuNurseRole->givePermissionTo([$viewRadarPerm, $manageObsPerm, $createResPerm, $viewResPerm]);

        $this->admin = User::factory()->create(['name' => 'مدير النظام']);
        $this->admin->assignRole('admin');

        $this->nicuNurse = User::factory()->create(['name' => 'ممرضة خدج']);
        $this->nicuNurse->assignRole('nicu_nurse');

        $this->plainUser = User::factory()->create(['name' => 'مستخدم عادي']);

        $hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'الإدارة',
            'license_number' => 'LIC-200',
            'phone' => '07760507060',
            'email' => 'alkafaathospital@gmail.com',
            'address' => 'بغداد - الحارثية شارع الكندي',
        ]);

        $this->department = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'العمليات الجراحية',
            'type' => 'obstetrics',
            'room_number' => 'OR-1',
            'consultation_fee' => 15000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '20:00:00',
            'code' => 'OBS-01',
        ]);

        $doctorUser = User::factory()->create(['name' => 'الدكتورة اسيل هاني']);
        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'department_id' => $this->department->id,
            'specialization' => 'نسائية وتوليد',
            'qualification' => 'بورد',
            'license_number' => 'DOC-OBS-'.uniqid(),
            'consultation_fee' => 25000,
            'is_active' => true,
        ]);

        $this->cSectionOp = SurgicalOperation::create([
            'name' => 'عملية القيصرية الثانية',
            'category' => 'النسائيه والتوليد',
            'fee' => 0,
            'is_active' => true,
            'show_in_nicu_radar' => true,
        ]);

        $this->theater = OperationTheater::create([
            'name' => 'صالة الولادة 1',
            'room_number' => 'OR-101',
            'is_active' => true,
        ]);

        $motherUser = User::factory()->create(['name' => 'رسل باسل أحمد']);
        $this->motherPatient = Patient::create([
            'user_id' => $motherUser->id,
            'file_number' => 'FN-3001',
            'gender' => 'female',
            'age' => 34,
            'date_of_birth' => '1992-07-01',
        ]);
    }

    public function test_unauthorized_user_gets_403_on_observation_endpoints(): void
    {
        $response = $this->actingAs($this->plainUser)
            ->post(route('nicu.observations.store-manual'), [
                'mother_name' => 'أم تجريبية',
            ]);

        $response->assertStatus(403);
    }

    public function test_nicu_staff_can_start_two_hour_observation_from_surgery_with_free_default(): void
    {
        $surgery = Surgery::create([
            'patient_id' => $this->motherPatient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'surgical_operation_id' => $this->cSectionOp->id,
            'surgery_type' => $this->cSectionOp->name,
            'surgery_category' => 'النسائيه والتوليد',
            'scheduled_date' => today()->format('Y-m-d'),
            'scheduled_time' => '11:45:00',
            'status' => 'completed',
            'operation_theater_id' => $this->theater->id,
            'anesthesia_type' => 'Spinal',
            'expected_stay_days' => 2,
        ]);

        $response = $this->actingAs($this->nicuNurse)
            ->post(route('nicu.observations.from-surgery', $surgery));

        $observation = NicuDeliveryObservation::where('surgery_id', $surgery->id)->first();
        $this->assertNotNull($observation);
        $response->assertRedirect(route('nicu.observations.show', $observation));

        $this->assertEquals('رسل باسل أحمد', $observation->mother_name);
        $this->assertEquals('الدكتورة اسيل هاني', $observation->surgeon_name);
        $this->assertEquals('عملية القيصرية الثانية', $observation->operative_description);
        $this->assertEquals(120, $observation->target_duration_minutes);
        $this->assertFalse($observation->is_billable);
        $this->assertEquals(0.0, (float) $observation->observation_fee);
        $this->assertEquals('free', $observation->payment_status);
        $this->assertCount(1, $observation->babies);

        // Radar page shows the new tabs and active observation
        $radarResponse = $this->actingAs($this->nicuNurse)
            ->get(route('nicu.delivery-radar.index', ['date' => today()->format('Y-m-d')]));
        $radarResponse->assertStatus(200);
        $radarResponse->assertSee('ملف المعاينة');
        $radarResponse->assertSee('معاينات الوليد الحية');
    }

    public function test_manual_normal_delivery_observation_update_handover_print_and_signed_archive(): void
    {
        Storage::fake('public');

        $storeResp = $this->actingAs($this->nicuNurse)
            ->post(route('nicu.observations.store-manual'), [
                'mother_name' => 'مريم علي كريم',
                'operative_description' => 'ولادة طبيعية',
                'no_of_borns' => 1,
                'baby_gender' => 'Male',
                'baby_weight_kg' => 3.25,
                'delivery_note' => '11:45 am',
            ]);

        $observation = NicuDeliveryObservation::where('mother_name', 'مريم علي كريم')->firstOrFail();
        $storeResp->assertRedirect(route('nicu.observations.show', $observation));
        $this->assertCount(1, $observation->babies);

        $baby = $observation->babies->first();

        // Update clinical fields and optional treatments
        $updateResp = $this->actingAs($this->nicuNurse)
            ->put(route('nicu.observations.update', $observation), [
                'mother_name' => 'مريم علي كريم',
                'mother_age_gender' => '29F',
                'case_of_delivery' => 'SINGLE',
                'operative_description' => 'ولادة طبيعية',
                'delivery_note' => '11:45 am',
                'nicu_doctor_name' => 'الدكتور محمد علاء دهام',
                'treatments' => [
                    [
                        'generic_name' => 'Phytomenadione (Vitamin K1)',
                        'brand_name' => 'Konakion 1 mg IM',
                        'frequency' => 'Stat once after birth',
                    ],
                ],
                'babies' => [
                    [
                        'id' => $baby->id,
                        'born_name' => 'طفل / مريم علي كريم',
                        'live_status' => 'Live Birth',
                        'gender' => 'Male',
                        'weight_kg' => 3.25,
                    ],
                ],
            ]);

        $updateResp->assertRedirect(route('nicu.observations.show', $observation));
        $observation->refresh();
        $this->assertEquals('الدكتور محمد علاء دهام', $observation->nicu_doctor_name);
        $this->assertCount(1, $observation->treatments);

        // Legal handover to father
        $handoverResp = $this->actingAs($this->nicuNurse)
            ->post(route('nicu.observations.handover', $observation), [
                'recipient_name' => 'مصطفى موفق ستار',
                'recipient_relation' => 'الأب',
                'recipient_id_number' => '199258652915',
                'recipient_phone' => '07731295876',
                'recipient_address' => 'شارع فلسطين',
            ]);

        $handoverResp->assertRedirect(route('nicu.observations.show', $observation));
        $observation->refresh();
        $this->assertEquals(NicuDeliveryObservation::STATUS_HANDED_OVER, $observation->status);
        $this->assertEquals('مصطفى موفق ستار', $observation->recipient_name);

        // A4 Print renders Obstetric Case Sheet & handover info
        $printResp = $this->actingAs($this->nicuNurse)
            ->get(route('nicu.observations.print', $observation));
        $printResp->assertStatus(200);
        $printResp->assertSee('Obstetric Case Sheet');
        $printResp->assertSee('مصطفى موفق ستار');
        $printResp->assertSee('199258652915');
        $printResp->assertSee('Konakion 1 mg IM');

        // Upload & preview signed form scan
        $fakeFile = UploadedFile::fake()->image('signed_sheet.jpg');
        $uploadResp = $this->actingAs($this->nicuNurse)
            ->post(route('nicu.observations.signed-form.upload', $observation), [
                'signed_form' => $fakeFile,
            ]);
        $uploadResp->assertRedirect(route('nicu.observations.show', $observation));

        $observation->refresh();
        $this->assertNotNull($observation->signed_form_path);
        Storage::disk('public')->assertExists($observation->signed_form_path);

        $downloadResp = $this->actingAs($this->nicuNurse)
            ->get(route('nicu.observations.signed-form.download', $observation));
        $downloadResp->assertStatus(200);
    }

    public function test_twins_can_split_between_handover_and_nicu_incubator_admission(): void
    {
        $incubator = Incubator::create([
            'incubator_number' => 'INC-901',
            'incubator_type' => 'normal',
            'floor_location' => 'الطابق الثاني - الخدج',
            'status' => Incubator::STATUS_AVAILABLE,
        ]);

        $this->actingAs($this->nicuNurse)
            ->post(route('nicu.observations.store-manual'), [
                'patient_id' => $this->motherPatient->id,
                'mother_name' => 'رسل باسل أحمد',
                'no_of_borns' => 2,
                'case_of_delivery' => 'TWIN',
                'baby_weight_kg' => 2.15,
            ]);

        $observation = NicuDeliveryObservation::where('patient_id', $this->motherPatient->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertCount(2, $observation->babies);
        $baby1 = $observation->babies[0];
        $baby2 = $observation->babies[1];

        // Prefill intake create page for baby 1
        $createResp = $this->actingAs($this->nicuNurse)
            ->get(route('incubator-reservations.create', [
                'observation_baby_id' => $baby1->id,
            ]));
        $createResp->assertStatus(200);
        $createResp->assertSee('تحويل مباشر من معاينة المواليد');
        $createResp->assertSee($baby1->born_name);

        // Admit baby 1 into NICU incubator
        $admitResp = $this->actingAs($this->nicuNurse)
            ->post(route('incubator-reservations.store'), [
                'patient_id' => $this->motherPatient->id,
                'is_independent_patient' => 0,
                'baby_name' => $baby1->born_name,
                'incubator_id' => $incubator->id,
                'doctor_id' => $this->doctor->id,
                'department_id' => $this->department->id,
                'admission_date' => today()->format('Y-m-d'),
                'admission_time' => '12:00',
                'admission_source' => 'internal',
                'observation_baby_id' => $baby1->id,
            ]);

        $admitResp->assertSessionHasNoErrors();
        $baby1->refresh();
        $this->assertNotNull($baby1->incubator_reservation_id);
        $this->assertEquals('admitted_to_nicu', $baby1->disposition);

        // Hand over baby 2 to father
        $this->actingAs($this->nicuNurse)
            ->post(route('nicu.observations.handover', $observation), [
                'recipient_name' => 'أب التوأم',
                'recipient_id_number' => 'ID-998877',
                'recipient_phone' => '07700001122',
                'baby_ids' => [$baby2->id],
            ]);

        $baby2->refresh();
        $observation->refresh();
        $this->assertEquals('handed_over', $baby2->disposition);
        $this->assertEquals(NicuDeliveryObservation::STATUS_PARTIAL, $observation->status);
    }

    public function test_dynamic_observation_settings_control_duration_and_optional_billing(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('nicu.delivery-radar.observation-settings.update'), [
                'observation_duration_minutes' => 90,
                'observation_is_billable' => 1,
                'observation_fee' => 25000,
                'hospital_print_name_ar' => 'مستشفى الاختبار التخصصي',
                'hospital_print_name_en' => 'Test Specialty Hospital',
                'hospital_print_email' => 'info@testhosp.com',
                'hospital_print_phone' => '07701234567',
                'hospital_print_address' => 'بغداد',
            ]);

        $response->assertRedirect(route('nicu.delivery-radar.settings'));
        $this->assertEquals(90, NicuSetting::getInt('observation_duration_minutes'));
        $this->assertTrue(NicuSetting::getBool('observation_is_billable'));
        $this->assertEquals(25000.0, NicuSetting::getFloat('observation_fee'));

        // New observation automatically inherits updated dynamic settings
        $this->actingAs($this->nicuNurse)
            ->post(route('nicu.observations.store-manual'), [
                'mother_name' => 'سارة محمود',
            ]);

        $obs = NicuDeliveryObservation::where('mother_name', 'سارة محمود')->firstOrFail();
        $this->assertEquals(90, $obs->target_duration_minutes);
        $this->assertTrue($obs->is_billable);
        $this->assertEquals(25000.0, (float) $obs->observation_fee);
        $this->assertEquals('pending', $obs->payment_status);
    }
}
