<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== محاكاة دخول موظف الأشعة ===\n\n";

// تسجيل دخول موظف الأشعة
$radiologyStaff = \App\Models\User::where('email', 'radiology@hospital.com')->first();

if (!$radiologyStaff) {
    echo "❌ لم يتم العثور على موظف الأشعة\n";
    exit;
}

echo "✓ موظف الأشعة: {$radiologyStaff->name}\n";

// التحقق من الأدوار
$roles = $radiologyStaff->roles->pluck('name')->toArray();
echo "الأدوار: " . implode(', ', $roles) . "\n\n";

if (!in_array('radiology_staff', $roles)) {
    echo "❌ المستخدم ليس لديه دور radiology_staff\n";
    exit;
}

echo "✓ المستخدم لديه دور radiology_staff\n\n";

// محاكاة تسجيل الدخول
\Illuminate\Support\Facades\Auth::login($radiologyStaff);

// تشغيل كود StaffRequestController->index
$user = \Illuminate\Support\Facades\Auth::user();

// تحديد نوع الطلبات حسب دور المستخدم
$allowedTypes = [];
if ($user->hasRole('lab_staff')) {
    $allowedTypes[] = 'lab';
}
if ($user->hasRole('radiology_staff')) {
    $allowedTypes[] = 'radiology';
}
if ($user->hasRole('pharmacy_staff')) {
    $allowedTypes[] = 'pharmacy';
}

echo "الأنواع المسموحة: " . implode(', ', $allowedTypes) . "\n\n";

if (empty($allowedTypes)) {
    echo "❌ لا توجد أنواع مسموحة\n";
    exit;
}

// فلترة الطلبات حسب النوع المسموح
$query = \App\Models\Request::with(['visit.patient.user', 'visit.doctor.user'])
    ->whereIn('type', $allowedTypes)
    ->where(function($q) {
        // عرض الطلبات المدفوعة أو التي بانتظار تحديد الخدمات
        $q->where('payment_status', 'paid')
          ->orWhere('status', 'pending_service_selection');
    });

$requests = $query->orderBy('created_at', 'desc')->get();

echo "=== النتيجة ===\n";
echo "عدد الطلبات التي سيراها موظف الأشعة: {$requests->count()}\n\n";

foreach ($requests as $request) {
    echo "طلب #{$request->id}\n";
    echo "  - النوع: {$request->type}\n";
    echo "  - الحالة: {$request->status}\n";
    echo "  - حالة الدفع: {$request->payment_status}\n";
    if ($request->visit && $request->visit->patient) {
        echo "  - المريض: {$request->visit->patient->user->name}\n";
    }
    echo "\n";
}

if ($requests->count() === 0) {
    echo "❌ لا توجد طلبات تظهر لموظف الأشعة!\n";
    echo "\nالتحقق من السبب:\n";
    
    // التحقق من وجود طلبات أشعة
    $allRadiologyRequests = \App\Models\Request::where('type', 'radiology')->get();
    echo "- إجمالي طلبات الأشعة في النظام: {$allRadiologyRequests->count()}\n";
    
    foreach ($allRadiologyRequests as $r) {
        echo "  طلب #{$r->id}: status={$r->status}, payment_status={$r->payment_status}\n";
    }
}
