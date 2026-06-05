<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UrineAnalysisSubTestsSeeder extends Seeder
{
    public function run(): void
    {
        // البحث عن تحليل البول
        $urineTests = DB::table('lab_tests')
            ->where('name', 'like', '%Urine%')
            ->orWhere('name', 'like', '%بول%')
            ->orWhere('name', 'like', '%urine%')
            ->get();
        
        if ($urineTests->isEmpty()) {
            $this->command->warn('Urine Analysis test not found. Creating one...');
            DB::table('lab_tests')->insert([
                'main_category' => 'المختبر',
                'subcategory' => 'Urine Analysis',
                'code' => 'URINE',
                'name' => 'General Urine Examination',
                'unit' => '',
                'description' => 'General Urine Examination - Complete',
                'is_active' => 1,
                'price' => 10000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $urineTests = DB::table('lab_tests')->where('name', 'General Urine Examination')->get();
        }

        $subTests = [
            // Physical examination
            ['name' => 'Color', 'unit' => '', 'reference_range' => 'Yellow', 'result_type' => 'text', 'sort_order' => 1, 'notes' => 'Physical examination'],
            ['name' => 'Aspect', 'unit' => '', 'reference_range' => 'Clear', 'result_type' => 'text', 'sort_order' => 2, 'notes' => 'Physical examination'],
            ['name' => 'Deposit', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 3, 'notes' => 'Physical examination'],
            
            // Chemical examination
            ['name' => 'Reaction', 'unit' => '', 'reference_range' => 'Acidic', 'result_type' => 'text', 'sort_order' => 4, 'notes' => 'Chemical examination'],
            ['name' => 'Protein', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 5, 'notes' => 'Chemical examination'],
            ['name' => 'Sugar', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 6, 'notes' => 'Chemical examination'],
            ['name' => 'Acetone', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 7, 'notes' => 'Chemical examination'],
            ['name' => 'Nitrate', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 8, 'notes' => 'Chemical examination'],
            ['name' => 'Specific Gravity', 'unit' => '', 'reference_range' => '1.010-1.030', 'result_type' => 'numeric', 'sort_order' => 9, 'notes' => 'Chemical examination'],
            ['name' => 'Urobilinogen', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 10, 'notes' => 'Chemical examination'],
            ['name' => 'Leucocytes', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 11, 'notes' => 'Chemical examination'],
            
            // Microscopic examination
            ['name' => 'Pus cell', 'unit' => '/HPF', 'reference_range' => '0-6', 'result_type' => 'numeric', 'sort_order' => 12, 'notes' => 'Microscopic examination'],
            ['name' => 'RBC', 'unit' => '/HPF', 'reference_range' => '0-6', 'result_type' => 'numeric', 'sort_order' => 13, 'notes' => 'Microscopic examination'],
            ['name' => 'Epithelial cell', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 14, 'notes' => 'Microscopic examination'],
            ['name' => 'Cast', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 15, 'notes' => 'Microscopic examination'],
            ['name' => 'Amorphous', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 16, 'notes' => 'Microscopic examination'],
            ['name' => 'Mucus', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 17, 'notes' => 'Microscopic examination'],
            ['name' => 'Crystals', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 18, 'notes' => 'Microscopic examination'],
            ['name' => 'Bacteria', 'unit' => '', 'reference_range' => 'Nil', 'result_type' => 'text', 'sort_order' => 19, 'notes' => 'Microscopic examination'],
        ];

        foreach ($urineTests as $urineTest) {
            $this->command->info("Adding sub-tests to {$urineTest->name}...");
            
            foreach ($subTests as $subTest) {
                DB::table('lab_test_sub_tests')->updateOrInsert(
                    ['lab_test_id' => $urineTest->id, 'name' => $subTest['name']],
                    array_merge($subTest, [
                        'lab_test_id' => $urineTest->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }

        $this->command->info('Urine Analysis sub-tests (19 parameters) seeded successfully!');
    }
}
