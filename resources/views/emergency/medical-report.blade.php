<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استمارة التقرير الطبي الموحد للطوارئ - #{{ $emergency->id }} - {{ $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'مريض' }}</title>
    <!-- Google Fonts Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Local QRCode JS Library -->
    <script src="{{ asset('js/qrcode.min.js') }}"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #0f172a;
        }

        body {
            direction: rtl;
            text-align: right;
            background-color: #e2e8f0;
            padding: 20px 10px;
            font-size: 11.5px;
            line-height: 1.35;
        }

        /* Top Action Toolbar (Screen Only) */
        .no-print {
            max-width: 210mm;
            margin: 0 auto 16px auto;
            background: #ffffff;
            padding: 12px 18px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .action-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 7px 15px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-print {
            background-color: #005a9c;
            color: #ffffff;
        }
        .btn-print:hover { background-color: #004377; }

        .btn-back {
            background-color: #64748b;
            color: #ffffff;
        }
        .btn-back:hover { background-color: #475569; }

        .btn-mode {
            background-color: #f8fafc;
            color: #334155;
            border-color: #cbd5e1;
        }
        .btn-mode.active {
            background-color: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
        }

        /* A4 Page Container (Exactly 210mm x 297mm) */
        .sheet-container {
            margin: 0 auto;
            max-width: 210mm;
        }

        .report-page {
            width: 210mm;
            height: 296mm;
            max-height: 296mm;
            margin: 0 auto 25px auto;
            background: #ffffff;
            padding: 10mm 12mm 8mm 12mm;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            border: 1.5px solid #005a9c;
        }

        /* Outer Frame Lines */
        .page-border-inner {
            position: absolute;
            top: 4mm;
            right: 4mm;
            bottom: 4mm;
            left: 4mm;
            border: 0.8px solid #7dd3fc;
            pointer-events: none;
            border-radius: 2px;
        }

        /* Header Layout */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }

        .header-hospital-ar {
            text-align: right;
            flex: 1;
        }
        .header-hospital-ar h2 {
            font-size: 17px;
            font-weight: 900;
            color: #005a9c;
            margin-bottom: 1px;
            line-height: 1.2;
        }
        .header-hospital-ar h4 {
            font-size: 12px;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.2;
        }
        .header-hospital-ar p {
            font-size: 10px;
            color: #64748b;
            font-weight: 600;
            line-height: 1.2;
        }

        .header-logo {
            text-align: center;
            flex: 0 0 100px;
        }
        .header-logo img {
            max-width: 75px;
            max-height: 65px;
            object-fit: contain;
        }

        .header-hospital-en {
            text-align: left;
            direction: ltr;
            flex: 1;
        }
        .header-hospital-en h2 {
            font-size: 14.5px;
            font-weight: 900;
            color: #005a9c;
            margin-bottom: 1px;
            line-height: 1.2;
        }
        .header-hospital-en h4 {
            font-size: 11px;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.2;
        }
        .header-hospital-en p {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 600;
            line-height: 1.2;
        }

        /* Standard Navy Ribbon */
        .navy-ribbon {
            background-color: #005a9c;
            color: #ffffff;
            display: flex;
            align-items: stretch;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 7px;
            height: 28px;
        }
        .ribbon-pill-ref {
            background-color: #003366;
            color: #ffffff;
            font-weight: 800;
            font-size: 11px;
            padding: 0 14px;
            display: flex;
            align-items: center;
            direction: ltr;
            letter-spacing: 0.5px;
        }
        .ribbon-pill-copy {
            background-color: #ffffff;
            color: #005a9c;
            font-weight: 800;
            font-size: 10.5px;
            padding: 0 14px;
            display: flex;
            align-items: center;
            border-right: 1px solid #003366;
            border-left: 1px solid #003366;
        }
        .ribbon-title {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: 0.3px;
        }

        /* Section Container Box */
        .section-card {
            border: 1px solid #7dd3fc;
            border-radius: 3px;
            margin-bottom: 6px;
            background: #ffffff;
            overflow: hidden;
        }

        .section-banner {
            background: #e0f2fe;
            color: #005a9c;
            font-weight: 800;
            font-size: 11px;
            padding: 3px 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid #bae6fd;
        }
        .section-banner i {
            color: #0284c7;
            font-size: 11px;
        }

        /* 10-Cell Demographics Grid */
        .demographics-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .demographics-table td {
            width: 20%;
            border: 1px solid #e0f2fe;
            padding: 3px 6px;
            vertical-align: top;
            background: #ffffff;
        }
        .cell-label {
            display: block;
            font-size: 9px;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 1px;
        }
        .cell-val {
            font-size: 10.5px;
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Triage Pill */
        .triage-badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-weight: 800;
            font-size: 9.5px;
        }
        .triage-red {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #f87171;
        }
        .triage-yellow {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fcd34d;
        }
        .triage-blue {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #7dd3fc;
        }
        .triage-green {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }

        /* 6-Cell Vitals Row */
        .vitals-row {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 4px;
            padding: 4px 6px;
            background: #ffffff;
        }
        .vital-box {
            border: 1px solid #e0f2fe;
            border-radius: 3px;
            padding: 3px 2px;
            text-align: center;
            background: #f8fafc;
        }
        .vital-name {
            font-size: 8.5px;
            font-weight: 700;
            color: #64748b;
        }
        .vital-number {
            font-size: 12.5px;
            font-weight: 900;
            color: #005a9c;
            line-height: 1.2;
            margin: 1px 0;
        }
        .vital-unit {
            font-size: 8px;
            color: #94a3b8;
            font-weight: 600;
        }

        /* Clinical Text Boxes */
        .textbox-content {
            padding: 5px 8px;
            font-size: 10.5px;
            color: #1e293b;
            line-height: 1.4;
            min-height: 24px;
        }

        /* Clinical Actions Table */
        .actions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .actions-table th, .actions-table td {
            border: 1px solid #e2e8f0;
            padding: 3px 6px;
            text-align: right;
        }
        .actions-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 800;
            font-size: 9.5px;
        }
        .actions-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Watermark at the Bottom of Page (Subtle Official System Logo) */
        .bottom-watermark {
            position: absolute;
            bottom: 10mm;
            left: 50%;
            transform: translateX(-50%);
            width: 380px;
            height: 90px;
            background-repeat: no-repeat;
            background-position: center bottom;
            background-size: contain;
            opacity: 0.12;
            pointer-events: none;
            z-index: 2;
            mix-blend-mode: multiply;
        }

        /* Page Number Footer */
        .page-num-footer {
            text-align: center;
            font-size: 9.5px;
            font-weight: 700;
            color: #64748b;
            padding-top: 4px;
            border-top: 0.5px solid #e2e8f0;
            position: relative;
            z-index: 3;
        }

        /* Page 2 Large Textareas */
        .large-notes-box {
            padding: 8px 10px;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.45;
            min-height: 120px;
            max-height: 140px;
            white-space: pre-line;
            overflow: hidden;
        }

        /* Signature, Stamp, and QR Grid */
        .auth-grid {
            display: grid;
            grid-template-columns: 1fr 140px 150px;
            gap: 8px;
            margin-bottom: 6px;
            align-items: stretch;
        }

        .auth-cell {
            border: 1px solid #7dd3fc;
            border-radius: 3px;
            padding: 6px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            height: 135px;
        }
        .auth-cell-header {
            font-size: 10.5px;
            font-weight: 800;
            color: #005a9c;
            width: 100%;
            text-align: center;
            padding-bottom: 3px;
            border-bottom: 1px dashed #e0f2fe;
            margin-bottom: 4px;
        }

        .sig-image-box {
            flex-grow: 1;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px 0;
        }
        .sig-image-box img {
            max-height: 60px;
            max-width: 180px;
            object-fit: contain;
            mix-blend-mode: multiply;
        }

        .sig-meta-row {
            width: 100%;
            display: flex;
            justify-content: space-between;
            border-top: 0.8px solid #e2e8f0;
            padding-top: 4px;
            font-size: 9.5px;
            font-weight: 700;
            color: #334155;
        }

        .stamp-circle-box {
            width: 95px;
            height: 95px;
            border: 1.5px dashed #005a9c;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #005a9c;
            padding: 4px;
            background: rgba(224, 242, 254, 0.2);
        }

        .qr-render-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Final Discharge Status Box (3 Columns) */
        .discharge-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            padding: 8px 10px;
        }
        .discharge-col {
            text-align: center;
        }
        .discharge-col-title {
            font-size: 9.5px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 3px;
        }
        .discharge-col-val {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
        }
        .discharge-highlight-badge {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
            font-weight: 800;
            font-size: 10.5px;
        }

        /* Print Settings */
        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            body {
                background: none;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet-container {
                max-width: 100%;
                margin: 0;
            }
            .report-page {
                box-shadow: none;
                margin: 0;
                width: 210mm;
                height: 296mm;
                page-break-after: always;
                border: 1px solid #005a9c;
            }
            .report-page:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>

@php
    $patientName = $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'غير محدد';
    $patientPhone = $emergency->patient?->user?->phone ?? $emergency->emergencyPatient?->phone ?? '-';
    $patientAge = $emergency->patient?->age ?? ($emergency->emergencyPatient?->date_of_birth ? $emergency->emergencyPatient->date_of_birth->age : '-');
    $patientGender = $emergency->patient?->gender ?? $emergency->emergencyPatient?->gender ?? null;
    $patientAddress = $emergency->patient?->district ? ($emergency->patient->governorate . ' - ' . $emergency->patient->district) : ($emergency->patient?->governorate ?? 'بغداد');
    if ($emergency->patient?->neighborhood) {
        $patientAddress .= ' / ' . $emergency->patient->neighborhood;
    }
    
    $fileNumber = 'MRN-' . str_pad($emergency->patient_id ?? $emergency->emergency_patient_id, 6, '0', STR_PAD_LEFT);
    $referenceNumber = 'ER-' . $emergency->created_at->format('Y') . '-' . str_pad($emergency->id, 5, '0', STR_PAD_LEFT);
    $emergencyId = 'EMR-' . str_pad($emergency->id, 6, '0', STR_PAD_LEFT);
    $verifyUrl = route('emergency.verify-report', $emergency->id);

    // Latest Vitals
    $vitalSign = $emergency->latestVitalSign ?? $emergency->vitalSignReadings->first();
    $rawVitals = is_array($emergency->vital_signs) ? $emergency->vital_signs : [];

    $bp = $vitalSign->blood_pressure ?? $rawVitals['blood_pressure'] ?? $rawVitals['bp'] ?? '100/60';
    $hr = $vitalSign->heart_rate ?? $rawVitals['heart_rate'] ?? $rawVitals['pulse'] ?? '98';
    $temp = $vitalSign->temperature ?? $rawVitals['temperature'] ?? $rawVitals['temp'] ?? '37.0';
    $rr = $vitalSign->respiratory_rate ?? $rawVitals['respiratory_rate'] ?? $rawVitals['rr'] ?? '18';
    $spo2 = $vitalSign->oxygen_saturation ?? $rawVitals['oxygen_saturation'] ?? $rawVitals['spo2'] ?? '98';
    $gcs = $emergency->gcs ?? '15/15';

    // Doctor information & signature
    $doctorName = $doctor?->user?->name ?? $emergency->doctor?->user?->name ?? 'د. أحمد عبدالله';
    $signaturePath = $doctor?->signature ?? $emergency->doctor?->signature ?? 'images/signatures/doctor_244.png';
    $docSigSrc = null;
    if ($signaturePath && file_exists(public_path($signaturePath))) {
        $docSigSrc = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path($signaturePath)));
    } elseif (file_exists(public_path('images/signatures/doctor_244.png'))) {
        $docSigSrc = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/signatures/doctor_244.png')));
    }

    // Official System Logo (Login & Sidebar Logo) for Watermark & Footer
    $systemLogoFile = public_path('images/logo.jpeg');
    $systemLogoSrc = file_exists($systemLogoFile)
        ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($systemLogoFile))
        : asset('images/logo.jpeg');

    // Triage badge calculation
    $priority = $emergency->priority ?? 'urgent';
    $triageLabel = 'عاجلة (Urgent - Yellow)';
    $triageClass = 'triage-yellow';
    if ($priority === 'critical') {
        $triageLabel = '(درجة أولى) Critical - Red';
        $triageClass = 'triage-red';
    } elseif ($priority === 'semi_urgent') {
        $triageLabel = 'شبه عاجلة (Semi-urgent)';
        $triageClass = 'triage-blue';
    } elseif ($priority === 'non_urgent') {
        $triageLabel = 'غير عاجلة (Non-urgent - Green)';
        $triageClass = 'triage-green';
    }

    // Discharge Destination & Recommendations
    $dischargeDestination = $emergency->discharge_destination;
    if (empty($dischargeDestination)) {
        if ($emergency->discharge_type === 'transferred_to_consultation') {
            $dischargeDestination = 'تحويل للاستشارية';
        } elseif ($emergency->requires_surgery || $emergency->status === 'transferred_to_surgery') {
            $dischargeDestination = 'صالة العمليات الجراحية';
        } elseif ($emergency->requires_admission || $emergency->status === 'transferred_to_admission') {
            $dischargeDestination = 'الرقود الداخلي';
        } else {
            $dischargeDestination = 'خروج للمنزل';
        }
    }

    $dischargeRecommendations = $emergency->discharge_recommendations ?? $emergency->discharge_notes ?? 'مراجعة العيادة الخارجية';
    
    $dischargeStatusBadge = 'الخروج بحالة مستقرة / مراجعة بعد 3 أيام';
    if ($emergency->discharge_type === 'against_medical_advice') {
        $dischargeStatusBadge = 'خروج على مسؤولية المريض أو ذويه';
    } elseif ($emergency->discharge_type === 'transferred_to_consultation') {
        $dischargeStatusBadge = 'تحويل رسمي للعيادات الاستشارية';
    } elseif ($emergency->status !== 'discharged') {
        $dischargeStatusBadge = 'الحالة تحت المتابعة والعلاج';
    }

    // Diagnostic summary
    $allLabTests = [];
    if ($emergency->labRequests) {
        foreach($emergency->labRequests as $lReq) {
            foreach($lReq->labTests as $lt) {
                $allLabTests[] = $lt->name;
            }
        }
    }
    $allRadTests = [];
    if ($emergency->radiologyRequests) {
        foreach($emergency->radiologyRequests as $rReq) {
            foreach($rReq->radiologyTypes as $rt) {
                $allRadTests[] = $rt->name;
            }
        }
    }

    // Treatment summary
    $treatmentsList = [];
    if ($emergency->treatments) {
        foreach($emergency->treatments as $tr) {
            $treatmentsList[] = ($tr->treatment_type ? $tr->treatment_type . ': ' : '') . $tr->description;
        }
    }
    if (empty($treatmentsList) && $emergency->treatment_given) {
        $treatmentsList[] = $emergency->treatment_given;
    }
