<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RadiologyType;
use App\Models\RadiologyRequest;

echo "=== اختبار نظام الإشعة ===\n\n";

// فحص أنواع الإشعة
echo "أنواع الإشعة المتاحة:\n";
$types = RadiologyType::all();
foreach ($types as $type) {
    echo "- {$type->name} ({$type->code}): " . number_format($type->base_price) . " د.ع\n";
}

echo "\nعدد أنواع الإشعة: " . $types->count() . "\n";

// فحص طلبات الإشعة
echo "\nطلبات الإشعة الحالية:\n";
$requests = RadiologyRequest::all();
echo "عدد الطلبات: " . $requests->count() . "\n";

if ($requests->count() > 0) {
    foreach ($requests as $request) {
        echo "- طلب ID {$request->id}: {$request->radiologyType->name} - حالة: {$request->status}\n";
    }
}

echo "\nتم الانتهاء من الاختبار!\n";