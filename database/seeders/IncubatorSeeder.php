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

        // إنشاء 16 حاضنة بنوعين معتمدين (حاضنة اعتيادية، وكبسولة علاج ضوئي)
        $incubators = [
            // حاضنات اعتيادية (1-14)
            ['number' => '1', 'type' => 'normal', 'fee' => 0],
            ['number' => '2', 'type' => 'normal', 'fee' => 0],
            ['number' => '3', 'type' => 'normal', 'fee' => 0],
            ['number' => '4', 'type' => 'normal', 'fee' => 0],
            ['number' => '5', 'type' => 'normal', 'fee' => 0],
            ['number' => '6', 'type' => 'normal', 'fee' => 0],
            ['number' => '7', 'type' => 'normal', 'fee' => 0],
            ['number' => '8', 'type' => 'normal', 'fee' => 0],
            ['number' => '9', 'type' => 'normal', 'fee' => 0],
            ['number' => '10', 'type' => 'normal', 'fee' => 0],
            ['number' => '11', 'type' => 'normal', 'fee' => 0],
            ['number' => '12', 'type' => 'normal', 'fee' => 0],
            ['number' => '13', 'type' => 'normal', 'fee' => 0],
            ['number' => '14', 'type' => 'normal', 'fee' => 0],
            
            // كبسولات علاج ضوئي مكثف (15-16)
            ['number' => '15', 'type' => 'capsule', 'fee' => 0],
            ['number' => '16', 'type' => 'capsule', 'fee' => 0],
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
            'capsule' => 'كبسولة علاج ضوئي مكثف للخدج (Phototherapy Capsule)',
            default => 'حاضنة عناية فائقة للخدج (Incubator)',
        };
    }
}
