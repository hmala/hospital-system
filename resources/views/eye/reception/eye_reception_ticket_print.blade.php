<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تذكرة طابور مراجع العيون - {{ $appointment->appointment_number }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #1a1a1a;
            margin: 0;
            padding: 15px;
            display: flex;
            justify-content: center;
        }
        .ticket-wrapper {
            width: 100%;
            max-width: 320px;
            background: #ffffff;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            text-align: center;
            box-sizing: border-box;
        }
        .hospital-header {
            border-bottom: 2px dashed #94a3b8;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .hospital-name {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 3px;
        }
        .center-title {
            font-size: 14px;
            font-weight: 700;
            color: #0284c7;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin-bottom: 4px;
        }
        .ticket-type-badge {
            display: inline-block;
            background-color: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
        }
        .queue-box {
            background: #f8fafc;
            border: 2px solid #0284c7;
            border-radius: 10px;
            padding: 12px 6px;
            margin: 12px 0;
        }
        .queue-label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .queue-number {
            font-size: 46px;
            font-weight: 900;
            color: #0284c7;
            line-height: 1.1;
            margin: 4px 0;
        }
        .queue-time {
            font-size: 11px;
            color: #64748b;
        }
        .details-list {
            text-align: right;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 10px;
            margin-bottom: 10px;
            font-size: 12px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dotted #f1f5f9;
        }
        .detail-label {
            color: #64748b;
            font-weight: 600;
        }
        .detail-value {
            font-weight: 700;
            color: #0f172a;
            max-width: 65%;
            text-align: left;
            word-break: break-word;
        }
        .barcode-section {
            margin: 10px 0;
            padding: 6px 0;
        }
        .barcode-stripes {
            height: 36px;
            margin: 0 auto 4px auto;
            background: repeating-linear-gradient(
                90deg,
                #000 0,
                #000 2px,
                #fff 2px,
                #fff 4px,
                #000 4px,
                #000 7px,
                #fff 7px,
                #fff 9px,
                #000 9px,
                #000 13px,
                #fff 13px,
                #fff 15px
            );
            width: 75%;
            max-width: 200px;
        }
        .barcode-text {
            font-family: monospace;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #334155;
        }
        .instructions-box {
            background-color: #fefce8;
            border: 1px solid #fef08a;
            color: #854d0e;
            border-radius: 6px;
            padding: 8px;
            font-size: 11px;
            line-height: 1.4;
            margin-top: 10px;
            text-align: right;
        }
        .footer-note {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 10px;
        }
        .actions-bar {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn {
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-print {
            background-color: #0284c7;
            color: #fff;
        }
        .btn-print:hover {
            background-color: #0369a1;
        }
        .btn-close {
            background-color: #e2e8f0;
            color: #334155;
        }
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .ticket-wrapper {
                box-shadow: none;
                border: none;
                max-width: 100%;
                width: 100%;
                padding: 4px;
            }
            .actions-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="ticket-wrapper">
    <!-- الترويسة -->
    <div class="hospital-header">
        <div class="hospital-name">{{ config('app.name', 'مستشفى الجامعة التعليمي') }}</div>
        <div class="center-title">
            <span>👁️</span>
            <span>مركز وجراحة العيون التخصصي</span>
        </div>
        <div class="ticket-type-badge">تذكرة وكارت حجز مراجع</div>
    </div>

    <!-- صندوق رقم الدور الكبير -->
    <div class="queue-box">
        <div class="queue-label">رقم الدور في الطابور</div>
        <div class="queue-number">#{{ $appointment->queue_number ?: $appointment->id }}</div>
        <div class="queue-time">
            تاريخ الحجز: {{ $appointment->created_at->format('Y-m-d') }} &bull; {{ $appointment->created_at->format('h:i A') }}
        </div>
    </div>

    <!-- التفاصيل السريرية والإدارية -->
    <div class="details-list">
        <div class="detail-row">
            <span class="detail-label">رقم الموعد:</span>
            <span class="detail-value">{{ $appointment->appointment_number }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">المريض:</span>
            <span class="detail-value">{{ $appointment->patient->name }}</span>
        </div>
        @if($appointment->patient->medical_record_number)
        <div class="detail-row">
            <span class="detail-label">الرقم الطبي (MRN):</span>
            <span class="detail-value">{{ $appointment->patient->medical_record_number }}</span>
        </div>
        @endif
        <div class="detail-row">
            <span class="detail-label">طبيب العيون:</span>
            <span class="detail-value">د. {{ $appointment->doctor->user->name ?? 'طبيب العيون العام' }}</span>
        </div>
        @if($appointment->doctor && $appointment->doctor->specialization)
        <div class="detail-row">
            <span class="detail-label">التخصص الدقيق:</span>
            <span class="detail-value">{{ $appointment->doctor->specialization }}</span>
        </div>
        @endif
        <div class="detail-row">
            <span class="detail-label">نوع المراجعة:</span>
            <span class="detail-value">{{ $appointment->visit_type_arabic }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">فئة الدفع:</span>
            <span class="detail-value">
                @if($appointment->insurance_type === 'health_insurance')
                    الضمان الصحي (نسبة 10%)
                @elseif($appointment->insurance_type === 'moi')
                    قوى الأمن الداخلي
                @else
                    دفع نقدي (Cash)
                @endif
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">حالة الكاشير:</span>
            <span class="detail-value">
                @if($appointment->latestInvoice && $appointment->latestInvoice->status === 'paid')
                    ✅ مدفوع ({{ number_format($appointment->latestInvoice->paid_amount) }} د.ع)
                @else
                    ⏳ بانتظار التحصيل بالكاشير
                @endif
            </span>
        </div>
    </div>

    <!-- الباركود التخطيطي للماسح الضوئي -->
    <div class="barcode-section">
        <div class="barcode-stripes"></div>
        <div class="barcode-text">*{{ $appointment->appointment_number }}*</div>
    </div>

    <!-- تنبيهات وتعليمات سريرية -->
    <div class="instructions-box">
        <div><strong>💡 تنبيهات للمراجع:</strong></div>
        <div>• يرجى انتظار المناداة على شاشة صالة الانتظار.</div>
        <div>• في حال طلب الطبيب توسيع الحدقة (قطرات Mydriacyl)، يرجى مراجعة التمريض والانتظار 20 دقيقة حتى اكتمال التوسع.</div>
    </div>

    <div class="footer-note">
        نظام مركز العيون &bull; تذكرة صالحة لليوم فقط
    </div>

    <!-- أزرار الشاشة -->
    <div class="actions-bar">
        <button class="btn btn-print" onclick="window.print()">🖨️ طباعة التذكرة</button>
        <button class="btn btn-close" onclick="window.close()">إغلاق</button>
    </div>
</div>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        // طباعة تلقائية عند الفتح
        setTimeout(() => {
            window.print();
        }, 400);
    });
</script>

</body>
</html>
