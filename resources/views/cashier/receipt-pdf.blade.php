<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال دفع - {{ $payment->receipt_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 0.5cm;
        }
        
        * {
            font-family: 'Arial', sans-serif;
            font-weight: bold !important;
        }
        
        body {
            direction: rtl;
            text-align: right;
            font-size: 16px;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #28a745;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            color: #28a745;
            margin: 0;
            font-size: 32px;
        }
        
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 18px;
        }
        
        .receipt-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .receipt-info table {
            width: 100%;
        }
        
        .receipt-info td {
            padding: 3px 10px;
        }
        
        .section-title {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            margin: 10px 0 5px 0;
            border-radius: 3px;
            font-size: 18px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .info-table td {
            padding: 5px;
            border-bottom: 1px solid #ddd;
            font-size: 16px;
        }
        
        .info-table td:first-child {
            color: #555;
            width: 150px;
        }
        
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .payment-table th,
        .payment-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            font-size: 16px;
        }
        
        .payment-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 20px;
        }
        
        .total-amount {
            color: #28a745;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #ddd;
        }
        
        .signature {
            margin-top: 30px;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            width: 200px;
            display: inline-block;
            margin: 0 10px;
        }
        
        .notes {
            background-color: #e7f3ff;
            padding: 10px;
            border-right: 4px solid #0056b3;
            margin: 20px 0;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-success {
            color: #28a745;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .badge-primary {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>مستشفى الكفاءات الأهلي</h1>
        <p>إيصال دفع رسوم الخدمات الطبية</p>
    </div>

    <!-- Receipt Info -->
    <div class="receipt-info">
        <table>
            <tr>
                <td><strong>رقم الإيصال:</strong> <span class="text-success">{{ $payment->receipt_number }}</span></td>
                <td style="text-align: left;"><strong>التاريخ:</strong> {{ $payment->paid_at->format('Y-m-d H:i') }}</td>
            </tr>
        </table>
    </div>

    <!-- Patient Info -->
    <div class="section-title">معلومات المريض</div>
    <table class="info-table">
        <tr>
            <td>الاسم:</td>
            <td><strong>{{ optional($payment->patient)->user->name ?? 'غير محدد' }}</strong></td>
        </tr>
        <tr>
            <td>الرقم الوطني:</td>
            <td><strong>{{ optional($payment->patient)->national_id ?? 'غير محدد' }}</strong></td>
        </tr>
        <tr>
            <td>رقم الهاتف:</td>
            <td><strong>{{ optional($payment->patient)->user->phone ?? 'غير محدد' }}</strong></td>
        </tr>
    </table>

    @if($payment->appointment)
    <!-- Appointment Info -->
    <div class="section-title">تفاصيل الموعد</div>
    <table class="info-table">
        <tr>
            <td>رقم الموعد:</td>
            <td><strong>#{{ $payment->appointment->id }}</strong></td>
        </tr>
        <tr>
            <td>تاريخ الموعد:</td>
            <td><strong>{{ $payment->appointment->appointment_date->format('Y-m-d H:i') }}</strong></td>
        </tr>
        <tr>
            <td>الطبيب:</td>
            <td><strong>د. {{ $payment->appointment->doctor->user->name }}</strong></td>
        </tr>
        <tr>
            <td>القسم:</td>
            <td><strong>{{ $payment->appointment->department->name }}</strong></td>
        </tr>
    </table>
    @endif

    <!-- Payment Details -->
    <div class="section-title">تفاصيل الدفع</div>
    <table class="payment-table">
        <thead>
            <tr>
                <th>الوصف</th>
                <th>نوع الدفع</th>
                <th>طريقة الدفع</th>
                <th>المبلغ (IQD)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $payment->description }}</td>
                <td><span class="badge badge-info">{{ $payment->payment_type_name }}</span></td>
                <td><span class="badge badge-primary">{{ $payment->payment_method_name }}</span></td>
                <td><strong>{{ number_format($payment->amount, 2) }}</strong></td>
            </tr>
            <tr class="total-row">
                <td colspan="3" style="text-align: left;">الإجمالي:</td>
                <td class="total-amount">{{ number_format($payment->amount, 2) }} IQD</td>
            </tr>
        </tbody>
    </table>

    @if($payment->notes)
    <!-- Notes -->
    <div class="notes">
        <strong>ملاحظات:</strong> {{ $payment->notes }}
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td>
                    <strong>تم الاستلام بواسطة:</strong> {{ $payment->cashier->name }}
                </td>
            </tr>
        </table>
    </div>

    <div class="text-center" style="margin-top: 40px; font-size: 12px; color: #666;">
        <p>هذا إيصال رسمي صادر من نظام إدارة المستشفى</p>
        <p>للاستفسارات يرجى الاتصال على: 0790-XXX-XXXX</p>
        <p style="margin-top: 20px;">تم الطباعة في: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
