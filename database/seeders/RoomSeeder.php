<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 6 طوابق، كل طابق 10 غرف
     * الغرف العادية: 40,000 د.ع
     * غرف VIP: 100,000 د.ع
     */
    public function run(): void
    {
        // مسح الغرف الموجودة (اختياري)
        // Room::truncate();

        $floors = [
            2 => 'الطابق الثاني',
            3 => 'الطابق الثالث',
            4 => 'الطابق الرابع',
            5 => 'الطابق الخامس',
            6 => 'الطابق السادس',
            7 => 'الطابق السابع',
        ];

        foreach ($floors as $floorNum => $floorName) {
            for ($roomNum = 1; $roomNum <= 10; $roomNum++) {
                $roomNumber = $floorNum . str_pad($roomNum, 2, '0', STR_PAD_LEFT);
                
                // غرف VIP: الغرف 01, 02, 10 في كل طابق
                $isVip = in_array($roomNum, [1, 2, 10]);
                
                Room::create([
                    'room_number' => $roomNumber,
                    'room_type' => $isVip ? 'vip' : 'regular',
                    'floor' => $floorName,
                    'daily_fee' => $isVip ? 100000 : 40000,
                    'status' => 'available',
                    'beds_count' => $isVip ? 1 : 2,
                    'has_bathroom' => true,
                    'has_tv' => true,
                    'has_ac' => true,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('تم إنشاء 60 غرفة بنجاح (6 طوابق × 10 غرف)');
    }
}
