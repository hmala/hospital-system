<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $patient = $emergencyRadiology->patient ?? $emergencyRadiology->emergency?->patient;
        $patientUser = optional($patient)->user;
        $patientName = $patientUser->name ?? optional($patient)->name ?? 'مريض غير محدد';
        $patientPhone = $patientUser->phone ?? optional($patient)->phone ?? '-';
        $patientAge = optional($patient)->age ?? '-';
        $patientGender = optional($patient)->gender === 'male' ? 'ذكر' : (optional($patient)->gender === 'female' ? 'أنثى' : '-');
        $patientMedNo = optional($patient)->medical_number ?? (optional($patient)->id ? '#' . optional($patient)->id : '-');

        $doctor = $emergencyRadiology->emergency?->doctor;
        $doctorName = optional(optional($doctor)->user)->name ?? optional($doctor)->name ?? 'طبيب الطوارئ';
        $departmentName = optional($emergencyRadiology->emergency?->doctor?->department)->name ?? 'قسم الطوارئ والحوادث';

        $types = $emergencyRadiology->radiologyTypes;
        $scanName = $types->isNotEmpty() ? $types->pluck('name')->implode(', ') : ($emergencyRadiology->notes ?: 'فحص أشعة طوارئ');
        $scanCode = 'ERAD-' . $emergencyRadiology->id;
        $scanCategory = $types->first()?->subcategory ?? ($types->first()?->main_category ?? 'طوارئ الأشعة');

        $resultsData = is_array($emergencyRadiology->results_data) ? $emergencyRadiology->results_data : (json_decode($emergencyRadiology->results_data ?? '[]', true) ?: []);
        
        $findings = $resultsData['findings'] ?? null;
        if (empty($findings)) {
            $findings = $types->map(function ($type) use ($types) {
                $res = trim((string) ($type->pivot->result ?? ''));
                if ($res === '') return null;
                return $types->count() > 1 ? $type->name . ': ' . $res : $res;
            })->filter()->implode("\n\n");
        }

        $impression = $resultsData['impression'] ?? '';
        $recommendations = $resultsData['recommendations'] ?? ($emergencyRadiology->notes ?? '');
        
        $images = $resultsData['images'] ?? [];
        if (!is_array($images) || empty($images)) {
            $images = $types->map(function ($type) {
                return $type->pivot->image_path ?? null;
            })->filter()->values()->toArray();
        }

        $attachments = collect();
        foreach ($types as $type) {
            $rawPath = $type->pivot->image_path ?? null;
            if (!empty($rawPath)) {
                $attachments->push([
                    'name' => $type->name,
                    'path' => $rawPath,
                    'url' => route('staff.emergency-radiology.attachment', ['emergencyRadiology' => $emergencyRadiology, 'typeId' => $type->id]),
                    'src' => asset('storage/' . $rawPath),
                ]);
            }
        }
        foreach ($images as $img) {
            if (!$attachments->contains('path', $img)) {
                $attachments->push([
                    'name' => 'صورة إضافية',
                    'path' => $img,
                    'url' => asset('storage/' . $img),
                    'src' => asset('storage/' . $img),
                ]);
            }
        }

        $radiologistName = $resultsData['radiologist'] ?? 'طبيب / فني الأشعة';
        $reportedDate = !empty($resultsData['reported_at']) 
            ? \Carbon\Carbon::parse($resultsData['reported_at'])->format('Y-m-d H:i') 
            : ($emergencyRadiology->completed_at ? $emergencyRadiology->completed_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i'));
        $examDate = !empty($emergencyRadiology->requested_at) 
            ? $emergencyRadiology->requested_at->format('Y-m-d H:i') 
            : now()->format('Y-m-d H:i');

        $insuranceType = $emergencyRadiology->emergency?->insurance_type ?? optional($patient)->insurance_type ?? 'none';
        $isInsured = ($insuranceType && $insuranceType !== 'none');

        $verifyUrl = url('/staff/emergency-radiology/' . $emergencyRadiology->id);

        // شعار المستشفى المدمج بتقنية Base64 للظهور الفوري
        $hospitalLogoFile = public_path('images/1.jpg');
        $hospitalLogoSrc = file_exists($hospitalLogoFile) && filesize($hospitalLogoFile) > 0
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($hospitalLogoFile))
            : asset('images/1.jpg');
    @endphp

    <title>تقرير أشعة طوارئ - #{{ $emergencyRadiology->id }} - {{ $patientName }}</title>

    <!-- Google Fonts Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
            background-color: #f1f5f9;
            padding: 20px 10px;
            font-size: 12px;
            line-height: 1.45;
        }

        /* Top Action Toolbar (Screen Only) */
        .no-print {
            max-width: 210mm;
            margin: 0 auto 16px auto;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            border: 1px solid #e2e8f0;
        }

        .action-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-print {
            background-color: #005a9c;
            color: #ffffff;
        }
        .btn-print:hover { background-color: #004377; }

        .btn-pdf {
            background-color: #0284c7;
            color: #ffffff;
        }
        .btn-pdf:hover { background-color: #0369a1; }

        .btn-whatsapp {
            background-color: #10b981;
            color: #ffffff;
        }
        .btn-whatsapp:hover { background-color: #059669; }

        .btn-back {
            background-color: #64748b;
            color: #ffffff;
        }
        .btn-back:hover { background-color: #475569; }

        /* Letterhead Toggle Styles */
        .mode-toggle-group {
            display: flex;
            background: #f1f5f9;
            padding: 3px;
            border-radius: 8px;
            gap: 4px;
            border: 1px solid #cbd5e1;
        }

        .mode-btn {
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            color: #64748b;
            background: transparent;
            transition: all 0.2s ease;
        }

        .mode-btn.active {
            background: #005a9c;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        :root {
            --letterhead-top: 45mm;
        }

        body.letterhead-mode .report-header,
        body.letterhead-mode .document-title-banner,
        body.letterhead-mode .report-footer {
            display: none !important;
        }

        body.letterhead-mode .report-page::before {
            display: none !important;
        }

        body.letterhead-mode .report-page {
            padding-top: var(--letterhead-top, 45mm) !important;
            border: none !important;
            box-shadow: none !important;
        }

        /* A4 Page Container (210mm x 297mm) */
        .sheet-container {
            margin: 0 auto;
            max-width: 210mm;
        }

        .report-page {
            width: 210mm;
            min-height: 296mm;
            margin: 0 auto 25px auto;
            background: #ffffff;
            padding: 12mm 14mm 10mm 14mm;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1.5px solid #005a9c;
            border-radius: 4px;
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

        /* Subtle Center Watermark */
        .report-page::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url('{!! $hospitalLogoSrc !!}');
            background-repeat: no-repeat;
            background-position: center 48%;
            background-size: 50% auto;
            opacity: 0.045;
            pointer-events: none;
            z-index: 0;
        }

        .report-content-wrapper {
            position: relative;
            z-index: 1;
        }

        /* Header Layout */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 8px;
            margin-bottom: 10px;
            border-bottom: 2px solid #005a9c;
        }

        .header-hospital-ar {
            text-align: right;
            flex: 1;
        }
        .header-hospital-ar h2 {
            font-size: 17px;
            font-weight: 900;
            color: #005a9c;
            margin-bottom: 2px;
            line-height: 1.2;
        }
        .header-hospital-ar h4 {
            font-size: 12.5px;
            font-weight: 800;
            color: #0284c7;
            margin-bottom: 1px;
        }
        .header-hospital-ar p {
            font-size: 10px;
            color: #64748b;
            font-weight: 600;
        }

        .header-logo {
            text-align: center;
            width: 110px;
            padding: 0 8px;
        }
        .header-logo img {
            max-width: 95px;
            max-height: 75px;
            object-fit: contain;
        }

        .header-hospital-en {
            text-align: left;
            direction: ltr;
            flex: 1;
        }
        .header-hospital-en h2 {
            font-size: 15px;
            font-weight: 900;
            color: #005a9c;
            margin-bottom: 2px;
            line-height: 1.2;
        }
        .header-hospital-en h4 {
            font-size: 11.5px;
            font-weight: 800;
            color: #0284c7;
            margin-bottom: 1px;
        }
        .header-hospital-en p {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 600;
        }

        /* Document Title Banner */
        .document-title-banner {
            background: linear-gradient(90deg, #005a9c 0%, #0284c7 100%);
            color: #ffffff;
            text-align: center;
            padding: 6px 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .document-title-banner h3 {
            font-size: 14.5px;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: 0.3px;
        }
        .document-title-banner .report-code-badge {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 20px;
            direction: ltr;
        }

        /* Patient Info Section */
        .patient-card {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background-color: #f8fafc;
            padding: 10px 14px;
            margin-bottom: 12px;
        }
        .patient-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px 14px;
        }
        .p-field {
            display: flex;
            flex-direction: column;
        }
        .p-label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
        }
        .p-val {
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
        }
        .badge-coverage {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 800;
            width: fit-content;
        }
        .badge-insurance {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .badge-cash {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        /* Scan Type Banner */
        .scan-banner {
            border: 1.5px solid #0284c7;
            background-color: #f0f9ff;
            border-radius: 8px;
            padding: 9px 14px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .scan-title {
            font-size: 13.5px;
            font-weight: 900;
            color: #0369a1;
        }
        .scan-meta {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
        }

        /* Clinical Section Boxes */
        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            margin-bottom: 12px;
            overflow: hidden;
            background: #ffffff;
        }
        .section-header {
            background-color: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 800;
            color: #005a9c;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .section-body {
            padding: 12px 16px;
            font-size: 12.5px;
            line-height: 1.7;
            color: #1e293b;
            min-height: 80px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .findings-body {
            font-size: 13px;
            line-height: 1.8;
            color: #0f172a;
            min-height: 140px;
        }

        /* Highlight Boxes (Impression & Recommendations) */
        .impression-box {
            border-right: 4px solid #005a9c;
            background-color: #f8fafc;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 10px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
        }
        .impression-title {
            font-size: 11.5px;
            font-weight: 800;
            color: #005a9c;
            margin-bottom: 4px;
        }
        .impression-text {
            font-size: 12.5px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.6;
        }

        .recommendation-box {
            border-right: 4px solid #059669;
            background-color: #f0fdf4;
            padding: 9px 14px;
            border-radius: 6px;
            margin-bottom: 12px;
            border-top: 1px solid #bbf7d0;
            border-bottom: 1px solid #bbf7d0;
            border-left: 1px solid #bbf7d0;
        }
        .rec-title {
            font-size: 11px;
            font-weight: 800;
            color: #059669;
            margin-bottom: 3px;
        }
        .rec-text {
            font-size: 12px;
            font-weight: 600;
            color: #166534;
        }

        /* Images Preview Grid */
        .images-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }
        .image-cell {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 4px;
            text-align: center;
            background: #f8fafc;
        }
        .image-cell img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            border-radius: 4px;
        }
        .image-cell a {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            color: #0284c7;
            text-decoration: none;
            font-weight: 700;
        }

        /* Official Authentication & Signatures Block */
        .auth-block {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 16px;
            background-color: #ffffff;
            margin-top: 10px;
            display: grid;
            grid-template-columns: 2fr 1.3fr 1.2fr;
            gap: 16px;
            align-items: center;
        }

        .auth-cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .signature-box-empty {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .sig-empty-line {
            width: 90%;
            height: 44px;
            border-bottom: 1.5px dashed #005a9c;
            margin-bottom: 6px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            color: #94a3b8;
            font-size: 11px;
            font-style: italic;
        }
        .sig-title {
            font-size: 12px;
            font-weight: 800;
            color: #005a9c;
            margin-bottom: 2px;
        }
        .sig-subtitle {
            font-size: 10.5px;
            color: #64748b;
            font-weight: 600;
        }

        .stamp-circle-box {
            width: 82px;
            height: 82px;
            border: 2px dashed #005a9c;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #005a9c;
            background: #f8fafc;
            padding: 4px;
        }

        .qr-render-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .qr-image-wrapper {
            width: 70px;
            height: 70px;
            border: 1px solid #cbd5e1;
            padding: 3px;
            border-radius: 4px;
            background: #ffffff;
        }
        .qr-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .qr-label {
            font-size: 9px;
            font-weight: 800;
            color: #005a9c;
            margin-top: 4px;
            direction: ltr;
        }

        /* Official Footer */
        .report-footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9.5px;
            color: #64748b;
            font-weight: 600;
        }
        .footer-contacts {
            display: flex;
            gap: 16px;
        }

        /* Print Settings */
        @media print {
            body {
                background: none !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            .sheet-container {
                max-width: 100% !important;
                margin: 0 !important;
            }

            .report-page {
                box-shadow: none !important;
                margin: 0 !important;
                width: 100% !important;
                min-height: 296mm !important;
                border: 1.5px solid #005a9c !important;
                page-break-after: always;
                page-break-inside: avoid;
            }

            .page-border-inner {
                border-color: #7dd3fc !important;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Toolbar (Screen Only) -->
    <div class="no-print">
        <div class="action-group">
            <span style="font-weight: 800; color: #005a9c; font-size: 13.5px;">
                <i class="fas fa-file-medical-alt me-1"></i> تقرير فحص الأشعة الموحد - طوارئ STAT
            </span>
            <span style="color: #cbd5e1;">|</span>
            <span class="badge-coverage {{ $isInsured ? 'badge-insurance' : 'badge-cash' }}">
                {{ $isInsured ? 'مشمول بالتأمين الصحي' : 'دفع نقدي (طوارئ)' }}
            </span>
            <div class="mode-toggle-group me-2">
                <button type="button" class="mode-btn active" id="btnModeFull" onclick="setLetterheadMode(false)">
                    <i class="fas fa-file-alt me-1"></i> تقرير كامل
                </button>
                <button type="button" class="mode-btn" id="btnModeLetterhead" onclick="setLetterheadMode(true)">
                    <i class="fas fa-stamp me-1"></i> ورق مروس
                </button>
            </div>
        </div>

        <div class="action-group">
            @if($patientPhone && $patientPhone !== '-')
                @php
                    $cleanPhone = preg_replace('/[^0-9]/', '', $patientPhone);
                    if (str_starts_with($cleanPhone, '0')) {
                        $cleanPhone = '964' . substr($cleanPhone, 1);
                    }
                    $waText = "مرحباً {$patientName}، نرفق لكم نتيجة فحص الأشعة بقسم الطوارئ في مستشفى الكفاءات الأهلي: " . $verifyUrl;
                @endphp
                <a href="https://wa.me/{{ $cleanPhone }}?text={{ urlencode($waText) }}" target="_blank" class="btn btn-whatsapp">
                    <i class="fab fa-whatsapp"></i> واتساب المريض
                </a>
            @endif
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> طباعة التقرير (Print)
            </button>
            <button class="btn btn-pdf" onclick="window.print()">
                <i class="fas fa-file-pdf"></i> حفظ PDF
            </button>
            <a href="{{ route('staff.emergency-radiology.show', $emergencyRadiology) }}" class="btn btn-back">
                <i class="fas fa-arrow-right"></i> العودة للمحطة
            </a>
        </div>
    </div>

    <!-- Sheet Container -->
    <div class="sheet-container">
        <div class="report-page">
            <div class="page-border-inner"></div>

            <div class="report-content-wrapper">
                
                <!-- 1. Hospital Header -->
                <div class="report-header">
                    <div class="header-hospital-ar">
                        <h2>مستشفى الكفاءات الأهلي</h2>
                        <h4>قسم الأشعة والتصوير الطبي</h4>
                        <p>وحدة طوارئ الأشعة والحوادث STAT</p>
                    </div>
                    <div class="header-logo">
                        <img src="{!! $hospitalLogoSrc !!}" 
                             alt="Hospital Logo" 
                             class="hospital-logo-img" 
                             onerror="this.onerror=null; this.src='{{ asset('images/1.jpg') }}';" 
                             style="max-height: 85px; max-width: 120px; object-fit: contain;">
                    </div>
                    <div class="header-hospital-en">
                        <h2>Al-Kafa'at Private Hospital</h2>
                        <h4>Radiology & Medical Imaging Dept</h4>
                        <p>Emergency STAT Imaging Unit</p>
                    </div>
                </div>

                <!-- 2. Document Title Banner -->
                <div class="document-title-banner">
                    <h3>تقرير الأشعة التشخيصية - طوارئ STAT (Diagnostic Imaging Report)</h3>
                    <div class="report-code-badge">
                        {{ $scanCode }}
                    </div>
                </div>

                <!-- 3. Patient Information Grid -->
                <div class="patient-card">
                    <div class="patient-grid">
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-user text-secondary me-1"></i> اسم المريض:</span>
                            <span class="p-val" style="font-size: 13px; color: #005a9c;">{{ $patientName }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-birthday-cake text-secondary me-1"></i> العمر / الجنس:</span>
                            <span class="p-val">{{ $patientAge }} سنة • {{ $patientGender }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-phone text-secondary me-1"></i> هاتف المريض:</span>
                            <span class="p-val" style="direction: ltr; text-align: right;">{{ $patientPhone }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-id-card text-secondary me-1"></i> التغطية المالية:</span>
                            <span class="badge-coverage {{ $isInsured ? 'badge-insurance' : 'badge-cash' }}">
                                {{ $isInsured ? 'مشمول بالتأمين الصحي' : 'دفع نقدي (خاص)' }}
                            </span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-user-md text-secondary me-1"></i> طبيب الطوارئ:</span>
                            <span class="p-val">د. {{ $doctorName }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-clinic-medical text-secondary me-1"></i> القسم المحيل:</span>
                            <span class="p-val">{{ $departmentName }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-calendar-alt text-secondary me-1"></i> تاريخ الطلب:</span>
                            <span class="p-val">{{ $examDate }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-clock text-secondary me-1"></i> تاريخ التقرير:</span>
                            <span class="p-val">{{ $reportedDate }}</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Required Scan Banner -->
                <div class="scan-banner">
                    <div>
                        <span class="scan-meta d-block" style="font-size: 10px; color: #0284c7;">الفحص المطلوب (Emergency Study / Procedure):</span>
                        <span class="scan-title">{{ $scanName }}</span>
                    </div>
                    <div style="text-align: left; direction: ltr;">
                        <span class="scan-meta d-block" style="font-size: 10px;">التصنيف:</span>
                        <span style="font-size: 11.5px; font-weight: 800; color: #005a9c;">{{ $scanCategory }}</span>
                    </div>
                </div>

                <!-- 5. Clinical Findings Box (النتائج والمشاهدات التشخيصية) -->
                <div class="section-box">
                    <div class="section-header">
                        <i class="fas fa-file-medical-alt text-primary"></i>
                        <span>النتائج والمشاهدات التشخيصية السريرية (Clinical Findings)</span>
                    </div>
                    <div class="section-body findings-body">@if(!empty(trim($findings))){{ trim($findings) }}@else<span style="color: #94a3b8; font-style: italic;">تم فحص الحالة وإجراء الأشعة بنجاح.</span>@endif</div>
                </div>

                <!-- 6. Impression Box (الاستنتاج السريري) -->
                @if(!empty(trim($impression)))
                    <div class="impression-box">
                        <div class="impression-title">
                            <i class="fas fa-check-circle me-1"></i> الاستنتاج والتشخيص السريري (Impression / Conclusion):
                        </div>
                        <div class="impression-text">
                            {{ trim($impression) }}
                        </div>
                    </div>
                @endif

                <!-- 7. Recommendations Box (التوصيات والمتابعة) -->
                @if(!empty(trim($recommendations)))
                    <div class="recommendation-box">
                        <div class="rec-title">
                            <i class="fas fa-notes-medical me-1"></i> التوصيات والمتابعة السريرية (Recommendations):
                        </div>
                        <div class="rec-text">
                            {{ trim($recommendations) }}
                        </div>
                    </div>
                @endif

                <!-- 8. Attached Scan Images (صور الأشعة المرفقة) -->
                @if($attachments->isNotEmpty())
                    <div class="section-box" style="margin-top: 10px;">
                        <div class="section-header">
                            <i class="fas fa-images text-primary"></i>
                            <span>صور الأشعة التشخيصية والمرفقات المعتمدة ({{ $attachments->count() }})</span>
                        </div>
                        <div style="padding: 10px;">
                            <div class="images-grid">
                                @foreach($attachments->take(6) as $index => $att)
                                    @php
                                        $imgExt = strtolower(pathinfo($att['path'], PATHINFO_EXTENSION));
                                        $isPdf = in_array($imgExt, ['pdf']);
                                    @endphp
                                    <div class="image-cell">
                                        @if($isPdf)
                                            <div style="height: 130px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #e2e8f0; border-radius: 4px;">
                                                <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                                <span style="font-size: 11px; font-weight: bold;">تقرير جهاز PDF</span>
                                            </div>
                                        @else
                                            <img src="{{ $att['src'] }}" alt="Emergency Radiology Scan" onerror="this.onerror=null; this.src='{{ asset('images/1.jpg') }}';">
                                        @endif
                                        <a href="{{ $att['url'] }}" target="_blank">
                                            <i class="fas fa-external-link-alt me-1"></i> معاينة المرفق {{ $index + 1 }} {{ !empty($att['name']) ? '('.$att['name'].')' : '' }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- 9. Official Authentication & Signatures Block -->
                <div class="auth-block">
                    <!-- 1. Doctor / Radiologist Signature -->
                    <div class="auth-cell">
                        <div class="signature-box-empty">
                            <div class="sig-title">طبيب / فني الأشعة المعتمد</div>
                            <div class="sig-empty-line">
                                {{ $radiologistName }}
                            </div>
                            <div class="sig-subtitle">قسم الأشعة والتصوير الطبي</div>
                        </div>
                    </div>

                    <!-- 2. Official Hospital Seal -->
                    <div class="auth-cell">
                        <div class="stamp-circle-box">
                            <i class="fas fa-certificate text-muted" style="font-size: 16px; margin-bottom: 2px;"></i>
                            <span style="font-size: 9px; font-weight: 800; line-height: 1.1;">الختم الرسمي<br>المعتمد</span>
                        </div>
                    </div>

                    <!-- 3. Electronic Verification QR Code -->
                    <div class="auth-cell">
                        <div class="qr-render-box">
                            <div class="qr-image-wrapper">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($verifyUrl) }}" alt="QR Code">
                            </div>
                            <div class="qr-label">
                                {{ $scanCode }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 10. Official Footer -->
            <div class="report-footer">
                <div class="footer-contacts">
                    <span><i class="fas fa-phone-alt me-1"></i> +964 (0) 778 050 7060</span>
                    <span><i class="fas fa-envelope me-1"></i> info@alkafaathospital.com</span>
                    <span><i class="fas fa-map-marker-alt me-1"></i> النجف الأشرف - شارع الحزام الأخضر</span>
                </div>
                <div>
                    <span style="font-weight: 800; color: #005a9c;">طوارئ STAT • ERAD-{{ $emergencyRadiology->id }}</span>
                </div>
            </div>

        </div>
    </div>

    <script>
        function setLetterheadMode(enable) {
            if (enable) {
                document.body.classList.add('letterhead-mode');
                document.getElementById('btnModeLetterhead').classList.add('active');
                document.getElementById('btnModeFull').classList.remove('active');
            } else {
                document.body.classList.remove('letterhead-mode');
                document.getElementById('btnModeFull').classList.add('active');
                document.getElementById('btnModeLetterhead').classList.remove('active');
            }
        }
    </script>
</body>
</html>
