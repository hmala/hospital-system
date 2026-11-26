<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Room;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Surgery;
use App\Models\SurgeryTreatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NursingFloorDispenseTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected Hospital $hospital;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'د. صاحب المستشفى',
            'license_number' => 'LIC-001',
            'phone' => '07700000000',
            'email' => 'info@alkafaat.com',
            'address' => 'بغداد',
        ]);

        $this->department = Department::create([
            'hospital_id' => $this->hospital->id,
            'name' => 'قسم الجراحة العامة',
            'type' => 'surgery',
            'room_number' => '101',
            'consultation_fee' => 50000,
            'working_hours_start' => '08:00',
            'working_hours_end' => '20:00',
            'max_patients_per_day' => 30,
            'is_active' => true,
        ]);
    }

    protected function createPatient(string $name = 'مريض تجريبي'): Patient
    {
        $user = User::factory()->create(['name' => $name]);

        return Patient::create([
            'user_id' => $user->id,
            'age' => 30,
            'gender' => 'male',
        ]);
    }

    protected function createSurgery(Patient $patient, ?Room $room = null): Surgery
    {
        return Surgery::create([
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'room_id' => $room?->id,
            'surgery_type' => 'جراحة عامة',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'status' => 'scheduled',
            'surgery_fee' => 100000,
        ]);
    }

    public function test_surgery_correctly_resolves_floor_sub_store_location(): void
    {
        $locFloor3 = Location::create(['name' => 'ردهةالطابق الثالث', 'type' => 'sub']);
        $locFloor2 = Location::create(['name' => 'ردهة الطابق الثاني', 'type' => 'sub']);

        $patient = $this->createPatient();
        $room302 = Room::create(['room_number' => '302', 'floor' => 'الطابق الثالث', 'room_type' => 'regular']);
        $surgery = $this->createSurgery($patient, $room302);

        $resolvedLoc = $surgery->getFloorSubStoreLocation();
        $this->assertNotNull($resolvedLoc);
        $this->assertEquals($locFloor3->id, $resolvedLoc->id);

        // Change room to floor 2
        $room201 = Room::create(['room_number' => '201', 'floor' => 'الطابق الثاني', 'room_type' => 'vip']);
        $surgery->update(['room_id' => $room201->id]);
        $surgery->refresh();

        $resolvedLoc2 = $surgery->getFloorSubStoreLocation();
        $this->assertNotNull($resolvedLoc2);
        $this->assertEquals($locFloor2->id, $resolvedLoc2->id);
    }

    public function test_nurse_can_dispense_supply_from_floor_sub_store_and_deducts_quantity(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $locFloor3 = Location::create(['name' => 'ردهةالطابق الثالث', 'type' => 'sub']);
        $room305 = Room::create(['room_number' => '305', 'floor' => 'الطابق الثالث', 'room_type' => 'regular']);
        $patient = $this->createPatient('أحمد قاسم');
        $surgery = $this->createSurgery($patient, $room305);

        $product = Product::create([
            'name' => 'Cannula Blue 22G',
            'code' => 'SUP-001',
            'category' => 'مستلزمات طبية',
            'unit' => 'قطعة',
        ]);

        $batch = StockBatch::create([
            'product_id' => $product->id,
            'location_id' => $locFloor3->id,
            'internal_barcode' => 'BC1001',
            'manufacturer_lot_number' => 'LOT9988',
            'cost_price' => 2500,
            'initial_qty' => 15,
            'current_qty' => 15,
            'received_at' => now(),
            'expiry_date' => now()->addYear(),
        ]);

        $payload = [
            'batch_id' => $batch->id,
            'quantity' => 3,
            'timing' => 'تركيب كانيولا وريدية',
            'admin_notes' => 'تم التثبيت باليد اليمنى دون مضاعفات',
            'expected_type' => 'supply',
        ];

        $response = $this->actingAs($nurse)
            ->post(route('clinical-station.supplies.dispense', $surgery), $payload);

        $response->assertSessionHas('success');

        // Verify stock deducted
        $batch->refresh();
        $this->assertEquals(12, $batch->current_qty);

        // Verify StockMovement created
        $movement = StockMovement::where('batch_id', $batch->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('issue_to_patient', $movement->type);
        $this->assertEquals(3, $movement->quantity);
        $this->assertEquals($locFloor3->id, $movement->from_location_id);
        $this->assertEquals($nurse->id, $movement->user_id);
        $this->assertEquals($surgery->id, $movement->surgery_id);

        // Verify SurgeryTreatment created as supply
        $treatment = SurgeryTreatment::where('surgery_id', $surgery->id)->first();
        $this->assertNotNull($treatment);
        $this->assertEquals(SurgeryTreatment::TYPE_SUPPLY, $treatment->item_type);
        $this->assertEquals('administered', $treatment->status);
        $this->assertEquals('Cannula Blue 22G', $treatment->description);
        $this->assertEquals('3 قطعة', $treatment->dosage);
        $this->assertEquals($batch->id, $treatment->batch_id);
        $this->assertEquals($movement->id, $treatment->stock_movement_id);
        $this->assertEquals(3, $treatment->quantity);
        $this->assertEquals($nurse->id, $treatment->administered_by);
    }

    public function test_floor_dispense_rejects_medication_products_strictly(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $locFloor3 = Location::create(['name' => 'ردهةالطابق الثالث', 'type' => 'sub']);
        $room305 = Room::create(['room_number' => '305', 'floor' => 'الطابق الثالث', 'room_type' => 'regular']);
        $patient = $this->createPatient('سعد جبار');
        $surgery = $this->createSurgery($patient, $room305);

        $product = Product::create([
            'name' => 'Ceftriaxone 1g Vial',
            'code' => 'MED-001',
            'category' => 'أدوية',
            'unit' => 'فيال',
        ]);

        $batch = StockBatch::create([
            'product_id' => $product->id,
            'location_id' => $locFloor3->id,
            'internal_barcode' => 'BC-MED-01',
            'cost_price' => 2500,
            'initial_qty' => 10,
            'current_qty' => 10,
            'received_at' => now(),
            'expiry_date' => now()->addYear(),
        ]);

        $response = $this->actingAs($nurse)
            ->post(route('clinical-station.supplies.dispense', $surgery), [
                'batch_id' => $batch->id,
                'quantity' => 1,
                'expected_type' => 'supply',
            ]);

        $response->assertSessionHas('error');
        $this->assertEquals(10, $batch->fresh()->current_qty);
        $this->assertEquals(0, StockMovement::count());
        $this->assertEquals(0, SurgeryTreatment::count());
    }

    public function test_dispense_fails_when_quantity_exceeds_available_floor_stock(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $locFloor3 = Location::create(['name' => 'ردهةالطابق الثالث', 'type' => 'sub']);
        $room = Room::create(['room_number' => '301', 'floor' => 'الطابق الثالث', 'room_type' => 'regular']);
        $patient = $this->createPatient();
        $surgery = $this->createSurgery($patient, $room);

        $product = Product::create([
            'name' => 'Sterile Gauze 10x10',
            'code' => 'SUP-002',
            'category' => 'مستلزمات',
            'unit' => 'باكيت',
        ]);

        $batch = StockBatch::create([
            'product_id' => $product->id,
            'location_id' => $locFloor3->id,
            'internal_barcode' => 'BC1002',
            'cost_price' => 1500,
            'initial_qty' => 2,
            'current_qty' => 2,
            'received_at' => now(),
            'expiry_date' => now()->addYear(),
        ]);

        $payload = [
            'batch_id' => $batch->id,
            'quantity' => 5, // Exceeds available 2
            'expected_type' => 'supply',
        ];

        $response = $this->actingAs($nurse)
            ->post(route('clinical-station.supplies.dispense', $surgery), $payload);

        $response->assertSessionHas('error');

        // Stock must remain intact
        $batch->refresh();
        $this->assertEquals(2, $batch->current_qty);
        $this->assertEquals(0, StockMovement::count());
        $this->assertEquals(0, SurgeryTreatment::count());
    }

    public function test_dispense_fails_if_batch_does_not_belong_to_patient_floor(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $locFloor3 = Location::create(['name' => 'ردهةالطابق الثالث', 'type' => 'sub']);
        $locFloor2 = Location::create(['name' => 'ردهة الطابق الثاني', 'type' => 'sub']);

        // Patient is on Floor 3
        $room = Room::create(['room_number' => '303', 'floor' => 'الطابق الثالث', 'room_type' => 'regular']);
        $patient = $this->createPatient();
        $surgery = $this->createSurgery($patient, $room);

        $product = Product::create([
            'name' => 'Cannula Blue 22G',
            'code' => 'SUP-001',
            'category' => 'مستلزمات',
            'unit' => 'قطعة',
        ]);

        // Batch is located in Floor 2!
        $batchFloor2 = StockBatch::create([
            'product_id' => $product->id,
            'location_id' => $locFloor2->id,
            'internal_barcode' => 'BC2001',
            'cost_price' => 500,
            'initial_qty' => 20,
            'current_qty' => 20,
            'received_at' => now(),
            'expiry_date' => now()->addYear(),
        ]);

        $payload = [
            'batch_id' => $batchFloor2->id,
            'quantity' => 1,
            'expected_type' => 'supply',
        ];

        $response = $this->actingAs($nurse)
            ->post(route('clinical-station.supplies.dispense', $surgery), $payload);

        $response->assertSessionHas('error');

        // Stock must remain intact
        $batchFloor2->refresh();
        $this->assertEquals(20, $batchFloor2->current_qty);
        $this->assertEquals(0, StockMovement::count());
    }

    public function test_nurse_can_batch_dispense_multiple_supplies_at_once_with_proper_deductions_and_audit_trail(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $locFloor1 = Location::create(['name' => 'ردهة الطابق الأول', 'type' => 'sub']);
        $room101 = Room::create(['room_number' => '101', 'floor' => 'الطابق الأول', 'room_type' => 'regular']);
        $patient = $this->createPatient('كرار حيدر');
        $surgery = $this->createSurgery($patient, $room101);

        $prod1 = Product::create([
            'name' => 'Syringe 10ml',
            'code' => 'SYR-10',
            'category' => 'مستلزمات',
            'unit' => 'سرنجة',
        ]);
        $batch1 = StockBatch::create([
            'product_id' => $prod1->id,
            'location_id' => $locFloor1->id,
            'internal_barcode' => 'BC-SYR10',
            'cost_price' => 200,
            'initial_qty' => 50,
            'current_qty' => 50,
            'received_at' => now(),
        ]);

        $prod2 = Product::create([
            'name' => 'Gauze Sterile 10x10',
            'code' => 'GAU-10',
            'category' => 'مستلزمات',
            'unit' => 'باكيت',
        ]);
        $batch2 = StockBatch::create([
            'product_id' => $prod2->id,
            'location_id' => $locFloor1->id,
            'internal_barcode' => 'BC-GAU10',
            'cost_price' => 1500,
            'initial_qty' => 20,
            'current_qty' => 20,
            'received_at' => now(),
        ]);

        $batchPayload = [
            'phase' => 'post_op',
            'from' => 'nursing',
            'items' => [
                [
                    'batch_id' => $batch1->id,
                    'quantity' => 5,
                    'timing' => 'سحب عينات وسوائل',
                    'admin_notes' => 'صرف وجبة صباحية',
                ],
                [
                    'batch_id' => $batch2->id,
                    'quantity' => 2,
                    'timing' => 'تضميد الجرح الجراحي',
                    'admin_notes' => 'غيار يومي',
                ],
            ],
        ];

        $response = $this->actingAs($nurse)
            ->post(route('clinical-station.supplies.dispense', $surgery), $batchPayload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify stock batches were decremented properly
        $batch1->refresh();
        $this->assertEquals(45, $batch1->current_qty);

        $batch2->refresh();
        $this->assertEquals(18, $batch2->current_qty);

        // Verify stock movements
        $movements = StockMovement::where('surgery_id', $surgery->id)->get();
        $this->assertCount(2, $movements);
        $this->assertTrue($movements->every(fn ($m) => $m->type === 'issue_to_patient'));

        // Verify clinical treatments recorded
        $treatments = SurgeryTreatment::where('surgery_id', $surgery->id)->get();
        $this->assertCount(2, $treatments);
        $this->assertEquals('Syringe 10ml', $treatments[0]->description);
        $this->assertEquals('5 سرنجة', $treatments[0]->dosage);
        $this->assertEquals('Gauze Sterile 10x10', $treatments[1]->description);
        $this->assertEquals('2 باكيت', $treatments[1]->dosage);
    }
}
