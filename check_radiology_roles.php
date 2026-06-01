<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "الأدوار المتعلقة بالأشعة في قاعدة البيانات:" . PHP_EOL . PHP_EOL;

$radiologyRoles = \Spatie\Permission\Models\Role::where('name', 'like', '%radiology%')->get();

if ($radiologyRoles->count() > 0) {
    echo "عدد الأدوار: " . $radiologyRoles->count() . PHP_EOL . PHP_EOL;
    foreach ($radiologyRoles as $role) {
        $userCount = \App\Models\User::role($role->name)->count();
        echo "✓ {$role->name}";
        echo " (عدد المستخدمين: {$userCount})";
        echo PHP_EOL;
    }
} else {
    echo "❌ لا توجد أدوار للأشعة في قاعدة البيانات" . PHP_EOL;
}

echo PHP_EOL . "التصنيفات الفرعية الموجودة في radiology_types:" . PHP_EOL;
$subcats = \App\Models\RadiologyType::distinct()->pluck('subcategory');
foreach ($subcats as $subcat) {
    $count = \App\Models\RadiologyType::where('subcategory', $subcat)->count();
    echo "  - {$subcat} ({$count} نوع)" . PHP_EOL;
}
