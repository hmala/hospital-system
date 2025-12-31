<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Request as MedicalRequest;
use Illuminate\Support\Facades\Auth;

echo "==========================================\n";
echo " محاكاة ما يراه د. ظاهر علي في الصفحة \n";
echo "==========================================\n\n";

// تسجيل دخول الطبيب
$doctor = User::where('name', 'LIKE', '%ظاهر%')->first();
if (!$doctor) {
    echo "⚠️  لم يتم العثور على الطبيب!\n";
    exit;
}

Auth::login($doctor);
$user = Auth::user();

echo "تم تسجيل الدخول كـ: {$user->name}\n";
echo "========================================\n\n";

// تنفيذ نفس الكود الموجود في Controller
if (!$user->hasRole(['admin', 'doctor'])) {
    echo "❌ المستخدم ليس طبيباً!\n";
    exit;
}

$doctorRecord = $user->doctor;
if (!$doctorRecord) {
    echo "❌ بيانات الطبيب غير مكتملة!\n";
    exit;
}

echo "✓ بيانات الطبيب موجودة (ID: {$doctorRecord->id})\n\n";

// زيارات اليوم
$todayVisits = Visit::where('doctor_id', $doctorRecord->id)
    ->where('status', '!=', 'cancelled')
    ->whereDate('visit_date', today())
    ->with(['patient.user', 'appointment'])
    ->orderBy('visit_time', 'asc')
    ->get();

echo "زيارات اليوم: " . $todayVisits->count() . "\n";
foreach ($todayVisits as $visit) {
    echo "  - {$visit->patient->user->name} - {$visit->status}\n";
}
echo "\n";

// زيارات غير مكتملة من الأيام السابقة
$incompleteVisits = Visit::where('doctor_id', $doctorRecord->id)
    ->where('status', '!=', 'completed')
    ->where('status', '!=', 'cancelled')
    ->whereDate('visit_date', '<', today())
    ->with(['patient.user', 'appointment'])
    ->orderBy('visit_date', 'asc')
    ->orderBy('visit_time', 'asc')
    ->limit(50)
    ->get();

echo "الزيارات غير المكتملة من الأيام السابقة: " . $incompleteVisits->count() . "\n";
foreach ($incompleteVisits as $visit) {
    $daysAgo = $visit->visit_date->diffInDays(today());
    echo "  - {$visit->patient->user->name} - {$visit->visit_date->format('Y-m-d')} (منذ {$daysAgo} يوم) - {$visit->status}\n";
}
echo "\n";

// زيارات مكتملة من الأيام السابقة
$completedVisits = Visit::where('doctor_id', $doctorRecord->id)
    ->where('status', 'completed')
    ->whereDate('visit_date', '<', today())
    ->with(['patient.user', 'appointment'])
    ->latest('visit_date')
    ->latest('visit_time')
    ->limit(100)
    ->get();

echo "الزيارات المكتملة من الأيام السابقة: " . $completedVisits->count() . "\n";
foreach ($completedVisits as $visit) {
    echo "  - {$visit->patient->user->name} - {$visit->visit_date->format('Y-m-d')}\n";
}
echo "\n";

// المواعيد المجدولة
$appointments = Appointment::where('doctor_id', $doctorRecord->id)
    ->whereIn('status', ['scheduled', 'confirmed'])
    ->whereDoesntHave('visit')
    ->whereDate('appointment_date', '>=', today())
    ->with(['patient.user', 'department'])
    ->orderBy('appointment_date', 'asc')
    ->limit(50)
    ->get();

echo "المواعيد المجدولة: " . $appointments->count() . "\n";
foreach ($appointments as $appointment) {
    echo "  - {$appointment->patient->user->name} - {$appointment->appointment_date->format('Y-m-d')}\n";
}
echo "\n";

// الطلبات الطبية
$doctorRequests = MedicalRequest::whereHas('visit', function($query) use ($doctorRecord) {
    $query->where('doctor_id', $doctorRecord->id);
})
->with(['visit.patient.user'])
->latest()
->limit(50)
->get();

echo "الطلبات الطبية: " . $doctorRequests->count() . "\n";
foreach ($doctorRequests as $request) {
    echo "  - {$request->visit->patient->user->name} - {$request->type} - {$request->status}\n";
}
echo "\n";

echo "========================================\n";
echo "الملخص:\n";
echo "========================================\n";
echo "إجمالي العناصر التي سيراها الطبيب في الصفحة:\n";
echo "  - زيارات اليوم: " . $todayVisits->count() . "\n";
echo "  - زيارات غير مكتملة (قديمة): " . $incompleteVisits->count() . "\n";
echo "  - زيارات مكتملة (قديمة): " . $completedVisits->count() . "\n";
echo "  - مواعيد مجدولة: " . $appointments->count() . "\n";
echo "  - طلبات طبية: " . $doctorRequests->count() . "\n";
echo "\n";

$total = $todayVisits->count() + $incompleteVisits->count() + $completedVisits->count() + $appointments->count() + $doctorRequests->count();
echo "المجموع الكلي: {$total}\n\n";

if ($incompleteVisits->count() > 0) {
    echo "✓ الزيارة من الأمس يجب أن تظهر في قسم 'الزيارات غير المكتملة'!\n";
} else {
    echo "⚠️  لا توجد زيارات غير مكتملة لعرضها!\n";
}

echo "\n✓ التحقق اكتمل!\n";
