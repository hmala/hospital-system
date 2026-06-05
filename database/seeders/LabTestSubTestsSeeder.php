<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LabTestSubTestsSeeder extends Seeder
{
    public function run(): void
    {
        $abg = DB::table('lab_tests')->where('name', 'ABG')->first();
        
        if (!$abg) {
            $this->command->warn('ABG test not found in lab_tests table.');
            return;
        }

        $subTests = [
            ['name' => 'pH', 'unit' => '', 'reference_range' => '7.35-7.45', 'result_type' => 'numeric', 'sort_order' => 1],
            ['name' => 'pO2', 'unit' => 'mmHg', 'reference_range' => '80-100', 'result_type' => 'numeric', 'sort_order' => 2],
            ['name' => 'pCO2', 'unit' => 'mmHg', 'reference_range' => '35-45', 'result_type' => 'numeric', 'sort_order' => 3],
            ['name' => 'Hct', 'unit' => '%', 'reference_range' => '35-50', 'result_type' => 'numeric', 'sort_order' => 4],
            ['name' => 'tHB (est)', 'unit' => 'g/dL', 'reference_range' => '12-16', 'result_type' => 'numeric', 'sort_order' => 5],
            ['name' => 'sO2 (est)', 'unit' => '%', 'reference_range' => '95-100', 'result_type' => 'numeric', 'sort_order' => 6],
            ['name' => 'Na+', 'unit' => 'mEq/L', 'reference_range' => '136-145', 'result_type' => 'numeric', 'sort_order' => 7],
            ['name' => 'K+', 'unit' => 'mEq/L', 'reference_range' => '3.5-5.0', 'result_type' => 'numeric', 'sort_order' => 8],
            ['name' => 'Ca++', 'unit' => 'mg/dL', 'reference_range' => '8.5-10.5', 'result_type' => 'numeric', 'sort_order' => 9],
            ['name' => 'Cl-', 'unit' => 'mEq/L', 'reference_range' => '96-106', 'result_type' => 'numeric', 'sort_order' => 10],
            ['name' => 'Ca++ (PH= 7.4)', 'unit' => 'mg/dL', 'reference_range' => '8.5-10.5', 'result_type' => 'numeric', 'sort_order' => 11],
            ['name' => 'Glu', 'unit' => 'mg/dL', 'reference_range' => '70-110', 'result_type' => 'numeric', 'sort_order' => 12],
            ['name' => 'Lac', 'unit' => 'mmol/L', 'reference_range' => '0.5-2.2', 'result_type' => 'numeric', 'sort_order' => 13],
            ['name' => 'CH+', 'unit' => 'mmol/L', 'reference_range' => '', 'result_type' => 'numeric', 'sort_order' => 14],
            ['name' => 'HCO3-act', 'unit' => 'mmol/L', 'reference_range' => '22-28', 'result_type' => 'numeric', 'sort_order' => 15],
            ['name' => 'HCO3-std', 'unit' => 'mmol/L', 'reference_range' => '22-26', 'result_type' => 'numeric', 'sort_order' => 16],
            ['name' => 'Base Excess (Extracellular fluid)', 'unit' => 'mmol/L', 'reference_range' => '-2 to +2', 'result_type' => 'numeric', 'sort_order' => 17],
            ['name' => 'Base Excess (Blood)', 'unit' => 'mmol/L', 'reference_range' => '-2 to +2', 'result_type' => 'numeric', 'sort_order' => 18],
            ['name' => 'Base Buffer (Blood)', 'unit' => 'mmol/L', 'reference_range' => '48-52', 'result_type' => 'numeric', 'sort_order' => 19],
            ['name' => 'ctCO2', 'unit' => 'mmol/L', 'reference_range' => '23-29', 'result_type' => 'numeric', 'sort_order' => 20],
            ['name' => 'pO2 (A – a)', 'unit' => 'mmHg', 'reference_range' => '<10', 'result_type' => 'numeric', 'sort_order' => 21],
            ['name' => 'pO2 (a/A)', 'unit' => '', 'reference_range' => '>0.75', 'result_type' => 'numeric', 'sort_order' => 22],
            ['name' => 'RI', 'unit' => '', 'reference_range' => '', 'result_type' => 'numeric', 'sort_order' => 23],
            ['name' => 'Anion Gap', 'unit' => 'mmol/L', 'reference_range' => '8-16', 'result_type' => 'numeric', 'sort_order' => 24],
            ['name' => 'mO2m', 'unit' => '', 'reference_range' => '', 'result_type' => 'numeric', 'sort_order' => 25],
        ];

        foreach ($subTests as $subTest) {
            DB::table('lab_test_sub_tests')->updateOrInsert(
                ['lab_test_id' => $abg->id, 'name' => $subTest['name']],
                array_merge($subTest, [
                    'lab_test_id' => $abg->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('ABG sub-tests seeded successfully!');
    }
}
