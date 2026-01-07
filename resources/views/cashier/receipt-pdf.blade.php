<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال دفع - {{ $payment->receipt_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        
        * {
            font-family: 'Arial', sans-serif;
        }
        
        body {
            direction: rtl;
            text-align: right;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #28a745;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #28a745;
            margin: 0;
            font-size: 28px;
        }
        
        .header p {
            margin: 5px 0;
            color: #666;
        }
        
        .receipt-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .receipt-info table {
            width: 100%;
        }
        
        .receipt-info td {
            padding: 5px 10px;
        }
        
        .section-title {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            margin: 20px 0 10px 0;
            border-radius: 3px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .info-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .info-table td:first-child {
            color: #666;
            width: 150px;
        }
        
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .payment-table th,
        .payment-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        
        .payment-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 18px;
        }
        
        .total-amount {
            color: #28a745;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
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
        <h1>مستشفى النظام الطبي</h1>
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
            <td><strong>{{ $payment->patient->user->name }}</strong></td>
        </tr>
        <tr>
            <td>الرقم الوطني:</td>
            <td><strong>{{ $payment->patient->national_id ?? 'غير محدد' }}</strong></td>
        </tr>
        <tr>
            <td>رقم الهاتف:</td>
            <td><strong>{{ $payment->patient->user->phone ?? 'غير محدد' }}</strong></td>
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
                    <strong>تم الاستلام بواسطة:</strong><br>
                    {{ $payment->cashier->name }}
                </td>
                <td style="text-align: left;">
                    <strong>التوقيع:</strong><br>
                    <div class="signature-line"></div>
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
