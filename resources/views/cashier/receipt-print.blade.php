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
                size: A5;
                margin: 0.5cm;
            }
            
            body {
                margin: 0;
                padding: 10px;
            }
        }
        
        * {
            font-family: 'Courier New', 'Arial', monospace;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            direction: rtl;
            text-align: right;
            background-color: white;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 15px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 20px;
            margin: 5px 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .header .subtitle {
            font-size: 11px;
            color: #333;
            margin: 3px 0;
        }
        
        .receipt-number-box {
            text-align: center;
            padding: 8px;
            border: 2px dashed #000;
            margin: 10px 0;
            background: #f9f9f9;
        }
        
        .receipt-number {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .receipt-date {
            font-size: 10px;
            margin-top: 3px;
        }
        
        .section {
            margin: 12px 0;
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 11px;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 80px;
        }
        
        .info-value {
            flex: 1;
            text-align: left;
        }
        
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
        }
        
        .services-table th {
            background-color: #000;
            color: white;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }
        
        .services-table td {
            border: 1px solid #ddd;
            padding: 5px 4px;
            text-align: center;
        }
        
        .services-table td:nth-child(2) {
            text-align: right;
        }
        
        .total-row {
            background-color: #000;
            color: white;
            font-weight: bold;
        }
        
        .total-amount {
            font-size: 14px;
        }
        
        .payment-info {
            margin: 10px 0;
            padding: 8px;
            background: #f5f5f5;
            border: 1px dashed #000;
            text-align: center;
        }
        
        .payment-method {
            font-weight: bold;
            font-size: 13px;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #000;
            font-size: 10px;
        }
        
        .signature-box {
            display: inline-block;
            margin: 10px 20px;
            text-align: center;
        }
        
        .signature-line {
            width: 120px;
            border-bottom: 1px solid #000;
            margin: 20px auto 5px;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border: 1px solid #000;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-5 {
            margin-top: 5px;
        }
        
        .mb-5 {
            margin-bottom: 5px;
        }
        
        .print-button {
            background-color: #000;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 3px;
            font-size: 14px;
            cursor: pointer;
            margin: 5px;
        }
        
        .print-button:hover {
            background-color: #333;
        }
        
        .watermark {
            text-align: center;
            font-size: 9px;
            color: #999;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Buttons -->
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button class="print-button" onclick="window.print()">
                طباعة الإيصال
            </button>
            <button class="print-button" onclick="window.history.back()" style="background-color: #666;">
                العودة
            </button>
        </div>

        <!-- Header -->
        <div class="header">
            <h1>مستشفى النظام الطبي</h1>
            <div class="subtitle">إيصال دفع رسوم الخدمات الطبية</div>
            <div class="subtitle">Medical Services Payment Receipt</div>
        </div>

        <!-- Receipt Number -->
        <div class="receipt-number-box">
            <div class="receipt-number">رقم الإيصال: {{ $payment->receipt_number }}</div>
            <div class="receipt-date">{{ $payment->paid_at->format('Y-m-d | H:i') }}</div>
        </div>

        <!-- Patient Info -->
        <div class="section">
            <div class="section-title">معلومات المريض</div>
            <div class="info-row">
                <span class="info-label">الاسم:</span>
                <span class="info-value">{{ $payment->patient->user->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الرقم الوطني:</span>
                <span class="info-value">{{ $payment->patient->national_id ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">رقم الهاتف:</span>
                <span class="info-value">{{ $payment->patient->user->phone ?? '-' }}</span>
            </div>
        </div>

        @if($payment->appointment)
        <!-- Appointment Info -->
        <div class="section">
            <div class="section-title">تفاصيل الموعد</div>
            <div class="info-row">
                <span class="info-label">رقم الموعد:</span>
                <span class="info-value">#{{ $payment->appointment->id }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الطبيب:</span>
                <span class="info-value">د. {{ $payment->appointment->doctor->user->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">القسم:</span>
                <span class="info-value">{{ $payment->appointment->department->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">التاريخ:</span>
                <span class="info-value">{{ $payment->appointment->appointment_date->format('Y-m-d H:i') }}</span>
            </div>
        </div>
        @endif

        @if($payment->request)
        <!-- Request Info -->
        <div class="section">
            <div class="section-title">تفاصيل الطلب الطبي</div>
            <div class="info-row">
                <span class="info-label">رقم الطلب:</span>
                <span class="info-value">#{{ $payment->request->id }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">النوع:</span>
                <span class="info-value">
                    <span class="badge">
                        @if($payment->request->type === 'lab')
                            تحاليل مخبرية
                        @elseif($payment->request->type === 'radiology')
                            فحوصات أشعة
                        @else
                            {{ $payment->request->type }}
                        @endif
                    </span>
                </span>
            </div>
            @if($payment->request->visit && $payment->request->visit->doctor)
            <div class="info-row">
                <span class="info-label">الطبيب:</span>
                <span class="info-value">د. {{ $payment->request->visit->doctor->user->name }}</span>
            </div>
            @endif
        </div>

        @php
            $details = is_string($payment->request->details) ? json_decode($payment->request->details, true) : $payment->request->details;
        @endphp

        @if($payment->request->type === 'lab' && isset($details['lab_test_ids']))
        <!-- Lab Tests Table -->
        <table class="services-table">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>اسم التحليل</th>
                    <th style="width: 80px;">الرمز</th>
                    <th style="width: 100px;">السعر (IQD)</th>
                </tr>
            </thead>
            <tbody>
                @php $totalAmount = 0; @endphp
                @foreach($details['lab_test_ids'] as $index => $testId)
                    @php
                        $test = \App\Models\LabTest::find($testId);
                        if($test) {
                            $price = $test->price ?? 0;
                            $totalAmount += $price;
                        }
                    @endphp
                    @if($test)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $test->name }}</td>
                        <td>{{ $test->code }}</td>
                        <td>{{ number_format($price, 0) }}</td>
                    </tr>
                    @endif
                @endforeach
                <tr class="total-row">
                    <td colspan="3">الإجمالي</td>
                    <td class="total-amount">{{ number_format($totalAmount, 0) }}</td>
                </tr>
            </tbody>
        </table>
        @elseif($payment->request->type === 'radiology' && isset($details['radiology_type_ids']))
        <!-- Radiology Tests Table -->
        <table class="services-table">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>نوع الأشعة</th>
                    <th>الوصف</th>
                    <th style="width: 100px;">السعر (IQD)</th>
                </tr>
            </thead>
            <tbody>
                @php $totalAmount = 0; @endphp
                @foreach($details['radiology_type_ids'] as $index => $typeId)
                    @php
                        $radiologyType = \App\Models\RadiologyType::find($typeId);
                        if($radiologyType) {
                            $price = $radiologyType->base_price ?? 0;
                            $totalAmount += $price;
                        }
                    @endphp
                    @if($radiologyType)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $radiologyType->name }}</td>
                        <td>{{ $radiologyType->description ?? '-' }}</td>
                        <td>{{ number_format($price, 0) }}</td>
                    </tr>
                    @endif
                @endforeach
                <tr class="total-row">
                    <td colspan="3">الإجمالي</td>
                    <td class="total-amount">{{ number_format($totalAmount, 0) }}</td>
                </tr>
            </tbody>
        </table>
        @endif
        @endif

        <div class="divider"></div>

        <!-- Payment Summary -->
        <div class="payment-info">
            <div class="mb-5">طريقة الدفع: <span class="payment-method">{{ $payment->payment_method_name }}</span></div>
            <div style="font-size: 16px; font-weight: bold;">المبلغ المدفوع: {{ number_format($payment->amount, 0) }} IQD</div>
        </div>

        @if($payment->notes)
        <div class="section">
            <div class="section-title">ملاحظات</div>
            <div style="padding: 5px;">{{ $payment->notes }}</div>
        </div>
        @endif

        <div class="divider"></div>

        <!-- Footer Signatures -->
        <div class="text-center">
            <div class="signature-box">
                <div style="font-size: 10px; margin-bottom: 5px;">الكاشير</div>
                <div>{{ $payment->cashier->name }}</div>
                <div class="signature-line"></div>
                <div style="font-size: 9px;">التوقيع</div>
            </div>
            <div class="signature-box">
                <div style="font-size: 10px; margin-bottom: 5px;">المريض</div>
                <div>{{ $payment->patient->user->name }}</div>
                <div class="signature-line"></div>
                <div style="font-size: 9px;">التوقيع</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>هذا إيصال رسمي صادر من نظام إدارة المستشفى</div>
            <div class="mt-5">للاستفسارات: 0790-XXX-XXXX | info@hospital.com</div>
        </div>

        <div class="watermark">طبع في: {{ now()->format('Y-m-d H:i:s') }}</div>
    </div>

    <script>
        // Auto print option
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
