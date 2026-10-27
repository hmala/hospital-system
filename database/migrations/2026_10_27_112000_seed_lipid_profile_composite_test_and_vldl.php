<?php

use App\Models\LabTest;
use App\Models\LabTestReference;
use App\Models\LabTestSubTest;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. إضافة أو تحديث فحص VLDL (Very Low Density Lipoprotein)
        $vldlTest = LabTest::firstOrCreate(
            ['name' => 'Very Low Density Lipoprotein'],
            [
                'code' => 'VLDL',
                'main_category' => 'Biochemistry',
                'subcategory' => 'LIPID PROFILE',
                'description' => 'Very Low Density Lipoprotein (Calculated: TG / 5)',
                'unit' => 'mg/dL',
                'result_entry_type' => LabTest::ENTRY_TYPE_SINGLE,
                'is_active' => true,
                'price' => 10000.00,
                'hi_price' => 4000.00,
                'is_hi_active' => true,
            ]
        );

        if ($vldlTest) {
            LabTestReference::firstOrCreate(
                [
                    'lab_test_id' => $vldlTest->id,
                    'gender' => 'both',
                ],
                [
                    'ref_min' => 5,
                    'ref_max' => 30,
                    'ref_text' => '5 - 30',
                    'unit' => 'mg/dL',
                    'notes' => 'Calculated: TG / 5 (mg/dL)',
                ]
            );
        }

        // 2. إنشاء الفحص المركب باقة الدهون الشاملة (Lipid Profile) بنمط SubTests
        $lipidProfile = LabTest::firstOrCreate(
            ['name' => 'Lipid Profile'],
            [
                'code' => 'LIPID_PROFILE',
                'main_category' => 'Biochemistry',
                'subcategory' => 'LIPID PROFILE',
                'description' => 'Comprehensive Lipid Profile Panel (TC, TG, HDL, LDL, VLDL)',
                'unit' => 'mg/dL',
                'result_entry_type' => LabTest::ENTRY_TYPE_SUBTESTS,
                'is_active' => true,
                'price' => 35000.00,
                'hi_price' => 15000.00,
                'is_hi_active' => true,
            ]
        );

        // التأكد من ضبط نوع الإدخال كفحص مركب
        if ($lipidProfile->result_entry_type !== LabTest::ENTRY_TYPE_SUBTESTS) {
            $lipidProfile->result_entry_type = LabTest::ENTRY_TYPE_SUBTESTS;
            $lipidProfile->save();
        }

        // الفحوصات والباراميترات الفرعية الخمسة لباقة الدهون
        $subTests = [
            [
                'name' => 'Cholesterol',
                'unit' => 'mg/dL',
                'reference_range' => '100 - 200',
                'result_type' => 'numeric',
                'sort_order' => 1,
                'notes' => 'Total Cholesterol',
            ],
            [
                'name' => 'Triglyceride',
                'unit' => 'mg/dL',
                'reference_range' => '60 - 160',
                'result_type' => 'numeric',
                'sort_order' => 2,
                'notes' => 'Triglycerides',
            ],
            [
                'name' => 'High Density Lipoprotein',
                'unit' => 'mg/dL',
                'reference_range' => '> 35',
                'result_type' => 'numeric',
                'sort_order' => 3,
                'notes' => 'HDL Cholesterol (Good Cholesterol)',
            ],
            [
                'name' => 'Low Density Lipoprotein (Calculated)',
                'unit' => 'mg/dL',
                'reference_range' => '< 130',
                'result_type' => 'numeric',
                'sort_order' => 4,
                'notes' => 'Calculated via Friedewald Eq: TC - HDL - (TG/5)',
            ],
            [
                'name' => 'Very Low Density Lipoprotein (Calculated)',
                'unit' => 'mg/dL',
                'reference_range' => '5 - 30',
                'result_type' => 'numeric',
                'sort_order' => 5,
                'notes' => 'Calculated: TG / 5',
            ],
        ];

        foreach ($subTests as $st) {
            LabTestSubTest::updateOrCreate(
                [
                    'lab_test_id' => $lipidProfile->id,
                    'name' => $st['name'],
                ],
                [
                    'unit' => $st['unit'],
                    'reference_range' => $st['reference_range'],
                    'result_type' => $st['result_type'],
                    'sort_order' => $st['sort_order'],
                    'notes' => $st['notes'],
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $lipidProfile = LabTest::where('code', 'LIPID_PROFILE')->first();
        if ($lipidProfile) {
            LabTestSubTest::where('lab_test_id', $lipidProfile->id)->delete();
            $lipidProfile->delete();
        }

        $vldl = LabTest::where('code', 'VLDL')->first();
        if ($vldl) {
            LabTestReference::where('lab_test_id', $vldl->id)->delete();
            $vldl->delete();
        }
    }
};
