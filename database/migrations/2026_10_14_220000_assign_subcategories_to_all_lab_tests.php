<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Assigns precise subcategories and unifies main_category for all laboratory tests.
     */
    public function up(): void
    {
        $newTests = DB::table('lab_tests')->where('id', '>', 152)->get(['id', 'name', 'main_category']);

        foreach ($newTests as $t) {
            $cat = $t->main_category;
            $name = strtolower($t->name);
            
            $sub = 'Biochemistry';
            
            if (str_contains($cat, 'هرمون') || str_contains($name, 'hormone') || str_contains($name, 'aldosterone') || str_contains($name, 'prog') || str_contains($name, 'cortisol')) {
                $sub = 'Hormone';
            } elseif (str_contains($cat, 'أنسجة') || str_contains($cat, 'نسيج') || str_contains($name, 'biopsy') || str_contains($name, 'histopath') || str_contains($name, 'stain') || str_contains($name, 'smear')) {
                $sub = 'Histopathology';
            } elseif (str_contains($cat, 'pcr') || str_contains($name, 'pcr') || str_contains($name, 'viral load') || str_contains($name, 'genexpert')) {
                $sub = 'PCR';
            } elseif (str_contains($cat, 'زرع') || str_contains($cat, 'أحياء مجهرية') || str_contains($name, 'c/s') || str_contains($name, 'culture') || str_contains($name, 'swab')) {
                $sub = 'Microbiology';
            } elseif (str_contains($cat, 'دم') || str_contains($name, 'factor') || str_contains($name, 'coag') || str_contains($name, 'platelet') || str_contains($name, 'fibrinogen') || str_contains($name, 'hemoglobin')) {
                $sub = 'Hematology';
            } elseif (str_contains($cat, 'فيروس') || str_contains($name, 'hbv') || str_contains($name, 'hcv') || str_contains($name, 'hiv') || str_contains($name, 'virus')) {
                $sub = 'Virology';
            } elseif (str_contains($cat, 'سيرولوجي')) {
                $sub = 'Serology';
            } elseif (str_contains($cat, 'مناعة') || str_contains($name, 'anti') || str_contains($name, 'igm') || str_contains($name, 'igg') || str_contains($name, 'ab ') || str_contains($name, 'ag ')) {
                $sub = 'Immunity';
            } elseif (str_contains($cat, 'كيمياء')) {
                $sub = 'Biochemistry';
            }
            
            DB::table('lab_tests')->where('id', $t->id)->update([
                'main_category' => 'المختبر',
                'subcategory' => $sub,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
