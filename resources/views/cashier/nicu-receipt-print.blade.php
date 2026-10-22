<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال قبض أمانات الخدج - {{ $nicuPayment->receipt_number }}</title>
    <!-- Google Fonts Cairo -->
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
            font-size: 13px;
            line-height: 1.4;
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
            background-color: #10b981;
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
            margin-bottom: 10px;
        }

        .header-box .ar-title {
            font-size: 15px;
            font-weight: 900;
            line-height: 1.2;
        }

        .header-box .en-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .header-box .sub-title {
            font-size: 12px;
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
            font-size: 11.5px;
            font-weight: 700;
            margin-bottom: 4px;
            border-bottom: 1px dashed #000;
            padding-bottom: 4px;
        }

        .patient-box {
            border: 1px solid #000;
            border-radius: 4px;
            padding: 6px 8px;
            margin: 8px 0;
            background: #fafafa;
        }

        .patient-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 12px;
        }

        .patient-row:last-child {
            margin-bottom: 0;
        }

        .patient-label {
            font-weight: 700;
            color: #333;
        }

        .patient-val {
            font-weight: 900;
        }

        .amount-box {
            border: 2px solid #000;
            border-radius: 4px;
            padding: 8px;
            text-align: center;
            margin: 10px 0;
            background: #fff;
        }

        .amount-title {
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .amount-val {
            font-size: 20px;
            font-weight: 900;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 11.5px;
        }

        .summary-table td {
            padding: 3px 0;
        }

        .summary-table tr.total-row td {
            font-weight: 900;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 0;
        }

        .dotted-divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .footer {
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            margin-top: 10px;
            line-height: 1.4;
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
        <button onclick="window.print()" class="btn-print">🖨️ طباعة الوصل (Print)</button>
        <button onclick="window.close()" class="btn-back">✕ إغلاق النافذة</button>
    </div>

    @php
        $res = $nicuPayment->reservation;
        $babyTitle = $res ? ($res->baby_name ?: ('طفل ' . ($res->patient?->name ?? 'غير مسجل'))) : 'الخديج';
        $incName = $res?->incubator?->name ?? ('حاضنة #' . ($res?->incubator_id ?? ''));
        $docName = $res?->doctor?->user?->name ? ('د. ' . $res->doctor->user->name) : 'طبيب الخدج المشرف';
        $guardian = $res?->patient?->name ?? 'غير مسجل';
        $phone = $res?->patient?->phone ?? '—';
    @endphp

    <div class="thermal-ticket">
        <!-- الترويسة الرسمية -->
        <div class="header-box">
            <div class="ar-title">مستشفى الكفاءات التخصصي</div>
            <div class="en-title">AL-KAFAAT SPECIALIZED HOSPITAL</div>
            @if($nicuPayment->payment_type === 'refund')
                <div class="sub-title">سند صرف واسترجاع أمانات - قسم الخدج (NICU)</div>
            @else
                <div class="sub-title">سند قبض أمانات - قسم الخدج والحاضنات (NICU)</div>
            @endif
        </div>

        <!-- بيانات السند والكاشير -->
        <div class="ticket-info">
            <span>رقم الوصل: <strong>{{ $nicuPayment->receipt_number }}</strong></span>
            <span>{{ $nicuPayment->paid_at ? $nicuPayment->paid_at->format('Y/m/d H:i') : now()->format('Y/m/d H:i') }}</span>
        </div>
        <div class="ticket-info">
            <span>الكاشير: <strong>{{ $nicuPayment->cashier?->name ?? 'الكاشير' }}</strong></span>
            <span>النوع: <strong>{{ $nicuPayment->payment_type_name }}</strong></span>
        </div>

        <!-- بيانات الطفل والحاضنة -->
        <div class="patient-box">
            <div class="patient-row">
                <span class="patient-label">الطفل الخديج:</span>
                <span class="patient-val">{{ $babyTitle }}</span>
            </div>
            <div class="patient-row">
                <span class="patient-label">رقم الحاضنة:</span>
                <span class="patient-val">{{ $incName }}</span>
            </div>
            <div class="patient-row">
                <span class="patient-label">الطبيب المشرف:</span>
                <span class="patient-val">{{ $docName }}</span>
            </div>
            <div class="patient-row">
                <span class="patient-label">ولي الأمر / الهاتف:</span>
                <span class="patient-val">{{ $guardian }} / {{ $phone }}</span>
            </div>
        </div>

        <!-- صندوق المبلغ المقبوض / المصروف -->
        <div class="amount-box">
            <div class="amount-title">{{ $nicuPayment->payment_type === 'refund' ? 'المبلغ المصروف والمسترجع نقداً للمريض' : 'المبلغ المقبوض في الصندوق (نقداً)' }}</div>
            <div class="amount-val">{{ $nicuPayment->payment_type === 'refund' ? '-' : '' }}{{ number_format($nicuPayment->amount) }} د.ع</div>
            <small style="font-size: 11px; font-weight: 700;">طريقة {{ $nicuPayment->payment_type === 'refund' ? 'الصرف' : 'القبض' }}: {{ $nicuPayment->payment_method === 'cash' ? 'نقدي' : 'بطاقة' }}</small>
        </div>

        <!-- جدول الموقف المالي التراكمي -->
        @if(isset($stayDetails))
            <div style="font-size: 11.5px; font-weight: 800; margin-bottom: 2px;">الموقف المالي التراكمي للحاضنة:</div>
            <table class="summary-table">
                <tr>
                    <td>أجور الحاضنة والإقامة:</td>
                    <td style="text-align: left; font-weight: 700;">{{ number_format($stayDetails['total_service_cost']) }} د.ع</td>
                </tr>
                <tr>
                    <td>التحاليل والمختبر ({{ $stayDetails['medical_orders']->where('order_type', 'lab')->count() }} فحص):</td>
                    <td style="text-align: left; font-weight: 700;">{{ number_format($stayDetails['medical_orders']->where('order_type', 'lab')->sum('price')) }} د.ع</td>
                </tr>
                <tr>
                    <td>الأشعة والسونار ({{ $stayDetails['medical_orders']->where('order_type', 'radiology')->count() }} فحص):</td>
                    <td style="text-align: left; font-weight: 700;">{{ number_format($stayDetails['medical_orders']->where('order_type', 'radiology')->sum('price')) }} د.ع</td>
                </tr>
                @if($stayDetails['total_consultations_cost'] > 0)
                    <tr>
                        <td>استشارات الأطباء:</td>
                        <td style="text-align: left; font-weight: 700;">{{ number_format($stayDetails['total_consultations_cost']) }} د.ع</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>إجمالي المطالبة حتى الآن:</td>
                    <td style="text-align: left;">{{ number_format($stayDetails['patient_grand_total']) }} د.ع</td>
                </tr>
                <tr>
                    <td>صافي الأمانات بعد التسوية:</td>
                    <td style="text-align: left; font-weight: 900;">{{ number_format($stayDetails['total_paid']) }} د.ع</td>
                </tr>
                @if(($stayDetails['excess_deposit'] ?? 0) > 0)
                    <tr>
                        <td>فائض أمانات متبقي للأهل:</td>
                        <td style="text-align: left; font-weight: 900;">{{ number_format($stayDetails['excess_deposit']) }} د.ع</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>الصافي المتبقي بذمة المريض:</td>
                    <td style="text-align: left; font-size: 13px;">{{ number_format($stayDetails['net_balance']) }} د.ع</td>
                </tr>
            </table>
        @endif

        <div class="dotted-divider"></div>

        <div class="footer">
            <div>{{ $nicuPayment->payment_type === 'refund' ? 'سند صرف واسترجاع مالي رسمي صادر من قسم حسابات المستشفى' : 'وصل قبض مالي رسمي صادر من قسم حسابات المستشفى' }}</div>
            <div style="font-size: 10px; margin-top: 2px;">تمنياتنا للطفل الخديج بدوام الصحة والسلامة والشفاء العاجل</div>
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
