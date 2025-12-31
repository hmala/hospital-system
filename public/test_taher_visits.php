<!DOCTYPE html>
<html>
<head>
    <title>اختبار عرض زيارات د. ظاهر علي</title>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { direction: rtl; text-align: right; }
        .alert { margin: 20px; }
        .table-container { margin: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="my-4">اختبار عرض الزيارات - د. ظاهر علي</h2>

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;
use App\Models\User;

$doctor = User::where('name', 'LIKE', '%ظاهر%')->first();
if (!$doctor || !$doctor->doctor) {
    echo '<div class="alert alert-danger">لم يتم العثور على الطبيب!</div>';
    exit;
}

$incompleteVisits = Visit::where('doctor_id', $doctor->doctor->id)
    ->where('status', '!=', 'completed')
    ->where('status', '!=', 'cancelled')
    ->whereDate('visit_date', '<', today())
    ->with(['patient.user', 'appointment'])
    ->orderBy('visit_date', 'asc')
    ->orderBy('visit_time', 'asc')
    ->get();

$todayVisits = Visit::where('doctor_id', $doctor->doctor->id)
    ->where('status', '!=', 'cancelled')
    ->whereDate('visit_date', today())
    ->with(['patient.user', 'appointment'])
    ->get();

echo "<div class='alert alert-info'>";
echo "الطبيب: {$doctor->name}<br>";
echo "عدد الزيارات غير المكتملة: " . $incompleteVisits->count() . "<br>";
echo "عدد زيارات اليوم: " . $todayVisits->count();
echo "</div>";

if ($incompleteVisits->count() > 0):
?>

        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-circle"></i> لديك <?= $incompleteVisits->count() ?> زيارة غير مكتملة</h5>
        </div>

        <div class="table-container">
            <h4>الزيارات غير المكتملة</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>المريض</th>
                        <th>التاريخ</th>
                        <th>الوقت</th>
                        <th>الحالة</th>
                        <th>الشكوى</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($incompleteVisits as $visit): ?>
                    <tr style="background: #fef3c7;">
                        <td><?= $visit->patient->user->name ?></td>
                        <td><?= $visit->visit_date->format('Y-m-d') ?></td>
                        <td><?= $visit->visit_time ?: 'غير محدد' ?></td>
                        <td><?= $visit->status ?></td>
                        <td><?= $visit->chief_complaint ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

<?php else: ?>
        <div class="alert alert-success">
            ✓ لا توجد زيارات غير مكتملة
        </div>
<?php endif; ?>

<?php if ($todayVisits->count() > 0): ?>
        <div class="table-container">
            <h4>زيارات اليوم</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>المريض</th>
                        <th>الوقت</th>
                        <th>الحالة</th>
                        <th>الشكوى</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($todayVisits as $visit): ?>
                    <tr style="background: #e0f2fe;">
                        <td><?= $visit->patient->user->name ?></td>
                        <td><?= $visit->visit_time ?: 'غير محدد' ?></td>
                        <td><?= $visit->status ?></td>
                        <td><?= $visit->chief_complaint ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php endif; ?>

    </div>
</body>
</html>
