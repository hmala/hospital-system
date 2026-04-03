<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Doctor;
use App\Models\User;

echo "🗑️  جاري حذف جميع الأطباء...\n\n";

$doctors = Doctor::all();
$deletedDoctors = 0;
$deletedUsers = 0;

foreach ($doctors as $doctor) {
    $user = User::find($doctor->user_id);
    if ($user) {
        $user->delete();
        $deletedUsers++;
    }
    $doctor->delete();
    $deletedDoctors++;
}

echo "✅ تم حذف {$deletedDoctors} طبيب\n";
echo "✅ تم حذف {$deletedUsers} مستخدم\n\n";

echo "📊 التحقق النهائي:\n";
echo "  - الأطباء المتبقين: " . Doctor::count() . "\n";
echo "  - المستخدمين بدور طبيب: " . User::where('role', 'doctor')->count() . "\n\n";

echo "✅ قاعدة البيانات جاهزة!\n";
echo "🚀 قم بتشغيل: php artisan db:seed --class=DoctorSeeder\n";
