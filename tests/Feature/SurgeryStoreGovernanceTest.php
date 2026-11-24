<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Location;
use App\Models\LocationCustodianHistory;
use App\Models\Patient;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurgeryStoreGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $perm = Permission::firstOrCreate(['name' => 'manage surgery store', 'guard_name' => 'web']);
        $surgeryRole = Role::firstOrCreate(['name' => 'surgery_staff', 'guard_name' => 'web']);
        $surgeryRole->givePermissionTo($perm);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])->givePermissionTo($perm);
        Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
    }

    public function test_surgery_staff_and_admin_can_access_surgery_store_dashboard(): void
    {
        $surgeryStaff = User::factory()->create(['name' => 'موظف العمليات علي', 'role' => 'surgery_staff']);
        $surgeryStaff->assignRole('surgery_staff');

        $mainSurgeryStore = Location::create(['name' => 'مخزن عمليات الرئيسي', 'type' => 'sub']);
        $theaterStore = Location::create(['name' => 'صالة العمليات 1', 'type' => 'sub']);

        // مواقع خارج العمليات يجب استبعادها من لوحة العمليات
        $wardStore = Location::create(['name' => 'ردهة الطابق الخامس', 'type' => 'sub']);
        $labStore = Location::create(['name' => 'المختبر', 'type' => 'sub']);
        $maternityStore = Location::create(['name' => 'صالة الولادة', 'type' => 'sub']);

        $response = $this->actingAs($surgeryStaff)->get(route('surgery-store.index'));

        $response->assertStatus(200);
        $response->assertSee('مخزن وعُهد صالات العمليات');
        $response->assertSee('مخزن عمليات الرئيسي');
        $response->assertSee('صالة العمليات 1');

        $response->assertViewHas('subStores', function ($stores) use ($mainSurgeryStore, $theaterStore, $wardStore, $labStore, $maternityStore) {
            return $stores->contains('id', $mainSurgeryStore->id)
                && $stores->contains('id', $theaterStore->id)
                && ! $stores->contains('id', $wardStore->id)
                && ! $stores->contains('id', $labStore->id)
                && ! $stores->contains('id', $maternityStore->id);
        });
    }

    public function test_unauthorized_user_cannot_access_surgery_store(): void
    {
        $regularNurse = User::factory()->create(['name' => 'ممرض اعتيادي', 'role' => 'nurse']);
        $regularNurse->assignRole('nurse');

        $response = $this->actingAs($regularNurse)->get(route('surgery-store.index'));
        $response->assertStatus(403);
    }

    public function test_surgery_staff_can_assign_and_change_custodian_with_history_log(): void
    {
        $admin = User::factory()->create(['name' => 'مدير المستشفى', 'role' => 'admin']);
        $admin->assignRole('admin');

        $mainStore = Location::create(['name' => 'مخزن عمليات الرئيسي', 'type' => 'sub']);

        $staff1 = User::factory()->create(['name' => 'موظف عمليات 1', 'role' => 'surgery_staff']);
        $staff1->assignRole('surgery_staff');

        $assignResponse = $this->actingAs($admin)->post(route('surgery-store.assign-custodian', $mainStore), [
            'custodian_id' => $staff1->id,
            'sync_user_location' => true,
            'notes' => 'تكليف رسمي بإدارة مخزن العمليات الرئيسي',
        ]);

        $assignResponse->assertRedirect(route('surgery-store.index'));
        $assignResponse->assertSessionHas('success');

        $mainStore->refresh();
        $this->assertEquals($staff1->id, $mainStore->custodian_id);

        $staff1->refresh();
        $this->assertEquals($mainStore->id, $staff1->location_id);

        $this->assertDatabaseHas('location_custodian_histories', [
            'location_id' => $mainStore->id,
            'user_id' => $staff1->id,
            'assigned_by' => $admin->id,
            'notes' => 'تكليف رسمي بإدارة مخزن العمليات الرئيسي',
        ]);

        // تغيير المسؤول إلى موظف عمليات آخر
        $staff2 = User::factory()->create(['name' => 'موظف عمليات 2', 'role' => 'surgery_staff']);
        $staff2->assignRole('surgery_staff');

        $changeResponse = $this->actingAs($admin)->post(route('surgery-store.assign-custodian', $mainStore), [
            'custodian_id' => $staff2->id,
            'sync_user_location' => true,
            'notes' => 'تبديل مسؤول العهدة الجراحية',
        ]);

        $changeResponse->assertRedirect(route('surgery-store.index'));

        $mainStore->refresh();
        $this->assertEquals($staff2->id, $mainStore->custodian_id);

        $this->assertCount(2, LocationCustodianHistory::where('location_id', $mainStore->id)->get());
    }

    public function test_surgery_staff_can_view_location_details_and_consumption_movements(): void
    {
        $surgeryStaff = User::factory()->create(['name' => 'موظف العمليات سمير', 'role' => 'surgery_staff']);
        $surgeryStaff->assignRole('surgery_staff');

        $theater = Location::create(['name' => 'صالة العمليات 1', 'type' => 'sub', 'custodian_id' => $surgeryStaff->id]);

        $product = Product::create(['name' => 'سيت عمليات جراحي ( بقجة )', 'code' => 'SURG-SET-01', 'unit' => 'سيت']);
        $batch = StockBatch::create([
            'product_id' => $product->id,
            'location_id' => $theater->id,
            'internal_barcode' => 'OR-BATCH-01',
            'current_qty' => 20,
            'initial_qty' => 20,
            'cost_price' => 15000,
            'received_at' => now(),
            'expiry_date' => now()->addYears(2),
        ]);

        $hospital = \App\Models\Hospital::create([
            'name' => 'المستشفى النموذجي',
            'owner_name' => 'الدكتور المدير',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-TEST-01',
        ]);

        $dept = Department::create([
            'hospital_id' => $hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => 'OR-101',
            'consultation_fee' => 10000,
            'working_hours_start' => '08:00:00',
            'working_hours_end' => '16:00:00',
        ]);

        $docUser = User::factory()->create(['name' => 'د. محمد الجراح']);
        $docUser->assignRole('doctor');
        $doctor = Doctor::create([
            'user_id' => $docUser->id,
            'department_id' => $dept->id,
            'specialization' => 'جراحة عامة',
            'qualification' => 'بورد / ماجستير',
            'license_number' => 'DOC-12345',
        ]);

        $patientUser = User::factory()->create(['name' => 'المريض قاسم']);
        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'blood_type' => 'O+',
        ]);

        $surgery = Surgery::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept->id,
            'surgeon_name' => 'د. محمد الجراح',
            'surgery_type' => 'استئصال الزائدة الدودية',
            'scheduled_date' => today()->format('Y-m-d'),
            'scheduled_time' => '10:00:00',
            'status' => 'scheduled',
            'location_id' => $theater->id,
        ]);

        // حركة صرف للمستلزم الجراحي مرتبطة بالعملية
        StockMovement::create([
            'batch_id' => $batch->id,
            'from_location_id' => $theater->id,
            'quantity' => 2,
            'type' => 'dispense',
            'surgery_id' => $surgery->id,
            'user_id' => $surgeryStaff->id,
            'notes' => 'تجهيز صالة العمليات 1 لعملية استئصال الزائدة',
        ]);

        $response = $this->actingAs($surgeryStaff)->get(route('surgery-store.show', $theater));

        $response->assertStatus(200);
        $response->assertSee('صالة العمليات 1');
        $response->assertSee('سيت عمليات جراحي ( بقجة )');
        $response->assertSee('استئصال الزائدة الدودية');
        $response->assertSee('موظف العمليات سمير');
    }
}
