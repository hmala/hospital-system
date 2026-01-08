<?php

/**
 * اختبار عرض الطلبات في الكاشير
 * يتحقق من أن التحاليل والأشعة المطلوبة تظهر عند الكاشير
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Request as MedicalRequest;
use App\Models\LabTest;
use App\Models\RadiologyType;

echo "\n========================================\n";
echo "اختبار عرض الطلبات في الكاشير\n";
echo "========================================\n\n";

// جلب الطلبات المعلقة
$pendingRequests = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
    ->where('status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->get();

echo "📋 إجمالي الطلبات المعلقة: " . $pendingRequests->count() . "\n\n";

if ($pendingRequests->count() === 0) {
    echo "⚠️  لا توجد طلبات معلقة حالياً.\n";
    echo "💡 قم بإنشاء طلب من الاستعلامات أولاً.\n\n";
} else {
    foreach ($pendingRequests as $request) {
        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
        
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "رقم الطلب: #" . $request->id . "\n";
        echo "النوع: ";
        
        if ($request->type === 'lab') {
            echo "🧪 تحاليل\n";
        } elseif ($request->type === 'radiology') {
            echo "📷 أشعة\n";
        } elseif ($request->type === 'pharmacy') {
            echo "💊 صيدلية\n";
        } else {
            echo $request->type . "\n";
        }
        
        echo "المريض: " . $request->visit->patient->user->name . "\n";
        echo "التاريخ: " . $request->created_at->format('Y-m-d H:i') . "\n";
        echo "الحالة: " . $request->status . "\n";
        
        // عرض التفاصيل
        if ($request->type === 'lab' && isset($details['lab_test_ids'])) {
            echo "\nالتحاليل المطلوبة (" . count($details['lab_test_ids']) . "):\n";
            foreach ($details['lab_test_ids'] as $index => $testId) {
                $test = LabTest::find($testId);
                if ($test) {
                    echo "  " . ($index + 1) . ". " . $test->name . " (" . $test->code . ")\n";
                }
            }
        } elseif ($request->type === 'radiology' && isset($details['radiology_type_ids'])) {
            echo "\nالأشعة المطلوبة (" . count($details['radiology_type_ids']) . "):\n";
            foreach ($details['radiology_type_ids'] as $index => $typeId) {
                $type = RadiologyType::find($typeId);
                if ($type) {
                    echo "  " . ($index + 1) . ". " . $type->name . " (" . $type->code . ")\n";
                }
            }
        } else {
            echo "\nالوصف: " . ($request->description ?? 'غير محدد') . "\n";
        }
        
        echo "\n";
    }
}

// تفاصيل إحصائية
$labRequests = $pendingRequests->where('type', 'lab')->count();
$radiologyRequests = $pendingRequests->where('type', 'radiology')->count();
$pharmacyRequests = $pendingRequests->where('type', 'pharmacy')->count();

echo "========================================\n";
echo "📊 الإحصائيات:\n";
echo "========================================\n";
echo "🧪 طلبات تحاليل: $labRequests\n";
echo "📷 طلبات أشعة: $radiologyRequests\n";
echo "💊 طلبات صيدلية: $pharmacyRequests\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📦 الإجمالي: " . $pendingRequests->count() . "\n\n";

echo "✅ جميع هذه الطلبات يجب أن تظهر في صفحة الكاشير!\n";
echo "🌐 افتح: http://localhost/hospital-system/public/cashier\n\n";
