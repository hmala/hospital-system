<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MedicalDevice;

class MedicalDevicesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // مسح الأجهزة السابقة لتجنب التكرار
        MedicalDevice::truncate();

        $devices = [
            // عربات التخدير
            ['name' => 'عربة تخدير', 'type' => 'تخدير', 'supplier' => 'GE', 'serial_number' => '70441136', 'price' => 26250000],
            ['name' => 'عربة تخدير', 'type' => 'تخدير', 'supplier' => 'GE', 'serial_number' => '70429794', 'price' => 26250000],
            ['name' => 'عربة تخدير', 'type' => 'تخدير', 'supplier' => 'GE', 'serial_number' => '70403039', 'price' => 26250000],
            ['name' => 'عربة تخدير', 'type' => 'تخدير', 'supplier' => 'GE', 'serial_number' => '70429823', 'price' => 26250000],
            ['name' => 'عربة تخدير', 'type' => 'تخدير', 'supplier' => 'GE', 'serial_number' => '70429337', 'price' => 26250000],
            ['name' => 'عربة تخدير', 'type' => 'تخدير', 'supplier' => 'GE', 'serial_number' => '70450927', 'price' => 26250000],
            ['name' => 'عربة تخدير', 'type' => 'تخدير', 'supplier' => 'GE', 'serial_number' => '70403040', 'price' => 26250000],
            ['name' => 'عربة تخدير', 'type' => 'تخدير', 'supplier' => 'GE', 'serial_number' => 'DTK39132881', 'price' => 26250000],
            
            // إضاءة وسرير عمليات
            ['name' => 'لايت عمليات سقفي', 'type' => 'إضاءة عمليات', 'supplier' => 'MINDRAY', 'serial_number' => 'MF2-34000021', 'price' => 24000000],
            ['name' => 'سرير عمليات', 'type' => 'سرير طبي', 'supplier' => 'MINDRAY', 'serial_number' => 'OPBED-01', 'price' => 26700000],
            
            // أجهزة كوي CAUTERY
            ['name' => 'CAUTERY', 'type' => 'كوي جراحي', 'supplier' => 'ERBY', 'serial_number' => '1132077', 'price' => 6750000],
            ['name' => 'CAUTERY', 'type' => 'كوي جراحي', 'supplier' => 'ERBY', 'serial_number' => '1132078', 'price' => 6750000],
            ['name' => 'CAUTERY', 'type' => 'كوي جراحي', 'supplier' => 'ERBY', 'serial_number' => '1132079', 'price' => 6750000],
            ['name' => 'CAUTERY', 'type' => 'كوي جراحي', 'supplier' => 'ERBY', 'serial_number' => '1132082', 'price' => 6750000],
            ['name' => 'CAUTERY', 'type' => 'كوي جراحي', 'supplier' => 'ERBY', 'serial_number' => '1132083', 'price' => 6750000],
            ['name' => 'CAUTERY', 'type' => 'كوي جراحي', 'supplier' => 'ERBY', 'serial_number' => '1132084', 'price' => 6750000],
            
            // أجهزة شفط جراحي Surgical Suction
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'EURO-HEALTH', 'serial_number' => 'DE23-05-83', 'price' => 1875000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'EURO-HEALTH', 'serial_number' => 'DE23-05-91', 'price' => 1875000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'EURO-HEALTH', 'serial_number' => 'DE23-05-79', 'price' => 1875000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'EURO-HEALTH', 'serial_number' => 'DE23-05-68', 'price' => 1875000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'EURO-HEALTH', 'serial_number' => 'DE23-05-90', 'price' => 1875000],
            
            // نواظير
            ['name' => 'SURGICAL ENDOSCOPE HD', 'type' => 'ناظور جراحي', 'supplier' => 'STORZ', 'serial_number' => 'AII2VV010', 'price' => 150000000],
            ['name' => 'SURGICAL ENDOSCOPE 4K', 'type' => 'ناظور جراحي', 'supplier' => 'STORZ', 'serial_number' => '28436053', 'price' => 237000000],
            ['name' => 'SURGICAL ENDOSCOPE 4K', 'type' => 'ناظور جراحي', 'supplier' => 'ARTHRIX', 'serial_number' => 'ANOO1647001214', 'price' => 65000000],
            ['name' => 'GASTROSCOPY', 'type' => 'ناظور معدة', 'supplier' => 'OLYMPUS', 'serial_number' => '2012572', 'price' => 76850000],
            ['name' => 'ناظور مرن', 'type' => 'ناظور', 'supplier' => 'Seplou', 'serial_number' => 'IU000220501X', 'price' => 0],
            ['name' => 'ناظور مرن', 'type' => 'ناظور', 'supplier' => 'Scivita', 'serial_number' => '851010762', 'price' => 0],
            ['name' => 'ناظور مرن', 'type' => 'ناظور', 'supplier' => 'WOOK', 'serial_number' => 'FLEXEND-01', 'price' => 0],

            // أجهزة شفط أخرى
            ['name' => 'Moller Suction device', 'type' => 'جهاز شفط', 'supplier' => 'MOLLER', 'serial_number' => '23120114', 'price' => 75000000],
            ['name' => 'Euromi Suction device', 'type' => 'جهاز شفط', 'supplier' => 'EUROMI', 'serial_number' => 'sp12307578-2023', 'price' => 24000000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'MEDELA-DOMINANT', 'serial_number' => 'DE23-05-79-MD', 'price' => 1875000],
            
            // تفتيت حصى وسونار
            ['name' => 'Quantasystem litho', 'type' => 'جهاز تفتيت حصى', 'supplier' => 'LithoEvo', 'serial_number' => 'LHV4179-1223', 'price' => 71400000],
            ['name' => 'Ultrasound P40', 'type' => 'سونار', 'supplier' => 'SonoScape', 'serial_number' => 'JF23060319-2324119', 'price' => 48000000],
            ['name' => 'Ultrasound', 'type' => 'سونار', 'supplier' => 'Siemens', 'serial_number' => '105070', 'price' => 39000000],
            
            // أجهزة أخرى متنوعة
            ['name' => 'A. T. S. 3000', 'type' => 'تورنيكي', 'supplier' => 'ZIMMER', 'serial_number' => '3013HAAK', 'price' => 3750000],
            ['name' => 'A. T. S. 3001', 'type' => 'تورنيكي', 'supplier' => 'ZIMMER', 'serial_number' => '3013HABC', 'price' => 3750000],
            ['name' => 'LegaSure device', 'type' => 'جهاز ليغاشور', 'supplier' => 'COVIDIEN', 'serial_number' => 'L22K0430GX', 'price' => 15450000],
            ['name' => 'LegaSure device', 'type' => 'جهاز ليغاشور', 'supplier' => 'COVIDIEN', 'serial_number' => 'L23C0616GX', 'price' => 15450000],
            ['name' => 'Leonardo lazer', 'type' => 'ليزر جراحي', 'supplier' => 'Biolitec', 'serial_number' => '1759-L', 'price' => 34500000],
            ['name' => 'Autoclave', 'type' => 'معقم', 'supplier' => 'SHINVA', 'serial_number' => '2023B0674', 'price' => 25500000],
            ['name' => 'Autoclave', 'type' => 'معقم', 'supplier' => 'SHINVA', 'serial_number' => '202434519', 'price' => 25500000],
            ['name' => 'كابسة حرارية للتعقيم', 'type' => 'تعقيم', 'supplier' => 'YUWELL', 'serial_number' => 'YOAHJ03', 'price' => 450000],
            ['name' => 'ماكنة تعفير', 'type' => 'تعقيم', 'supplier' => 'ANIOS', 'serial_number' => 'AU1250', 'price' => 11400000],
            ['name' => 'Screen X-Ray', 'type' => 'شاشة أشعة', 'supplier' => 'PHILIPS', 'serial_number' => '2091', 'price' => 83000000],
            ['name' => 'Screen X-Ray', 'type' => 'شاشة أشعة', 'supplier' => 'Siemens', 'serial_number' => '1813101', 'price' => 85000000],
            
            // شفرات
            ['name' => 'Shaver', 'type' => 'جهاز شفرة', 'supplier' => 'STORZ', 'serial_number' => 'SH-01', 'price' => 95000000],
            ['name' => 'Shaver', 'type' => 'جهاز شفرة', 'supplier' => 'STORZ', 'serial_number' => 'SH-02', 'price' => 95000000],
            ['name' => 'Shaver', 'type' => 'جهاز شفرة', 'supplier' => 'STORZ', 'serial_number' => 'SH-03', 'price' => 0],
            ['name' => 'Hand Shaver', 'type' => 'مقبض شفرة', 'supplier' => 'STORZ', 'serial_number' => 'HS-01', 'price' => 10000000],
            
            // أجهزة تخصصية
            ['name' => 'Plasma device', 'type' => 'بلازما', 'supplier' => 'PLASMS', 'serial_number' => '2515', 'price' => 27000000],
            ['name' => 'جهاز شفط دهون', 'type' => 'شفط دهون', 'supplier' => 'صيني', 'serial_number' => 'SUCT-CHN-01', 'price' => 11250000],
            ['name' => 'COBLATER ENT', 'type' => 'كوبليتر', 'supplier' => 'BONSS', 'serial_number' => 'A7-2407024TM', 'price' => 21855000],
            ['name' => 'Rezum Device', 'type' => 'ريزوم', 'supplier' => 'REZUM', 'serial_number' => '5103', 'price' => 105000000],
            ['name' => 'سيتات جراحية', 'type' => 'جراحة', 'supplier' => 'STORZ', 'serial_number' => 'SURGSET-01', 'price' => 6000000],
            
            // شاشات ومجهر
            ['name' => 'Monitor', 'type' => 'شاشة مراقبة', 'supplier' => 'GE', 'serial_number' => 'SEWH121552H1', 'price' => 4500000],
            ['name' => 'Monitor', 'type' => 'شاشة مراقبة', 'supplier' => 'UMOUNT', 'serial_number' => 'SEW12121549AH', 'price' => 4500000],
            ['name' => 'MICROSCOPE', 'type' => 'مجهر جراحي', 'supplier' => 'ALLTION', 'serial_number' => 'MIE240024', 'price' => 14400000],
            ['name' => 'SURGICAL LASER', 'type' => 'ليزر جراحي', 'supplier' => 'MEDICAL DIODE LASER', 'serial_number' => 'GA23-V4937', 'price' => 18000000],
            ['name' => 'سدية عمليات كسور', 'type' => 'سدية', 'supplier' => 'ESCHMANN', 'serial_number' => 'T2MB2L1883', 'price' => 18750000],
            ['name' => 'Advin Light Source', 'type' => 'مصدر ضوء', 'supplier' => 'Advin Health care', 'serial_number' => 'ADV-LS-01', 'price' => 1800000],
            ['name' => 'ليزر ثاليوم', 'type' => 'ليزر', 'supplier' => 'ASKPRO', 'serial_number' => 'THAL-LZ-01', 'price' => 90000000],
            ['name' => 'Warmer', 'type' => 'جهاز تدفئة', 'supplier' => 'المنارة الزرقاء', 'serial_number' => '231086431', 'price' => 3750000],
            ['name' => 'Warming System', 'type' => 'نظام تدفئة', 'supplier' => 'HEPHO', 'serial_number' => '210124060038', 'price' => 0],
            
            // أجهزة شفط Yuwell
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'yuwell', 'serial_number' => '23110800058', 'price' => 450000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'yuwell', 'serial_number' => '23110800012', 'price' => 450000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'yuwell', 'serial_number' => '23110800027', 'price' => 450000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'yuwell', 'serial_number' => '23110800010', 'price' => 450000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'yuwell', 'serial_number' => '23110800067', 'price' => 450000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'yuwell', 'serial_number' => '23110800029', 'price' => 450000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'Konsung', 'serial_number' => 'Ma22100320345', 'price' => 450000],
            ['name' => 'Surgical Suction', 'type' => 'شفط جراحي', 'supplier' => 'Konsung', 'serial_number' => 'Ma22100320243', 'price' => 450000],
            
            // أجهزة أخرى
            ['name' => 'DC Shock', 'type' => 'جهاز صدمة كهربائية', 'supplier' => 'Cardiolife', 'serial_number' => '9001', 'price' => 6750000],
            ['name' => 'جهاز تفتيت حصى', 'type' => 'تفتيت حصى', 'supplier' => 'هندي', 'serial_number' => 'ESWL-IND-01', 'price' => 1800000],
            ['name' => 'لايت سورس ستورز', 'type' => 'مصدر ضوء', 'supplier' => '173', 'serial_number' => 'LS-173', 'price' => 9000000],
            ['name' => 'لايت عمليات متنقل', 'type' => 'إضاءة عمليات', 'supplier' => 'ديار بغداد', 'serial_number' => 'L-PORT-01', 'price' => 5625000],
        ];

        foreach ($devices as $device) {
            MedicalDevice::create(array_merge($device, [
                'status' => 'active',
                'purchase_date' => now()->subYears(rand(1, 3))->format('Y-m-d'),
                'last_maintenance_at' => now()->subMonths(rand(1, 6))->format('Y-m-d'),
            ]));
        }
    }
}
