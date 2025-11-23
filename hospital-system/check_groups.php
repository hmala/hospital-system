<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tests = \App\Models\LabTest::where('is_active', true)->get();
$grouped = $tests->groupBy('category');

echo "الفئات الحالية:\n";
foreach($grouped as $cat => $group) {
    echo $cat . ': ' . $group->count() . "\n";
}

// اقتراح تجميع في مجموعات أكبر
$groupMappings = [
    'كيمياء سريرية' => [
        'كيمياء سريرية'
    ],
    'أمراض الدم' => [
        'أمراض الدم',
        'مصرف الدم'
    ],
    'الميكروبيولوجيا' => [
        'الأحياء المجهرية',
        'الطفيليات'
    ],
    'المناعة والهرمونات' => [
        'المناعة السريرية',
        'فيروسات',
        'هرمونات'
    ],
    'الخلايا والأنسجة' => [
        'الخلايا'
    ],
    'متفرقة' => [
        'متفرقة',
        'أخرى'
    ]
];

echo "\nاقتراح التجميع في مجموعات:\n";
foreach($groupMappings as $mainGroup => $subGroups) {
    $total = 0;
    foreach($subGroups as $subGroup) {
        if(isset($grouped[$subGroup])) {
            $total += $grouped[$subGroup]->count();
        }
    }
    echo $mainGroup . ': ' . $total . " (" . implode(', ', $subGroups) . ")\n";
}
?>