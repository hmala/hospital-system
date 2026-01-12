<?php
// فحص ما يراه موظف الأشعة

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ما يراه موظف الأشعة في الواجهة ===\n\n";

$requests = \App\Models\RadiologyRequest::with(['patient.user', 'doctor.user', 'radiologyType'])
    ->whereIn('status', ['pending', 'scheduled', 'in_progress', 'completed'])
    ->orderBy('priority', 'desc')
    ->orderBy('requested_date', 'desc')
    ->get();

echo "عدد الطلبات: " . $requests->count() . "\n\n";

foreach ($requests as $request) {
    echo "======================\n";
    echo "طلب #{$request->id}\n";
    echo "  المريض: {$request->patient->user->name}\n";
    echo "  نوع الأشعة: {$request->radiologyType->name}\n";
    echo "  الكود: {$request->radiologyType->code}\n";
    echo "  الطبيب: " . ($request->doctor ? $request->doctor->user->name : 'غير محدد') . "\n";
    echo "  الأولوية: {$request->priority}\n";
    echo "  الحالة: {$request->status}\n";
    echo "  التكلفة: " . number_format($request->total_cost ?? 0, 2) . " د.ع\n";
    echo "  تاريخ الطلب: {$request->requested_date->format('Y-m-d H:i')}\n";
    echo "  المؤشرات السريرية: {$request->clinical_indication}\n";
    echo "\n";
}
