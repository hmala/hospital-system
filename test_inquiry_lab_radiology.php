<?php

/**
 * ملف اختبار لنظام اختيار التحاليل والأشعة في الاستعلامات
 * 
 * هذا الملف يختبر:
 * 1. عرض أنواع التحاليل المتاحة
 * 2. عرض أنواع الأشعة المتاحة
 * 3. التأكد من وجود بيانات في الجداول
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\LabTest;
use App\Models\RadiologyType;

echo "\n========================================\n";
echo "اختبار نظام اختيار التحاليل والأشعة\n";
echo "========================================\n\n";

// 1. اختبار التحاليل
echo "📊 التحاليل المتاحة:\n";
echo "-------------------\n";

$labTests = LabTest::where('is_active', true)
    ->orderBy('main_category')
    ->orderBy('name')
    ->get();

echo "إجمالي عدد التحاليل: " . $labTests->count() . "\n\n";

$labsByCategory = $labTests->groupBy('main_category');

foreach ($labsByCategory as $category => $tests) {
    echo "📁 {$category} ({$tests->count()} تحليل)\n";
    
    // عرض أول 3 تحاليل من كل فئة
    foreach ($tests->take(3) as $test) {
        echo "   ✓ {$test->name} ({$test->code})\n";
    }
    
    if ($tests->count() > 3) {
        echo "   ... و " . ($tests->count() - 3) . " أخرى\n";
    }
    
    echo "\n";
}

// 2. اختبار الأشعة
echo "\n📷 الأشعة المتاحة:\n";
echo "-------------------\n";

$radiologyTypes = RadiologyType::where('is_active', true)
    ->orderBy('main_category')
    ->orderBy('name')
    ->get();

echo "إجمالي عدد أنواع الأشعة: " . $radiologyTypes->count() . "\n\n";

$radiologyByCategory = $radiologyTypes->groupBy('main_category');

foreach ($radiologyByCategory as $category => $types) {
    echo "📁 {$category} ({$types->count()} نوع)\n";
    
    // عرض أول 3 أنواع من كل فئة
    foreach ($types->take(3) as $type) {
        echo "   ✓ {$type->name} ({$type->code})\n";
    }
    
    if ($types->count() > 3) {
        echo "   ... و " . ($types->count() - 3) . " أخرى\n";
    }
    
    echo "\n";
}

// 3. إحصائيات
echo "\n📈 الإحصائيات:\n";
echo "-------------------\n";
echo "✓ عدد فئات التحاليل: " . $labsByCategory->count() . "\n";
echo "✓ عدد فئات الأشعة: " . $radiologyByCategory->count() . "\n";
echo "✓ إجمالي التحاليل النشطة: " . $labTests->count() . "\n";
echo "✓ إجمالي الأشعة النشطة: " . $radiologyTypes->count() . "\n";

// 4. اختبار نموذج البيانات
echo "\n🔍 اختبار نموذج البيانات:\n";
echo "-------------------\n";

// اختيار تحليل عشوائي
$randomLab = $labTests->random();
echo "✓ تحليل عشوائي: {$randomLab->name}\n";
echo "  - الرمز: {$randomLab->code}\n";
echo "  - الفئة الرئيسية: {$randomLab->main_category}\n";
echo "  - الفئة الفرعية: " . ($randomLab->subcategory ?? 'غير محدد') . "\n";
echo "  - الوحدة: " . ($randomLab->unit ?? 'غير محدد') . "\n\n";

// اختيار إشعة عشوائية
$randomRadiology = $radiologyTypes->random();
echo "✓ إشعة عشوائية: {$randomRadiology->name}\n";
echo "  - الرمز: {$randomRadiology->code}\n";
echo "  - الفئة الرئيسية: {$randomRadiology->main_category}\n";
echo "  - الفئة الفرعية: " . ($randomRadiology->subcategory ?? 'غير محدد') . "\n";
echo "  - السعر الأساسي: " . ($randomRadiology->base_price ?? '0.00') . " ج.م\n";
echo "  - المدة المقدرة: " . ($randomRadiology->estimated_duration ?? 'غير محدد') . " دقيقة\n";

echo "\n========================================\n";
echo "✅ اختبار النظام مكتمل بنجاح!\n";
echo "========================================\n\n";

echo "💡 ملاحظات:\n";
echo "------------\n";
echo "1. تأكد من أن جميع التحاليل والأشعة لها أسعار محددة\n";
echo "2. تحقق من تفعيل (is_active = 1) للعناصر المطلوب ظهورها\n";
echo "3. يمكنك الآن اختبار النموذج من واجهة الاستعلامات\n\n";
