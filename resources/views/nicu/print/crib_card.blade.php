<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كارت الحاضنة - {{ $reservation->baby_name }}</title>
    <style>
        @page {
            size: A6 landscape;
            margin: 5mm;
        }
        body {
            font-family: sans-serif;
            background: #fff;
            color: #000;
            margin: 0;
            padding: 10px;
        }
        .crib-card {
            border: 3px double #0d6efd;
            border-radius: 12px;
            padding: 15px;
            height: 90%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
            color: #0d6efd;
        }
        .header p {
            margin: 2px 0 0;
            font-size: 11px;
            color: #555;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 13px;
        }
        .grid-item {
            background: #f8f9fa;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        .grid-item label {
            display: block;
            font-size: 10px;
            color: #666;
            margin-bottom: 2px;
        }
        .grid-item strong {
            font-size: 14px;
            color: #000;
        }
        .footer {
            margin-top: 10px;
            border-top: 1px dashed #ccc;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #777;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="crib-card">
    <div class="header">
        <h2>مستشفى الرعاية التخصصي</h2>
        <p>قسم العناية المركزة لحديثي الولادة والخدج (NICU) - ط6</p>
    </div>

    <div class="grid">
        <div class="grid-item">
            <label>اسم المولود:</label>
            <strong>{{ $reservation->baby_name }}</strong>
        </div>
        <div class="grid-item">
            <label>رقم الحاضنة:</label>
            <strong style="color: #0d6efd; font-size: 16px;">حاضنة رقم {{ $reservation->incubator->incubator_number }}</strong>
        </div>
        <div class="grid-item">
            <label>اسم الأم:</label>
            <strong>{{ optional($reservation->patient?->user)->name ?? 'غير معروف' }}</strong>
        </div>
        <div class="grid-item">
            <label>وزن الدخول:</label>
            <strong>{{ $reservation->birth_weight ?? '—' }}</strong>
        </div>
        <div class="grid-item">
            <label>تاريخ وساعة الدخول:</label>
            <strong>{{ $reservation->admission_date->format('Y-m-d') }} ({{ $reservation->admission_time ? $reservation->admission_time->format('H:i') : '' }})</strong>
        </div>
        <div class="grid-item">
            <label>الطبيب الأخصائي المشرف:</label>
            <strong>{{ optional($reservation->doctor?->user)->name ?? 'طبيب القسم' }}</strong>
        </div>
        <div class="grid-item" style="grid-column: span 2;">
            <label>نوع الخدمة والباقة السريرية:</label>
            <strong style="color: #198754;">{{ $reservation->nicuService?->name ?? 'حاضنة رعاية الخدج' }}</strong>
        </div>
    </div>

    <div class="footer">
        <span>رقم الإضبارة: #{{ $reservation->patient_id }}</span>
        <span>باركود: NICU-{{ $reservation->id }}</span>
        <span>نسأل الله له الشفاء والعافية 💚</span>
    </div>
</div>

</body>
</html>
