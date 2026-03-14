<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== حالة الغرف بعد المزامنة ===" . PHP_EOL . PHP_EOL;

$occupiedRooms = App\Models\Room::where('status', 'occupied')->get();
$availableRooms = App\Models\Room::where('status', 'available')->count();
$maintenanceRooms = App\Models\Room::where('status', 'maintenance')->count();

echo "الغرف المتاحة: " . $availableRooms . PHP_EOL;
echo "الغرف المحجوزة: " . $occupiedRooms->count() . PHP_EOL;
echo "الغرف في الصيانة: " . $maintenanceRooms . PHP_EOL;
echo PHP_EOL;

if ($occupiedRooms->count() > 0) {
    echo "تفاصيل الغرف المحجوزة:" . PHP_EOL;
    foreach ($occupiedRooms as $room) {
        echo "  - الغرفة: " . $room->room_number . " (الطابق: " . $room->floor . ")" . PHP_EOL;
    }
}