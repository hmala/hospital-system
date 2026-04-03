<?php
/**
 * سكريبت ضبط جدولة أطباء الاستشاريين ليوم السبت
 * كل طبيب حسب الجدول الموجود في النص المرسل.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Doctor;

$doctors = [
    ['name' => 'د. عبد العزيز عبود', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. برير زهير', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. الاء عبد الكريم', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. حسن عبد الهادي', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. ايناس الخفاجي', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. سبا علي', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. نورا صباح', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. سرمد رحيم', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. احمد عجيل', 'start' => '10:00', 'end' => '17:00'],
    ['name' => 'د. احمد اسامة', 'start' => '20:00', 'end' => '22:00'],
    ['name' => 'د. زينب ثابت', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. فريال شاكر', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. معمر الاعرجي', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. نور فالح', 'start' => '17:00', 'end' => '21:00'],
    ['name' => 'د. قمر سعد', 'start' => '10:00', 'end' => '14:00'],
    ['name' => 'د. مريم محمد', 'start' => '10:00', 'end' => '14:00'],
];

echo "بدء تحديث أطباء الاستشاريين ليوم السبت...\n";

foreach ($doctors as $item) {
    $name = $item['name'];

    $doctor = Doctor::whereHas('user', function ($query) use ($name) {
        $query->where('name', 'like', "%{$name}%");
    })->first();

    if (!$doctor) {
        echo "⚠️ لم يُعثر على الطبيب: {$name}\n";
        continue;
    }

    $doctor->update([
        'type' => 'consultant',
        'working_days' => ['السبت'],
        'start_time' => $item['start'],
        'end_time' => $item['end'],
    ]);

    echo "✅ تم تحديث: {$doctor->user->name} (ID: {$doctor->id}) | {$item['start']} - {$item['end']}\n";
}

echo "تم الانتهاء من التحديث.\n";
