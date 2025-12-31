<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ICD10CodesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // التحقق من وجود بيانات مسبقاً
        if (DB::table('icd10_codes')->count() > 0) {
            $this->command->info('ICD10 codes already exist.');
            return;
        }

        $codes = [
            // أمراض الجهاز التنفسي
            ['code' => 'J00', 'description' => 'التهاب البلعوم الأنفي الحاد [نزلة البرد]', 'category' => 'أمراض الجهاز التنفسي'],
            ['code' => 'J01', 'description' => 'التهاب الجيوب الأنفية الحاد', 'category' => 'أمراض الجهاز التنفسي'],
            ['code' => 'J02', 'description' => 'التهاب البلعوم الحاد', 'category' => 'أمراض الجهاز التنفسي'],
            ['code' => 'J03', 'description' => 'التهاب اللوزتين الحاد', 'category' => 'أمراض الجهاز التنفسي'],
            ['code' => 'J06', 'description' => 'التهابات الجهاز التنفسي العلوي الحادة', 'category' => 'أمراض الجهاز التنفسي'],
            ['code' => 'J18', 'description' => 'الالتهاب الرئوي', 'category' => 'أمراض الجهاز التنفسي'],
            ['code' => 'J20', 'description' => 'التهاب الشعب الهوائية الحاد', 'category' => 'أمراض الجهاز التنفسي'],
            ['code' => 'J45', 'description' => 'الربو', 'category' => 'أمراض الجهاز التنفسي'],

            // أمراض الجهاز الهضمي
            ['code' => 'K21', 'description' => 'ارتجاع المريء', 'category' => 'أمراض الجهاز الهضمي'],
            ['code' => 'K29', 'description' => 'التهاب المعدة والاثنى عشر', 'category' => 'أمراض الجهاز الهضمي'],
            ['code' => 'K30', 'description' => 'عسر الهضم الوظيفي', 'category' => 'أمراض الجهاز الهضمي'],
            ['code' => 'K52', 'description' => 'التهاب المعدة والأمعاء', 'category' => 'أمراض الجهاز الهضمي'],
            ['code' => 'K58', 'description' => 'متلازمة القولون العصبي', 'category' => 'أمراض الجهاز الهضمي'],
            ['code' => 'K80', 'description' => 'حصوات المرارة', 'category' => 'أمراض الجهاز الهضمي'],

            // أمراض القلب والأوعية الدموية
            ['code' => 'I10', 'description' => 'ارتفاع ضغط الدم الأساسي', 'category' => 'أمراض القلب والأوعية الدموية'],
            ['code' => 'I20', 'description' => 'الذبحة الصدرية', 'category' => 'أمراض القلب والأوعية الدموية'],
            ['code' => 'I21', 'description' => 'احتشاء عضلة القلب الحاد', 'category' => 'أمراض القلب والأوعية الدموية'],
            ['code' => 'I25', 'description' => 'مرض القلب الإقفاري المزمن', 'category' => 'أمراض القلب والأوعية الدموية'],
            ['code' => 'I50', 'description' => 'فشل القلب', 'category' => 'أمراض القلب والأوعية الدموية'],

            // داء السكري
            ['code' => 'E10', 'description' => 'داء السكري من النوع الأول', 'category' => 'أمراض الغدد الصماء'],
            ['code' => 'E11', 'description' => 'داء السكري من النوع الثاني', 'category' => 'أمراض الغدد الصماء'],
            ['code' => 'E14', 'description' => 'داء السكري غير المحدد', 'category' => 'أمراض الغدد الصماء'],

            // أمراض الغدد الصماء
            ['code' => 'E03', 'description' => 'قصور الغدة الدرقية', 'category' => 'أمراض الغدد الصماء'],
            ['code' => 'E05', 'description' => 'فرط نشاط الغدة الدرقية', 'category' => 'أمراض الغدد الصماء'],
            ['code' => 'E66', 'description' => 'السمنة', 'category' => 'أمراض الغدد الصماء'],
            ['code' => 'E78', 'description' => 'اضطرابات الدهون في الدم', 'category' => 'أمراض الغدد الصماء'],

            // أمراض الجهاز العصبي
            ['code' => 'G43', 'description' => 'الصداع النصفي', 'category' => 'أمراض الجهاز العصبي'],
            ['code' => 'G44', 'description' => 'متلازمات الصداع الأخرى', 'category' => 'أمراض الجهاز العصبي'],
            ['code' => 'G47', 'description' => 'اضطرابات النوم', 'category' => 'أمراض الجهاز العصبي'],

            // أمراض الجلد
            ['code' => 'L20', 'description' => 'التهاب الجلد التأتبي', 'category' => 'أمراض الجلد'],
            ['code' => 'L30', 'description' => 'التهابات جلدية أخرى', 'category' => 'أمراض الجلد'],
            ['code' => 'L50', 'description' => 'الشرى (الأرتيكاريا)', 'category' => 'أمراض الجلد'],

            // أمراض الجهاز البولي
            ['code' => 'N30', 'description' => 'التهاب المثانة', 'category' => 'أمراض الجهاز البولي'],
            ['code' => 'N39', 'description' => 'التهاب المسالك البولية', 'category' => 'أمراض الجهاز البولي'],

            // الأعراض العامة
            ['code' => 'R05', 'description' => 'سعال', 'category' => 'أعراض وعلامات'],
            ['code' => 'R06', 'description' => 'اضطرابات التنفس', 'category' => 'أعراض وعلامات'],
            ['code' => 'R07', 'description' => 'ألم في الحلق والصدر', 'category' => 'أعراض وعلامات'],
            ['code' => 'R10', 'description' => 'ألم في البطن والحوض', 'category' => 'أعراض وعلامات'],
            ['code' => 'R11', 'description' => 'غثيان وقيء', 'category' => 'أعراض وعلامات'],
            ['code' => 'R50', 'description' => 'حمى', 'category' => 'أعراض وعلامات'],
            ['code' => 'R51', 'description' => 'صداع', 'category' => 'أعراض وعلامات'],

            // إصابات وحوادث
            ['code' => 'S06', 'description' => 'إصابة داخل الجمجمة', 'category' => 'إصابات وحوادث'],
            ['code' => 'S13', 'description' => 'التواء الرقبة', 'category' => 'إصابات وحوادث'],
            ['code' => 'S43', 'description' => 'خلع مفصل الكتف', 'category' => 'إصابات وحوادث'],
            ['code' => 'S52', 'description' => 'كسر في الساعد', 'category' => 'إصابات وحوادث'],
            ['code' => 'S82', 'description' => 'كسر في الساق', 'category' => 'إصابات وحوادث'],
            ['code' => 'T14', 'description' => 'إصابة غير محددة', 'category' => 'إصابات وحوادث'],
        ];

        DB::table('icd10_codes')->insert($codes);

        $this->command->info('ICD10 codes inserted successfully. Total: ' . count($codes));
    }
}
