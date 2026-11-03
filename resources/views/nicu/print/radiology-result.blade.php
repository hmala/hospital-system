<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير أشعة وسونار - {{ $order->reservation?->baby_name ?: 'طفل ' . ($order->reservation?->patient?->name ?? 'غير محدد') }}</title>
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
            border: 2px solid #2563eb;
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
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 19px;
            color: #2563eb;
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
        .exam-title-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .exam-title-box h3 {
            margin: 0;
            font-size: 14px;
            color: #1d4ed8;
        }
        .findings-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 15px;
            background: #ffffff;
        }
        .findings-box h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: #334155;
            text-transform: uppercase;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 5px;
        }
        .findings-content {
            font-size: 13px;
            line-height: 1.6;
            color: #0f172a;
            white-space: pre-wrap;
        }
        .notes-box {
            background: #f8fafc;
            border-right: 4px solid #2563eb;
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
        <button onclick="window.print()" style="background: #2563eb; color: white; border: none; padding: 8px 18px; border-radius: 6px; font-weight: bold; cursor: pointer;">
            🖨️ طباعة تقرير الأشعة والتصوير
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
        $specialistName = $order->processedByUser?->name ?? 'أخصائي الأشعة والتصوير';
    @endphp

    <div class="report-card">
        <div>
            <div class="header">
                <div>
                    <h1>🩻 قسم الأشعة والتصوير الطبي (NICU Radiology)</h1>
                    <p>تقرير الأشعة والسونار التشخيصي لحديثي الولادة والأطفال الخدج</p>
                </div>
                <div style="text-align: left; font-size: 11px; color: #64748b;">
                    <div><strong>رقم الفحص:</strong> #{{ $order->id }}</div>
                    <div><strong>تاريخ التقرير:</strong> {{ optional($order->completed_at)->format('Y-m-d H:i') ?: now()->format('Y-m-d H:i') }}</div>
                </div>
            </div>

            <div class="patient-info">
                <div><span>اسم الطفل الخديج:</span> <strong>{{ $babyName }}</strong></div>
                <div><span>الأم / المسؤول:</span> <strong>{{ $parentName }}</strong></div>
                <div><span>رقم الحاضنة:</span> <strong>حاضنة #{{ $incubatorNum }}</strong></div>
                <div><span>الطبيب المحول / المشرف:</span> <strong>د. {{ $docName }}</strong></div>
                <div><span>رقم الإضبارة:</span> <strong>#{{ $res?->id }}</strong></div>
                <div><span>تاريخ ووقت الفحص:</span> <strong>{{ optional($order->ordered_at)->format('Y-m-d H:i') ?: '—' }}</strong></div>
            </div>

            <div class="exam-title-box">
                <div>
                    <span style="font-size: 11px; color: #64748b; display: block;">الفحص المطلوب:</span>
                    <h3>{{ $order->item_name }}</h3>
                </div>
                @if($order->notes)
                    <div style="font-size: 11px; color: #475569; max-width: 50%; text-align: left;">
                        {{ $order->notes }}
                    </div>
                @endif
            </div>

            <div class="findings-box">
                <h4>التقرير والمشاهدات التشخيصية (Findings & Impression)</h4>
                <div class="findings-content">{{ $order->result ?: 'لا يوجد تقرير مسجل حتى الآن.' }}</div>
            </div>

            @if($order->technician_notes)
                <div class="notes-box">
                    <strong><i class="fas fa-stethoscope"></i> توصيات أخصائي الأشعة:</strong>
                    <div>{{ $order->technician_notes }}</div>
                </div>
            @endif

            @if($order->attachment_path)
                <div style="margin-top: 10px; font-size: 11px; color: #64748b; border: 1px dashed #cbd5e1; padding: 6px 10px; border-radius: 6px;">
                    🩻 <strong>مرفق صورة الفحص أو ملف DICOM:</strong> {{ $order->attachment_name ?: 'ملف شعاعي مرفق' }}
                </div>
            @endif
        </div>

        <div class="footer">
            <div class="signature-box">
                <div style="color: #64748b;">طبيب / اختصاصي الأشعة والتصوير</div>
                <div class="signature-line"></div>
                <strong>{{ $specialistName }}</strong>
            </div>
            <div style="text-align: center; color: #94a3b8; font-size: 10px;">
                <div>تم توثيق واعتماد التقرير إلكترونياً عبر المنظومة الطبية</div>
                <div>قسم الأشعة والتصوير الطبي - وحدة الخدج (NICU)</div>
            </div>
            <div class="signature-box">
                <div style="color: #64748b;">ختم واعتماد قسم الأشعة</div>
                <div class="signature-line"></div>
                <small style="color: #64748b;">تاريخ: {{ now()->format('Y-m-d') }}</small>
            </div>
        </div>
    </div>
</body>
</html>
