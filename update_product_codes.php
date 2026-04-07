<?php

/**
 * Script لتحديث أكواد المنتجات القديمة
 * 
 * يقوم هذا السكريبت بتوليد أكواد باركود تلقائية لجميع المنتجات التي لا تحتوي على كود
 * 
 * الاستخدام: php update_product_codes.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

echo "===================================\n";
echo "تحديث أكواد المنتجات\n";
echo "===================================\n\n";

// البحث عن المنتجات بدون كود
$productsWithoutCode = Product::whereNull('code')
    ->orWhere('code', '')
    ->get();

if ($productsWithoutCode->isEmpty()) {
    echo "✓ جميع المنتجات لديها أكواد بالفعل!\n";
    exit(0);
}

echo "عدد المنتجات التي تحتاج تحديث: {$productsWithoutCode->count()}\n\n";

$updated = 0;
$errors = 0;

foreach ($productsWithoutCode as $product) {
    try {
        // توليد كود فريد
        $code = 'PRD-' . str_pad($product->id, 4, '0', STR_PAD_LEFT);
        
        // التأكد من عدم تكرار الكود
        $suffix = 1;
        $originalCode = $code;
        while (Product::where('code', $code)->where('id', '!=', $product->id)->exists()) {
            $code = $originalCode . '-' . $suffix;
            $suffix++;
        }
        
        $product->code = $code;
        $product->save();
        
        echo "✓ تم تحديث: {$product->name} -> {$code}\n";
        $updated++;
        
    } catch (Exception $e) {
        echo "✗ خطأ في تحديث {$product->name}: {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\n===================================\n";
echo "النتائج:\n";
echo "===================================\n";
echo "تم التحديث بنجاح: $updated\n";
echo "أخطاء: $errors\n";
echo "===================================\n";
