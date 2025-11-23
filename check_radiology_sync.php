<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص مزامنة طلبات الأشعة ===\n\n";

// جلب جميع طلبات الأشعة
$radiologyRequests = App\Models\RadiologyRequest::with('radiologyType')->get();

echo "عدد طلبات الأشعة في radiology_requests: " . $radiologyRequests->count() . "\n\n";

foreach ($radiologyRequests as $rr) {
    echo "طلب أشعة #{$rr->id}\n";
    echo "  نوع الأشعة: " . ($rr->radiologyType ? $rr->radiologyType->name : 'غير محدد') . "\n";
    echo "  Visit ID: {$rr->visit_id}\n";
    echo "  الحالة: {$rr->status}\n";
    
    // البحث في جدول requests
    $request = App\Models\Request::where('visit_id', $rr->visit_id)
        ->where('type', 'radiology')
        ->first();
    
    if ($request) {
        echo "  ✓ موجود في جدول requests (ID: {$request->id})\n";
        echo "    الحالة في requests: {$request->status}\n";
        echo "    النتائج: " . ($request->result ? 'موجودة' : 'غير موجودة') . "\n";
    } else {
        echo "  ✗ غير موجود في جدول requests\n";
    }
    echo "---\n";
}
