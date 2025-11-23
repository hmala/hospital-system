<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== تحديث حالة طلب الأشعة يدوياً ===\n\n";

// الطلب رقم 5 في جدول requests
$request = App\Models\Request::find(5);

if ($request) {
    echo "الطلب موجود - ID: {$request->id}\n";
    echo "Visit ID: {$request->visit_id}\n";
    echo "النوع: {$request->type}\n";
    echo "الحالة الحالية: {$request->status}\n\n";
    
    // جلب نتائج الأشعة
    $radiologyRequest = App\Models\RadiologyRequest::where('visit_id', $request->visit_id)->first();
    
    if ($radiologyRequest && $radiologyRequest->result) {
        echo "تم العثور على نتائج في radiology_results\n";
        
        $result = $radiologyRequest->result;
        
        $request->status = 'completed';
        $request->result = [
            'findings' => $result->findings,
            'impression' => $result->impression,
            'recommendations' => $result->recommendations,
            'images' => $result->images ?? [],
            'radiologist' => $result->radiologist ? $result->radiologist->name : 'غير محدد',
            'reported_at' => $result->reported_at ? $result->reported_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s')
        ];
        
        $request->save();
        
        echo "✓ تم تحديث الطلب بنجاح!\n";
        echo "الحالة الجديدة: {$request->status}\n";
        echo "النتائج: موجودة\n";
    } else {
        echo "✗ لا توجد نتائج في radiology_results\n";
    }
} else {
    echo "الطلب غير موجود\n";
}
