<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// تسجيل الدخول كمستخدم كاشير
$cashier = App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'cashier');
})->first();
Auth::login($cashier);

// جلب الطلبات المعلقة - نفس كود CashierController
$pendingRequests = App\Models\Request::with(['visit.patient.user', 'visit.doctor.user'])
    ->where('payment_status', 'pending')
    ->whereHas('visit', function($q) {
        $q->where('status', '!=', 'cancelled');
    })
    ->orderBy('created_at', 'desc')
    ->paginate(15, ['*'], 'requests_page');

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>اختبار الطلبات المعلقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>اختبار الطلبات المعلقة</h2>
    
    <div class="alert alert-info">
        <strong>عدد الطلبات:</strong> <?= $pendingRequests->count() ?><br>
        <strong>إجمالي:</strong> <?= $pendingRequests->total() ?><br>
        <strong>نوع المتغير:</strong> <?= get_class($pendingRequests) ?>
    </div>

    <?php if($pendingRequests->count() > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>رقم الطلب</th>
                    <th>النوع</th>
                    <th>المريض</th>
                    <th>حالة الدفع</th>
                    <th>حالة الزيارة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pendingRequests as $request): ?>
                <tr>
                    <td><?= $request->id ?></td>
                    <td><?= $request->type ?></td>
                    <td><?= $request->visit->patient->user->name ?></td>
                    <td><?= $request->payment_status ?></td>
                    <td><?= $request->visit->status ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">لا توجد طلبات معلقة</div>
    <?php endif; ?>
</div>
</body>
</html>
