<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;
use App\Models\User;
use Carbon\Carbon;

echo "==========================================\n";
echo "  فحص الزيارات غير المكتملة للطبيب     \n";
echo "==========================================\n\n";

// البحث عن طبيب
$doctor = User::role('doctor')->with('doctor')->first();

if (!$doctor || !$doctor->doctor) {
    echo "⚠️  لم يتم العثور على طبيب في النظام!\n";
    exit;
}

echo "الطبيب: {$doctor->name}\n";
echo "----------------------------------------\n\n";

// الزيارات غير المكتملة من الأيام السابقة (ليس اليوم)
$incompleteVisits = Visit::where('doctor_id', $doctor->doctor->id)
    ->where('status', '!=', 'completed')
    ->where('status', '!=', 'cancelled')
    ->whereDate('visit_date', '<', today())  // فقط من الأيام السابقة
    ->with(['patient.user'])
    ->orderBy('visit_date', 'asc')
    ->get();

echo "عدد الزيارات غير المكتملة: " . $incompleteVisits->count() . "\n\n";

if ($incompleteVisits->isEmpty()) {
    echo "✓ لا توجد زيارات غير مكتملة\n\n";
} else {
    echo "قائمة الزيارات غير المكتملة:\n";
    echo "========================================\n\n";
    
    foreach ($incompleteVisits as $index => $visit) {
        $daysAgo = $visit->visit_date ? $visit->visit_date->diffInDays(today()) : 0;
        
        echo ($index + 1) . ". المريض: {$visit->patient->user->name}\n";
        echo "   تاريخ الزيارة: {$visit->visit_date->format('Y-m-d')}\n";
        echo "   الوقت: " . ($visit->visit_time ?: 'غير محدد') . "\n";
        echo "   الحالة: {$visit->status}\n";
        echo "   الشكوى: " . ($visit->chief_complaint ?: 'غير محدد') . "\n";
        
        if ($daysAgo > 0) {
            echo "   ⚠️  متأخرة منذ {$daysAgo} يوم\n";
        } elseif ($daysAgo == 0) {
            echo "   📅 من اليوم\n";
        }
        
        echo "\n";
    }
}

// زيارات اليوم
echo "----------------------------------------\n";
echo "زيارات اليوم:\n";
echo "========================================\n\n";

$todayVisits = Visit::where('doctor_id', $doctor->doctor->id)
    ->where('status', '!=', 'cancelled')
    ->whereDate('visit_date', today())
    ->with(['patient.user'])
    ->get();

echo "عدد زيارات اليوم: " . $todayVisits->count() . "\n\n";

foreach ($todayVisits as $index => $visit) {
    echo ($index + 1) . ". المريض: {$visit->patient->user->name}\n";
    echo "   الحالة: {$visit->status}\n";
    echo "   الوقت: " . ($visit->visit_time ?: 'غير محدد') . "\n\n";
}

echo "✓ التحقق اكتمل!\n";
