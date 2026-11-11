<?php

namespace Database\Seeders;

use App\Models\IcuBed;
use Illuminate\Database\Seeder;

class IcuBedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $beds = [
            [
                'bed_number' => '1',
                'bed_type' => IcuBed::TYPE_GENERAL,
                'room_number' => 'ICU-301',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 150000.00,
                'notes' => 'سرير عناية عامة مجهز بجهاز تنفس اصطناعي وشاشة مراقبة مركزية',
            ],
            [
                'bed_number' => '2',
                'bed_type' => IcuBed::TYPE_GENERAL,
                'room_number' => 'ICU-301',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 150000.00,
                'notes' => 'سرير عناية عامة متعدد الوضعيات',
            ],
            [
                'bed_number' => '3',
                'bed_type' => IcuBed::TYPE_GENERAL,
                'room_number' => 'ICU-302',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 150000.00,
                'notes' => 'سرير عناية عامة مجهز بمضخات محاليل دقيقة',
            ],
            [
                'bed_number' => '4',
                'bed_type' => IcuBed::TYPE_GENERAL,
                'room_number' => 'ICU-302',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 150000.00,
                'notes' => 'سرير عناية عامة مجهز بنظام مراقبة تنفسي',
            ],
            [
                'bed_number' => '5',
                'bed_type' => IcuBed::TYPE_GENERAL,
                'room_number' => 'ICU-303',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 150000.00,
                'notes' => 'سرير عناية عامة',
            ],
            [
                'bed_number' => '6',
                'bed_type' => IcuBed::TYPE_GENERAL,
                'room_number' => 'ICU-303',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 150000.00,
                'notes' => 'سرير عناية عامة',
            ],
            [
                'bed_number' => '7',
                'bed_type' => IcuBed::TYPE_GENERAL,
                'room_number' => 'ICU-304',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 150000.00,
                'notes' => 'سرير عناية عامة',
            ],
            [
                'bed_number' => '8',
                'bed_type' => IcuBed::TYPE_GENERAL,
                'room_number' => 'ICU-304',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 150000.00,
                'notes' => 'سرير عناية عامة',
            ],
            [
                'bed_number' => '9',
                'bed_type' => IcuBed::TYPE_NEURO,
                'room_number' => 'ICU-305',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 180000.00,
                'notes' => 'سرير عناية عصبية ودماغية Neuro-ICU مع مراقبة ضغط الجمجمة ICP',
            ],
            [
                'bed_number' => '10',
                'bed_type' => IcuBed::TYPE_CCU,
                'room_number' => 'ICU-306',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 180000.00,
                'notes' => 'سرير عناية قلبية فائقة CCU موصول بجهاز مراقبة الإيكو وتخطيط القلب 12-Lead',
            ],
            [
                'bed_number' => '11',
                'bed_type' => IcuBed::TYPE_CCU,
                'room_number' => 'ICU-306',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 180000.00,
                'notes' => 'سرير عناية قلبية فائقة CCU',
            ],
            [
                'bed_number' => '12',
                'bed_type' => IcuBed::TYPE_ISOLATION,
                'room_number' => 'ICU-ISO-1',
                'floor' => 'الطابق الثالث - العناية المركزة',
                'status' => IcuBed::STATUS_AVAILABLE,
                'daily_price' => 200000.00,
                'notes' => 'غرفة وسرير عزل متقدم بنظام ضغط سالب (Negative Pressure) للحالات التنفسية المعدية',
            ],
        ];

        foreach ($beds as $bedData) {
            IcuBed::updateOrCreate(
                ['bed_number' => $bedData['bed_number']],
                $bedData
            );
        }
    }
}
