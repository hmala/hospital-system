<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Doctor;
use App\Models\User;

echo "🔍 جاري البحث عن الأطباء الاستشاريين القدامى...\n\n";

// العثور على جميع الأطباء الاستشاريين
$consultantDoctors = Doctor::where('type', 'consultant')->get();

echo "📊 عدد الأطباء الاستشاريين الحاليين: " . $consultantDoctors->count() . "\n";

$deletedCount = 0;

foreach ($consultantDoctors as $doctor) {
    $user = User::find($doctor->user_id);
    if ($user) {
        echo "🗑️  حذف: {$user->name} (ID: {$doctor->id})\n";
        $doctor->delete();
        $user->delete();
        $deletedCount++;
    }
}

echo "\n✅ تم حذف {$deletedCount} طبيب استشاري\n";

// عرض الأطباء المتبقين
$remainingDoctors = Doctor::with('user')->get();
echo "\n📋 الأطباء المتبقين ({$remainingDoctors->count()}):\n";

$anesthesiologists = 0;
$surgeons = 0;

foreach ($remainingDoctors as $doctor) {
    if ($doctor->type === 'anesthesiologist') {
        $anesthesiologists++;
    } elseif ($doctor->type === 'surgeon') {
        $surgeons++;
    }
    echo "  - {$doctor->user->name} ({$doctor->type})\n";
}

echo "\n📊 الملخص:\n";
echo "  👨‍⚕️ أطباء التخدير: {$anesthesiologists}\n";
echo "  🔪 الجراحين: {$surgeons}\n";
echo "  📝 المجموع: {$remainingDoctors->count()}\n";

echo "\n✅ يمكنك الآن تشغيل السيدر لإضافة الأطباء الاستشاريين الجدد!\n";
echo "   php artisan db:seed --class=DoctorSeeder\n";
