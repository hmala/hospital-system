<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoolAnalysisSubTestsSeeder extends Seeder
{
    public function run(): void
    {
        // البحث عن تحليل البراز
        $stoolTests = DB::table('lab_tests')
            ->where('name', 'like', '%Stool%')
            ->orWhere('name', 'like', '%براز%')
            ->orWhere('name', 'like', '%stool%')
            ->get();
        
        if ($stoolTests->isEmpty()) {
            $this->command->warn('Stool Analysis test not found. Creating one...');
            DB::table('lab_tests')->insert([
                'main_category' => 'المختبر',
                'subcategory' => 'Stool Analysis',
                'code' => 'STOOL',
                'name' => 'Stool Analysis',
                'unit' => '',
                'description' => 'Stool Analysis - Complete',
                'is_active' => 1,
                'price' => 15000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $stoolTests = DB::table('lab_tests')->where('name', 'Stool Analysis')->get();
        }

        $subTests = [
            // Physical examination
            ['name' => 'Color', 'unit' => '', 'reference_range' => 'Brown', 'result_type' => 'text', 'sort_order' => 1, 'notes' => 'Physical examination'],
            ['name' => 'Appearance', 'unit' => '', 'reference_range' => 'Soft', 'result_type' => 'text', 'sort_order' => 2, 'notes' => 'Physical examination'],
            
            // Microscopic examination
            ['name' => 'Pus cell', 'unit' => '/HPF', 'reference_range' => '0-6', 'result_type' => 'numeric', 'sort_order' => 3, 'notes' => 'Microscopic examination'],
            ['name' => 'RBC', 'unit' => '/HPF', 'reference_range' => '0-6', 'result_type' => 'numeric', 'sort_order' => 4, 'notes' => 'Microscopic examination'],
            ['name' => 'Troph.', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 5, 'notes' => 'Microscopic examination - Trophozoites'],
            ['name' => 'Ova', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 6, 'notes' => 'Microscopic examination'],
            ['name' => 'Cyst', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 7, 'notes' => 'Microscopic examination'],
            ['name' => 'Monilia', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 8, 'notes' => 'Microscopic examination'],
            ['name' => 'Undigested food', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 9, 'notes' => 'Microscopic examination'],
            ['name' => 'Fatty drops', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 10, 'notes' => 'Microscopic examination'],
        ];

        foreach ($stoolTests as $stoolTest) {
            $this->command->info("Adding sub-tests to {$stoolTest->name}...");
            
            foreach ($subTests as $subTest) {
                DB::table('lab_test_sub_tests')->updateOrInsert(
                    ['lab_test_id' => $stoolTest->id, 'name' => $subTest['name']],
                    array_merge($subTest, [
                        'lab_test_id' => $stoolTest->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }

        $this->command->info('Stool Analysis sub-tests (10 parameters) seeded successfully!');
    }
}
