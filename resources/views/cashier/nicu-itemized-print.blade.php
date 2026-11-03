<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف خدمات الخدج التفصيلي - #{{ $reservation->id }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            color: #000000;
        }

        body {
            direction: rtl;
            text-align: right;
            background-color: #f3f4f6;
            padding: 20px 10px;
            font-size: 12.5px;
            line-height: 1.35;
        }

        .thermal-ticket {
            width: 78mm;
            max-width: 100%;
            margin: 0 auto;
            background: #ffffff;
            padding: 12px 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 4px;
        }

        .no-print {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .btn-print {
            background-color: #0ea5e9;
            color: white;
            padding: 8px 18px;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
        }

        .btn-back {
            background-color: #6b7280;
            color: white;
            padding: 8px 18px;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
        }

        .header-box {
            border: 1px solid #000000;
            border-radius: 4px;
            padding: 6px 8px;
            text-align: center;
            margin-bottom: 8px;
        }

        .header-box .ar-title {
            font-size: 15px;
            font-weight: 900;
        }

        .header-box .en-title {
            font-size: 11px;
            font-weight: 700;
        }

        .header-box .sub-title {
            font-size: 11.5px;
            font-weight: 800;
            margin-top: 3px;
            background: #000;
            color: #fff;
            padding: 2px 4px;
            border-radius: 2px;
            display: inline-block;
        }

        .ticket-info {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 4px;
            border-bottom: 1px dashed #000;
            padding-bottom: 3px;
        }

        .patient-box {
            border: 1px solid #000;
            border-radius: 4px;
            padding: 5px 7px;
            margin: 6px 0;
            background: #fafafa;
            font-size: 11.5px;
        }

        .section-header {
            font-size: 12px;
            font-weight: 900;
            margin: 8px 0 3px 0;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 11.5px;
        }

        .item-name {
            flex: 1;
            padding-left: 5px;
        }

        .item-price {
            font-weight: 700;
            white-space: nowrap;
        }

        .total-box {
            border: 1.5px solid #000;
            border-radius: 4px;
            padding: 6px 8px;
            margin: 8px 0;
            background: #fff;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 12px;
        }

        .total-row.grand {
            font-size: 13.5px;
            font-weight: 900;
            border-top: 1px dashed #000;
            padding-top: 4px;
            margin-top: 4px;
        }

        .dotted-divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .footer {
            text-align: center;
            font-size: 10.5px;
            font-weight: 700;
            margin-top: 8px;
        }

        @media print {
            body {
                background: none;
                padding: 0;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            .thermal-ticket {
                width: 78mm;
                padding: 4px 6px;
                box-shadow: none;
                border-radius: 0;
                margin: 0;
            }

            @page {
                size: 78mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn-print">🖨️ طباعة الكشف التفصيلي</button>
        <button onclick="window.close()" class="btn-back">✕ إغلاق النافذة</button>
    </div>

    @php
        $babyTitle = $reservation->baby_name ?: ('طفل ' . ($reservation->patient?->name ?? 'غير مسجل'));
        $incName = $reservation->incubator?->name ?? ('حاضنة #' . $reservation->incubator_id);
        $docName = $reservation->doctor?->user?->name ? ('د. ' . $reservation->doctor->user->name) : 'طبيب الخدج';
        $labs = $stayDetails['medical_orders']->where('order_type', 'lab');
        $rads = $stayDetails['medical_orders']->where('order_type', 'radiology');
    @endphp

    <div class="thermal-ticket">
        <div class="header-box">
            <div class="ar-title">مستشفى الكفاءات التخصصي</div>
            <div class="en-title">AL-KAFAAT SPECIALIZED HOSPITAL</div>
            <div class="sub-title">كشف حساب وخدمات تفصيلي - الخدج والحاضنات</div>
        </div>

        <div class="ticket-info">
            <span>رقم الإقامة: <strong>#{{ $reservation->id }}</strong></span>
            <span>التاريخ: {{ now()->format('Y/m/d H:i') }}</span>
        </div>

        <div class="patient-box">
            <div>الطفل: <strong>{{ $babyTitle }}</strong> ({{ $incName }})</div>
            <div>الطبيب: <strong>{{ $docName }}</strong></div>
            <div>ولي الأمر: {{ $reservation->patient?->name ?? '-' }} ({{ $reservation->patient?->phone ?? '-' }})</div>
        </div>

        <!-- 1. أجور الحاضنة والإقامة -->
        <div class="section-header">1. أجور الحاضنة والإقامة ({{ $stayDetails['calculated_days'] }} يوم):</div>
        @foreach($stayDetails['service_breakdown'] as $sb)
            <div class="item-row">
                <span class="item-name">{{ $sb['service_name'] }} ({{ $sb['days'] }} يوم)</span>
                <span class="item-price">{{ number_format($sb['total']) }} د.ع</span>
            </div>
        @endforeach

        <!-- 2. التحاليل المخبرية التفصيلية -->
        @if($labs->isNotEmpty())
            <div class="section-header">2. التحاليل المخبرية ({{ $labs->count() }} فحص):</div>
            @foreach($labs as $lab)
                <div class="item-row">
                    <span class="item-name">• {{ $lab->item_name }}</span>
                    <span class="item-price">{{ number_format($lab->price) }} د.ع</span>
                </div>
            @endforeach
            <div class="item-row" style="font-weight: 800; font-size: 11px; margin-top: 2px;">
                <span>مجموع المختبر:</span>
                <span>{{ number_format($labs->sum('price')) }} د.ع</span>
            </div>
        @endif

        <!-- 3. الفحوصات الإشعاعية والسونار -->
        @if($rads->isNotEmpty())
            <div class="section-header">3. الأشعة والسونار والإيكو ({{ $rads->count() }} فحص):</div>
            @foreach($rads as $rad)
                <div class="item-row">
                    <span class="item-name">• {{ $rad->item_name }}</span>
                    <span class="item-price">{{ number_format($rad->price) }} د.ع</span>
                </div>
            @endforeach
            <div class="item-row" style="font-weight: 800; font-size: 11px; margin-top: 2px;">
                <span>مجموع الأشعة والسونار:</span>
                <span>{{ number_format($rads->sum('price')) }} د.ع</span>
            </div>
        @endif

        <!-- 4. الاستشارات إن وجدت -->
        @if($stayDetails['consultations']->isNotEmpty())
            <div class="section-header">4. استشارات الأطباء:</div>
            @foreach($stayDetails['consultations'] as $con)
                <div class="item-row">
                    <span class="item-name">• {{ $con->doctor_display_name }} ({{ $con->specialty }})</span>
                    <span class="item-price">{{ number_format($con->fee) }} د.ع</span>
                </div>
            @endforeach
        @endif

        <!-- 5. الأمانات المسددة -->
        @if($stayDetails['payments']->isNotEmpty())
            <div class="section-header">5. الأمانات والوصولات النقدية المسددة:</div>
            @foreach($stayDetails['payments'] as $pmt)
                <div class="item-row">
                    <span class="item-name">وصل #{{ $pmt->receipt_number }} ({{ $pmt->paid_at ? $pmt->paid_at->format('m/d') : '' }})</span>
                    <span class="item-price text-success">-{{ number_format($pmt->amount) }} د.ع</span>
                </div>
            @endforeach
        @endif

        <!-- صندوق الموقف المالي الختامي -->
        <div class="total-box">
            <div class="total-row">
                <span>إجمالي الفاتورة المترتبة:</span>
                <span style="font-weight: 900;">{{ number_format($stayDetails['patient_grand_total']) }} د.ع</span>
            </div>
            <div class="total-row">
                <span>مجموع الأمانات المسددة:</span>
                <span style="font-weight: 900;">{{ number_format($stayDetails['total_paid']) }} د.ع</span>
            </div>
            <div class="total-row grand">
                <span>الرصيد المتبقي للدفع:</span>
                <span style="font-size: 14px;">{{ number_format($stayDetails['net_balance']) }} د.ع</span>
            </div>
            @if($stayDetails['excess_deposit'] > 0)
                <div class="total-row" style="color: #0284c7; font-weight: 800; margin-top: 2px;">
                    <span>فائض أمانات مستحق للأهل:</span>
                    <span>+{{ number_format($stayDetails['excess_deposit']) }} د.ع</span>
                </div>
            @endif
        </div>

        <div class="dotted-divider"></div>
        <div class="footer">
            <div>كشف رسمي بحسابات وخدمات الخديج — مستشفى الكفاءات</div>
            <div style="font-size: 9.5px; margin-top: 2px;">تم التوليد آلياً من نظام إدارة المستشفى</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 400);
        };
    </script>
</body>
</html>
