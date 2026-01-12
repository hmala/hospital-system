<?php
// فحص مزامنة طلبات الأشعة بين جدولي requests و radiology_requests

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص طلبات الأشعة ===\n\n";

// 1. جلب طلبات الأشعة من جدول requests
$medicalRequests = \App\Models\Request::where('type', 'radiology')->get();

echo "عدد طلبات الأشعة في جدول requests: " . $medicalRequests->count() . "\n\n";

foreach ($medicalRequests as $request) {
    echo "طلب #{$request->id}\n";
    echo "  - الزيارة: #{$request->visit_id}\n";
    echo "  - الحالة: {$request->status}\n";
    echo "  - حالة الدفع: " . ($request->payment_status ?? 'غير محدد') . "\n";
    
    $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
    
    if (isset($details['radiology_type_ids'])) {
        echo "  - أنواع الأشعة: " . implode(', ', $details['radiology_type_ids']) . "\n";
        
        // البحث عن سجلات مطابقة في radiology_requests
        $radiologyRequests = \App\Models\RadiologyRequest::where('visit_id', $request->visit_id)->get();
        
        if ($radiologyRequests->count() > 0) {
            echo "  - ✓ موجود في radiology_requests: " . $radiologyRequests->count() . " سجل\n";
            foreach ($radiologyRequests as $rr) {
                echo "      - RadiologyRequest #{$rr->id} - نوع: {$rr->radiology_type_id} - حالة: {$rr->status}\n";
            }
        } else {
            echo "  - ✗ غير موجود في radiology_requests\n";
            
            // محاولة إنشاء السجلات
            echo "  - محاولة الإنشاء...\n";
            foreach ($details['radiology_type_ids'] as $typeId) {
                try {
                    $radiologyType = \App\Models\RadiologyType::find($typeId);
                    $rr = \App\Models\RadiologyRequest::create([
                        'visit_id' => $request->visit_id,
                        'patient_id' => $request->visit->patient_id,
                        'doctor_id' => $request->visit->doctor_id,
                        'radiology_type_id' => $typeId,
                        'requested_date' => $request->created_at ?? now(),
                        'status' => 'pending',
                        'priority' => $details['priority'] ?? 'normal',
                        'clinical_indication' => $request->description ?? 'طلب من الاستعلامات',
                        'total_cost' => $radiologyType ? $radiologyType->base_price : null,
                    ]);
                    echo "      ✓ تم الإنشاء: RadiologyRequest #{$rr->id}\n";
                } catch (\Exception $e) {
                    echo "      ✗ فشل الإنشاء: " . $e->getMessage() . "\n";
                }
            }
        }
    } else {
        echo "  - لا توجد radiology_type_ids في التفاصيل\n";
        echo "  - التفاصيل: " . json_encode($details) . "\n";
    }
    
    echo "\n";
}

echo "\n=== إجمالي سجلات radiology_requests ===\n";
$totalRadiologyRequests = \App\Models\RadiologyRequest::count();
echo "الإجمالي: {$totalRadiologyRequests}\n\n";

$pendingRadiology = \App\Models\RadiologyRequest::where('status', 'pending')->count();
echo "المعلقة: {$pendingRadiology}\n";

$scheduledRadiology = \App\Models\RadiologyRequest::where('status', 'scheduled')->count();
echo "المجدولة: {$scheduledRadiology}\n";

$inProgressRadiology = \App\Models\RadiologyRequest::where('status', 'in_progress')->count();
echo "قيد التنفيذ: {$inProgressRadiology}\n";

$completedRadiology = \App\Models\RadiologyRequest::where('status', 'completed')->count();
echo "المكتملة: {$completedRadiology}\n";
