<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>أسورة معصم المولود - {{ $reservation->baby_name }}</title>
    <style>
        @page {
            size: 150mm 25mm;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 2mm 5mm;
            font-family: sans-serif;
            background: #fff;
            color: #000;
            width: 150mm;
            height: 25mm;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .band-content {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }
        .info-col {
            flex-grow: 1;
            line-height: 1.2;
            font-size: 10px;
        }
        .baby-name {
            font-size: 13px;
            font-weight: bold;
        }
        .barcode-col {
            text-align: center;
        }
        .barcode {
            font-family: monospace;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="band-content">
    <div class="info-col">
        <div class="baby-name">الطفل: {{ $reservation->baby_name }}</div>
        <div>الأم: {{ optional($reservation->patient?->user)->name ?? 'غير معروف' }}</div>
        <div>الدخول: {{ $reservation->admission_date->format('Y-m-d') }} | حاضنة: #{{ $reservation->incubator->incubator_number }}</div>
        <div>الوزن: {{ $reservation->birth_weight ?? '—' }} | الطبيب: {{ optional($reservation->doctor?->user)->name ?? '—' }}</div>
    </div>
    <div class="barcode-col">
        <div class="barcode">*NICU-{{ $reservation->id }}*</div>
        <div style="font-size: 8px;">مستشفى الرعاية التخصصي</div>
    </div>
</div>

</body>
</html>
