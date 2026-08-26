@php
    $isConsultation = $payment->appointment_id 
        || $payment->payment_type === 'appointment' 
        || $payment->payment_type === 'consultation' 
        || ($payment->appointment !== null)
        || request('format') === 'thermal'
        || (str_contains($payment->description ?? '', 'استشارية') && !$payment->emergency_id && !$payment->surgery_id);
@endphp

@if($isConsultation)
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال استشارية - {{ $payment->receipt_number }}</title>
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

        /* Screen action buttons */
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

        /* Header box */
        .header-box {
            border: 1px solid #000000;
            border-radius: 4px;
            padding: 6px 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .header-logo {
            width: 36px;
            height: 36px;
            object-fit: contain;
        }

        .header-titles {
            flex: 1;
            text-align: center;
        }

        .header-titles .ar-title {
            font-size: 14.5px;
            font-weight: 900;
            line-height: 1.2;
        }

        .header-titles .en-title {
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        /* Meta details */
        .meta-group {
            margin-bottom: 6px;
            font-size: 13px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .meta-label {
            font-weight: 800;
            font-size: 13px;
        }

        .meta-val-number {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-align: center;
            flex: 1;
        }

        .meta-val-date {
            font-size: 14px;
            font-weight: 800;
            text-align: center;
            flex: 1;
        }

        .patient-doctor-row {
            margin-top: 6px;
            margin-bottom: 10px;
            font-size: 12.5px;
            font-weight: 800;
            line-height: 1.5;
        }

        /* Services table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
            margin-bottom: 8px;
            font-size: 12.5px;
        }

        .items-table th, 
        .items-table td {
            border: 1px solid #000000;
            padding: 4px 6px;
        }

        .items-table th {
            font-weight: 900;
            text-align: center;
            background-color: #ffffff;
        }

        .items-table .col-service {
            text-align: right;
            font-weight: 800;
        }

        .items-table .col-qty {
            text-align: center;
            width: 40px;
            font-weight: 800;
        }

        .items-table .col-price {
            text-align: center;
            width: 75px;
            font-weight: 800;
        }

        .items-table .total-row td {
            font-weight: 900;
        }

        .items-table .total-label {
            text-align: center;
            font-size: 13px;
            font-weight: 900;
        }

        .items-table .total-val {
            text-align: center;
            font-size: 13px;
            font-weight: 900;
        }

        /* Bottom currency / payment box */
        .footer-amount-box {
            border: 1px solid #000000;
            border-collapse: collapse;
            width: 100%;
            margin-top: 4px;
            font-size: 12.5px;
        }

        .footer-amount-box td {
            border: 1px solid #000000;
            padding: 4px 8px;
            font-weight: 800;
        }

        .footer-amount-box .box-label {
            text-align: right;
            width: 65%;
        }

        .footer-amount-box .box-val {
            text-align: center;
            width: 35%;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                background: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .thermal-ticket {
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 1mm 2mm !important;
                margin: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ طباعة الإيصال</button>
        <button class="btn-back" onclick="window.history.back()">⬅️ العودة</button>
    </div>

    <div class="thermal-ticket">
        <!-- Header Box with Logo & Name -->
        <div class="header-box">
            <img src="{{ asset('images/لوغو.png') }}" class="header-logo" alt="Logo" onerror="this.src='{{ asset('images/hospital-logo.svg') }}';">
            <div class="header-titles">
                <div class="ar-title">مستشفى الكفاءات الاهلي</div>
                <div class="en-title">Al-Kafaat Hospital</div>
            </div>
            <div style="width: 36px;"></div>
        </div>

        @php
            // Extract numeric receipt number or ID
            $rawNumber = preg_replace('/[^0-9]/', '', $payment->receipt_number);
            $receiptNum = $rawNumber ? substr($rawNumber, -5) : $payment->id;
            
            // Patient Name
            $patientName = '-';
            if ($payment->patient && $payment->patient->user) {
                $patientName = $payment->patient->user->name;
            } elseif ($payment->appointment && $payment->appointment->patient && $payment->appointment->patient->user) {
                $patientName = $payment->appointment->patient->user->name;
            } elseif ($payment->emergency && $payment->emergency->emergencyPatient) {
                $patientName = $payment->emergency->emergencyPatient->name;
            }

            // Doctor Name
            $doctorName = '';
            if ($payment->appointment && $payment->appointment->doctor && $payment->appointment->doctor->user) {
                $doctorName = $payment->appointment->doctor->user->name;
            } elseif ($payment->request && $payment->request->visit && $payment->request->visit->doctor && $payment->request->visit->doctor->user) {
                $doctorName = $payment->request->visit->doctor->user->name;
            }

            $dateFormatted = ($payment->paid_at ?? now())->format('Y/m/d');
            $consultFee = $payment->amount;
        @endphp

        <!-- Metadata Section -->
        <div class="meta-group">
            <div class="meta-row">
                <span class="meta-val-number">{{ $receiptNum }}</span>
                <span class="meta-label">الرقم</span>
            </div>
            <div class="meta-row">
                <span class="meta-val-date">{{ $dateFormatted }}</span>
                <span class="meta-label">التاريخ</span>
            </div>
        </div>

        <!-- Patient and Doctor Line -->
        <div class="patient-doctor-row">
            اسم المريض <strong>{{ $patientName }}</strong>
            @if($doctorName)
                - <span>د. {{ $doctorName }}</span>
            @endif
        </div>

        <!-- Services Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-service">الخدمة الطبية</th>
                    <th class="col-qty">عدد</th>
                    <th class="col-price">السعر</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="col-service">اجور استشارية</td>
                    <td class="col-qty">1</td>
                    <td class="col-price">{{ number_format($consultFee, 0) }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" class="total-label">المجموع</td>
                    <td class="total-val">{{ number_format($consultFee, 0) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Bottom Payment / Dollar Box -->
        <table class="footer-amount-box">
            <tr>
                <td class="box-label">مجموع الدولار المقبوض</td>
                <td class="box-val"></td>
            </tr>
        </table>
    </div>

    <script>
        // Auto print trigger
        // window.onload = function() { window.print(); };
    </script>
</body>
</html>
@else
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال دفع - {{ $payment->receipt_number }}</title>
    <!-- Google Fonts Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* General resets & typography */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', 'Arial', sans-serif;
        }
        
        body {
            direction: rtl;
            text-align: right;
            background-color: #f3f4f6;
            color: #2c3e50;
            font-size: 14px;
            line-height: 1.5;
            padding: 20px 10px;
        }
        
        /* Container A5 format */
        .container {
            max-width: 148mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            position: relative;
        }
        
        /* Buttons for screen view */
        .no-print {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .print-button {
            background-color: #10b981;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 2px 5px rgba(16, 185, 129, 0.2);
            font-family: 'Cairo', sans-serif;
        }
        
        .print-button:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }
        
        .print-button-back {
            background-color: #6b7280;
            box-shadow: 0 2px 5px rgba(107, 114, 128, 0.2);
        }
        
        .print-button-back:hover {
            background-color: #4b5563;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 2px dashed #10b981;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 22px;
            color: #065f46;
            font-weight: 800;
            margin-bottom: 3px;
        }
        
        .header .subtitle {
            font-size: 12px;
            color: #4b5563;
            font-weight: 600;
        }
        
        /* Receipt metadata box */
        .receipt-number-box {
            text-align: center;
            padding: 10px;
            border: 1px solid #10b981;
            background-color: #f0fdf4;
            border-radius: 6px;
            margin: 15px 0;
        }
        
        .receipt-number {
            font-size: 16px;
            font-weight: 800;
            color: #065f46;
        }
        
        .receipt-date {
            font-size: 12px;
            color: #4b5563;
            margin-top: 2px;
            font-weight: 600;
        }
        
        /* Sections */
        .section {
            margin: 15px 0;
            padding: 12px;
            border: 1px solid #f3f4f6;
            background-color: #fafafa;
            border-radius: 6px;
        }
        
        .section-title {
            font-weight: 800;
            font-size: 13px;
            color: #065f46;
            border-right: 3px solid #10b981;
            padding-right: 8px;
            margin-bottom: 10px;
        }
        
        /* Key-value rows */
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 13px;
            border-bottom: 1px dotted #f1f5f9;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #6b7280;
        }
        
        .info-value {
            font-weight: 700;
            color: #1f2937;
            text-align: left;
        }
        
        /* Services table */
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 13px;
        }
        
        .services-table th {
            background-color: #f3f4f6;
            color: #374151;
            padding: 8px 6px;
            text-align: center;
            font-weight: 800;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .services-table td {
            border-bottom: 1px solid #f3f4f6;
            padding: 8px 6px;
            text-align: center;
            color: #4b5563;
            font-weight: 600;
        }
        
        .services-table td:nth-child(2) {
            text-align: right;
            font-weight: 700;
            color: #1f2937;
        }
        
        .total-row {
            background-color: #f0fdf4;
            color: #065f46;
            font-weight: 800;
        }
        
        .total-row td {
            border-top: 2px solid #10b981;
            color: #065f46 !important;
            font-weight: 800 !important;
        }
        
        .total-amount {
            font-size: 14px;
        }
        
        /* Payment summary box */
        .payment-info {
            margin: 15px 0;
            padding: 12px;
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 6px;
            text-align: center;
        }
        
        .payment-method {
            font-weight: 800;
            color: #047857;
        }
        
        .divider {
            border-top: 1px dashed #e5e7eb;
            margin: 15px 0;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #f3f4f6;
            font-size: 11px;
            color: #6b7280;
            font-weight: 600;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border: 1px solid #10b981;
            background-color: #f0fdf4;
            color: #065f46;
            font-size: 10px;
            font-weight: 700;
            border-radius: 4px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .watermark {
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            margin-top: 12px;
            font-weight: 600;
        }

        /* Print styles */
        @media print {
            * {
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                text-shadow: none !important;
                box-shadow: none !important;
            }
            
            html, body {
                height: 99%;
                overflow: hidden;
                background-color: white !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 11.5px;
                line-height: 1.35;
                font-weight: 700;
            }
            
            h1, h2, h3, h4, h5, h6, .section-title, .receipt-number, .payment-method, strong {
                font-weight: 800 !important;
            }
            
            .container {
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 5px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            @page {
                size: A5;
                margin: 0.4cm;
            }

            .section {
                margin: 6px 0 !important;
                padding: 8px !important;
                border: 1px solid #000000 !important;
                background-color: transparent !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .section-title {
                margin-bottom: 6px !important;
                border-right: 3px solid #000000 !important;
            }

            .services-table {
                margin: 5px 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .services-table th {
                background-color: #f3f4f6 !important;
                border-bottom: 2px solid #000000 !important;
                padding: 5px 4px !important;
            }

            .services-table td {
                padding: 5px 4px !important;
                border-bottom: 1px solid #000000 !important;
                font-weight: 700 !important;
            }

            .total-row {
                background-color: #f3f4f6 !important;
            }
            
            .total-row td {
                border-top: 2px solid #000000 !important;
                border-bottom: 2px double #000000 !important;
            }
            
            .payment-info {
                margin: 8px 0 !important;
                padding: 8px !important;
                background-color: #f0fdf4 !important;
                border: 1px solid #000000 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .divider {
                border-top: 1px dashed #000000 !important;
                margin: 8px 0 !important;
            }
            
            .footer {
                margin-top: 10px !important;
                padding-top: 5px !important;
                border-top: 1px solid #000000 !important;
            }
            
            .watermark {
                margin-top: 6px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Buttons -->
        <div class="no-print">
            <button class="print-button" onclick="window.print()">
                طباعة الإيصال
            </button>
            <button class="print-button print-button-back" onclick="window.history.back()">
                العودة
            </button>
        </div>

        <!-- Header -->
        <div class="header">
            <h1>مستشفى الكفاءات الأهلي</h1>
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
                @php
                    $p = $payment->patient;
                    if(!$p && $payment->emergency) {
                        $ep = $payment->emergency->emergencyPatient;
                        $pname = $ep ? $ep->name : '-';
                    } else {
                        $pname = $p ? ($p->user->name ?? '-') : '-';
                    }
                @endphp
                <span class="info-value">{{ $pname }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الرقم الوطني:</span>
                @php
                    $pid = $p ? ($p->national_id ?? '-') : '-';
                    if(empty($pid) && isset($ep)) { $pid = '(طوارئ)'; }
                @endphp
                <span class="info-value">{{ $pid }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">رقم الهاتف:</span>
                @php
                    $pphone = $p ? ($p->user->phone ?? '-') : '-';
                    if($pphone === '-' && isset($ep)) { $pphone = $ep->phone ?? '-'; }
                @endphp
                <span class="info-value">{{ $pphone }}</span>
            </div>
        </div>

        @php
            $lineItems = [];
            if($payment->emergency) {
                // 1. الخدمات التمريضية المدفوعة في هذا السند
                $paidServices = \DB::table('emergency_emergency_service')
                    ->join('emergency_services', 'emergency_emergency_service.emergency_service_id', '=', 'emergency_services.id')
                    ->where('emergency_emergency_service.payment_id', $payment->id)
                    ->select('emergency_services.name', 'emergency_services.price')
                    ->get();
                foreach ($paidServices as $svc) {
                    $lineItems[] = ['الخدمة' => 'خدمة طوارئ: ' . $svc->name, 'السعر' => $svc->price ?? 0];
                }

                // 2. التحاليل الطبية المدفوعة في هذا السند
                $labRequestIds = \DB::table('emergency_lab_requests')
                    ->where('payment_id', $payment->id)
                    ->pluck('id');
                if ($labRequestIds->isNotEmpty()) {
                    $labTests = \DB::table('emergency_lab_request_tests')
                        ->join('lab_tests', 'emergency_lab_request_tests.lab_test_id', '=', 'lab_tests.id')
                        ->whereIn('emergency_lab_request_tests.emergency_lab_request_id', $labRequestIds)
                        ->select('lab_tests.name', 'lab_tests.price')
                        ->get();
                    foreach ($labTests as $test) {
                        $lineItems[] = ['الخدمة' => 'تحليل طوارئ: ' . $test->name, 'السعر' => $test->price ?? 0];
                    }
                }

                // 3. الأشعة المدفوعة في هذا السند
                $radiologyRequestIds = \DB::table('emergency_radiology_requests')
                    ->where('payment_id', $payment->id)
                    ->pluck('id');
                if ($radiologyRequestIds->isNotEmpty()) {
                    $radiologyTests = \DB::table('emergency_radiology_request_types')
                        ->join('radiology_types', 'emergency_radiology_request_types.radiology_type_id', '=', 'radiology_types.id')
                        ->whereIn('emergency_radiology_request_types.emergency_radiology_request_id', $radiologyRequestIds)
                        ->select('radiology_types.name', 'radiology_types.base_price')
                        ->get();
                    foreach ($radiologyTests as $rad) {
                        $lineItems[] = ['الخدمة' => 'أشعة طوارئ: ' . $rad->name, 'السعر' => $rad->base_price ?? 0];
                    }
                }

                // 4. أجور متابعة الطبيب المدفوعة في هذا السند
                if ($payment->emergency->follow_up_payment_id == $payment->id && $payment->emergency->doctor_follow_up_fee > 0) {
                    $lineItems[] = ['الخدمة' => 'متابعة طبيب (طوارئ)', 'السعر' => $payment->emergency->doctor_follow_up_fee];
                }
            }
            if($payment->appointment) {
                $consultFee = $payment->appointment->consultation_fee ?? 0;
                $lineItems[] = ['الخدمة'=>'رسوم كشف العيادة الاستشارية','السعر'=>$consultFee];
            }
            if($payment->request) {
                $details = is_string($payment->request->details) ? json_decode($payment->request->details, true) : $payment->request->details;
                if($payment->request->type==='lab' && isset($details['lab_test_ids'])) {
                    foreach($details['lab_test_ids'] as $testId) {
                        $test = \App\Models\LabTest::find($testId);
                        if($test) {
                            $lineItems[] = ['الخدمة'=>'تحاليل: '.$test->name,'السعر'=>$test->price ?? 0];
                        }
                    }
                } elseif($payment->request->type==='radiology' && isset($details['radiology_type_ids'])) {
                    foreach($details['radiology_type_ids'] as $typeId) {
                        $type = \App\Models\RadiologyType::find($typeId);
                        if($type) {
                            $lineItems[] = ['الخدمة'=>'أشعة: '.$type->name,'السعر'=>$type->base_price ?? 0];
                        }
                    }
                } elseif($payment->request->type==='pharmacy') {
                    if(isset($details['tests']) && is_array($details['tests'])) {
                        foreach($details['tests'] as $drugName) {
                            $lineItems[] = ['الخدمة'=>'صيدلية: '.$drugName,'السعر'=>0];
                        }
                    }
                } elseif($payment->request->type==='emergency') {
                    if($payment->request->visit && $payment->request->visit->emergency) {
                        foreach($payment->request->visit->emergency->services as $svc) {
                            $lineItems[] = ['الخدمة'=>'خدمة طوارئ: '.$svc->name,'السعر'=>$svc->price ?? 0];
                        }
                    }
                }
            }

            // إذا كان الدفع محصوراً بالعمليات نضيف بنود الوصف أيضاً
            $surgery = null;
            if ($payment->payment_type === 'surgery' && preg_match('/ID: #(\d+)/', $payment->description, $matches)) {
                $surgery = \App\Models\Surgery::with(['patient.user', 'doctor.user', 'department', 'labTests.labTest', 'radiologyTests.radiologyType'])->find($matches[1]);
            }
            if($payment->payment_type==='surgery' && $surgery) {
                if (preg_match('/العناصر المدفوعة:
(.+)/s', $payment->description, $descMatches)) {
                    $itemLines = explode("
", trim($descMatches[1]));
                    foreach ($itemLines as $line) {
                        $line = trim(str_replace('- ', '', $line));
                        if (!empty($line)) {
                            $price = 0;
                            if (str_contains($line, 'رسوم العملية')) {
                                $price = $surgery->surgery_fee ?? 0;
                            } elseif (str_contains($line, 'تحليل:')) {
                                $name = trim(str_replace('تحليل:', '', $line));
                                foreach ($surgery->labTests as $labTest) {
                                    if ($labTest->labTest && $labTest->labTest->name === $name) {
                                        $price = $labTest->labTest->price ?? 0;
                                        break;
                                    }
                                }
                            } elseif (str_contains($line, 'أشعة:')) {
                                $name = trim(str_replace('أشعة:', '', $line));
                                foreach ($surgery->radiologyTests as $rad) {
                                    if ($rad->radiologyType && $rad->radiologyType->name === $name) {
                                        $price = $rad->radiologyType->base_price ?? 0;
                                        break;
                                    }
                                }
                            }
                            $lineItems[] = ['الخدمة'=>$line,'السعر'=>$price];
                        }
                    }
                }
            }
        @endphp

        @if(count($lineItems) > 0)
        <div class="section">
            <div class="section-title">تفصيل الخدمات المقدمة</div>
            <div class="table-responsive">
                <table class="services-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>الخدمة</th>
                            <th style="width: 120px;" class="text-end">السعر (IQD)</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lineItems as $idx=>$ln)
                        <tr>
                            <td>{{ $idx+1 }}</td>
                            <td>{{ $ln['الخدمة'] }}</td>
                            <td class="text-end">{{ number_format($ln['السعر'], 0) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="section">
            <div class="section-title">تفاصيل الخدمة</div>
            <p>{{ number_format($payment->amount, 0) }} IQD</p>
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
        @endif

        @if($payment->payment_type === 'surgery' && $surgery)
        <!-- Surgery Info -->
        <div class="section">
            <div class="section-title">تفاصيل العملية الجراحية</div>
            <div class="info-row">
                <span class="info-label">رقم العملية:</span>
                <span class="info-value">#{{ $surgery->id }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">نوع العملية:</span>
                <span class="info-value">{{ $surgery->surgery_type }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ العملية:</span>
                <span class="info-value">{{ $surgery->scheduled_date->format('Y-m-d') }} - {{ $surgery->scheduled_time->format('H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الجراح:</span>
                <span class="info-value">
                    @if($surgery->doctor && $surgery->doctor->user)
                        د. {{ $surgery->doctor->user->name }}
                    @elseif($surgery->surgeon_name)
                        {{ $surgery->surgeon_name }} <span class="badge bg-secondary">خارجي</span>
                    @else
                        غير محدد
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">القسم:</span>
                <span class="info-value">{{ $surgery->department->name ?? 'غير محدد' }}</span>
            </div>
            @if($surgery->payment_status === 'paid')
            <div style="margin-top: 8px; padding: 5px; background: #d4edda; border: 1px solid #28a745; font-size: 10px;">
                ✓ تم سداد جميع رسوم العملية بالكامل
            </div>
            @endif
        </div>
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
        <div class="text-center" style="margin-top: 15px; font-size: 14px;">
            <span><strong>الكاشير:</strong> {{ $payment->cashier->name ?? 'النظام' }}</span>
            <span style="margin-right: 30px;"><strong>المريض:</strong> {{ $pname ?? '-' }}</span>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>هذا إيصال رسمي صادر من نظام إدارة المستشفى</div>
            <div class="mt-5">للاستفسارات: info@hospital.com</div>
        </div>

        <div class="watermark">طبع في: {{ now()->format('Y-m-d H:i:s') }}</div>
    </div>

    <script>
        // Auto print option
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
@endif
