<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CBCSubTestsSeeder extends Seeder
{
    public function run(): void
    {
        // البحث عن CBC 3 Diff و CBC 5 Diff
        $cbc3Diff = DB::table('lab_tests')->where('name', 'CBC 3 Diff.')->first();
        $cbc5Diff = DB::table('lab_tests')->where('name', 'CBC 5 Diff.')->first();
        
        $cbcTests = [];
        if ($cbc3Diff) {
            $cbcTests[] = ['id' => $cbc3Diff->id, 'name' => 'CBC 3 Diff.'];
        }
        if ($cbc5Diff) {
            $cbcTests[] = ['id' => $cbc5Diff->id, 'name' => 'CBC 5 Diff.'];
        }
        
        if (empty($cbcTests)) {
            $this->command->warn('No CBC tests found in lab_tests table.');
            return;
        }

        $subTests = [
            // WBC Group
            ['name' => 'WBC', 'unit' => 'x10³/µL', 'reference_range' => '3.77-11.03', 'result_type' => 'numeric', 'sort_order' => 1],
            ['name' => 'LY', 'unit' => '%', 'reference_range' => '18.51-48.98', 'result_type' => 'numeric', 'sort_order' => 2],
            ['name' => 'MO', 'unit' => '%', 'reference_range' => '4.72-11.35', 'result_type' => 'numeric', 'sort_order' => 3],
            ['name' => 'NE', 'unit' => '%', 'reference_range' => '41.35-72.27', 'result_type' => 'numeric', 'sort_order' => 4],
            ['name' => 'EO', 'unit' => '%', 'reference_range' => '0.71-6.09', 'result_type' => 'numeric', 'sort_order' => 5],
            ['name' => 'BA', 'unit' => '%', 'reference_range' => '0.05-0.47', 'result_type' => 'numeric', 'sort_order' => 6],
            ['name' => 'LY#', 'unit' => 'x10³/µL', 'reference_range' => '1.16-3.78', 'result_type' => 'numeric', 'sort_order' => 7],
            ['name' => 'MO#', 'unit' => 'x10³/µL', 'reference_range' => '0.28-0.83', 'result_type' => 'numeric', 'sort_order' => 8],
            ['name' => 'NE#', 'unit' => 'x10³/µL', 'reference_range' => '2.03-7.67', 'result_type' => 'numeric', 'sort_order' => 9],
            ['name' => 'EO#', 'unit' => 'x10³/µL', 'reference_range' => '0.04-0.42', 'result_type' => 'numeric', 'sort_order' => 10],
            ['name' => 'BA#', 'unit' => 'x10³/µL', 'reference_range' => '0.00-0.03', 'result_type' => 'numeric', 'sort_order' => 11],
            ['name' => '©IMM', 'unit' => '%', 'reference_range' => '0.00-100.00', 'result_type' => 'numeric', 'sort_order' => 12],
            ['name' => '©IMM#', 'unit' => 'x10³/µL', 'reference_range' => '0.00-150.00', 'result_type' => 'numeric', 'sort_order' => 13],
            
            // RBC Group
            ['name' => 'RBC', 'unit' => 'x10⁶/µL', 'reference_range' => '3.83-5.06', 'result_type' => 'numeric', 'sort_order' => 14],
            ['name' => 'HGB', 'unit' => 'g/dL', 'reference_range' => '11.59-15.11', 'result_type' => 'numeric', 'sort_order' => 15],
            ['name' => 'HCT', 'unit' => '%', 'reference_range' => '34.6-44.1', 'result_type' => 'numeric', 'sort_order' => 16],
            ['name' => 'MCV', 'unit' => 'fL', 'reference_range' => '80.0-98.0', 'result_type' => 'numeric', 'sort_order' => 17],
            ['name' => 'MCH', 'unit' => 'pg', 'reference_range' => '26.6-33.5', 'result_type' => 'numeric', 'sort_order' => 18],
            ['name' => 'MCHC', 'unit' => 'g/dL', 'reference_range' => '32.9-35.4', 'result_type' => 'numeric', 'sort_order' => 19],
            ['name' => 'RDW', 'unit' => '%', 'reference_range' => '12.6-15.6', 'result_type' => 'numeric', 'sort_order' => 20],
            ['name' => 'RDW-SD', 'unit' => 'fL', 'reference_range' => '38.9-50.6', 'result_type' => 'numeric', 'sort_order' => 21],
            ['name' => '©LHO', 'unit' => '%', 'reference_range' => '0.0-100.0', 'result_type' => 'numeric', 'sort_order' => 22],
            ['name' => '©MAL', 'unit' => '', 'reference_range' => '0.0-99.9', 'result_type' => 'numeric', 'sort_order' => 23],
            
            // PLT Group
            ['name' => 'PLT', 'unit' => 'x10³/µL', 'reference_range' => '169.1-368.3', 'result_type' => 'numeric', 'sort_order' => 24],
            ['name' => 'MPV', 'unit' => 'fL', 'reference_range' => '7.45-10.84', 'result_type' => 'numeric', 'sort_order' => 25],
            ['name' => '©PCT', 'unit' => '%', 'reference_range' => '0.000-9.999', 'result_type' => 'numeric', 'sort_order' => 26],
            ['name' => '©PDW', 'unit' => '', 'reference_range' => '0.0-99.9', 'result_type' => 'numeric', 'sort_order' => 27],
        ];

        foreach ($cbcTests as $cbcTest) {
            $this->command->info("Adding sub-tests to {$cbcTest['name']}...");
            
            foreach ($subTests as $subTest) {
                DB::table('lab_test_sub_tests')->updateOrInsert(
                    ['lab_test_id' => $cbcTest['id'], 'name' => $subTest['name']],
                    array_merge($subTest, [
                        'lab_test_id' => $cbcTest['id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }

        $this->command->info('CBC sub-tests (27 parameters) seeded successfully for all CBC types!');
    }
}
