<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال دفع - {{ $payment->receipt_number }}</title>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            
            @page {
                size: A4;
                margin: 1cm;
            }
        }
        
        * {
            font-family: 'Arial', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            box-sizing: border-box;
        }
        
        body {
            direction: rtl;
            text-align: right;
            margin: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
            font-size: 32px;
        }
        
        .header p {
            margin: 10px 0;
            color: #666;
            font-size: 16px;
        }
        
        .receipt-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
        }
        
        .receipt-info div {
            flex: 1;
        }
        
        .receipt-number {
            font-size: 24px;
            color: #28a745;
            font-weight: bold;
        }
        
        .section-title {
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            margin: 25px 0 15px 0;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .info-item {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-item .label {
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }
        
        .info-item .value {
            font-weight: bold;
            font-size: 15px;
        }
        
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }
        
        .payment-table th,
        .payment-table td {
            border: 1px solid #ddd;
            padding: 15px;
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
            font-size: 24px;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .badge-primary {
            background-color: #007bff;
            color: white;
        }
        
        .notes {
            background-color: #e7f3ff;
            padding: 15px;
            border-right: 4px solid #0056b3;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 2px solid #000;
            width: 200px;
            margin: 10px 0;
            display: inline-block;
        }
        
        .bottom-footer {
            text-align: center;
            margin-top: 40px;
            font-size: 13px;
            color: #666;
        }
        
        .print-button {
            background-color: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .print-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Button -->
        <div class="no-print" style="text-align: center;">
            <button class="print-button" onclick="window.print()">
                🖨️ طباعة الإيصال
            </button>
            <button class="print-button" onclick="window.history.back()" style="background-color: #6c757d;">
                ◀️ العودة
            </button>
        </div>

        <!-- Header -->
        <div class="header">
            <h1>🏥 مستشفى النظام الطبي</h1>
            <p>إيصال دفع رسوم الخدمات الطبية</p>
        </div>

        <!-- Receipt Info -->
        <div class="receipt-info">
            <div>
                <div style="color: #666; font-size: 14px;">رقم الإيصال:</div>
                <div class="receipt-number">{{ $payment->receipt_number }}</div>
            </div>
            <div style="text-align: left;">
                <div style="color: #666; font-size: 14px;">تاريخ ووقت الدفع:</div>
                <div style="font-weight: bold; font-size: 16px;">{{ $payment->paid_at->format('Y-m-d H:i') }}</div>
            </div>
        </div>

        <!-- Patient Info -->
        <div class="section-title">👤 معلومات المريض</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="label">الاسم:</div>
                <div class="value">{{ $payment->patient->user->name }}</div>
            </div>
            <div class="info-item">
                <div class="label">الرقم الوطني:</div>
                <div class="value">{{ $payment->patient->national_id ?? 'غير محدد' }}</div>
            </div>
            <div class="info-item">
                <div class="label">رقم الهاتف:</div>
                <div class="value">{{ $payment->patient->user->phone ?? 'غير محدد' }}</div>
            </div>
            <div class="info-item">
                <div class="label">البريد الإلكتروني:</div>
                <div class="value">{{ $payment->patient->user->email ?? 'غير محدد' }}</div>
            </div>
        </div>

        @if($payment->appointment)
        <!-- Appointment Info -->
        <div class="section-title">📅 تفاصيل الموعد</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="label">رقم الموعد:</div>
                <div class="value">#{{ $payment->appointment->id }}</div>
            </div>
            <div class="info-item">
                <div class="label">تاريخ الموعد:</div>
                <div class="value">{{ $payment->appointment->appointment_date->format('Y-m-d H:i') }}</div>
            </div>
            <div class="info-item">
                <div class="label">الطبيب:</div>
                <div class="value">د. {{ $payment->appointment->doctor->user->name }}</div>
            </div>
            <div class="info-item">
                <div class="label">القسم:</div>
                <div class="value">{{ $payment->appointment->department->name }}</div>
            </div>
        </div>
        @endif

        <!-- Payment Details -->
        <div class="section-title">💰 تفاصيل الدفع</div>
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
                    <td style="font-weight: bold;">{{ number_format($payment->amount, 2) }}</td>
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
            <strong>📝 ملاحظات:</strong> {{ $payment->notes }}
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div>
                <div style="color: #666; margin-bottom: 5px;">تم الاستلام بواسطة:</div>
                <div style="font-weight: bold;">{{ $payment->cashier->name }}</div>
            </div>
            <div class="signature-box">
                <div style="color: #666; margin-bottom: 5px;">التوقيع:</div>
                <div class="signature-line"></div>
            </div>
        </div>

        <!-- Bottom Footer -->
        <div class="bottom-footer">
            <p>✅ هذا إيصال رسمي صادر من نظام إدارة المستشفى</p>
            <p>📞 للاستفسارات يرجى الاتصال على: 0790-XXX-XXXX</p>
            <p style="margin-top: 20px; color: #999;">تم الطباعة في: {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    <script>
        // Auto-print on page load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
