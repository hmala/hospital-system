<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== التحقق من أنواع الأشعة في قاعدة البيانات ===\n\n";

$radiologyTypes = \App\Models\RadiologyType::all();

echo "عدد أنواع الأشعة: " . $radiologyTypes->count() . "\n\n";

if ($radiologyTypes->count() > 0) {
    echo "أنواع الأشعة المتاحة:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-15s %-40s %-10s\n", "ID", "الكود", "الاسم", "الحالة");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($radiologyTypes as $type) {
        printf("%-5d %-15s %-40s %-10s\n", 
            $type->id,
            $type->code,
            $type->name,
            $type->is_active ? 'مفعل' : 'معطل'
        );
    }
    
    echo "\n✓ تم العثور على أنواع الأشعة بنجاح!\n";
} else {
    echo "❌ لا توجد أنواع أشعة في قاعدة البيانات!\n";
    echo "يرجى تشغيل: php artisan db:seed --class=RadiologyTypeSeeder\n";
}
