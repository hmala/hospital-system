<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Doctor;
use App\Models\User;

echo "📊 إحصائيات الأطباء بعد السيدر:\n\n";

$consultants = Doctor::where('type', 'consultant')->count();
$anesthesiologists = Doctor::where('type', 'anesthesiologist')->count();
$surgeons = Doctor::where('type', 'surgeon')->count();
$total = Doctor::count();

echo "🩺 الأطباء الاستشاريين: {$consultants}\n";
echo "👨‍⚕️ أطباء التخدير: {$anesthesiologists}\n";
echo "🔪 الجراحين: {$surgeons}\n";
echo "📝 المجموع: {$total}\n\n";

echo "📋 عينة من الأطباء الاستشاريين مع جداولهم:\n\n";

$sampleConsultants = Doctor::where('type', 'consultant')
    ->with('user')
    ->take(10)
    ->get();

foreach ($sampleConsultants as $doctor) {
    $days = $doctor->working_days;
    $daysCount = is_array($days) ? count($days) : 0;
    echo "  • {$doctor->user->name}\n";
    echo "    التخصص: {$doctor->specialization}\n";
    echo "    أيام العمل: {$daysCount} يوم - " . implode(', ', $days ?? []) . "\n";
    echo "    الأوقات: {$doctor->start_time} - {$doctor->end_time}\n\n";
}

// الأطباء الأكثر عملاً
echo "🏆 الأطباء الأكثر عملاً:\n";
$hardWorkers = Doctor::where('type', 'consultant')
    ->with('user')
    ->get()
    ->sortByDesc(function($doctor) {
        $days = $doctor->working_days;
        return is_array($days) ? count($days) : 0;
    })
    ->take(5);

foreach ($hardWorkers as $doctor) {
    $days = $doctor->working_days;
    $daysCount = is_array($days) ? count($days) : 0;
    echo "  🌟 {$doctor->user->name}: {$daysCount} أيام\n";
}
