<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Request as MedicalRequest;
use Illuminate\Support\Facades\Auth;

// محاكاة تسجيل دخول مستخدم admin
$user = \App\Models\User::where('email', 'admin@admin.com')->first();
if (!$user) {
    $user = \App\Models\User::first(); // أي مستخدم
}
Auth::login($user);

echo "=== اختبار StaffRequestController ===\n\n";
echo "المستخدم: {$user->name} (ID: {$user->id})\n";
echo "الأدوار: " . implode(', ', $user->roles->pluck('name')->toArray()) . "\n\n";

// محاكاة استدعاء الـ controller
$controller = new \App\Http\Controllers\StaffRequestController();

// الحصول على الطلبات للمختبر
$query = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
    ->whereIn('type', ['lab']) // للمختبر فقط
    ->where('payment_status', 'paid');

$requests = $query->orderBy('created_at', 'desc')->get();

echo "عدد الطلبات الموجودة: {$requests->count()}\n\n";

foreach ($requests as $request) {
    echo "Request #{$request->id}:\n";
    echo "  Type: {$request->type} ({$request->type_text})\n";
    echo "  Status: {$request->status} ({$request->status_text})\n";
    echo "  Payment Status: {$request->payment_status}\n";
    echo "  Patient: " . ($request->visit && $request->visit->patient && $request->visit->patient->user ? $request->visit->patient->user->name : 'غير محدد') . "\n";
    echo "  ---\n";
}