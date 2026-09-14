<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\LabTest;
use App\Models\Doctor;
use App\Models\RadiologyType;
use App\Models\EmergencyService;

class InsurancePricingTest extends TestCase
{
    /**
     * Test LabTest insurance pricing calculation
     */
    public function test_lab_test_copay_calculation(): void
    {
        $test = new LabTest([
            'price' => 20000,
            'moi_price' => 18000,
            'is_moi_active' => true,
            'hi_price' => 15000,
            'is_hi_active' => true,
        ]);

        // 1. MOI Insurance with 15% copay
        $moiPricing = $test->calculateInsurancePricing('moi', 15.0);
        $this->assertTrue($moiPricing['is_covered']);
        $this->assertEquals(18000, $moiPricing['total_amount']);
        $this->assertEquals(2700, $moiPricing['patient_share']); // 18000 * 0.15 = 2700
        $this->assertEquals(15300, $moiPricing['insurance_share']); // 18000 - 2700 = 15300

        // 2. Health Insurance (HI) with 20% copay
        $hiPricing = $test->calculateInsurancePricing('hi', 20.0);
        $this->assertTrue($hiPricing['is_covered']);
        $this->assertEquals(15000, $hiPricing['total_amount']);
        $this->assertEquals(3000, $hiPricing['patient_share']); // 15000 * 0.20 = 3000
        $this->assertEquals(12000, $hiPricing['insurance_share']);

        // 3. Cash / Uninsured patient
        $cashPricing = $test->calculateInsurancePricing('none', 0);
        $this->assertFalse($cashPricing['is_covered']);
        $this->assertEquals(20000, $cashPricing['total_amount']);
        $this->assertEquals(20000, $cashPricing['patient_share']);
        $this->assertEquals(0, $cashPricing['insurance_share']);
    }

    /**
     * Test Doctor consultation fee insurance pricing
     */
    public function test_doctor_consultation_copay_calculation(): void
    {
        $doctor = new Doctor([
            'consultation_fee' => 25000,
            'moi_price' => 20000,
            'is_moi_active' => true,
            'hi_price' => 18000,
            'is_hi_active' => false, // de-activated for HI
        ]);

        // MOI active
        $moiPricing = $doctor->calculateInsurancePricing('moi', 15.0);
        $this->assertTrue($moiPricing['is_covered']);
        $this->assertEquals(20000, $moiPricing['total_amount']);
        $this->assertEquals(3000, $moiPricing['patient_share']);
        $this->assertEquals(17000, $moiPricing['insurance_share']);

        // HI inactive -> falls back to cash price & not covered
        $hiPricing = $doctor->calculateInsurancePricing('hi', 15.0);
        $this->assertFalse($hiPricing['is_covered']);
        $this->assertEquals(25000, $hiPricing['total_amount']);
        $this->assertEquals(25000, $hiPricing['patient_share']);
        $this->assertEquals(0, $hiPricing['insurance_share']);
    }

    /**
     * Test HealthInsuranceCategory model and service copays
     */
    public function test_health_insurance_categories_matrix(): void
    {
        // Category A (0% copay across all, requires thermal stamp)
        $catA = new \App\Models\HealthInsuranceCategory([
            'code' => 'A',
            'name' => 'الفئة A - المشمولون بشبكة الحماية الاجتماعية',
            'requires_thermal_stamp' => true,
            'consultation_copay' => 0.00,
            'surgery_copay' => 0.00,
            'lab_copay' => 0.00,
            'radiology_copay' => 0.00,
            'emergency_copay' => 0.00,
            'is_active' => true,
        ]);

        $this->assertEquals(0.00, $catA->getCopayForService('consultation'));
        $this->assertEquals(0.00, $catA->getCopayForService('surgery'));
        $this->assertEquals(0.00, $catA->getCopayForService('lab'));
        $this->assertTrue($catA->requires_thermal_stamp);

        // Category H (25% consultation, 25% surgery, 25% lab, 0% emergency)
        $catH = new \App\Models\HealthInsuranceCategory([
            'code' => 'H',
            'name' => 'الفئة H - الموظفون والمتقاعدون',
            'requires_thermal_stamp' => false,
            'consultation_copay' => 25.00,
            'surgery_copay' => 25.00,
            'lab_copay' => 25.00,
            'radiology_copay' => 25.00,
            'emergency_copay' => 0.00,
            'is_active' => true,
        ]);

        $this->assertEquals(25.00, $catH->getCopayForService('consultation'));
        $this->assertEquals(25.00, $catH->getCopayForService('surgery'));
        $this->assertEquals(25.00, $catH->getCopayForService('lab'));
        $this->assertEquals(0.00, $catH->getCopayForService('emergency'));
        $this->assertFalse($catH->requires_thermal_stamp);
    }
}
