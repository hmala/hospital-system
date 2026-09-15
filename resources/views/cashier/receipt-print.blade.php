<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال دفع - {{ $payment->receipt_number }}</title>
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
            font-size: 12px;
            font-weight: 800;
            line-height: 1.5;
        }

        /* Services table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
            margin-bottom: 8px;
            font-size: 12px;
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
            width: 35px;
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
            font-size: 12.5px;
            font-weight: 900;
        }

        .items-table .total-val {
            text-align: center;
            font-size: 12.5px;
            font-weight: 900;
        }

        /* Bottom currency / payment box */
        .footer-amount-box {
            border: 1px solid #000000;
            border-collapse: collapse;
            width: 100%;
            margin-top: 4px;
            font-size: 12px;
        }

        .footer-amount-box td {
            border: 1px solid #000000;
            padding: 4px 8px;
            font-weight: 800;
        }

        .footer-amount-box .box-label {
            text-align: right;
            width: 60%;
        }

        .footer-amount-box .box-val {
            text-align: center;
            width: 40%;
        }

        .footer-meta {
            text-align: center;
            font-size: 10px;
            margin-top: 6px;
            color: #555555;
            font-weight: 700;
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
                width: 78mm !important;
                max-width: 78mm !important;
                margin: 0 auto !important;
                box-shadow: none !important;
                border: 1px solid #000000 !important;
                border-radius: 0 !important;
                padding: 3mm 3mm !important;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: auto;
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

            // Line Items Extraction
            $lineItems = [];

            // 1. Consultation
            if ($payment->appointment || $payment->payment_type === 'appointment' || $payment->payment_type === 'consultation') {
                $consultFee = $payment->appointment->consultation_fee ?? ($payment->total_amount ?: $payment->amount);
                $lineItems[] = ['name' => 'اجور كشف استشارية', 'qty' => 1, 'price' => $consultFee];
            }

            // 2. Surgery
            $surgery = $payment->surgery;
            if (!$surgery && $payment->payment_type === 'surgery' && preg_match('/ID: #(\d+)/', $payment->description, $matches)) {
                $surgery = \App\Models\Surgery::with(['room', 'patient.user', 'doctor.user', 'department', 'labTests.labTest', 'radiologyTests.radiologyType'])->find($matches[1]);
            }
            if ($payment->payment_type === 'surgery' && $surgery) {
                if ($surgery->doctor && $surgery->doctor->user) {
                    $doctorName = $surgery->doctor->user->name;
                } elseif ($surgery->surgeon_name) {
                    $doctorName = $surgery->surgeon_name;
                }

                if (preg_match('/العناصر المدفوعة:\n(.+)/s', $payment->description, $descMatches)) {
                    $itemLines = explode("\n", trim($descMatches[1]));
                    foreach ($itemLines as $line) {
                        $line = trim(str_replace('- ', '', $line));
                        if (empty($line) || str_starts_with($line, 'تغطية الضمان:') || str_starts_with($line, 'حصة المريض:')) {
                            continue;
                        }

                        $price = 0;
                        $serviceName = $line;

                        if (str_contains($line, 'رسوم العملية')) {
                            if (preg_match('/مدفوع:\s*([\d,]+)\s*د\.ع/', $line, $pm)) {
                                $price = (float)str_replace(',', '', $pm[1]);
                            } else {
                                $price = $surgery->surgery_fee ?? 0;
                            }
                            $serviceName = 'رسوم العملية: ' . $surgery->surgery_type;
                        } elseif (str_contains($line, 'أجور الغرفة')) {
                            if (preg_match('/مدفوع:\s*([\d,]+)\s*د\.ع/', $line, $pm)) {
                                $price = (float)str_replace(',', '', $pm[1]);
                            } else {
                                $price = $surgery->room_fee ?? 0;
                            }
                            $roomInfo = $surgery->room ? (' (' . $surgery->room->room_type_name . ' - رقم ' . $surgery->room->room_number . ')') : '';
                            $serviceName = 'أجور الغرفة' . $roomInfo;
                        } elseif (str_contains($line, 'تحليل:')) {
                            if (preg_match('/تحليل:\s*(.+?)\s*\(([\d,]+)\s*د\.ع\)/', $line, $lm)) {
                                $serviceName = 'تحليل: ' . trim($lm[1]);
                                $price = (float)str_replace(',', '', $lm[2]);
                            } else {
                                $name = trim(str_replace('تحليل:', '', $line));
                                $serviceName = 'تحليل: ' . $name;
                                foreach ($surgery->labTests as $labTest) {
                                    if ($labTest->labTest && (str_contains($name, $labTest->labTest->name) || $labTest->labTest->name === $name)) {
                                        $price = $labTest->labTest->price ?? 0;
                                        break;
                                    }
                                }
                            }
                        } elseif (str_contains($line, 'أشعة:')) {
                            if (preg_match('/أشعة:\s*(.+?)\s*\(([\d,]+)\s*د\.ع\)/', $line, $rm)) {
                                $serviceName = 'أشعة: ' . trim($rm[1]);
                                $price = (float)str_replace(',', '', $rm[2]);
                            } else {
                                $name = trim(str_replace('أشعة:', '', $line));
                                $serviceName = 'أشعة: ' . $name;
                                foreach ($surgery->radiologyTests as $rad) {
                                    if ($rad->radiologyType && (str_contains($name, $rad->radiologyType->name) || $rad->radiologyType->name === $name)) {
                                        $price = $rad->radiologyType->base_price ?? 0;
                                        break;
                                    }
                                }
                            }
                        }
                        $lineItems[] = ['name' => $serviceName, 'qty' => 1, 'price' => $price];
                    }
                }
            }

            // 3. Requests (Lab, Rad, Pharmacy, Emergency)
            if ($payment->request) {
                $details = is_string($payment->request->details) ? json_decode($payment->request->details, true) : $payment->request->details;
                if ($payment->request->type === 'lab' && isset($details['lab_test_ids'])) {
                    foreach ($details['lab_test_ids'] as $testId) {
                        $test = \App\Models\LabTest::find($testId);
                        if ($test) {
                            $pricing = $payment->insurance_type && $payment->insurance_type !== 'none'
                                ? $test->calculateInsurancePricing($payment->insurance_type, $payment->copay_percentage)
                                : null;
                            $price = $pricing ? (float)$pricing['approved_price'] : ($test->price ?? 0);
                            $lineItems[] = ['name' => 'تحليل: ' . $test->name, 'qty' => 1, 'price' => $price];
                        }
                    }
                } elseif ($payment->request->type === 'radiology' && isset($details['radiology_type_ids'])) {
                    foreach ($details['radiology_type_ids'] as $typeId) {
                        $type = \App\Models\RadiologyType::find($typeId);
                        if ($type) {
                            $pricing = $payment->insurance_type && $payment->insurance_type !== 'none'
                                ? $type->calculateInsurancePricing($payment->insurance_type, $payment->copay_percentage)
                                : null;
                            $price = $pricing ? (float)$pricing['approved_price'] : ($type->base_price ?? 0);
                            $lineItems[] = ['name' => 'أشعة: ' . $type->name, 'qty' => 1, 'price' => $price];
                        }
                    }
                } elseif ($payment->request->type === 'pharmacy') {
                    if (isset($details['tests']) && is_array($details['tests'])) {
                        foreach ($details['tests'] as $drugName) {
                            $lineItems[] = ['name' => 'صيدلية: ' . $drugName, 'qty' => 1, 'price' => 0];
                        }
                    }
                }
            }

            // 4. Emergency
            if ($payment->emergency) {
                $paidServices = \DB::table('emergency_emergency_service')
                    ->join('emergency_services', 'emergency_emergency_service.emergency_service_id', '=', 'emergency_services.id')
                    ->where('emergency_emergency_service.payment_id', $payment->id)
                    ->select('emergency_services.name', 'emergency_services.price')
                    ->get();
                foreach ($paidServices as $svc) {
                    $lineItems[] = ['name' => 'خدمة طوارئ: ' . $svc->name, 'qty' => 1, 'price' => $svc->price ?? 0];
                }
            }

            // Fallback if no item matched
            if (empty($lineItems)) {
                $lineItems[] = ['name' => $payment->description ?: 'خدمات طبية', 'qty' => 1, 'price' => $payment->total_amount ?: $payment->amount];
            }
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
                @foreach($lineItems as $item)
                    <tr>
                        <td class="col-service">{{ $item['name'] }}</td>
                        <td class="col-qty">{{ $item['qty'] }}</td>
                        <td class="col-price">{{ number_format($item['price'], 0) }}</td>
                    </tr>
                @endforeach

                @if($payment->insurance_type && $payment->insurance_type !== 'none')
                    <tr>
                        <td colspan="2" class="col-service" style="font-size: 11px; background: #fafafa;">
                            {{ $payment->insurance_type_name }} (تحمل {{ number_format($payment->copay_percentage, 0) }}%)
                            @if($payment->insurance_card_no)<br><small>بطاقة: {{ $payment->insurance_card_no }}</small>@endif
                        </td>
                        <td class="col-price" style="font-size: 11px; color: #1e40af;">حصة: {{ number_format($payment->insurance_share, 0) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="2" class="total-label">المدفوع نقداً (المريض)</td>
                        <td class="total-val">{{ number_format($payment->patient_share ?: $payment->amount, 0) }}</td>
                    </tr>
                @else
                    <tr class="total-row">
                        <td colspan="2" class="total-label">المجموع</td>
                        <td class="total-val">{{ number_format($payment->amount, 0) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Bottom Payment / Dollar Box -->
        <table class="footer-amount-box">
            <tr>
                <td class="box-label">طريقة الدفع: {{ $payment->payment_method_name }}</td>
                <td class="box-val">{{ number_format($payment->amount, 0) }} IQD</td>
            </tr>
        </table>

        @if($payment->notes)
            <div style="font-size: 11px; margin-top: 5px; padding: 3px; border: 1px dashed #ccc;">
                <strong>ملاحظة:</strong> {{ $payment->notes }}
            </div>
        @endif

        <div class="footer-meta">
            <span>الكاشير: {{ $payment->cashier->name ?? 'النظام' }}</span>
            <span style="margin-right: 15px;">{{ ($payment->paid_at ?? now())->format('H:i') }}</span>
        </div>
    </div>

    <script>
        // Auto print trigger
        // window.onload = function() { window.print(); };
    </script>
</body>
</html>
