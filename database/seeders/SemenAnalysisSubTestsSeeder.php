<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SemenAnalysisSubTestsSeeder extends Seeder
{
    public function run(): void
    {
        // البحث عن تحليل السائل المنوي
        $semenTests = DB::table('lab_tests')
            ->where('name', 'like', '%Semen%')
            ->orWhere('name', 'like', '%منوي%')
            ->orWhere('name', 'like', '%semen%')
            ->orWhere('name', 'like', '%Sperm%')
            ->get();
        
        if ($semenTests->isEmpty()) {
            $this->command->warn('Semen Analysis test not found. Creating one...');
            DB::table('lab_tests')->insert([
                'main_category' => 'المختبر',
                'subcategory' => 'Semen Analysis',
                'code' => 'SEMEN',
                'name' => 'Semen Analysis',
                'unit' => '',
                'description' => 'Semen Analysis - Complete',
                'is_active' => 1,
                'price' => 25000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $semenTests = DB::table('lab_tests')->where('name', 'Semen Analysis')->get();
        }

        $subTests = [
            // Gross Examination
            ['name' => 'Volume', 'unit' => 'ml', 'reference_range' => '>1.5', 'result_type' => 'numeric', 'sort_order' => 1, 'notes' => 'Gross Examination'],
            ['name' => 'PH', 'unit' => '', 'reference_range' => '7.2-8.0', 'result_type' => 'numeric', 'sort_order' => 2, 'notes' => 'Gross Examination'],
            ['name' => 'Color', 'unit' => '', 'reference_range' => 'Whitish/Gray', 'result_type' => 'text', 'sort_order' => 3, 'notes' => 'Gross Examination'],
            ['name' => 'Viscosity', 'unit' => '', 'reference_range' => 'Normal', 'result_type' => 'text', 'sort_order' => 4, 'notes' => 'Gross Examination'],
            ['name' => 'Liquefaction', 'unit' => '', 'reference_range' => 'Complete in 30-60min', 'result_type' => 'text', 'sort_order' => 5, 'notes' => 'Gross Examination'],
            
            // Microscopic Examination
            ['name' => 'Count', 'unit' => 'million/ml', 'reference_range' => '>15', 'result_type' => 'numeric', 'sort_order' => 6, 'notes' => 'Microscopic Examination'],
            ['name' => 'Total count (in 2.5ml)', 'unit' => 'million', 'reference_range' => '>37.5', 'result_type' => 'numeric', 'sort_order' => 7, 'notes' => 'Microscopic Examination'],
            ['name' => 'Total Motility', 'unit' => '%', 'reference_range' => '>40', 'result_type' => 'numeric', 'sort_order' => 8, 'notes' => 'Microscopic Examination'],
            ['name' => 'Progressive motility (A)', 'unit' => '%', 'reference_range' => '>10', 'result_type' => 'numeric', 'sort_order' => 9, 'notes' => 'Microscopic Examination'],
            ['name' => 'Non-progressive motility (B+C)', 'unit' => '%', 'reference_range' => '', 'result_type' => 'numeric', 'sort_order' => 10, 'notes' => 'Microscopic Examination'],
            ['name' => 'Immotile', 'unit' => '%', 'reference_range' => '<50', 'result_type' => 'numeric', 'sort_order' => 11, 'notes' => 'Microscopic Examination'],
            
            // Morphology
            ['name' => 'Normal sperm', 'unit' => '%', 'reference_range' => '>4', 'result_type' => 'numeric', 'sort_order' => 12, 'notes' => 'Morphology'],
            ['name' => 'Abnormal sperm', 'unit' => '%', 'reference_range' => '<96', 'result_type' => 'numeric', 'sort_order' => 13, 'notes' => 'Morphology'],
            ['name' => 'Head defect', 'unit' => '%', 'reference_range' => '', 'result_type' => 'numeric', 'sort_order' => 14, 'notes' => 'Morphology'],
            ['name' => 'Neck defect', 'unit' => '%', 'reference_range' => '', 'result_type' => 'numeric', 'sort_order' => 15, 'notes' => 'Morphology'],
            ['name' => 'Tail defect', 'unit' => '%', 'reference_range' => '', 'result_type' => 'numeric', 'sort_order' => 16, 'notes' => 'Morphology'],
            ['name' => 'Agglutination', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 17, 'notes' => 'Morphology'],
            ['name' => 'Aggregation', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 18, 'notes' => 'Morphology'],
            ['name' => 'Round cell (Pus + Immature)', 'unit' => '/HPF', 'reference_range' => '2-6', 'result_type' => 'numeric', 'sort_order' => 19, 'notes' => 'Morphology'],
        ];

        foreach ($semenTests as $semenTest) {
            $this->command->info("Adding sub-tests to {$semenTest->name}...");
            
            foreach ($subTests as $subTest) {
                DB::table('lab_test_sub_tests')->updateOrInsert(
                    ['lab_test_id' => $semenTest->id, 'name' => $subTest['name']],
                    array_merge($subTest, [
                        'lab_test_id' => $semenTest->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }

        $this->command->info('Semen Analysis sub-tests (19 parameters) seeded successfully!');
    }
}
