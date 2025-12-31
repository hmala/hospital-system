<?php

namespace Database\Seeders;

use App\Models\RadiologyType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RadiologyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $radiologyTypes = [
            // الأشعة المقطعية
            ['category' => 'الأشعة المقطعية', 'name' => 'أشعة مقطعية - عادي - بدون صبغة', 'code' => 'CT_PLAIN', 'description' => 'Key screen - snapshot', 'base_price' => 25000, 'estimated_duration' => 30, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الأشعة المقطعية', 'name' => 'أشعة مقطعية - صبغة - أي منطقة', 'code' => 'CT_CONTRAST', 'description' => 'x-ray computed tomography', 'base_price' => 30000, 'estimated_duration' => 30, 'requires_contrast' => true, 'requires_preparation' => true, 'preparation_instructions' => 'الصيام 4-6 ساعات قبل الفحص', 'is_active' => true],
            ['category' => 'الأشعة المقطعية', 'name' => 'سرعة 3', 'code' => 'CT_SPEED3', 'description' => 'Routine speed 3', 'base_price' => 15000, 'estimated_duration' => 20, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الأشعة المقطعية', 'name' => 'سرعة 2', 'code' => 'CT_SPEED2', 'description' => 'Routine speed 2', 'base_price' => 22000, 'estimated_duration' => 25, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الأشعة المقطعية', 'name' => 'سرعة كاملة', 'code' => 'CT_FULL_SPEED', 'description' => 'Full speed', 'base_price' => 40000, 'estimated_duration' => 30, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الأشعة المقطعية', 'name' => 'سرعة 2 بصبغة', 'code' => 'CT_SPEED2_CONTRAST', 'description' => 'Routine speed 2 with contrast', 'base_price' => 35000, 'estimated_duration' => 30, 'requires_contrast' => true, 'requires_preparation' => true, 'preparation_instructions' => 'الصيام 4-6 ساعات قبل الفحص', 'is_active' => true],
            ['category' => 'الأشعة المقطعية', 'name' => 'صدر وبطن وحوض', 'code' => 'CT_CHEST_ABD_PELVIS', 'description' => 'Chest, Abdomen & Pelvis CT', 'base_price' => 60000, 'estimated_duration' => 45, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الأشعة المقطعية', 'name' => 'صدر وبطن', 'code' => 'CT_CHEST_ABD', 'description' => 'Chest & Abdomen CT', 'base_price' => 45000, 'estimated_duration' => 40, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الأشعة المقطعية', 'name' => 'تصوير الأوعية الدموية المقطعية', 'code' => 'CTA', 'description' => 'CTA (CT Angiography)', 'base_price' => 60000, 'estimated_duration' => 45, 'requires_contrast' => true, 'requires_preparation' => true, 'preparation_instructions' => 'الصيام 4-6 ساعات قبل الفحص', 'is_active' => true],
            ['category' => 'الأشعة المقطعية', 'name' => 'تصوير الأوعية الدموية المقطعية للرئة', 'code' => 'CTPA', 'description' => 'CTPA (CT Pulmonary Angiography)', 'base_price' => 65000, 'estimated_duration' => 45, 'requires_contrast' => true, 'requires_preparation' => true, 'preparation_instructions' => 'الصيام 4-6 ساعات قبل الفحص', 'is_active' => true],
            ['category' => 'الأشعة المقطعية', 'name' => 'قياس درجة تكلس شرايين القلب', 'code' => 'CALCIUM_SCORE', 'description' => 'Calcium Score', 'base_price' => 45000, 'estimated_duration' => 30, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],

            // الخدمات الأخرى  
            ['category' => 'الخدمات الأخرى', 'name' => 'صورة أشعة - عادي - بدون صبغة', 'code' => 'XRAY_PLAIN', 'description' => 'Plain X-ray', 'base_price' => 10000, 'estimated_duration' => 15, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'صورة أشعة - صبغة - أي منطقة', 'code' => 'XRAY_CONTRAST', 'description' => 'X-ray with contrast', 'base_price' => 15000, 'estimated_duration' => 20, 'requires_contrast' => true, 'requires_preparation' => true, 'preparation_instructions' => 'حسب نوع الفحص', 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'صورة صدر', 'code' => 'CHEST_XRAY', 'description' => 'Chest X-ray', 'base_price' => 8000, 'estimated_duration' => 10, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'صورة بطن', 'code' => 'ABD_XRAY', 'description' => 'Abdominal X-ray', 'base_price' => 10000, 'estimated_duration' => 10, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'صورة حوض', 'code' => 'PELVIS_XRAY', 'description' => 'Pelvic X-ray', 'base_price' => 10000, 'estimated_duration' => 10, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'فحص الثدي بالموجات الفوق صوتية', 'code' => 'BREAST_US', 'description' => 'Breast US', 'base_price' => 25000, 'estimated_duration' => 20, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'ايكو', 'code' => 'ECHO', 'description' => 'Echocardiography', 'base_price' => 40000, 'estimated_duration' => 30, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'دوبلر', 'code' => 'DOPPLER', 'description' => 'Doppler', 'base_price' => 30000, 'estimated_duration' => 25, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'سونار', 'code' => 'ULTRASOUND', 'description' => 'Ultrasound', 'base_price' => 20000, 'estimated_duration' => 20, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'فحص العظام والمفاصل', 'code' => 'BONE_JOINT', 'description' => 'Bone & Joint examination', 'base_price' => 15000, 'estimated_duration' => 15, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'صورة بانوراما للأسنان', 'code' => 'DENTAL_PANORAMA', 'description' => 'Dental Panorama', 'base_price' => 15000, 'estimated_duration' => 10, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'طبع الأشعة', 'code' => 'PRINT_XRAY', 'description' => 'Print X-ray', 'base_price' => 2000, 'estimated_duration' => 5, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'نسخة قرص مدمج', 'code' => 'CD_COPY', 'description' => 'CD Copy', 'base_price' => 5000, 'estimated_duration' => 5, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'American A Doppler اوعية (سكان)', 'code' => 'AMERICAN_DOPPLER_SCAN', 'description' => 'American A Doppler (Scan)', 'base_price' => 50000, 'estimated_duration' => 30, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'American A Doppler اوعية (طبع)', 'code' => 'AMERICAN_DOPPLER_PRINT', 'description' => 'American A Doppler (Print)', 'base_price' => 55000, 'estimated_duration' => 35, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'فحص الثدي بالأشعة الثلاثية الأبعاد', 'code' => 'BILATERAL_BREAST_US', 'description' => 'Bilateral Breast US', 'base_price' => 60000, 'estimated_duration' => 30, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات الأخرى', 'name' => 'ماموكرام', 'code' => 'MAMMOGRAM', 'description' => 'Mammography', 'base_price' => 40000, 'estimated_duration' => 20, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => 'تجنب استخدام مزيل العرق', 'is_active' => true],

            // الخدمات المتخصصة
            ['category' => 'الخدمات المتخصصة', 'name' => 'صورة الغدد اللعابية', 'code' => 'SALIVARY_GLANDS', 'description' => 'Salivary glands', 'base_price' => 25000, 'estimated_duration' => 20, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات المتخصصة', 'name' => 'صورة الغدة الدرقية', 'code' => 'THYROID', 'description' => 'Thyroid', 'base_price' => 20000, 'estimated_duration' => 15, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات المتخصصة', 'name' => 'صورة الخصية', 'code' => 'TESTICULAR', 'description' => 'Testicular', 'base_price' => 25000, 'estimated_duration' => 15, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات المتخصصة', 'name' => 'مفصل صناعي (أعلى)', 'code' => 'ARTIFICIAL_JOINT_UPPER', 'description' => 'Artificial Joint (Upper)', 'base_price' => 15000, 'estimated_duration' => 20, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
            ['category' => 'الخدمات المتخصصة', 'name' => 'مفصل صناعي (أسفل)', 'code' => 'ARTIFICIAL_JOINT_LOWER', 'description' => 'Artificial Joint (Lower)', 'base_price' => 15000, 'estimated_duration' => 20, 'requires_contrast' => false, 'requires_preparation' => false, 'preparation_instructions' => null, 'is_active' => true],
        ];

        foreach ($radiologyTypes as $type) {
            RadiologyType::create($type);
        }
    }
}
