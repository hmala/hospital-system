<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "البحث عن أنواع الإيكو في جدول radiology_types:" . PHP_EOL . PHP_EOL;

$types = \App\Models\RadiologyType::where(function($q) {
    $q->where('name', 'like', '%ايكو%')
      ->orWhere('name', 'like', '%إيكو%')
      ->orWhere('name', 'like', '%echo%')
      ->orWhere('name', 'like', '%Echo%');
})->get(['id', 'name', 'subcategory']);

if ($types->count() > 0) {
    echo "تم العثور على " . $types->count() . " نوع:" . PHP_EOL;
    foreach ($types as $type) {
        echo "  ID: {$type->id} | الاسم: {$type->name} | التصنيف: {$type->subcategory}" . PHP_EOL;
    }
} else {
    echo "❌ لا يوجد أي أنواع إيكو في الجدول" . PHP_EOL;
}

echo PHP_EOL . "جميع التصنيفات الفرعية الموجودة:" . PHP_EOL;
$subcats = \App\Models\RadiologyType::distinct()->pluck('subcategory');
foreach ($subcats as $subcat) {
    echo "  - {$subcat}" . PHP_EOL;
}
