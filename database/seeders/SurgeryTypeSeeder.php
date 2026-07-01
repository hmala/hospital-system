<?php

namespace Database\Seeders;

use App\Models\SurgeryType;
use Illuminate\Database\Seeder;

class SurgeryTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'عملية استئصال الزائدة الدودية', 'description' => 'Appendectomy'],
            ['name' => 'عملية استئصال البواسير الخارجية', 'description' => 'External hemorrhoidectomy'],
            ['name' => 'عملية استئصال المرارة', 'description' => 'Cholecystectomy'],
            ['name' => 'عملية الفتق الإربي', 'description' => 'Inguinal hernia repair'],
            ['name' => 'عملية قيصرية', 'description' => 'Cesarean section'],
            ['name' => 'عملية استئصال اللوزتين', 'description' => 'Tonsillectomy'],
            ['name' => 'عملية تبديل مفصل الركبة', 'description' => 'Knee replacement'],
            ['name' => 'عملية تبديل مفصل الورك', 'description' => 'Hip replacement'],
            ['name' => 'عملية تنظير المفصل', 'description' => 'Arthroscopy'],
            ['name' => 'عملية استئصال الرحم', 'description' => 'Hysterectomy'],
            ['name' => 'عملية إزالة المياة البيضاء (الكاتاراكت)', 'description' => 'Cataract surgery'],
            ['name' => 'عملية ترقيع الجلد', 'description' => 'Skin graft'],
            ['name' => 'عملية استئصال الغدة الدرقية', 'description' => 'Thyroidectomy'],
            ['name' => 'عملية تثبيت الكسر', 'description' => 'Fracture fixation'],
            ['name' => 'عملية فتح القصبة الهوائية', 'description' => 'Tracheostomy'],
            ['name' => 'أخرى', 'description' => 'Other - يرجى تحديد النوع يدوياً'],
        ];

        foreach ($types as $type) {
            SurgeryType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
