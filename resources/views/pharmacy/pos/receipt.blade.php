<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة صيدلية - {{ $sale->invoice_number }}</title>
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
        }

        .ticket-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .hospital-name {
            font-size: 16px;
            font-weight: 900;
        }

        .sub-title {
            font-size: 12px;
            font-weight: 700;
            margin-top: 2px;
        }

        .ticket-info {
            font-size: 12px;
            margin-bottom: 8px;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 8px;
        }

        .items-table th {
            border-bottom: 1px solid #000;
            padding: 4px 2px;
            font-weight: 800;
        }

        .items-table td {
            padding: 4px 2px;
            border-bottom: 1px dotted #ccc;
        }

        .totals-section {
            border-top: 1px dashed #000;
            padding-top: 6px;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .grand-total {
            font-size: 15px;
            font-weight: 900;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 4px 0;
            margin-top: 4px;
        }

        .ticket-footer {
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            margin-top: 8px;
            border-top: 1px dashed #000;
            padding-top: 6px;
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
                width: 78mm !important;
                box-shadow: none;
                padding: 4px;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ طباعة الإيصال</button>
        <button class="btn-back" onclick="window.close()">إغلاق</button>
    </div>

    <div class="thermal-ticket">
        <div class="ticket-header">
            <div class="hospital-name">مستشفى الكفيل التخصصي</div>
            <div class="sub-title">الصيدلية المركزية - فاتورة صرف دواء</div>
        </div>

        <div class="ticket-info">
            <div class="info-row">
                <span>رقم الفاتورة:</span>
                <span style="font-family: monospace; font-weight: 800;">{{ $sale->invoice_number }}</span>
            </div>
            <div class="info-row">
                <span>التاريخ:</span>
                <span>{{ $sale->created_at->format('Y-m-d h:i A') }}</span>
            </div>
            <div class="info-row">
                <span>المريض:</span>
                <span style="font-weight: 700;">{{ $sale->patient_name ?? 'مريض مباشر OTC' }}</span>
            </div>
            @if($sale->insurance_type !== 'none')
                <div class="info-row">
                    <span>جهة التأمين:</span>
                    <span>{{ $sale->insurance_type === 'health_insurance' ? 'هيئة الضمان الصحي' : 'وزارة الداخلية' }}</span>
                </div>
                <div class="info-row">
                    <span>نسبة الاستقطاع:</span>
                    <span>{{ $sale->copay_percentage }}%</span>
                </div>
            @endif
            <div class="info-row">
                <span>مسار الدفع:</span>
                <span>{{ $sale->payment_route === 'pharmacy_cashier' ? 'كاشير الصيدلية' : 'الكاشير المركزي' }}</span>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="text-align: right;">الصنف</th>
                    <th style="text-align: center;">الوحدة</th>
                    <th style="text-align: center;">العدد</th>
                    <th style="text-align: left;">المجموع</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td style="text-align: right; font-weight: 700;">{{ $item->item_name }}</td>
                        <td style="text-align: center;">{{ $item->unit_label }}</td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: left;">{{ number_format($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <div class="total-row">
                <span>المبلغ الإجمالي:</span>
                <span>{{ number_format($sale->total_amount) }} د.ع</span>
            </div>
            @if($sale->insurance_type !== 'none')
                <div class="total-row">
                    <span>حصة جهة الضمان:</span>
                    <span>{{ number_format($sale->insurance_share) }} د.ع</span>
                </div>
            @endif
            <div class="total-row grand-total">
                <span>المطلوب من المريض:</span>
                <span>{{ number_format($sale->patient_share) }} د.ع</span>
            </div>
        </div>

        <div class="ticket-footer">
            <div>الصيدلي: {{ $sale->dispenser->name ?? ($sale->user->name ?? 'صيدلي المناوبة') }}</div>
            <div style="margin-top: 4px;">نتمنى لكم الشفاء العاجل 🌿</div>
            <div style="font-size: 9px; margin-top: 2px;">لا يتم إرجاع الأدوية إلا بنفس يوم الشراء والوصل الأصلي</div>
        </div>
    </div>

    <script>
        // الطباعة التلقائية عند الفتح
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
