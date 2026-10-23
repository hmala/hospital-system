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

        @page {
            size: 80mm auto;
            margin: 0mm;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            html, body {
                width: 80mm !important;
                max-width: 80mm !important;
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .thermal-ticket {
                width: 100% !important;
                max-width: 76mm !important;
                margin: 0 auto !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 1mm 2mm !important;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" class="btn-print" onclick="doPrint()">🖨️ طباعة الإيصال</button>
        <button type="button" class="btn-back" onclick="window.close(); if(!window.closed) window.history.back();">⬅️ إغلاق / عودة</button>
    </div>

    <div class="thermal-ticket">
        <!-- Header Box with Logo & Name -->
        <div class="header-box">
            <img src="{{ asset('images/hospital-logo.svg') }}" class="header-logo" alt="Logo" onerror="this.style.display='none';">
            <div class="header-titles">
                <div class="ar-title">مستشفى الكفاءات الاهلي</div>
                <div class="en-title">Al-Kafaat Hospital</div>
            </div>
            <div style="width: 36px;"></div>
        </div>

        @if((float)$payment->amount < 0)
            <div style="text-align: center; background: #000; color: #fff; padding: 4px; font-weight: 900; font-size: 13px; margin: 6px 0; border-radius: 3px;">
                ⚠️ سند استرجاع مالي (REFUND)
            </div>
        @endif

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
            } elseif ($payment->emergency) {
                $emergencyApt = $payment->emergency->appointments()
                    ->where('status', '!=', 'cancelled')
                    ->with('doctor.user')
                    ->latest('id')
                    ->first();
                if ($emergencyApt && $emergencyApt->doctor && $emergencyApt->doctor->user) {
                    $doctorName = $emergencyApt->doctor->user->name;
                } elseif ($payment->emergency->consultantDoctor && $payment->emergency->consultantDoctor->user) {
                    $doctorName = $payment->emergency->consultantDoctor->user->name;
                } elseif ($payment->emergency->doctor && $payment->emergency->doctor->user) {
                    $doctorName = $payment->emergency->doctor->user->name;
                }
            }

            $dateFormatted = ($payment->paid_at ?? now())->format('Y/m/d');

            // Line Items Extraction
            $lineItems = [];

            // 1. Consultation / Appointment Service
            if ($payment->appointment || $payment->payment_type === 'appointment' || $payment->payment_type === 'consultation') {
                $consultFee = $payment->appointment->consultation_fee ?? ($payment->total_amount ?: $payment->amount);
                if ($payment->insurance_type && $payment->insurance_type !== 'none') {
                    $consultFee = (float)($payment->patient_share ?: round((float)$consultFee * ((float)($payment->copay_percentage ?? 0) / 100)));
                }
                
                $serviceTitle = 'اجور كشف استشارية';
                $scanType = null;
                if ($payment->appointment && $payment->appointment->visit) {
                    $medReq = \App\Models\Request::where('visit_id', $payment->appointment->visit->id)->where('type', 'radiology')->first();
                    if ($medReq) {
                        $details = is_string($medReq->details) ? json_decode($medReq->details, true) : $medReq->details;
                        $radTypeId = $details['ultrasound_type_id'] ?? ($details['radiology_type_ids'][0] ?? null);
                        if ($radTypeId) {
                            $scanType = \App\Models\RadiologyType::find($radTypeId);
                        }
                    }
                }

                if ($scanType) {
                    $serviceTitle = 'فحص سونار: ' . $scanType->name;
                } elseif (preg_match('/دفع رسوم\s+(.+)/u', $payment->description, $m) && !str_contains($m[1], 'موعد #')) {
                    $serviceTitle = trim($m[1]);
                } elseif ($payment->appointment && !empty($payment->appointment->reason) && $payment->appointment->reason !== 'كشف طبي عام') {
                    $serviceTitle = $payment->appointment->reason;
                }

                $lineItems[] = ['name' => $serviceTitle, 'qty' => 1, 'price' => (float)$consultFee];
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

            // 3. Requests (Lab, Rad, Pharmacy, Emergency) - دعم الوصل الموحد الشامل لكافة خدمات الزيارة
            $linkedRequests = \App\Models\Request::where('payment_id', $payment->id)->get();
            if ($linkedRequests->isEmpty() && $payment->request) {
                $linkedRequests = collect([$payment->request]);
            }

            foreach ($linkedRequests as $reqItem) {
                $details = is_string($reqItem->details) ? json_decode($reqItem->details, true) : $reqItem->details;
                if ($reqItem->type === 'lab') {
                    $packageId = $details['package_id'] ?? null;
                    $pkg = $packageId ? \App\Models\Package::find($packageId) : null;

                    if ($pkg) {
                        $pkgName = 'باقة: ' . $pkg->name . ' (' . count($pkg->labTests()->pluck('lab_tests.id')->toArray()) . ' تحاليل مشمولة)';
                        if (!in_array($pkgName, array_column($lineItems, 'name'))) {
                            $pkgPrice = (float)$pkg->price;
                            if ($payment->insurance_type && $payment->insurance_type !== 'none') {
                                $pkgPrice = round($pkgPrice * ((float)($payment->copay_percentage ?? 0) / 100));
                            }
                            $lineItems[] = ['name' => $pkgName, 'qty' => 1, 'price' => $pkgPrice];
                        }

                        $pkgTestIds = $pkg->labTests()->pluck('lab_tests.id')->toArray();
                        $allTestIds = $details['lab_test_ids'] ?? [];
                        $extraTestIds = array_diff($allTestIds, $pkgTestIds);
                        foreach ($extraTestIds as $testId) {
                            $test = \App\Models\LabTest::find($testId);
                            if ($test && $test->is_active) {
                                $itemName = 'تحليل إضافي: ' . trim($test->name);
                                if (in_array(mb_strtolower($itemName), array_map('mb_strtolower', array_column($lineItems, 'name')))) continue;
                                $pricing = $payment->insurance_type && $payment->insurance_type !== 'none'
                                    ? $test->calculateInsurancePricing($payment->insurance_type, $payment->copay_percentage)
                                    : null;
                                $price = $pricing ? (float)$pricing['patient_share'] : ($test->price ?? 0);
                                $lineItems[] = ['name' => $itemName, 'qty' => 1, 'price' => $price];
                            }
                        }
                    } else {
                        $testIds = $details['lab_test_ids'] ?? [];
                        $testNames = $details['tests'] ?? [];
                        $foundTestIds = [];

                        if (!empty($testIds)) {
                            foreach ($testIds as $testId) {
                                $test = \App\Models\LabTest::find($testId);
                                if ($test && $test->is_active) {
                                    $itemName = 'تحليل: ' . trim($test->name);
                                    if (in_array(mb_strtolower($itemName), array_map('mb_strtolower', array_column($lineItems, 'name')))) continue;
                                    $foundTestIds[] = $test->id;
                                    $pricing = $payment->insurance_type && $payment->insurance_type !== 'none'
                                        ? $test->calculateInsurancePricing($payment->insurance_type, $payment->copay_percentage)
                                        : null;
                                    $price = $pricing ? (float)$pricing['patient_share'] : ($test->price ?? 0);
                                    $lineItems[] = ['name' => $itemName, 'qty' => 1, 'price' => $price];
                                }
                            }
                        }

                        if (!empty($testNames)) {
                            foreach ($testNames as $tName) {
                                $test = \App\Models\LabTest::where('is_active', true)
                                    ->where(function($q) use ($tName) {
                                        $q->where('name', $tName)->orWhere('code', $tName);
                                    })
                                    ->first();
                                if ($test && !in_array($test->id, $foundTestIds)) {
                                    $itemName = 'تحليل: ' . trim($test->name);
                                    if (in_array(mb_strtolower($itemName), array_map('mb_strtolower', array_column($lineItems, 'name')))) continue;
                                    $foundTestIds[] = $test->id;
                                    $pricing = $payment->insurance_type && $payment->insurance_type !== 'none'
                                        ? $test->calculateInsurancePricing($payment->insurance_type, $payment->copay_percentage)
                                        : null;
                                    $price = $pricing ? (float)$pricing['patient_share'] : ($test->price ?? 0);
                                    $lineItems[] = ['name' => $itemName, 'qty' => 1, 'price' => $price];
                                } elseif (!$test && !in_array(mb_strtolower('تحليل: ' . trim($tName)), array_map('mb_strtolower', array_column($lineItems, 'name')))) {
                                    $lineItems[] = ['name' => 'تحليل: ' . trim($tName), 'qty' => 1, 'price' => 0];
                                }
                            }
                        }
                    }
                } elseif ($reqItem->type === 'radiology') {
                    $radTypeIds = array_unique(array_filter(array_map('intval', (array)($details['radiology_type_ids'] ?? $details['radiology_types'] ?? []))));
                    if (!empty($radTypeIds)) {
                        foreach ($radTypeIds as $typeId) {
                            $type = \App\Models\RadiologyType::find($typeId);
                            if ($type) {
                                $targetName = $details['radiology_targets'][$typeId] ?? null;
                                $displayName = $targetName ? "أشعة: {$type->name} [العضو: {$targetName}]" : "أشعة: {$type->name}";
                                if (in_array($displayName, array_column($lineItems, 'name'))) continue;
                                $pricing = $payment->insurance_type && $payment->insurance_type !== 'none'
                                    ? $type->calculateInsurancePricing($payment->insurance_type, $payment->copay_percentage)
                                    : null;
                                $price = $pricing ? (float)$pricing['patient_share'] : ($type->base_price ?? 0);
                                $lineItems[] = ['name' => $displayName, 'qty' => 1, 'price' => $price];
                            }
                        }
                    } elseif (!empty($reqItem->description) && !in_array($reqItem->description, array_column($lineItems, 'name'))) {
                        $lineItems[] = ['name' => $reqItem->description, 'qty' => 1, 'price' => (float)$payment->amount];
                    }
                } elseif ($reqItem->type === 'pharmacy') {
                    if (isset($details['tests']) && is_array($details['tests'])) {
                        foreach ($details['tests'] as $drugName) {
                            $itemName = 'صيدلية: ' . $drugName;
                            if (in_array($itemName, array_column($lineItems, 'name'))) continue;
                            $lineItems[] = ['name' => $itemName, 'qty' => 1, 'price' => 0];
                        }
                    }
                }
            }

            // 4. Emergency
            if ($payment->emergency) {
                $paidServices = \DB::table('emergency_emergency_service')
                    ->join('emergency_services', 'emergency_emergency_service.emergency_service_id', '=', 'emergency_services.id')
                    ->where('emergency_emergency_service.payment_id', $payment->id)
                    ->select('emergency_services.*')
                    ->get();
                foreach ($paidServices as $svc) {
                    $esModel = \App\Models\EmergencyService::find($svc->id);
                    $pricing = $esModel && $payment->insurance_type && $payment->insurance_type !== 'none'
                        ? $esModel->calculateInsurancePricing($payment->insurance_type, $payment->copay_percentage)
                        : null;
                    $price = $pricing ? (float)$pricing['patient_share'] : ($svc->price ?? 0);
                    $lineItems[] = ['name' => 'خدمة طوارئ: ' . $svc->name, 'qty' => 1, 'price' => $price];
                }

                // استشارات الطوارئ للأطباء الاستشاريين
                $emergencyAppointments = $payment->emergency->appointments()
                    ->where('status', '!=', 'cancelled')
                    ->with('doctor.user')
                    ->get();

                foreach ($emergencyAppointments as $apt) {
                    $docTitle = $apt->doctor?->user?->name ? ('د. ' . preg_replace('/^د[\.\s]+/u', '', $apt->doctor->user->name)) : 'الاستشاري';
                    $fee = (float)($apt->consultation_fee > 0 ? $apt->consultation_fee : 50000);
                    if ($payment->insurance_type && $payment->insurance_type !== 'none') {
                        $fee = round($fee * ((float)($payment->copay_percentage ?? 0) / 100));
                    }
                    $lineItems[] = [
                        'name' => 'استشارة طبية طوارئ: ' . $docTitle,
                        'qty' => 1,
                        'price' => $fee,
                    ];
                }

                // طلبات الفحوصات للمختبر والأشعة المسددة بهذا الإيصال
                $paidLabs = $payment->emergency->labRequests()->where('payment_id', $payment->id)->with('labTests')->get();
                foreach ($paidLabs as $labReq) {
                    foreach ($labReq->labTests as $lt) {
                        $pricing = $payment->insurance_type && $payment->insurance_type !== 'none'
                            ? $lt->calculateInsurancePricing($payment->insurance_type, $payment->copay_percentage)
                            : null;
                        $price = $pricing ? (float)$pricing['patient_share'] : ($lt->price ?? 0);
                        $lineItems[] = ['name' => 'تحليل طوارئ: ' . $lt->name, 'qty' => 1, 'price' => $price];
                    }
                }

                $paidRads = $payment->emergency->radiologyRequests()->where('payment_id', $payment->id)->with('radiologyTypes')->get();
                foreach ($paidRads as $radReq) {
                    foreach ($radReq->radiologyTypes as $rt) {
                        $pricing = $payment->insurance_type && $payment->insurance_type !== 'none'
                            ? $rt->calculateInsurancePricing($payment->insurance_type, $payment->copay_percentage)
                            : null;
                        $price = $pricing ? (float)$pricing['patient_share'] : ($rt->base_price ?? 0);
                        $lineItems[] = ['name' => 'أشعة طوارئ: ' . $rt->name, 'qty' => 1, 'price' => $price];
                    }
                }

                if ($payment->emergency->follow_up_payment_id === $payment->id && $payment->emergency->doctor_follow_up_fee > 0) {
                    $lineItems[] = ['name' => 'رسوم متابعة طبيب الطوارئ', 'qty' => 1, 'price' => $payment->emergency->doctor_follow_up_fee];
                }
            }

            // Fallback if no item matched or refund
            if ((float)$payment->amount < 0) {
                $lineItems = [
                    ['name' => $payment->description ?: 'استرجاع مالي للمريض: إلغاء فحص خارجي', 'qty' => 1, 'price' => abs((float)$payment->amount)]
                ];
            } elseif (empty($lineItems)) {
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
            اسم المريض: <strong>{{ $patientName }}</strong>
            @if($doctorName)
                <br>الطبيب: <span>د. {{ $doctorName }}</span>
            @endif
            @if($payment->insurance_type && $payment->insurance_type !== 'none')
                <div style="font-size: 11px; font-weight: 700; color: #000; margin-top: 2px;">
                    التغطية: {{ $payment->insurance_type === 'hi' ? 'الضمان الصحي الوطني' : 'ضمان قوى الأمن' }}
                </div>
            @endif
        </div>

        <!-- Services Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-service">الخدمة الطبية</th>
                    <th class="col-qty">عدد</th>
                    <th class="col-price">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lineItems as $item)
                    @php
                        $rowPrice = (float)($item['price'] ?? 0);
                        if ($rowPrice == 0 && count($lineItems) === 1) {
                            $rowPrice = abs((float)$payment->amount);
                        }
                    @endphp
                    <tr>
                        <td class="col-service">{{ $item['name'] }}</td>
                        <td class="col-qty">{{ $item['qty'] }}</td>
                        <td class="col-price">{{ number_format($rowPrice, 0) }}</td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="2" class="total-label">{{ (float)$payment->amount < 0 ? 'المبلغ المسترجع للمريض' : 'المبلغ المدفوع' }}</td>
                    <td class="total-val">{{ number_format(abs((float)$payment->amount), 0) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Bottom Payment Box -->
        <table class="footer-amount-box">
            <tr>
                <td class="box-label">{{ (float)$payment->amount < 0 ? 'طريقة الاسترجاع:' : 'طريقة الدفع:' }} {{ $payment->payment_method === 'card' ? 'دفع إلكتروني (POS)' : 'نقدي (Cash)' }}</td>
                <td class="box-val">{{ number_format(abs((float)$payment->amount), 0) }} IQD</td>
            </tr>
        </table>

        @if($payment->appointment)
            @php
                $botUsername = config('services.telegram.bot_username', 'Kafathospitalbot');
                $deepLink = "https://t.me/{$botUsername}?start=appt_" . $payment->appointment->id;
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=90x90&margin=1&data=" . urlencode($deepLink);
            @endphp
            <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed #000; text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <img src="{{ $qrUrl }}" alt="QR" style="width: 60px; height: 60px; border: 1px solid #000;" onerror="this.closest('.qr-box')?.remove();">
                    <div style="text-align: right; font-size: 10px; font-weight: 800; line-height: 1.3;">
                        <span>📱 امسح الرمز بهاتفك</span><br>
                        <span style="color: #0088cc;">لمتابعة الدور واستلام نداء التيليجرام</span><br>
                        <small style="color: #555;">@Kafathospitalbot</small>
                    </div>
                </div>
            </div>
        @endif

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
        function doPrint() {
            window.print();
        }

        // الطباعة التلقائية عند اكتمال تحميل الصفحة
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 350);
        });
    </script>
</body>
</html>
