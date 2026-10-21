<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير فحص مخبري - {{ $order->reservation?->baby_name ?: 'طفل ' . ($order->reservation?->patient?->name ?? 'غير محدد') }}</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 8mm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            color: #1e293b;
            margin: 0;
            padding: 10px;
            direction: rtl;
        }
        .report-card {
            border: 2px solid #0284c7;
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
            border-bottom: 2px solid #0284c7;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 19px;
            color: #0284c7;
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
        .results-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .results-table th {
            background: #f1f5f9;
            color: #334155;
            padding: 9px 12px;
            border-bottom: 2px solid #cbd5e1;
            text-align: right;
        }
        .results-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-normal { background: #dcfce7; color: #166534; }
        .status-high { background: #fee2e2; color: #991b1b; }
        .status-low { background: #fef3c7; color: #92400e; }
        .status-abnormal { background: #fef2f2; color: #dc2626; border: 1px solid #f87171; }
        .notes-box {
            background: #f8fafc;
            border-right: 4px solid #0284c7;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 11px;
            margin-top: 10px;
        }
        .footer {
            border-top: 1px dashed #cbd5e1;
            padding-top: 12px;
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 11px;
        }
        .signature-box {
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
        <button onclick="window.print()" style="background: #0284c7; color: white; border: none; padding: 8px 18px; border-radius: 6px; font-weight: bold; cursor: pointer;">
            🖨️ طباعة التقرير المخبري
        </button>
        <button onclick="window.close()" style="background: #64748b; color: white; border: none; padding: 8px 14px; border-radius: 6px; margin-right: 5px; cursor: pointer;">
            إغلاق
        </button>
    </div>

    @php
        $res = $order->reservation;
        $babyName = $res?->baby_name ?: 'طفل ' . ($res?->patient?->name ?? 'غير محدد');
        $parentName = $res?->patient?->name ?? '—';
        $incubatorNum = $res?->incubator?->name ?? $res?->incubator_id;
        $docName = $res?->doctor?->user?->name ?? 'طبيب الخدج';
        $techName = $order->processedByUser?->name ?? 'فني المختبر';
    @endphp

    <div class="report-card">
        <div>
            <div class="header">
                <div>
                    <h1>🧪 قسم المختبر والتحاليل الطبية (NICU Lab)</h1>
                    <p>تقرير الفحص السريري لحديثي الولادة والأطفال الخدج</p>
                </div>
                <div style="text-align: left; font-size: 11px; color: #64748b;">
                    <div><strong>رقم الفحص:</strong> #{{ $order->id }}</div>
                    <div><strong>تاريخ النتيجة:</strong> {{ optional($order->completed_at)->format('Y-m-d H:i') ?: now()->format('Y-m-d H:i') }}</div>
                </div>
            </div>

            <div class="patient-info">
                <div><span>اسم الطفل الخديج:</span> <strong>{{ $babyName }}</strong></div>
                <div><span>الأم / المسؤول:</span> <strong>{{ $parentName }}</strong></div>
                <div><span>رقم الحاضنة:</span> <strong>حاضنة #{{ $incubatorNum }}</strong></div>
                <div><span>الطبيب المشرف:</span> <strong>د. {{ $docName }}</strong></div>
                <div><span>رقم الإضبارة:</span> <strong>#{{ $res?->id }}</strong></div>
                <div><span>تاريخ الطلب:</span> <strong>{{ optional($order->ordered_at)->format('Y-m-d H:i') ?: '—' }}</strong></div>
            </div>

            <div class="results-box">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>الفحص المخبري</th>
                            <th style="width: 25%; text-align: center;">النتيجة المقاسة</th>
                            <th style="width: 15%; text-align: center;">الوحدة</th>
                            <th style="width: 25%; text-align: center;">المعدل الطبيعي والمرجع</th>
                            <th style="width: 15%; text-align: center;">التقييم</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong style="font-size: 13px; color: #0f172a;">{{ $order->item_name }}</strong>
                                @if($order->notes)
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">{{ $order->notes }}</div>
                                @endif
                            </td>
                            <td style="text-align: center; font-size: 15px; font-weight: bold; color: #0284c7; font-family: monospace;">
                                {{ $order->result ?: '—' }}
                            </td>
                            <td style="text-align: center; color: #475569; font-size: 11px;">
                                {{ $order->unit ?: ($order->labTest?->unit ?? '—') }}
                            </td>
                            <td style="text-align: center; color: #475569; font-size: 11px;">
                                {{ $order->reference_range ?: '—' }}
                            </td>
                            <td style="text-align: center;">
                                @if($order->result_status == 'high')
                                    <span class="status-badge status-high">مرتفع ⬆️</span>
                                @elseif($order->result_status == 'low')
                                    <span class="status-badge status-low">منخفض ⬇️</span>
                                @elseif($order->result_status == 'abnormal')
                                    <span class="status-badge status-abnormal">غير طبيعي ⚠️</span>
                                @else
                                    <span class="status-badge status-normal">طبيعي ✓</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if($order->technician_notes)
                <div class="notes-box">
                    <strong><i class="fas fa-comment-dots"></i> ملاحظات فني المختبر:</strong>
                    <div>{{ $order->technician_notes }}</div>
                </div>
            @endif

            @if($order->attachment_path)
                <div style="margin-top: 10px; font-size: 11px; color: #64748b; border: 1px dashed #cbd5e1; padding: 6px 10px; border-radius: 6px;">
                    📎 <strong>مرفق تقرير الجهاز:</strong> {{ $order->attachment_name ?: 'ملف مرفق بالجهاز المخبري' }}
                </div>
            @endif
        </div>

        <div class="footer">
            <div class="signature-box">
                <div style="color: #64748b;">فني / اختصاصي المختبر</div>
                <div class="signature-line"></div>
                <strong>{{ $techName }}</strong>
            </div>
            <div style="text-align: center; color: #94a3b8; font-size: 10px;">
                <div>تم إصدار التقرير إلكترونياً عبر نظام المستشفى المركزي</div>
                <div>وحدة رعاية حديثي الولادة والخدج (NICU)</div>
            </div>
            <div class="signature-box">
                <div style="color: #64748b;">ختم واعتماد المختبر</div>
                <div class="signature-line"></div>
                <small style="color: #64748b;">تاريخ: {{ now()->format('Y-m-d') }}</small>
            </div>
        </div>
    </div>
</body>
</html>
