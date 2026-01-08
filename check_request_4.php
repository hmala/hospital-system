<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Request as MedicalRequest;

echo "فحص الطلب #4 للمريضة نور\n";
echo "================================\n\n";

$request = MedicalRequest::find(4);

if ($request) {
    echo "✓ الطلب موجود\n";
    echo "  حالة الطلب: " . $request->status . "\n";
    echo "  النوع: " . $request->type . "\n";
    
    $visit = $request->visit;
    if ($visit) {
        echo "\n✓ الزيارة موجودة (ID: " . $visit->id . ")\n";
        echo "  حالة الزيارة: " . $visit->status . "\n";
        echo "  المريض: " . $visit->patient->user->name . "\n";
    } else {
        echo "\n✗ لا توجد زيارة مرتبطة بالطلب!\n";
    }
    
    // الآن نختبر الشروط في Controller
    echo "\n\nاختبار الشروط:\n";
    echo "================================\n";
    
    $passesConditions = true;
    
    // الشرط 1: status = pending
    if ($request->status === 'pending') {
        echo "✓ الشرط 1: حالة الطلب = pending\n";
    } else {
        echo "✗ الشرط 1 فشل: حالة الطلب = " . $request->status . "\n";
        $passesConditions = false;
    }
    
    // الشرط 2: الزيارة ليست ملغاة
    if ($visit && $visit->status !== 'cancelled') {
        echo "✓ الشرط 2: حالة الزيارة ليست cancelled (الحالة: " . $visit->status . ")\n";
    } else {
        echo "✗ الشرط 2 فشل: حالة الزيارة = " . ($visit ? $visit->status : 'لا توجد زيارة') . "\n";
        $passesConditions = false;
    }
    
    if ($passesConditions) {
        echo "\n✅ الطلب يجب أن يظهر في الكاشير!\n";
    } else {
        echo "\n❌ الطلب لن يظهر في الكاشير بسبب فشل الشروط\n";
    }
    
} else {
    echo "✗ الطلب #4 غير موجود في قاعدة البيانات\n";
}

echo "\n\nاختبار الاستعلام الفعلي:\n";
echo "================================\n";

$pendingRequests = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
    ->where('status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->orderBy('created_at', 'desc')
    ->get();

echo "عدد الطلبات المعلقة: " . $pendingRequests->count() . "\n\n";

foreach ($pendingRequests as $req) {
    echo "طلب #" . $req->id . " - " . $req->visit->patient->user->name . " - " . $req->type . "\n";
}
