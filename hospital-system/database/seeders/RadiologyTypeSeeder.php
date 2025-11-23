<?php

namespace Database\Seeders;

use App\Models\RadiologyType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RadiologyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $radiologyTypes = [
            [
                'name' => 'أشعة عادية (X-ray)',
                'code' => 'XRAY',
                'description' => 'تصوير الأشعة العادية للعظام والصدر والمفاصل',
                'base_price' => 25000, // 25 دينار
                'estimated_duration' => 15,
                'requires_contrast' => false,
                'requires_preparation' => false,
                'preparation_instructions' => null,
                'is_active' => true,
            ],
            [
                'name' => 'أشعة مقطعية (CT Scan)',
                'code' => 'CT',
                'description' => 'التصوير المقطعي المحوسب للأعضاء الداخلية',
                'base_price' => 150000, // 150 دينار
                'estimated_duration' => 30,
                'requires_contrast' => true,
                'requires_preparation' => true,
                'preparation_instructions' => 'الصيام لمدة 6 ساعات قبل الفحص. قد يتطلب حقن مادة تباين.',
                'is_active' => true,
            ],
            [
                'name' => 'الرنين المغناطيسي (MRI)',
                'code' => 'MRI',
                'description' => 'تصوير بالرنين المغناطيسي للأنسجة الرخوة',
                'base_price' => 200000, // 200 دينار
                'estimated_duration' => 45,
                'requires_contrast' => false,
                'requires_preparation' => true,
                'preparation_instructions' => 'إزالة جميع المعادن والأجهزة الإلكترونية. إخبار الطبيب بأي أجهزة طبية مزروعة.',
                'is_active' => true,
            ],
            [
                'name' => 'الموجات فوق الصوتية (Ultrasound)',
                'code' => 'US',
                'description' => 'فحص بالموجات فوق الصوتية للبطن والحوض والقلب',
                'base_price' => 35000, // 35 دينار
                'estimated_duration' => 20,
                'requires_contrast' => false,
                'requires_preparation' => true,
                'preparation_instructions' => 'الصيام لمدة 8 ساعات لفحص البطن. شرب كمية كافية من الماء لفحص الحوض.',
                'is_active' => true,
            ],
            [
                'name' => 'تصوير الثدي (Mammography)',
                'code' => 'MAMMO',
                'description' => 'تصوير الثدي للكشف المبكر عن سرطان الثدي',
                'base_price' => 40000, // 40 دينار
                'estimated_duration' => 15,
                'requires_contrast' => false,
                'requires_preparation' => false,
                'preparation_instructions' => 'تجنب استخدام مزيل العرق أو الكريمات في منطقة الثدي يوم الفحص.',
                'is_active' => true,
            ],
            [
                'name' => 'أشعة الدينتال (Dental X-ray)',
                'code' => 'DENTAL',
                'description' => 'تصوير الأسنان والفكين',
                'base_price' => 15000, // 15 دينار
                'estimated_duration' => 10,
                'requires_contrast' => false,
                'requires_preparation' => false,
                'preparation_instructions' => null,
                'is_active' => true,
            ],
            [
                'name' => 'أشعة العظام (Bone Scan)',
                'code' => 'BONE',
                'description' => 'تصوير العظام والمفاصل',
                'base_price' => 30000, // 30 دينار
                'estimated_duration' => 20,
                'requires_contrast' => false,
                'requires_preparation' => false,
                'preparation_instructions' => null,
                'is_active' => true,
            ],
            [
                'name' => 'تصوير الأوعية الدموية (Angiography)',
                'code' => 'ANGIO',
                'description' => 'تصوير الأوعية الدموية والشرايين',
                'base_price' => 180000, // 180 دينار
                'estimated_duration' => 60,
                'requires_contrast' => true,
                'requires_preparation' => true,
                'preparation_instructions' => 'الصيام لمدة 6 ساعات. إخبار الطبيب بأي حساسية للمواد المقابلة.',
                'is_active' => true,
            ],
        ];

        foreach ($radiologyTypes as $type) {
            RadiologyType::create($type);
        }
    }
}
