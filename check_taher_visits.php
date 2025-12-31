<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;
use App\Models\User;
use Carbon\Carbon;

echo "==========================================\n";
echo "   فحص زيارات د. ظاهر علي             \n";
echo "==========================================\n\n";

// البحث عن الطبيب
$doctor = User::where('name', 'LIKE', '%ظاهر%')->orWhere('name', 'LIKE', '%طاهر%')->with('doctor')->first();

if (!$doctor || !$doctor->doctor) {
    echo "⚠️  لم يتم العثور على الطبيب!\n";
    
    // البحث بطرق أخرى
    echo "\nالبحث بين جميع الأطباء:\n";
    $doctors = User::role('doctor')->get();
    foreach ($doctors as $doc) {
        if (stripos($doc->name, 'ظاهر') !== false || stripos($doc->name, 'طاهر') !== false) {
            echo "وجدت: {$doc->name} ({$doc->email})\n";
        }
    }
    exit;
}

echo "الطبيب: {$doctor->name} ({$doctor->email})\n";
echo "Doctor ID: {$doctor->doctor->id}\n";
echo "========================================\n\n";

// جميع الزيارات لهذا الطبيب
$allVisits = Visit::where('doctor_id', $doctor->doctor->id)
    ->with(['patient.user'])
    ->orderBy('visit_date', 'desc')
    ->get();

echo "إجمالي عدد الزيارات: " . $allVisits->count() . "\n\n";

if ($allVisits->isEmpty()) {
    echo "⚠️  لا توجد زيارات لهذا الطبيب!\n\n";
} else {
    echo "جميع الزيارات:\n";
    echo "========================================\n\n";
    
    foreach ($allVisits as $index => $visit) {
        echo ($index + 1) . ". الزيارة #{$visit->id}\n";
        echo "   المريض: " . ($visit->patient && $visit->patient->user ? $visit->patient->user->name : 'غير معروف') . "\n";
        echo "   التاريخ: " . ($visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد') . "\n";
        echo "   الوقت: " . ($visit->visit_time ?: 'غير محدد') . "\n";
        echo "   الحالة: {$visit->status}\n";
        echo "   الشكوى: " . ($visit->chief_complaint ?: 'غير محدد') . "\n";
        
        if ($visit->visit_date) {
            $daysAgo = $visit->visit_date->diffInDays(today(), false);
            if ($daysAgo > 0) {
                echo "   📅 منذ {$daysAgo} يوم\n";
            } elseif ($daysAgo == 0) {
                echo "   📅 اليوم\n";
            } else {
                echo "   📅 بعد " . abs($daysAgo) . " يوم\n";
            }
        }
        
        echo "\n";
    }
}

// التحقق من الكود الذي يجلب الزيارات
echo "========================================\n";
echo "اختبار الكود المستخدم في Controller:\n";
echo "========================================\n\n";

// زيارات اليوم
$todayVisits = Visit::where('doctor_id', $doctor->doctor->id)
    ->where('status', '!=', 'cancelled')
    ->whereDate('visit_date', today())
    ->get();
echo "زيارات اليوم: " . $todayVisits->count() . "\n";

// زيارات غير مكتملة من الأيام السابقة
$incompleteVisits = Visit::where('doctor_id', $doctor->doctor->id)
    ->where('status', '!=', 'completed')
    ->where('status', '!=', 'cancelled')
    ->whereDate('visit_date', '<', today())
    ->get();
echo "زيارات غير مكتملة من الأيام السابقة: " . $incompleteVisits->count() . "\n";

// زيارات مكتملة من الأيام السابقة
$completedVisits = Visit::where('doctor_id', $doctor->doctor->id)
    ->where('status', 'completed')
    ->whereDate('visit_date', '<', today())
    ->get();
echo "زيارات مكتملة من الأيام السابقة: " . $completedVisits->count() . "\n\n";

echo "✓ التحقق اكتمل!\n";
