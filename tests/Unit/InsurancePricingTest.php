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
}
