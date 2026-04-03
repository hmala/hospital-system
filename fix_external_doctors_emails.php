<?php
/**
 * سكريبت لتصحيح عناوين البريد الإلكتروني للأطباء الخارجيين
 * يقوم بتحويل الأحرف العربية إلى صيغة ASCII صالحة
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "تصحيح عناوين البريد الإلكتروني للأطباء الخارجيين\n";
echo "========================================\n\n";

// البحث عن جميع المستخدمين الذين بريدهم يبدأ بـ external_ وينتهي بـ @external.local
$externalDoctors = User::where('email', 'like', 'external_%@external.local')->get();

echo "تم العثور على " . $externalDoctors->count() . " طبيب خارجي\n\n";

$fixed = 0;
$skipped = 0;

foreach ($externalDoctors as $doctor) {
    $oldEmail = $doctor->email;
    
    // التحقق من وجود أحرف غير ASCII في البريد
    if (preg_match('/[^\x00-\x7F]/', $oldEmail)) {
        // إنشاء بريد جديد صالح
        $slugName = strtolower(trim($doctor->name));
        $slugName = preg_replace('/[^a-z0-9]+/i', '_', $slugName);
        $slugName = trim($slugName, '_');
        
        if (empty($slugName)) {
            $slugName = 'doctor';
        }
        
        // إنشاء بريد جديد مع التأكد من عدم التكرار
        $newEmail = 'external_' . $slugName . '_' . $doctor->id . '@external.local';
        
        // التحقق من عدم وجود البريد الجديد
        $counter = 1;
        while (User::where('email', $newEmail)->where('id', '!=', $doctor->id)->exists()) {
            $newEmail = 'external_' . $slugName . '_' . $doctor->id . '_' . $counter . '@external.local';
            $counter++;
        }
        
        // تحديث البريد
        try {
            DB::table('users')
                ->where('id', $doctor->id)
                ->update(['email' => $newEmail]);
            
            echo "✓ تم تصحيح: {$doctor->name}\n";
            echo "  القديم: {$oldEmail}\n";
            echo "  الجديد: {$newEmail}\n\n";
            
            $fixed++;
        } catch (\Exception $e) {
            echo "✗ خطأ في تحديث: {$doctor->name}\n";
            echo "  الخطأ: {$e->getMessage()}\n\n";
        }
    } else {
        echo "○ تم تجاهل (البريد صحيح): {$doctor->name} - {$oldEmail}\n";
        $skipped++;
    }
}

echo "\n========================================\n";
echo "النتائج:\n";
echo "- تم التصحيح: {$fixed}\n";
echo "- تم التجاهل: {$skipped}\n";
echo "========================================\n";