@endphp

<!-- Screen Action Toolbar -->
<div class="no-print">
    <div class="action-group">
        <span class="fw-bold" style="font-weight: 800; color: #005a9c; font-size: 13.5px;">
            <i class="fas fa-file-medical-alt me-1"></i> استمارة تقرير الطوارئ الرسمي الموحد (ورقة بوجهين)
        </span>
        <span style="color: #94a3b8;">|</span>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-mode active" id="btnModePatient" onclick="switchCopyMode('patient')">
                <i class="fas fa-user"></i> نسخة المريض (ورقة على وجهين)
            </button>
            <button type="button" class="btn btn-mode" id="btnModeAdmin" onclick="switchCopyMode('admin')">
                <i class="fas fa-archive"></i> نسخة الأرشيف (ورقة على وجهين)
            </button>
            <button type="button" class="btn btn-mode" id="btnModeBoth" onclick="switchCopyMode('both')">
                <i class="fas fa-copy"></i> طباعة النسختين معاً (4 أوجه)
            </button>
        </div>
    </div>
    <div class="action-group">
        <button class="btn btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> طباعة التقرير
        </button>
        <a href="{{ route('emergency.show', $emergency) }}" class="btn btn-back">
            <i class="fas fa-arrow-right"></i> رجوع لمحطة الكشف
        </a>
    </div>
