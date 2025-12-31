<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;
use App\Models\User;
use Carbon\Carbon;

echo "==========================================\n";
echo "  فحص جميع الزيارات في قاعدة البيانات  \n";
echo "==========================================\n\n";

// جميع الزيارات
$allVisits = Visit::with(['patient.user', 'doctor.user'])
    ->orderBy('visit_date', 'desc')
    ->get();

echo "إجمالي عدد الزيارات: " . $allVisits->count() . "\n\n";

if ($allVisits->isEmpty()) {
    echo "⚠️  لا توجد زيارات في قاعدة البيانات!\n\n";
} else {
    echo "تفصيل الزيارات:\n";
    echo "========================================\n\n";
    
    foreach ($allVisits as $index => $visit) {
        echo ($index + 1) . ". رقم الزيارة: #{$visit->id}\n";
        echo "   المريض: " . ($visit->patient && $visit->patient->user ? $visit->patient->user->name : 'غير معروف') . "\n";
        echo "   الطبيب: " . ($visit->doctor && $visit->doctor->user ? $visit->doctor->user->name : 'غير معروف') . " (ID: {$visit->doctor_id})\n";
        echo "   التاريخ: " . ($visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد') . "\n";
        echo "   الوقت: " . ($visit->visit_time ?: 'غير محدد') . "\n";
        echo "   الحالة: {$visit->status}\n";
        
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

// فحص الأطباء
echo "========================================\n";
echo "الأطباء في النظام:\n";
echo "========================================\n\n";

$doctors = User::role('doctor')->with('doctor')->get();

foreach ($doctors as $doctor) {
    echo "الطبيب: {$doctor->name} ({$doctor->email})\n";
    echo "  - لديه علاقة doctor: " . ($doctor->doctor ? 'نعم (ID: ' . $doctor->doctor->id . ')' : 'لا') . "\n";
    
    if ($doctor->doctor) {
        $visitsCount = Visit::where('doctor_id', $doctor->doctor->id)->count();
        echo "  - عدد الزيارات: {$visitsCount}\n";
        
        $todayCount = Visit::where('doctor_id', $doctor->doctor->id)
            ->whereDate('visit_date', today())
            ->count();
        echo "  - زيارات اليوم: {$todayCount}\n";
        
        $pastCount = Visit::where('doctor_id', $doctor->doctor->id)
            ->whereDate('visit_date', '<', today())
            ->count();
        echo "  - زيارات سابقة: {$pastCount}\n";
    }
    
    echo "\n";
}

echo "✓ التحقق اكتمل!\n";
