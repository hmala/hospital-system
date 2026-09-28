<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وصل قبض مركز العيون - {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .receipt-container {
            max-width: 400px;
            margin: auto;
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #bbb;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }
        .hospital-name {
            font-size: 20px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 4px;
        }
        .center-name {
            font-size: 16px;
            font-weight: bold;
            color: #198754;
            margin-bottom: 6px;
        }
        .receipt-title {
            font-size: 14px;
            font-weight: bold;
            background: #eef2f7;
            padding: 4px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 13px;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        .meta-label {
            color: #666;
            margin-bottom: 2px;
        }
        .meta-val {
            font-weight: bold;
            color: #111;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-bottom: 16px;
        }
        th, td {
            padding: 8px 4px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f8f9fa;
            color: #555;
        }
        .totals-table td {
            border: none;
            padding: 4px 0;
        }
        .total-highlight {
            font-size: 16px;
            font-weight: bold;
            color: #198754;
            border-top: 2px dashed #aaa;
            padding-top: 8px !important;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #777;
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #eee;
        }
        .btn-print {
            display: block;
            width: 100%;
            padding: 10px;
            background: #0d6efd;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 6px;
            margin-top: 16px;
            cursor: pointer;
            border: none;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="header">
        <div class="hospital-name">مستشفى النور التخصصي</div>
        <div class="center-name">👁️ مركز طب وجراحة العيون التخصصي</div>
        <div class="receipt-title">وصل قبض رسمي (Eye Cashier Receipt)</div>
    </div>

    <div class="meta-grid">
        <div>
            <div class="meta-label">رقم الفاتورة:</div>
            <div class="meta-val" style="font-family: monospace;">{{ $invoice->invoice_number }}</div>
        </div>
        <div>
            <div class="meta-label">التاريخ والوقت:</div>
            <div class="meta-val">{{ $invoice->created_at->format('Y-m-d h:i A') }}</div>
        </div>
        <div>
            <div class="meta-label">اسم المريض:</div>
            <div class="meta-val">{{ $invoice->patient->name }}</div>
        </div>
        <div>
            <div class="meta-label">الطبيب المعالج:</div>
            <div class="meta-val">{{ $invoice->appointment->doctor->user->name ?? 'طبيب العيون' }}</div>
        </div>
        <div>
            <div class="meta-label">فئة الدفع:</div>
            <div class="meta-val">{{ $invoice->insurance_type == 'cash' ? 'نقدي' : 'ضمان صحي' }}</div>
        </div>
        <div>
            <div class="meta-label">طريقة السداد:</div>
            <div class="meta-val">{{ $invoice->payment_method }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>البيان والخدمة</th>
                <th style="text-align: center;">العدد</th>
                <th style="text-align: left;">المبلغ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: left;">{{ number_format($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>الإجمالي الكلي:</td>
            <td style="text-align: left; font-weight: bold;">{{ number_format($invoice->total_amount) }} د.ع</td>
        </tr>
        @if($invoice->insurance_share > 0)
        <tr>
            <td style="color: #666;">مساهمة الضمان الصحي:</td>
            <td style="text-align: left; color: #198754;">{{ number_format($invoice->insurance_share) }} د.ع</td>
        </tr>
        @endif
        <tr class="total-highlight">
            <td>المدفوع نقداً:</td>
            <td style="text-align: left;">{{ number_format($invoice->paid_amount) }} د.ع</td>
        </tr>
    </table>

    <div class="footer">
        <div>المستلم (الكاشير): <strong>{{ $invoice->cashier->name ?? 'كاشير العيون' }}</strong></div>
        <div style="margin-top: 4px;">نتمنى لكم دوام الصحة والعافية</div>
    </div>

    <button onclick="window.print()" class="btn-print">🖨️ طباعة الوصل الفوري</button>
</div>

<script>
    window.onload = function() {
        // طباعة تلقائية عند فتح الصفحة
        setTimeout(() => { window.print(); }, 500);
    };
</script>

</body>
</html>
