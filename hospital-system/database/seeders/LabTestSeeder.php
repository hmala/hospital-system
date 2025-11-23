<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LabTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tests = [
            ['name' => 'فحص دم', 'description' => 'فحص عام للدم', 'category' => 'blood'],
            ['name' => 'فحص بول', 'description' => 'تحليل البول', 'category' => 'urine'],
            ['name' => 'فحص براز', 'description' => 'تحليل البراز', 'category' => 'stool'],
            ['name' => 'كيمياء دم', 'description' => 'فحوصات كيميائية للدم', 'category' => 'blood'],
            ['name' => 'هرمونات', 'description' => 'فحوصات هرمونية', 'category' => 'hormones'],
            ['name' => 'وظائف الكبد', 'description' => 'اختبارات وظائف الكبد', 'category' => 'liver'],
            ['name' => 'وظائف الكلى', 'description' => 'اختبارات وظائف الكلى', 'category' => 'kidney'],
            ['name' => 'ملف الدهون', 'description' => 'قياس مستويات الدهون في الدم', 'category' => 'blood'],
            ['name' => 'الغدة الدرقية', 'description' => 'فحوصات الغدة الدرقية', 'category' => 'hormones'],
            ['name' => 'فحوصات السكري', 'description' => 'قياس مستوى السكر', 'category' => 'blood'],
            ['name' => 'التخثر', 'description' => 'اختبارات التخثر', 'category' => 'blood'],
            ['name' => 'فيروسات الكبد', 'description' => 'فحص فيروسات الكبد', 'category' => 'blood'],
            ['name' => 'المناعة', 'description' => 'فحوصات المناعة', 'category' => 'blood'],
            ['name' => 'السرطان', 'description' => 'علامات السرطان', 'category' => 'blood'],
            ['name' => 'الالتهابات', 'description' => 'علامات الالتهاب', 'category' => 'blood'],
        ];

        foreach ($tests as $test) {
            \App\Models\LabTest::create($test);
        }
    }
}