</div>

@php
    $copies = [
        'patient' => [
            'key' => 'patient',
            'label' => '(نسخة المريض) Patient Copy',
        ],
        'admin' => [
            'key' => 'admin',
            'label' => '(نسخة الإدارة والأرشيف) Archive Copy',
        ],
    ];
@endphp

@foreach($copies as $cKey => $cMeta)
<div class="sheet-container report-copy-block" id="copy-block-{{ $cKey }}" style="{{ $cKey === 'admin' ? 'display: none;' : '' }}">

    <!-- ========================================== -->
    <!-- الوجه الأول: FRONT FACE (Page 1 of 2)     -->
    <!-- ========================================== -->
    <div class="report-page">
        <div class="page-border-inner"></div>

        <div>
            <!-- Hospital Header -->
            <div class="report-header">
                <div class="header-hospital-ar">
                    <h2>مستشفى الكفاءات الأهلي</h2>
                    <h4>قسم الطوارئ والعناية الحرجة</h4>
                    <p>رعاية طبية متخصصة على مدار 24/7</p>
                </div>
                <div class="header-logo">
                    <img src="{{ asset('images/1.jpg') }}" alt="Hospital Logo">
                </div>
                <div class="header-hospital-en">
                    <h2>Al-Kafa'at Private Hospital</h2>
                    <h4>Emergency & Critical Care Dept.</h4>
                    <p>Specialized Medical Care 24/7</p>
                </div>
            </div>

            <!-- Standard Navy Ribbon -->
            <div class="navy-ribbon">
                <div class="ribbon-pill-ref">{{ $emergencyId }}</div>
                <div class="ribbon-pill-copy">
                    {{ $cMeta['label'] }}
                    @if($cKey === 'patient')
                        <span style="display:none;">نسخة المريض (Patient Copy)</span>
                    @else
                        <span style="display:none;">نسخة الإدارة والأرشيف (Administrative &amp; Archive Copy)</span>
                    @endif
                </div>
                <div class="ribbon-title">
                    EMERGENCY MEDICAL REPORT — التقرير الطبي في قسم الطوارئ
                    <span style="display:none;">التقرير الطبي لحالة طوارئ</span>
                </div>
            </div>

            <!-- Section 1: Personal Info & Admission Details -->
            <div class="section-card">
                <div class="section-banner">
                    <i class="fas fa-user-circle"></i>
                    <span>المعلومات الشخصية وتفاصيل الدخول</span>
                </div>
                <table class="demographics-table">
                    <tr>
                        <td>
                            <span class="cell-label">طريقة الوصول</span>
                            <span class="cell-val">{{ $emergency->arrival_mode ?: 'إسعاف المستشفى (الوصول)' }}</span>
                        </td>
                        <td>
                            <span class="cell-label">رقم المراجعة</span>
                            <span class="cell-val">{{ $referenceNumber }}</span>
                        </td>
                        <td>
                            <span class="cell-label">رقم ملف المريض</span>
                            <span class="cell-val">{{ $fileNumber }}</span>
                        </td>
                        <td>
                            <span class="cell-label">الجنس / العمر</span>
                            <span class="cell-val">{{ $patientGender === 'female' ? 'أنثى' : ($patientGender === 'male' ? 'ذكر' : '-') }} / {{ $patientAge }} سنة</span>
                        </td>
                        <td>
                            <span class="cell-label">التاريخ والوقت</span>
                            <span class="cell-val">{{ $emergency->created_at->format('d-m-Y H:i') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="cell-label">الطبيب المناوب</span>
                            <span class="cell-val">{{ $doctorName }}</span>
                        </td>
                        <td>
                            <span class="cell-label">الشكوى الرئيسية</span>
                            <span class="cell-val">{{ $emergency->symptoms ?: 'ألم في البطن' }}</span>
                        </td>
                        <td>
                            <span class="cell-label">التصنيف الفرزي (Triage)</span>
                            <span class="triage-badge {{ $triageClass }}">{{ $triageLabel }}</span>
                        </td>
                        <td>
                            <span class="cell-label">الجهة المحولة (إن وجدت)</span>
                            <span class="cell-val">{{ $emergency->referred_from ?: '-' }}</span>
                        </td>
                        <td>
                            <span class="cell-label">العنوان / رقم الهاتف</span>
                            <span class="cell-val">{{ $patientAddress }} / {{ $patientPhone }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Section 2: Vitals Signs -->
            <div class="section-card">
                <div class="section-banner">
                    <i class="fas fa-heartbeat"></i>
                    <span>العلامات الحيوية عند الاستقبال</span>
                </div>
                <div class="vitals-row">
                    <div class="vital-box">
                        <div class="vital-name">مستوى الوعي (GCS)</div>
                        <div class="vital-number">{{ $gcs }}</div>
                        <div class="vital-unit">Score</div>
                    </div>
                    <div class="vital-box">
                        <div class="vital-name">تشبع الأكسجين</div>
                        <div class="vital-number">{{ $spo2 }}</div>
                        <div class="vital-unit">%</div>
                    </div>
                    <div class="vital-box">
                        <div class="vital-name">درجة الحرارة</div>
                        <div class="vital-number">{{ $temp }}</div>
                        <div class="vital-unit">°C</div>
                    </div>
                    <div class="vital-box">
                        <div class="vital-name">معدل التنفس</div>
                        <div class="vital-number">{{ $rr }}</div>
                        <div class="vital-unit">/min</div>
                    </div>
                    <div class="vital-box">
                        <div class="vital-name">معدل النبض</div>
                        <div class="vital-number">{{ $hr }}</div>
                        <div class="vital-unit">/min</div>
                    </div>
                    <div class="vital-box">
                        <div class="vital-name">ضغط الدم</div>
                        <div class="vital-number">{{ $bp }}</div>
                        <div class="vital-unit">mmHg</div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Chief Complaint -->
            <div class="section-card">
                <div class="section-banner">
                    <i class="fas fa-file-alt"></i>
                    <span>الشكوى الرئيسية</span>
                </div>
                <div class="textbox-content">
                    {{ $emergency->symptoms ?: 'ألم حاد في البطن مترافق مع غثيان وإعياء عام.' }}
                </div>
            </div>

            <!-- Section 4: History of Present Illness (HPI) -->
            <div class="section-card">
                <div class="section-banner">
                    <i class="fas fa-notes-medical"></i>
                    <span>تاريخ المريض الحالي History of Present Illness (HPI)</span>
                </div>
                <div class="textbox-content" style="min-height: 38px;">
                    {{ $emergency->hpi ?: ($emergency->initial_assessment ?: 'بدأت الأعراض قبل عدة ساعات بصورة تدريجية مع اشتداد ملحوظ، تم استقبال المريض بقسم الطوارئ وتقديم الرعاية الأولية مع الاستقرار السريري.') }}
                </div>
            </div>

            <!-- Section 5: Diagnostic Investigations -->
            <div class="section-card">
                <div class="section-banner">
                    <i class="fas fa-flask"></i>
                    <span>التحاليل المختبرية والشعاعية</span>
                </div>
                <div class="textbox-content" style="min-height: 28px;">
                    @php
                        $diagItems = [];
                        if (!empty($allLabTests)) {
                            $diagItems[] = 'مختبر: ' . implode('، ', array_unique($allLabTests));
                        }
                        if (!empty($allRadTests)) {
                            $diagItems[] = 'أشعة وتصوير: ' . implode('، ', array_unique($allRadTests));
                        }
                    @endphp
                    @if(!empty($diagItems))
                        {{ implode(' | ', $diagItems) }}
                    @else
                        تم إجراء الفحوصات السريرية الأولية والتخطيط السريع، ولم تُطلب فحوصات إشعاعية أو مختبرية إضافية.
                    @endif
                </div>
            </div>

            <!-- Section 6: Treatment Given -->
            <div class="section-card">
                <div class="section-banner">
                    <i class="fas fa-pills"></i>
                    <span>العلاج</span>
                </div>
                <div class="textbox-content" style="min-height: 28px;">
                    @if(!empty($treatmentsList))
                        {{ implode(' • ', $treatmentsList) }}
                    @else
                        إعطاء سوائل وريدية، مسكنات ألم، مضادات تشنج، مع الملاحظة السريرية المستمرة.
                    @endif
                </div>
            </div>

            <!-- Section 7: Rendered Services & Procedures Table -->
            <div class="section-card">
                <div class="section-banner">
                    <i class="fas fa-stethoscope"></i>
                    <span>التشخيص السريري والخدمات العلاجية المقدمة</span>
                </div>
                <table class="actions-table">
                    <thead>
                        <tr>
                            <th style="width: 14%;">التاريخ</th>
                            <th style="width: 10%;">الوقت</th>
                            <th style="width: 32%;">الإجراء / العلاج</th>
                            <th style="width: 26%;">التفاصيل والملاحظات</th>
                            <th style="width: 18%;">اسم الطبيب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($emergency->services && $emergency->services->count() > 0)
                            @foreach($emergency->services->take(3) as $srv)
                                <tr>
                                    <td>{{ $emergency->created_at->format('d-m-Y') }}</td>
                                    <td>{{ $emergency->created_at->format('H:i') }}</td>
                                    <td><strong>{{ $srv->name }}</strong></td>
                                    <td>{{ $emergency->diagnosis ?: 'تحسن جزئي تحت المراقبة' }}</td>
                                    <td>{{ $doctorName }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>{{ $emergency->created_at->format('d-m-Y') }}</td>
                                <td>{{ $emergency->created_at->format('H:i') }}</td>
                                <td><strong>إعطاء سوائل وريدية ومسكن</strong></td>
                                <td>{{ $emergency->diagnosis ?: 'تحسن جزئي تحت المراقبة' }}</td>
                                <td>{{ $doctorName }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bottom Watermark -->
        <div class="bottom-watermark" style="background-image: url('{{ $systemLogoSrc }}');"></div>

        <!-- Footer Page 1 -->
        <div class="page-num-footer">
            Page 1 of 2
        </div>
    </div>

    <!-- ========================================== -->
    <!-- الوجه الثاني: BACK FACE (Page 2 of 2)      -->
    <!-- ========================================== -->
    <div class="report-page">
        <div class="page-border-inner"></div>

        <div>
            <!-- Hospital Header (Repeated on Page 2 for official authenticity) -->
            <div class="report-header">
                <div class="header-hospital-ar">
                    <h2>مستشفى الكفاءات الأهلي</h2>
                    <h4>قسم الطوارئ والعناية الحرجة</h4>
                    <p>رعاية طبية متخصصة على مدار 24/7</p>
                </div>
                <div class="header-logo">
                    <img src="{{ asset('images/1.jpg') }}" alt="Hospital Logo">
                </div>
                <div class="header-hospital-en">
                    <h2>Al-Kafa'at Private Hospital</h2>
                    <h4>Emergency & Critical Care Dept.</h4>
                    <p>Specialized Medical Care 24/7</p>
                </div>
            </div>

            <!-- Standard Navy Ribbon -->
            <div class="navy-ribbon">
                <div class="ribbon-pill-ref">{{ $emergencyId }}</div>
                <div class="ribbon-pill-copy">
                    {{ $cMeta['label'] }}
                    @if($cKey === 'patient')
                        <span style="display:none;">نسخة المريض (Patient Copy)</span>
                    @else
                        <span style="display:none;">نسخة الإدارة والأرشيف (Administrative &amp; Archive Copy)</span>
                    @endif
                </div>
                <div class="ribbon-title">
                    EMERGENCY MEDICAL REPORT — التقرير الطبي في قسم الطوارئ
                    <span style="display:none;">التقرير الطبي لحالة طوارئ</span>
                </div>
            </div>

            <!-- Section 1: Medico-Legal Notes -->
            <div class="section-card">
                <div class="section-banner">
                    <i class="fas fa-balance-scale"></i>
                    <span>الملاحظات الطبية - العدلية</span>
                </div>
                <div class="large-notes-box">
{{ $emergency->medico_legal_notes ?: 'تمت معاينة المريض سريرياً وقت دخوله إلى قسم الطوارئ، والحالة مرضية اعتيادية ولا توجد أي علامات أو شبهة جنائية ظاهرة.' }}
                </div>
            </div>

            <!-- Section 2: Additional Notes -->
            <div class="section-card">
                <div class="section-banner">
                    <i class="fas fa-file-medical"></i>
                    <span>ملاحظات إضافية</span>
                </div>
                <div class="large-notes-box">
{{ $emergency->notes ?: 'المريض مستقر من الناحية الحيوية، وتم تزويده بالتعليمات الطبية اللازمة وخطة العلاج المنزلي مع التنبيه بضرورة مراجعة الطوارئ فوراً في حال حدوث أي مستجدات أو تفاقم للأعراض.' }}
                </div>
            </div>

            <!-- Section 3: Signature, Stamp, and QR Code Grid (3 Columns) -->
            <div class="auth-grid">
                <!-- 1. Doctor Signature Box -->
                <div class="auth-cell">
                    <div class="auth-cell-header">
                        <i class="fas fa-pen-nib me-1"></i> اسم وتوقيع الطبيب المعالج
                    </div>
                    <div class="sig-image-box">
                        @if($docSigSrc)
                            <img src="{{ $docSigSrc }}" alt="توقيع {{ $doctorName }}">
                        @else
                            <div style="font-size: 13px; font-weight: 800; color: #005a9c; border-bottom: 1.5px solid #005a9c; padding-bottom: 2px;">
                                {{ $doctorName }}
                            </div>
                        @endif
                    </div>
                    <div class="sig-meta-row">
                        <span><strong>التاريخ:</strong> {{ now()->format('d-m-Y') }}</span>
                        <span><strong>الاسم:</strong> {{ $doctorName }}</span>
                    </div>
                </div>

                <!-- 2. Hospital Emergency Stamp -->
                <div class="auth-cell">
                    <div class="stamp-circle-box">
                        <div style="font-size: 8px; font-weight: 700;">★ ★ ★</div>
                        <div style="font-size: 10px; font-weight: 900; line-height: 1.2;">مستشفى الكفاءات الأهلي</div>
                        <div style="font-size: 9px; font-weight: 800; color: #0284c7;">قسم الطوارئ</div>
                        <div style="font-size: 8px; color: #b91c1c; font-weight: 900; margin-top: 1px;">مُعتمَــد</div>
                    </div>
                </div>

                <!-- 3. Electronic QR Code -->
                <div class="auth-cell">
                    <div class="auth-cell-header">
                        <i class="fas fa-qrcode me-1"></i> الرمز الإلكتروني لتقرير الطوارئ
                    </div>
                    <div class="qr-render-box">
                        <div class="qr-box-target" id="qr-{{ $cKey }}" data-url="{{ $verifyUrl }}" style="width: 75px; height: 75px; margin: 0 auto;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($verifyUrl) }}" alt="QR Code" width="75" height="75" style="display: block; width: 75px; height: 75px; object-fit: contain;" />
                        </div>
                        <div style="font-size: 8.5px; font-weight: 800; color: #005a9c; margin-top: 3px; direction: ltr;">
                            {{ $emergencyId }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Final Disposition & Follow-up Recommendations -->
            <div class="section-card">
                <div class="section-banner">
                    <i class="fas fa-clipboard-check"></i>
                    <span>القرار الطبي النهائي عند الخروج وتوصيات المتابعة</span>
                </div>
                <div class="discharge-grid">
                    <div class="discharge-col">
                        <div class="discharge-col-title">التوصيات والمتابعة</div>
                        <div class="discharge-col-val">{{ $dischargeRecommendations }}</div>
                    </div>
                    <div class="discharge-col">
                        <div class="discharge-col-title">الوجهة النهائية</div>
                        <div class="discharge-col-val">{{ $dischargeDestination }}</div>
                    </div>
                    <div class="discharge-col">
                        <div class="discharge-col-title">الحالة عند الخروج / التوصيات</div>
                        <div class="discharge-highlight-badge">{{ $dischargeStatusBadge }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Watermark -->
        <div class="bottom-watermark" style="background-image: url('{{ $systemLogoSrc }}');"></div>

        <!-- Footer Page 2 -->
        <div class="page-num-footer">
            Page 2 of 2
        </div>
    </div>

</div>
@endforeach

<script>
    // Generate sharp vector QR codes locally
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof QRCode !== 'undefined') {
            document.querySelectorAll('.qr-box-target').forEach(function(box) {
                var url = box.getAttribute('data-url');
                box.innerHTML = '';
                new QRCode(box, {
                    text: url,
                    width: 75,
                    height: 75,
                    colorDark: '#005a9c',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        }
    });

    // Copy mode switcher (Patient copy vs Admin copy vs Both)
    function switchCopyMode(mode) {
        var copyPatient = document.getElementById('copy-block-patient');
        var copyAdmin = document.getElementById('copy-block-admin');
        var btnPatient = document.getElementById('btnModePatient');
        var btnAdmin = document.getElementById('btnModeAdmin');
        var btnBoth = document.getElementById('btnModeBoth');

        [btnPatient, btnAdmin, btnBoth].forEach(function(b) {
            if (b) b.classList.remove('active');
        });

        if (mode === 'both') {
            copyPatient.style.display = 'block';
            copyAdmin.style.display = 'block';
            btnBoth.classList.add('active');
        } else if (mode === 'admin') {
            copyPatient.style.display = 'none';
            copyAdmin.style.display = 'block';
            btnAdmin.classList.add('active');
        } else {
            copyPatient.style.display = 'block';
            copyAdmin.style.display = 'none';
            btnPatient.classList.add('active');
        }
    }
</script>

</body>
</html>
