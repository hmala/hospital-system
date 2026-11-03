<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الوصفة الطبية والعلاجية - {{ $reservation->baby_name }}</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 10mm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            color: #1e293b;
            margin: 0;
            padding: 10px;
            direction: rtl;
        }
        .rx-card {
            border: 2px solid #0d6efd;
            border-radius: 12px;
            padding: 18px;
            min-height: 95vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #0d6efd;
        }
        .header p {
            margin: 3px 0 0;
            font-size: 11px;
            color: #64748b;
        }
        .patient-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 12px;
            margin-bottom: 18px;
        }
        .patient-info span {
            color: #64748b;
        }
        .patient-info strong {
            color: #0f172a;
        }
        .rx-symbol {
            font-size: 32px;
            font-weight: 900;
            color: #0d6efd;
            font-family: serif;
            margin-bottom: 10px;
        }
        .medications-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .medications-table th {
            background: #f1f5f9;
            color: #334155;
            padding: 8px 10px;
            border-bottom: 2px solid #cbd5e1;
            text-align: right;
        }
        .medications-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .footer {
            border-top: 1px dashed #cbd5e1;
            padding-top: 15px;
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 11px;
        }
        .doctor-signature {
            text-align: center;
            min-width: 160px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            height: 35px;
            margin-bottom: 4px;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px; text-align: left;">
        <button onclick="window.print()" style="background: #0d6efd; color: white; border: none; padding: 8px 18px; border-radius: 6px; font-weight: bold; cursor: pointer;">
            🖨️ طباعة الوصفة (Print)
        </button>
        <button onclick="window.close()" style="background: #64748b; color: white; border: none; padding: 8px 14px; border-radius: 6px; margin-right: 5px; cursor: pointer;">
            إغلاق
        </button>
    </div>

    <div class="rx-card">
        <div>
            <div class="header">
                <div>
                    <h1>🏥 قسم رعاية الخدج وحديثي الولادة (NICU)</h1>
                    <p>وصفة طبية إلكترونية رسمية للأدوية والعلاجات السريرية</p>
                </div>
                <div style="text-align: left; font-size: 11px; color: #64748b;">
                    <div><strong>رقم الإضبارة:</strong> #{{ $reservation->id }}</div>
                    <div><strong>التاريخ:</strong> {{ now()->format('Y-m-d H:i') }}</div>
                </div>
            </div>

            <div class="patient-info">
                <div><span>اسم الطفل:</span> <strong>{{ $reservation->baby_name }}</strong></div>
                <div><span>الأم / المسؤول:</span> <strong>{{ optional($reservation->patient?->user)->name ?? '—' }}</strong></div>
                <div><span>رقم الحاضنة:</span> <strong>حاضنة {{ $reservation->incubator->incubator_number }} ({{ $reservation->incubator->room?->room_number ?? 'NICU' }})</strong></div>
                <div><span>الوزن الحالي:</span> <strong>{{ $reservation->current_weight ? $reservation->current_weight . ' جرام' : '—' }}</strong></div>
                <div><span>الطبيب المشرف:</span> <strong>{{ optional($reservation->doctor?->user)->name ?? '—' }}</strong></div>
                <div><span>تاريخ الدخول:</span> <strong>{{ $reservation->admission_date->format('Y-m-d') }}</strong></div>
            </div>

            <div class="rx-symbol">℞</div>

            <table class="medications-table">
                <thead>
                    <tr>
                        <th style="width: 35px;">#</th>
                        <th>اسم الدواء / العلاج</th>
                        <th>الجرعة والتعليمات</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reservation->medicalOrders as $idx => $med)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td><strong style="color: #0f172a;">{{ $med->item_name }}</strong></td>
                            <td>{{ $med->instructions ?? 'حسب إرشادات الطبيب المشرف' }}</td>
                            <td style="color: #64748b; font-size: 10px;">{{ $med->ordered_at ? $med->ordered_at->format('Y-m-d H:i') : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 25px; color: #94a3b8;">
                                لا توجد أدوية مسجلة في هذه الوصفة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="footer">
            <div>
                <div><strong>المستشفى الأهلي التخصصي</strong> - وحدة العناية المركزة للخدج</div>
                <div style="color: #64748b; font-size: 10px; margin-top: 2px;">تطبع هذه الوصفة مباشرة من النظام السريري وتُعتمد للملف الطبي وللصيدلية.</div>
            </div>
            <div class="doctor-signature">
                <div class="signature-line"></div>
                <div><strong>توقيع وختم الطبيب المشرف</strong></div>
                <div style="color: #64748b; font-size: 10px;">{{ optional($reservation->doctor?->user)->name }}</div>
            </div>
        </div>
    </div>
</body>
</html>
