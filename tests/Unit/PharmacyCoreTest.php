<?php

namespace Tests\Unit;

use App\Models\Medicine;
use App\Models\MedicineAlternative;
use App\Models\MedicineBatch;
use App\Models\PharmacySale;
use App\Models\PharmacySaleItem;
use App\Models\PharmacyService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_medicine_creation_and_dynamic_pricing()
    {
        $medicine = Medicine::create([
            'national_code' => '01-C00-038',
            'name' => 'Amoxicillin 500mg',
            'generic_name' => 'Amoxicillin',
            'dosage_form' => 'كبسول',
            'strength' => '500mg',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2, // علبة فيها شريطين
            'cost_price' => 2000,
            'sale_price' => 3000, // 3000 للعلبة
            'sub_unit_sale_price' => 1500, // 1500 للشريط
            'hi_price' => 2500, // سعر الضمان الصحي
            'is_insurance_covered' => true,
        ]);

        $this->assertDatabaseHas('medicines', ['national_code' => '01-C00-038']);

        // 1. حساب الكاش للعلبة
        $cashBox = $medicine->calculatePricing('main_unit', 'none');
        $this->assertEquals(3000, $cashBox['total_amount']);
        $this->assertEquals(3000, $cashBox['patient_share']);
        $this->assertEquals(0, $cashBox['insurance_share']);

        // 2. حساب الكاش للشريط
        $cashStrip = $medicine->calculatePricing('sub_unit', 'none');
        $this->assertEquals(1500, $cashStrip['total_amount']);
        $this->assertEquals(1500, $cashStrip['patient_share']);

        // 3. حساب الضمان الصحي للعلبة بنسبة استقطاع 25% (فئة E مثلاً)
        $hiBox = $medicine->calculatePricing('main_unit', 'hi', 25.0);
        $this->assertEquals(2500, $hiBox['total_amount']);
        $this->assertEquals(625, $hiBox['patient_share']); // 25% of 2500
        $this->assertEquals(1875, $hiBox['insurance_share']); // 75% of 2500
    }

    public function test_medicine_batch_fefo_and_deduct_stock_with_box_unwrapping()
    {
        $medicine = Medicine::create([
            'name' => 'Paracetamol 500mg',
            'main_unit' => 'علبة',
            'sub_unit' => 'شريط',
            'sub_units_count' => 2, // 2 أشرطة بالعلبة
            'sale_price' => 1000,
        ]);

        // وجبة تنتهي بعد 30 يوم
        $batch1 = MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'LOT-001',
            'expiry_date' => now()->addDays(30)->toDateString(),
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'current_sub_units' => 0,
        ]);

        // وجبة تنتهي بعد 10 أيام (يجب أن تسبق في FEFO)
        $batch2 = MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'LOT-002',
            'expiry_date' => now()->addDays(10)->toDateString(),
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'current_sub_units' => 0,
        ]);

        // فحص ترتيب FEFO
        $firstBatch = $medicine->activeBatches()->first();
        $this->assertEquals('LOT-002', $firstBatch->batch_number);

        // فحص خصم 3 أشرطة من علب مغلقة (يجب فتح علبتين: 2 * 2 = 4 أشرطة، ويتبقى 1 شريط مفتوح)
        $deducted = $batch2->deductStock(mainUnits: 0, subUnits: 3, subUnitsCount: 2);
        $this->assertTrue($deducted);
        $batch2->refresh();

        $this->assertEquals(3, $batch2->current_quantity); // فتحت علبتين من أصل 5 فبقي 3 علب
        $this->assertEquals(1, $batch2->current_sub_units); // 4 - 3 = 1 شريط متبقي
    }

    public function test_medicine_alternatives_relationship()
    {
        $original = Medicine::create([
            'name' => 'Panadol 500mg',
            'sale_price' => 1500,
        ]);

        $substitute = Medicine::create([
            'name' => 'Adol 500mg',
            'sale_price' => 1000,
        ]);

        $original->alternatives()->attach($substitute->id, ['notes' => 'نفس المادة الفعالة والجرعة']);

        $this->assertTrue($original->alternatives->contains($substitute));
        $this->assertEquals('نفس المادة الفعالة والجرعة', $original->alternatives->first()->pivot->notes);
    }

    public function test_pharmacy_sales_and_items_flow()
    {
        $user = User::factory()->create();

        $medicine = Medicine::create([
            'name' => 'Ibuprofen 400mg',
            'sale_price' => 2000,
        ]);

        $service = PharmacyService::create([
            'name' => 'قياس ضغط الدم',
            'price' => 1000,
        ]);

        $invoiceNum = PharmacySale::generateInvoiceNumber();
        $this->assertStringStartsWith('PH-', $invoiceNum);

        $sale = PharmacySale::create([
            'invoice_number' => $invoiceNum,
            'patient_name' => 'علي التميمي',
            'sale_type' => 'direct_otc',
            'total_amount' => 3000,
            'patient_share' => 3000,
            'insurance_share' => 0,
            'payment_status' => 'paid',
            'dispensing_status' => 'dispensed',
            'user_id' => $user->id,
        ]);

        PharmacySaleItem::create([
            'sale_id' => $sale->id,
            'item_type' => 'medicine',
            'medicine_id' => $medicine->id,
            'unit_type' => 'main_unit',
            'quantity' => 1,
            'unit_price' => 2000,
            'subtotal' => 2000,
        ]);

        PharmacySaleItem::create([
            'sale_id' => $sale->id,
            'item_type' => 'service',
            'service_id' => $service->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);

        $this->assertEquals(2, $sale->items()->count());
        $this->assertEquals('Ibuprofen 400mg', $sale->items->first()->item_name);
        $this->assertEquals('قياس ضغط الدم', $sale->items->last()->item_name);
    }
}
