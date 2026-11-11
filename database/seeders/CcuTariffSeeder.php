<?php

namespace Database\Seeders;

use App\Models\CcuService;
use App\Models\CcuSetting;
use App\Models\CcuStayBracket;
use Illuminate\Database\Seeder;

class CcuTariffSeeder extends Seeder
{
    public function run(): void
    {
        self::seedDefaults();
    }

    public static function seedDefaults(): void
    {
        // 1. الإعدادات العامة الداينميكية
        $settings = [
            [
                'key' => 'annual_max_insured_days',
                'value' => '30',
                'label' => 'الحد الأقصى للرقود السنوي بالضمان (أيام)',
                'description' => 'الحد الأقصى لإجمالي أيام الرقود المغطاة في الردهات الحرجة للكبار خلال السنة (30 يوماً حسب الوثيقة الرسمية).',
            ],
            [
                'key' => 'post_max_days_hia_percent',
                'value' => '0',
                'label' => 'نسبة تغطية الضمان بعد تجاوز الحد السنوي (%)',
                'description' => 'نسبة التغطية بعد استنفاد الـ 30 يوماً السنوية (الافتراضي 0%، يتحمل المريض 100%).',
            ],
            [
                'key' => 'cutoff_hour',
                'value' => '12',
                'label' => 'ساعة قطع اليوم الفندقي السريري (12 ظهراً)',
                'description' => 'توقيت بداية ونهاية اليوم الفندقي (12:00 ظهراً بتوقيت العراق).',
            ],
            [
                'key' => 'grace_period_hours',
                'value' => '6',
                'label' => 'نافذة السماحية الفندقية (ساعات)',
                'description' => 'عدد ساعات السماح للدخول المبكر أو الخروج المتأخر حول الساعة 12:00 ظهراً.',
            ],
            [
                'key' => 'internal_consultation_hia_percent',
                'value' => '90',
                'label' => 'نسبة تغطية الضمان للاستشارات الداخلية (%)',
                'description' => 'نسبة تحمل هيئة الضمان للاستشارات الطبية الداخلية داخل المستشفى.',
            ],
            [
                'key' => 'extra_lab_hia_percent',
                'value' => '75',
                'label' => 'نسبة تغطية الضمان للتحاليل الإضافية خارج الباقة (%)',
                'description' => 'نسبة تغطية الضمان عند احتساب تحليل خارج الباقة بضغطة زر لمرضى الضمان.',
            ],
            [
                'key' => 'extra_radiology_hia_percent',
                'value' => '75',
                'label' => 'نسبة تغطية الضمان للأشعة والسونار المشمول (%)',
                'description' => 'نسبة تغطية الضمان عند احتساب فحص أشعة أو سونار لمرضى الضمان.',
            ],
            [
                'key' => 'category_a_exempt',
                'value' => '1',
                'label' => 'إعفاء الفئة A بنسبة 100%',
                'description' => 'إعفاء فئة الرعاية الاجتماعية وذوي الشهداء بنسبة 100% على الضمان.',
            ],
        ];

        foreach ($settings as $s) {
            CcuSetting::firstOrCreate(['key' => $s['key']], $s);
        }

        // 2. شرائح نسب التغطية حسب مدة الرقود (Official MOH 2026 Adult ICU Brackets)
        if (CcuStayBracket::count() === 0) {
            $brackets = [
                [
                    'name' => 'الأيام الخمسة الأولى (1 - 5 أيام)',
                    'policy_category' => 'ALL',
                    'min_day' => 1,
                    'max_day' => 5,
                    'hia_percent' => 75.00,
                    'patient_percent' => 25.00,
                    'sort_order' => 1,
                    'is_active' => true,
                ],
                [
                    'name' => 'الأيام الخمسة الثانية (6 - 10 أيام)',
                    'policy_category' => 'ALL',
                    'min_day' => 6,
                    'max_day' => 10,
                    'hia_percent' => 50.00,
                    'patient_percent' => 50.00,
                    'sort_order' => 2,
                    'is_active' => true,
                ],
                [
                    'name' => 'الأيام الخمسة الثالثة (11 - 15 يوماً)',
                    'policy_category' => 'ALL',
                    'min_day' => 11,
                    'max_day' => 15,
                    'hia_percent' => 25.00,
                    'patient_percent' => 75.00,
                    'sort_order' => 3,
                    'is_active' => true,
                ],
                [
                    'name' => 'فترة الرقود أكثر من 15 يوماً (16 - 30 يوماً)',
                    'policy_category' => 'ALL',
                    'min_day' => 16,
                    'max_day' => 30,
                    'hia_percent' => 10.00,
                    'patient_percent' => 90.00,
                    'sort_order' => 4,
                    'is_active' => true,
                ],
            ];

            foreach ($brackets as $b) {
                CcuStayBracket::create($b);
            }
        }

        // 3. باقات وخدمات العناية المركزة للكبار (الرسمية + تسعيرة الكاش والخدمات المنفصلة)
        $services = [
            [
                'name' => 'العناية المركزة - دعم تنفسي اصطناعي (Ventilator Support)',
                'code' => 'ventilator_support',
                'official_claim_name' => 'رقود ردهة العناية المركزة (VENTILATOR SUPPORT) شاملة الخدمات الطبية والتمريضية والتقليدية والعلاجية والفحوصات المختبرية',
                'service_type' => CcuService::TYPE_PACKAGE,
                'daily_fee' => 750000.00,
                'hi_price' => 750000.00,
                'is_hi_active' => true,
                'include_bed_fee' => true,
                'is_bundled_lab' => true,
                'is_bundled_radiology' => false,
                'is_bundled_medications' => true,
                'hotel_fee' => 150000.00,
                'nursing_fee' => 150000.00,
                'device_fee' => 450000.00,
                'description' => 'باقة العناية المركزة لمريض الفنتليتر (750,000 د.ع لليوم): شاملة الخدمات الطبية، التمريضية، الفندقية، العلاجية كافة، والفحوصات المختبرية.',
                'is_active' => true,
            ],
            [
                'name' => 'العناية المركزة - مراقبة الأكسجين (Oxygen Monitoring)',
                'code' => 'oxygen_monitoring',
                'official_claim_name' => 'رقود ردهة العناية المركزة (OXYGEN MONITORING) شاملة الخدمات الطبية والتمريضية والتقليدية والعلاجية والفحوصات المختبرية',
                'service_type' => CcuService::TYPE_PACKAGE,
                'daily_fee' => 500000.00,
                'hi_price' => 500000.00,
                'is_hi_active' => true,
                'include_bed_fee' => true,
                'is_bundled_lab' => true,
                'is_bundled_radiology' => false,
                'is_bundled_medications' => true,
                'hotel_fee' => 150000.00,
                'nursing_fee' => 150000.00,
                'device_fee' => 200000.00,
                'description' => 'باقة العناية المركزة لمراقبة الأكسجين (500,000 د.ع لليوم): شاملة الخدمات الطبية، التمريضية، الفندقية، العلاجية كافة، والفحوصات المختبرية.',
                'is_active' => true,
            ],
            [
                'name' => 'ردهات العناية القلبية (CCU)',
                'code' => 'ccu_cardiac',
                'official_claim_name' => 'الرقود في ردهات العناية القلبية (CCU) شامل الخدمات التمريضية والفندقية',
                'service_type' => CcuService::TYPE_PACKAGE,
                'daily_fee' => 350000.00,
                'hi_price' => 350000.00,
                'is_hi_active' => true,
                'include_bed_fee' => true,
                'is_bundled_lab' => false,
                'is_bundled_radiology' => false,
                'is_bundled_medications' => false,
                'hotel_fee' => 150000.00,
                'nursing_fee' => 125000.00,
                'device_fee' => 75000.00,
                'description' => 'الرقود في ردهات العناية القلبية CCU (350,000 د.ع لليوم) شامل الخدمات التمريضية والفندقية.',
                'is_active' => true,
            ],
            [
                'name' => 'الرقود في غرفة منفردة (رعاية ومتابعة)',
                'code' => 'single_room_followup',
                'official_claim_name' => 'الرقود في غرفة منفردة ليوم واحد مع الخدمات التمريضية والفندقية',
                'service_type' => CcuService::TYPE_PACKAGE,
                'daily_fee' => 200000.00,
                'hi_price' => 200000.00,
                'is_hi_active' => true,
                'include_bed_fee' => true,
                'is_bundled_lab' => false,
                'is_bundled_radiology' => false,
                'is_bundled_medications' => false,
                'hotel_fee' => 120000.00,
                'nursing_fee' => 80000.00,
                'device_fee' => 0.00,
                'description' => 'الرقود في غرفة منفردة ليوم واحد (200,000 د.ع) مع الخدمات التمريضية والفندقية.',
                'is_active' => true,
            ],
            [
                'name' => 'تسعيرة سرير العناية فقط (كاش - خدمات منفصلة)',
                'code' => 'cash_bed_only_separate',
                'official_claim_name' => 'إقامة سرير العناية المركزة (تسعيرة السرير الخاصة منفصلة)',
                'service_type' => CcuService::TYPE_PACKAGE,
                'daily_fee' => 0.00,
                'hi_price' => 0.00,
                'is_hi_active' => false,
                'include_bed_fee' => false, // يعتمد سعر السرير اليومي icu_beds.daily_price + الخدمات الإضافية المنفصلة
                'is_bundled_lab' => false,
                'is_bundled_radiology' => false,
                'is_bundled_medications' => false,
                'hotel_fee' => 0.00,
                'nursing_fee' => 0.00,
                'device_fee' => 0.00,
                'description' => 'نظام الكاش المخصص: يحتسب سعر السرير اليومي المحدد لكل سرير وتُضاف التحاليل والأشعة والأجهزة كخدمات منفصلة.',
                'is_active' => true,
            ],
            // خدمات إضافية مخصصة منفصلة (Add-on Custom Services)
            [
                'name' => 'خدمة جهاز تنفس اصطناعي إضافي (Ventilator Daily Add-on)',
                'code' => 'addon_ventilator_daily',
                'official_claim_name' => 'جهاز تنفس اصطناعي منفصل',
                'service_type' => CcuService::TYPE_ADDON,
                'daily_fee' => 250000.00,
                'hi_price' => 250000.00,
                'is_hi_active' => false,
                'include_bed_fee' => false,
                'is_bundled_lab' => false,
                'is_bundled_radiology' => false,
                'is_bundled_medications' => false,
                'description' => 'خدمة يومية منفصلة لجهاز التنفس الاصطناعي تضاف على أجرة السرير في نظام الكاش المخصص.',
                'is_active' => true,
            ],
            [
                'name' => 'خدمة مراقبة قلبية ومضخات تسريب (Advanced Monitoring)',
                'code' => 'addon_monitoring_pumps',
                'official_claim_name' => 'مراقبة قلبية متقدمة ومضخات تسريب',
                'service_type' => CcuService::TYPE_ADDON,
                'daily_fee' => 100000.00,
                'hi_price' => 100000.00,
                'is_hi_active' => false,
                'include_bed_fee' => false,
                'is_bundled_lab' => false,
                'is_bundled_radiology' => false,
                'is_bundled_medications' => false,
                'description' => 'خدمة يومية إضافية للمونيتور ومضخات التسريب في نظام التسعيرة المنفصلة.',
                'is_active' => true,
            ],
            [
                'name' => 'جلسة غسيل كلوي مستمر بالعناية (CRRT Session)',
                'code' => 'addon_crrt_session',
                'official_claim_name' => 'غسيل كلوي مستمر CRRT',
                'service_type' => CcuService::TYPE_ADDON,
                'daily_fee' => 300000.00,
                'hi_price' => 300000.00,
                'is_hi_active' => false,
                'include_bed_fee' => false,
                'is_bundled_lab' => false,
                'is_bundled_radiology' => false,
                'is_bundled_medications' => false,
                'description' => 'خدمة مخصصة منفصلة لجلسة الغسيل الكلوي بالعناية الحرجة.',
                'is_active' => true,
            ],
        ];

        foreach ($services as $srv) {
            CcuService::firstOrCreate(['code' => $srv['code']], $srv);
        }
    }
}
