<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $patient = $radiology->patient ?? optional(optional($radiology)->medical_request)->visit?->patient;
        $patientUser = optional($patient)->user;
        $patientName = $patientUser->name ?? optional($patient)->name ?? 'مريض غير محدد';
        $patientPhone = $patientUser->phone ?? optional($patient)->phone ?? '—';
        $patientAge = optional($patient)->age ?? '—';
        $patientGender = optional($patient)->gender === 'male' ? 'ذكر' : (optional($patient)->gender === 'female' ? 'أنثى' : '—');
        $patientMedNo = optional($patient)->medical_number ?? (optional($patient)->id ? '#' . optional($patient)->id : '—');

        $doctor = $radiology->doctor ?? optional(optional($radiology)->medical_request)->visit?->doctor;
        $doctorName = optional(optional($doctor)->user)->name ?? optional($doctor)->name ?? 'الاستشارية';
        $departmentName = optional(optional(optional($radiology)->medical_request)->visit)->department?->name ?? 'العيادات الاستشارية';

        $scanName = optional($radiology->radiologyType)->name ?? ($radiology->clinical_indication ?: 'فحص السونار والتصوير الطبي');
        $scanCode = optional($radiology->radiologyType)->code ?? ('RAD-' . $radiology->id);
        $scanCategory = optional($radiology->radiologyType)->subcategory ?? (optional($radiology->radiologyType)->main_category ?? 'السونار والأشعة');

        $resultObj = $radiology->result ?? null;
        $findings = optional($resultObj)->findings ?? '';
        $impression = optional($resultObj)->impression ?? '';
        $recommendations = optional($resultObj)->recommendations ?? '';
        $images = optional($resultObj)->images ?? [];
        if (!is_array($images)) $images = [];

        $radiologistName = optional(optional($resultObj)->radiologist)->name ?? (is_string(optional($resultObj)->radiologist) ? optional($resultObj)->radiologist : 'أخصائي الأشعة والتصوير');
        $reportedDate = optional($resultObj)->reported_at ? (\Carbon\Carbon::parse(optional($resultObj)->reported_at)->format('Y-m-d H:i')) : now()->format('Y-m-d H:i');
        $examDate = !empty($radiology->requested_date) ? (\Carbon\Carbon::parse($radiology->requested_date)->format('Y-m-d H:i')) : now()->format('Y-m-d H:i');

        $insuranceType = $radiology->insurance_type ?? optional($patient)->insurance_type ?? 'none';
        $isInsured = ($insuranceType && $insuranceType !== 'none');

        $verifyUrl = url('/radiology-staff/requests/' . $radiology->id . '/show');

        // الشعار المعتمد في ترويسة المختبر والطوارئ (1.jpg) مع التضمين الآمن Base64
        $hospitalLogoFile = public_path('images/1.jpg');
        $hospitalLogoSrc = file_exists($hospitalLogoFile) && filesize($hospitalLogoFile) > 0
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($hospitalLogoFile))
            : asset('images/1.jpg');
    @endphp

    <title>تقرير فحص السونار والأشعة - #{{ $radiology->id }} - {{ $patientName }}</title>

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

        .btn-back {
            background-color: #64748b;
            color: #ffffff;
        }
        .btn-back:hover { background-color: #475569; }

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
            font-size: 15px;
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

        /* Clinical Section Cards */
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
            min-height: 160px;
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

        /* Images Preview Grid (if attached) */
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
            height: 140px;
            object-fit: cover;
            border-radius: 4px;
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

        /* Empty Signature Area for Doctor / Specialist (as requested) */
        .signature-box-empty {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .sig-empty-line {
            width: 90%;
            height: 48px;
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

        /* Official Hospital Stamp Placeholder */
        .stamp-circle-box {
            width: 88px;
            height: 88px;
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

        /* Electronic QR Code */
        .qr-render-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .qr-image-wrapper {
            width: 72px;
            height: 72px;
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
                <i class="fas fa-file-medical-alt me-1"></i> استمارة التقرير الطبي المعتمد للسونار والأشعة التشخيصية
            </span>
            <span style="color: #cbd5e1;">|</span>
            <span class="badge-coverage {{ $isInsured ? 'badge-insurance' : 'badge-cash' }}">
                {{ $isInsured ? '🛡️ مريض مسجل بالضمان الصحي' : '💵 فحص حساب نقدي (كاش)' }}
            </span>
        </div>
        <div class="action-group">
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> طباعة التقرير (Print)
            </button>
            <button class="btn btn-pdf" onclick="window.print()">
                <i class="fas fa-file-pdf"></i> حفظ كـ PDF
            </button>
            <button class="btn btn-back" onclick="window.history.back()">
                <i class="fas fa-arrow-right"></i> رجوع
            </button>
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
                        <h4>قسم الأشعة والتصوير الطبي والسونار</h4>
                        <p>وحدة الموجات فوق الصوتية والتشخيص الإشعاعي المعتمد</p>
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
                        <p>Ultrasound & Diagnostic Imaging Unit</p>
                    </div>
                </div>

                <!-- 2. Document Title Banner -->
                <div class="document-title-banner">
                    <h3>تقرير الفحص الطبي التشخيصي (Diagnostic Imaging Report)</h3>
                    <div class="report-code-badge">
                        RAD-#{{ $radiology->id }}
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
                            <span class="p-label"><i class="fas fa-birthday-cake text-secondary me-1"></i> العمر والجنس:</span>
                            <span class="p-val">{{ $patientAge }} سنة • {{ $patientGender }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-phone text-secondary me-1"></i> رقم الهاتف:</span>
                            <span class="p-val" style="direction: ltr; text-align: right;">{{ $patientPhone }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-id-card text-secondary me-1"></i> التغطية المالية:</span>
                            <span class="badge-coverage {{ $isInsured ? 'badge-insurance' : 'badge-cash' }}">
                                {{ $isInsured ? '🛡️ الضمان الصحي' : '💵 حساب نقدي' }}
                            </span>
                        </div>

                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-user-md text-secondary me-1"></i> الطبيب المحول:</span>
                            <span class="p-val">د. {{ $doctorName }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-clinic-medical text-secondary me-1"></i> العيادة / القسم:</span>
                            <span class="p-val">{{ $departmentName }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-calendar-alt text-secondary me-1"></i> تاريخ وتوقيت الفحص:</span>
                            <span class="p-val">{{ $examDate }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-folder text-secondary me-1"></i> رقم الملف الطبي:</span>
                            <span class="p-val">{{ $patientMedNo }}</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Required Scan Banner -->
                <div class="scan-banner">
                    <div>
                        <span class="scan-meta d-block" style="font-size: 10px; color: #0284c7;">نوع الفحص المطلوب (Procedure / Study):</span>
                        <span class="scan-title">{{ $scanName }}</span>
                    </div>
                    <div style="text-align: left; direction: ltr;">
                        <span class="scan-meta d-block" style="font-size: 10px;">التصنيف:</span>
                        <span style="font-size: 11.5px; font-weight: 800; color: #005a9c;">{{ $scanCategory }}</span>
                    </div>
                </div>

                <!-- 5. Clinical Findings Box (المشاهدات والتقرير الطبي المعتمد) -->
                <div class="section-box">
                    <div class="section-header">
                        <i class="fas fa-file-medical-alt text-primary"></i>
                        <span>المشاهدات والتقرير الشعاعي السريري (Clinical Findings)</span>
                    </div>
                    <div class="section-body findings-body">@if(!empty(trim($findings))){{ trim($findings) }}@else<span style="color: #94a3b8; font-style: italic;">لم يتم تدوين المشاهدات التفصيلية للفحص بعد.</span>@endif</div>
                </div>

                <!-- 6. Impression Box (الانطباع التشخيصي) -->
                @if(!empty(trim($impression)))
                    <div class="impression-box">
                        <div class="impression-title">
                            <i class="fas fa-check-circle me-1"></i> الانطباع والتشخيص النهائي (Impression / Conclusion):
                        </div>
                        <div class="impression-text">
                            {{ trim($impression) }}
                        </div>
                    </div>
                @endif

                <!-- 7. Recommendations Box (التوصيات) -->
                @if(!empty(trim($recommendations)))
                    <div class="recommendation-box">
                        <div class="rec-title">
                            <i class="fas fa-notes-medical me-1"></i> التوصيات والمتابعة (Recommendations):
                        </div>
                        <div class="rec-text">
                            {{ trim($recommendations) }}
                        </div>
                    </div>
                @endif

                <!-- 8. Attached Scan Images (إن وجدت) -->
                @if(count($images) > 0)
                    <div class="section-box" style="margin-top: 10px;">
                        <div class="section-header">
                            <i class="fas fa-images text-primary"></i>
                            <span>الصور والمرفقات الإشعاعية التوضيحية ({{ count($images) }})</span>
                        </div>
                        <div style="padding: 10px;">
                            <div class="images-grid">
                                @foreach(array_slice($images, 0, 3) as $img)
                                    @php
                                        $imgUrl = asset('storage/' . $img);
                                    @endphp
                                    <div class="image-cell">
                                        <img src="{{ $imgUrl }}" alt="Radiology Scan">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- 9. Official Authentication & Signatures Block (مكان التوقيع فارغ للاختبار) -->
                <div class="auth-block">
                    <!-- 1. Doctor / Radiologist Signature (مربع التوقيع فارغ تماماً للاختبار اليدوي) -->
                    <div class="auth-cell">
                        <div class="signature-box-empty">
                            <div class="sig-title">أخصائي الأشعة والتصوير الطبي / فاحص السونار</div>
                            <div class="sig-subtitle">د. {{ $radiologistName }}</div>
                            <!-- مكان التوقيع فارغ -->
                            <div class="sig-empty-line">
                                (مكان التوقيع والاعتماد)
                            </div>
                            <div style="font-size: 10px; color: #64748b; font-weight: 700; margin-top: 2px;">
                                التاريخ: {{ now()->format('Y-m-d') }}
                            </div>
                        </div>
                    </div>

                    <!-- 2. Official Hospital / Dept Stamp -->
                    <div class="auth-cell">
                        <div class="stamp-circle-box">
                            <div style="font-size: 8px; font-weight: 800; color: #005a9c;">★ ★ ★</div>
                            <div style="font-size: 9.5px; font-weight: 900; line-height: 1.2;">مستشفى الكفاءات</div>
                            <div style="font-size: 8.5px; font-weight: 800; color: #0284c7;">الأشعة والسونار</div>
                            <div style="font-size: 8px; color: #b91c1c; font-weight: 900; margin-top: 1px;">مُعتمَــد</div>
                        </div>
                    </div>

                    <!-- 3. Electronic Verification QR Code -->
                    <div class="auth-cell">
                        <div class="qr-render-box">
                            <div class="qr-image-wrapper">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($verifyUrl) }}" alt="QR Code">
                            </div>
                            <div class="qr-label">
                                RAD-#{{ $radiology->id }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 10. Official Footer -->
            <div class="report-footer">
                <div class="footer-contacts">
                    <span>📍 بغداد - الحارثية - شارع الكندي</span>
                    <span>📞 هاتف: +964 (0) 778 050 7060</span>
                    <span>📧 info@alkafaathospital.com</span>
                </div>
                <div>
                    <span>صفحة 1 من 1 • Al-Kafa'at Hospital HIS</span>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
