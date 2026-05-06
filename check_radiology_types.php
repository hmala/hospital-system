<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== إحصائيات جدول radiology_types ===\n\n";

// إجمالي السجلات
$total = DB::table('radiology_types')->count();
echo "إجمالي السجلات: {$total}\n\n";

// حسب التصنيف الفرعي
echo "=== التصنيفات الفرعية ===\n";
$categories = DB::table('radiology_types')
    ->select('subcategory', DB::raw('count(*) as count'))
    ->groupBy('subcategory')
    ->get();

foreach ($categories as $cat) {
    echo "{$cat->subcategory}: {$cat->count} نوع\n";
}

echo "\n=== أمثلة من البيانات (أول 10 سجلات) ===\n";
$samples = DB::table('radiology_types')->take(10)->get();

foreach ($samples as $sample) {
    echo "\n";
    echo "النوع: {$sample->name}\n";
    echo "الرمز: {$sample->code}\n";
    echo "التصنيف: {$sample->subcategory}\n";
    echo "السعر: " . number_format($sample->base_price) . " IQD\n";
    echo "المدة: {$sample->estimated_duration} دقيقة\n";
    echo str_repeat('-', 50) . "\n";
}
