<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SurgeryRadiologyTest;
use App\Models\SurgeryLabTest;
use Illuminate\Support\Facades\DB;

echo "=== تنظيف السجلات العامة لتحاليل وأشعة العمليات ===\n\n";

// حذف سجلات التحاليل العامة (بدون lab_test_id محدد)
$genericLabTests = SurgeryLabTest::whereNull('lab_test_id')->get();
echo "عدد سجلات التحاليل العامة: " . $genericLabTests->count() . "\n";

if ($genericLabTests->count() > 0) {
    echo "حذف سجلات التحاليل العامة...\n";
    $deletedLab = SurgeryLabTest::whereNull('lab_test_id')->delete();
    echo "تم حذف {$deletedLab} سجل تحليل عام\n";
}
echo "\n";

// حذف سجلات الأشعة العامة (بدون radiology_type_id محدد)
$genericRadiologyTests = SurgeryRadiologyTest::whereNull('radiology_type_id')->get();
echo "عدد سجلات الأشعة العامة: " . $genericRadiologyTests->count() . "\n";

if ($genericRadiologyTests->count() > 0) {
    echo "حذف سجلات الأشعة العامة...\n";
    $deletedRadiology = SurgeryRadiologyTest::whereNull('radiology_type_id')->delete();
    echo "تم حذف {$deletedRadiology} سجل أشعة عام\n";
}
echo "\n";

// حذف السجلات التي تشير إلى عمليات محذوفة
echo "البحث عن سجلات تشير إلى عمليات محذوفة...\n";

$orphanedLabTests = SurgeryLabTest::whereNotNull('surgery_id')
    ->whereDoesntHave('surgery')
    ->get();
echo "سجلات تحاليل يتيمة: " . $orphanedLabTests->count() . "\n";

if ($orphanedLabTests->count() > 0) {
    $deletedOrphanLab = SurgeryLabTest::whereNotNull('surgery_id')
        ->whereDoesntHave('surgery')
        ->delete();
    echo "تم حذف {$deletedOrphanLab} سجل تحليل يتيم\n";
}
echo "\n";

$orphanedRadiologyTests = SurgeryRadiologyTest::whereNotNull('surgery_id')
    ->whereDoesntHave('surgery')
    ->get();
echo "سجلات أشعة يتيمة: " . $orphanedRadiologyTests->count() . "\n";

if ($orphanedRadiologyTests->count() > 0) {
    $deletedOrphanRadiology = SurgeryRadiologyTest::whereNotNull('surgery_id')
        ->whereDoesntHave('surgery')
        ->delete();
    echo "تم حذف {$deletedOrphanRadiology} سجل أشعة يتيم\n";
}
echo "\n";

echo "=== اكتملت عملية التنظيف ===\n";
echo "\nملاحظة: من الآن فصاعداً، عند حجز عملية جراحية:\n";
echo "- لن يتم إنشاء سجلات عامة (بدون تحديد نوع التحليل/الأشعة)\n";
echo "- سيقوم موظف المختبر/الأشعة بإضافة الطلبات المطلوبة يدوياً\n";
