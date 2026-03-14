<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$count = App\Models\BedReservation::where('status', 'confirmed')->count();
echo 'عدد الحجوزات النشطة: ' . $count . PHP_EOL;

if($count > 0) {
    echo 'الغرف المشغولة:' . PHP_EOL;
    $reservations = App\Models\BedReservation::where('status', 'confirmed')->with('room')->get();
    $grouped = $reservations->groupBy('room_id');
    foreach($grouped as $roomId => $res) {
        $room = $res->first()->room;
        echo 'الغرفة: ' . $room->room_number . ' - عدد المرضى: ' . $res->count() . PHP_EOL;
    }
} else {
    echo 'لا توجد حجوزات نشطة.';
}