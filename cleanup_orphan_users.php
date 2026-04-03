<?php
/**
 * سكريبت تنظيف المستخدمين الذين ليس لديهم سجل في جدول doctors
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Doctor;

echo "========================================\n";
echo "تنظيف جدول المستخدمين من الأطباء المحذوفين\n";
echo "========================================\n\n";

// البحث عن جميع المستخدمين الأطباء
$doctorUsers = User::where('role', 'doctor')->get();

echo "تم العثور على " . $doctorUsers->count() . " مستخدم بدور طبيب\n\n";

$deleted = 0;
$kept = 0;

foreach ($doctorUsers as $user) {
    // التحقق من وجود سجل في جدول doctors
    $hasDoctor = Doctor::where('user_id', $user->id)->exists();
    
    if (!$hasDoctor) {
        echo "🗑️ حذف: {$user->name} (ID: {$user->id}) - لا يوجد له سجل في جدول doctors\n";
        $user->delete();
        $deleted++;
    } else {
        $kept++;
    }
}

echo "\n========================================\n";
echo "النتائج:\n";
echo "- تم الحذف: {$deleted}\n";
echo "- تم الاحتفاظ: {$kept}\n";
echo "========================================\n";

echo "\nعدد المستخدمين الأطباء المتبقي: " . User::where('role', 'doctor')->count() . "\n";
echo "عدد الأطباء في جدول doctors: " . Doctor::count() . "\n";
