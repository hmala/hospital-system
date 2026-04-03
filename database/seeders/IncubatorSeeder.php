<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Incubator;
use App\Models\Room;

class IncubatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // البحث عن غرفة ط6 أو إنشائها
        $room = Room::where('room_number', 'ط6')->first();
        
        if (!$room) {
            $room = Room::create([
                'room_number' => 'ط6',
                'room_type' => 'regular',
                'room_purpose' => 'incubators',
                'floor' => 'الطابق السادس',
                'daily_fee' => 0, // الأجرة للحاضنة نفسها
                'status' => 'available',
                'beds_count' => 16, // 16 حاضنة
                'description' => 'قسم العناية المركزة بالخدج - يحتوي على 16 حاضنة',
                'is_active' => true,
            ]);
        }

        // إنشاء 16 حاضنة بأنواع مختلفة
        $incubators = [
            // حاضنات عادية (1-6)
            ['number' => '1', 'type' => 'normal', 'fee' => 50000],
            ['number' => '2', 'type' => 'normal', 'fee' => 50000],
            ['number' => '3', 'type' => 'normal', 'fee' => 50000],
            ['number' => '4', 'type' => 'normal', 'fee' => 50000],
            ['number' => '5', 'type' => 'normal', 'fee' => 50000],
            ['number' => '6', 'type' => 'normal', 'fee' => 50000],
            
            // حاضنات بأكسجين (7-11)
            ['number' => '7', 'type' => 'oxygen', 'fee' => 75000],
            ['number' => '8', 'type' => 'oxygen', 'fee' => 75000],
            ['number' => '9', 'type' => 'oxygen', 'fee' => 75000],
            ['number' => '10', 'type' => 'oxygen', 'fee' => 75000],
            ['number' => '11', 'type' => 'oxygen', 'fee' => 75000],
            
            // حاضنات بعلاج ضوئي (12-16)
            ['number' => '12', 'type' => 'phototherapy', 'fee' => 80000],
            ['number' => '13', 'type' => 'phototherapy', 'fee' => 80000],
            ['number' => '14', 'type' => 'phototherapy', 'fee' => 80000],
            ['number' => '15', 'type' => 'phototherapy', 'fee' => 80000],
            ['number' => '16', 'type' => 'phototherapy', 'fee' => 80000],
        ];

        foreach ($incubators as $incData) {
            // التحقق من عدم وجود الحاضنة مسبقاً
            $existing = Incubator::where('incubator_number', $incData['number'])->first();
            
            if (!$existing) {
                Incubator::create([
                    'incubator_number' => $incData['number'],
                    'incubator_type' => $incData['type'],
                    'status' => 'available',
                    'room_id' => $room->id,
                    'daily_fee' => $incData['fee'],
                    'description' => $this->getDescription($incData['type']),
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('تم إنشاء 16 حاضنة في غرفة ط6 بنجاح!');
    }

    /**
     * الحصول على وصف الحاضنة حسب نوعها
     */
    private function getDescription(string $type): string
    {
        return match($type) {
            'normal' => 'حاضنة عادية للخدج - مجهزة بأنظمة التحكم بدرجة الحرارة والرطوبة',
            'oxygen' => 'حاضنة مزودة بنظام أكسجين - للحالات التي تتطلب دعم تنفسي',
            'phototherapy' => 'حاضنة مزودة بعلاج ضوئي - لعلاج الصفراء (اليرقان الوليدي)',
            default => 'حاضنة للعناية بالخدج',
        };
    }
}
