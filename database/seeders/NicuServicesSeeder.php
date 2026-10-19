<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NicuService;

class NicuServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'حاضنة فقط + O2 (أكسجين)',
                'code' => 'incubator_o2',
                'daily_fee' => 200000.00,
                'hi_price' => 150000.00,
                'is_hi_active' => true,
                'official_claim_name' => 'رقود حديثي الولادة لليوم الواحد شامل الخدمات التمريضية و الفندقية',
                'hotel_fee' => 100000.00,
                'nursing_fee' => 50000.00,
                'device_fee' => 50000.00,
                'description' => 'حاضنة لحديثي الولادة مجهزة بدعم أكسجين مراقب، رعاية تمريضية ومراقبة علامات حيوية على مدار 24 ساعة.',
                'is_active' => true,
            ],
            [
                'name' => 'حاضنة فقط + علاج ضوئي (Phototherapy)',
                'code' => 'incubator_phototherapy',
                'daily_fee' => 200000.00,
                'hi_price' => 200000.00,
                'is_hi_active' => true,
                'official_claim_name' => 'رقود حديثي الولادة لليوم الواحد شامل الخدمات التمريضية والفندقية مع علاج ضوئي',
                'hotel_fee' => 100000.00,
                'nursing_fee' => 50000.00,
                'device_fee' => 50000.00,
                'description' => 'حاضنة مجهزة بوحدة علاج ضوئي لعلاج اليرقان الوليدي (الصفراء) مع حماية العين والمراقبة المستمرة.',
                'is_active' => true,
            ],
            [
                'name' => 'حاضنة فقط (عادية)',
                'code' => 'incubator_only',
                'daily_fee' => 200000.00,
                'hi_price' => 150000.00,
                'is_hi_active' => true,
                'official_claim_name' => 'رقود حديثي الولادة لليوم الواحد شامل الخدمات التمريضية و الفندقية',
                'hotel_fee' => 120000.00,
                'nursing_fee' => 80000.00,
                'device_fee' => 0.00,
                'description' => 'حاضنة عادية لضبط الحرارة والرطوبة والرعاية التمريضية للمواليد الخدج ومتابعة التغذية والنمو.',
                'is_active' => true,
            ],
            [
                'name' => 'حاضنة + CPAP (ضغط مجرى هوائي إيجابي)',
                'code' => 'incubator_cpap',
                'daily_fee' => 250000.00,
                'hi_price' => 200000.00,
                'is_hi_active' => true,
                'official_claim_name' => 'رقود حديثي الولادة لليوم الواحد شامل الخدمات التمريضية و الفندقية CPAP مع',
                'hotel_fee' => 100000.00,
                'nursing_fee' => 50000.00,
                'device_fee' => 100000.00,
                'description' => 'حاضنة مجهزة بجهاز ضغط المجرى الهوائي الإيجابي المستمر (CPAP) لدعم التنفس غير الغازي للخدج.',
                'is_active' => true,
            ],
            [
                'name' => 'حاضنة + جهاز تنفس اصطناعي (Ventilator)',
                'code' => 'incubator_ventilator',
                'daily_fee' => 360000.00,
                'hi_price' => 300000.00,
                'is_hi_active' => true,
                'official_claim_name' => 'رقود حديثي الولادة لليوم الواحد شامل الخدمات التمريضية على جهاز ventilator',
                'hotel_fee' => 100000.00,
                'nursing_fee' => 60000.00,
                'device_fee' => 200000.00,
                'description' => 'عناية مركزة متقدمة تشمل جهاز التنفس الاصطناعي الغازي عبر الأنبوب الرغامي ومراقبة معايير التنفس بدقة.',
                'is_active' => true,
            ],
            [
                'name' => 'كبسولة علاج ضوئي مكثف (Phototherapy Capsule)',
                'code' => 'capsule_phototherapy',
                'daily_fee' => 250000.00,
                'hi_price' => 200000.00,
                'is_hi_active' => true,
                'official_claim_name' => 'رقود حديثي الولادة لليوم الواحد شامل الخدمات التمريضية والفندقية مع علاج ضوئي',
                'hotel_fee' => 100000.00,
                'nursing_fee' => 50000.00,
                'device_fee' => 100000.00,
                'description' => 'كبسولة ضوئية متطورة 360 درجة لعلاج حالات اليرقان الشديدة لتقليل البيليروبين بسرعة فائقة.',
                'is_active' => true,
            ],
            [
                'name' => 'رقود للأطفال خارج الخدج (ردهة الأطفال)',
                'code' => 'pediatric_inpatient',
                'daily_fee' => 250000.00,
                'hi_price' => 200000.00,
                'is_hi_active' => true,
                'official_claim_name' => 'الرقود في غرفه منفردة ليوم واحد مع الخدمات التمريضيه و الفندقية',
                'hotel_fee' => 150000.00,
                'nursing_fee' => 100000.00,
                'device_fee' => 0.00,
                'description' => 'إقامة واستشفاء سريري للأطفال فوق سن الخدج في ردهات وغرف المستشفى العامة تحت إشراف طبي متخصص.',
                'is_active' => true,
            ],
            [
                'name' => 'حاضنة + Ventilator لطفل محال من خارج المستشفى (OUT)',
                'code' => 'incubator_ventilator_out',
                'daily_fee' => 300000.00,
                'hi_price' => 300000.00,
                'is_hi_active' => true,
                'official_claim_name' => 'رقود حديثي الولادة لليوم الواحد شامل الخدمات التمريضية على جهاز ventilator',
                'hotel_fee' => 100000.00,
                'nursing_fee' => 50000.00,
                'device_fee' => 150000.00,
                'description' => 'استقبال وعناية مركزة تنفسية لحالات الخدج الحرجة المحالة من مستشفيات أو مراكز ولادة خارجية.',
                'is_active' => true,
            ],
        ];

        foreach ($services as $serviceData) {
            NicuService::updateOrCreate(
                ['code' => $serviceData['code']],
                $serviceData
            );
        }
    }
}
