<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $patient = $surgery->patient;
        $patientUser = $patient?->user;
        $patientName = $patientUser?->name ?? __('مريض غير محدد');
        $patientPhone = $patientUser?->phone ?? $patient?->phone ?? '—';
        $patientAge = $patient?->age ?? '—';
        $patientGender = $patient?->gender === 'male' ? __('ذكر') : ($patient?->gender === 'female' ? __('أنثى') : '—');
        $patientMedNo = $patient?->medical_number ?? ($patient?->id ? '#' . $patient->id : '—');

        $insuranceType = $surgery->insurance_type ?? $patient?->insurance_type ?? 'none';
        $isInsured = ($insuranceType && $insuranceType !== 'none');

        $doctor = $surgery->doctor;
        $surgeonName = $doctor?->user?->name ?? $surgery->surgeon_name ?? 'الاستشارية الجراحية';

        $primaryAnesthName = $surgery->anesthesiaStation?->anesthesiologist?->user?->name
            ?? $surgery->anesthesiologist?->user?->name
            ?? null;
        $secondaryAnesthName = $surgery->anesthesiaStation?->anesthesiologist2?->user?->name
            ?? $surgery->anesthesiologist2?->user?->name
            ?? null;
        $anesthesiaAssistantName = $surgery->anesthesia_assistant_name
            ?? $surgery->anesthesiaStation?->assistant_name
            ?? null;

        $surgicalAssistantName = $surgery->surgical_assistant_name ?? null;

        $currentAnesthesiaType = $surgery->anesthesiaStation?->anesthesia_type ?? $surgery->anesthesia_type ?? '';
        $currentAnesthesiaNotes = $surgery->anesthesiaStation?->notes ?? '';

        $surgeryStartFormatted = '';
        if ($surgery->start_time) {
            $surgeryStartFormatted = is_string($surgery->start_time)
                ? \Carbon\Carbon::parse($surgery->start_time)->format('H:i')
                : $surgery->start_time->format('H:i');
        } elseif ($surgery->started_at) {
            $surgeryStartFormatted = $surgery->started_at->format('H:i');
        }

        $surgeryEndFormatted = '';
        if ($surgery->end_time) {
            $surgeryEndFormatted = is_string($surgery->end_time)
                ? \Carbon\Carbon::parse($surgery->end_time)->format('H:i')
                : $surgery->end_time->format('H:i');
        }

        $scheduledDate = $surgery->scheduled_date ? \Carbon\Carbon::parse($surgery->scheduled_date)->format('Y-m-d') : '—';
        $scheduledTime = $surgery->scheduled_time ?? '—';

        $hospitalNameAr = $hospital?->name ?? 'مستشفى الكفاءات الأهلي';
        $hospitalNameEn = 'Al-Kafa\'at Private Hospital';
        $hospitalPhone = $hospital?->phone ?? '0770 000 0000';
        $hospitalAddress = $hospital?->address ?? 'العراق - بغداد';
        $licenseNumber = $hospital?->license_number ?? 'LIC-OR-2025/11';

        $hospitalLogoFile = public_path('images/1.jpg');
        $hospitalLogoSrc = file_exists($hospitalLogoFile) && filesize($hospitalLogoFile) > 0
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($hospitalLogoFile))
            : asset('images/1.jpg');

        $statusLabel = match($surgery->status) {
            'completed' => __('مكتملة (Post-Op)'),
            'in_progress' => __('جارية داخل الصالة (Intra-Op)'),
            'waiting' => __('في قائمة الانتظار (Pre-Op)'),
            'scheduled' => __('مجدولة (Scheduled)'),
            'cancelled' => __('ملغاة (Cancelled)'),
            default => $surgery->status,
        };
        $statusColor = match($surgery->status) {
            'completed' => '#059669',
            'in_progress' => '#0284c7',
            'waiting' => '#d97706',
            'cancelled' => '#dc2626',
            default => '#475569',
        };

        $roomName = $surgery->room ? (__('غرفة') . ' ' . $surgery->room->room_number) : ($surgery->location?->name ?? __('صالة العمليات'));
        $additionalOps = $surgery->additionalOperations ?? collect();
        $treatments = ($surgery->surgeryTreatments ?? collect())->where('item_type', \App\Models\SurgeryTreatment::TYPE_MEDICATION);
        $devices = $surgery->medicalDevices ?? collect();
    @endphp

    <title>{{ __('تقرير العملية الجراحية') }} - OR-#{{ $surgery->id }} - {{ $patientName }}</title>

    <!-- Google Fonts Cairo & Tajawal -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @page {
            size: A4 portrait;
            margin: 4mm 6mm !important;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #0f172a;
        }

        body {
            direction: {{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }};
            text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
            background-color: #f1f5f9;
            padding: 12px 10px;
            font-size: 11px;
            line-height: 1.4;
        }

        /* Top Action Toolbar (Screen Only) */
        .no-print {
            max-width: 210mm;
            margin: 0 auto 12px auto;
            background: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border: 1px solid #e2e8f0;
        }

        .action-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 7px 14px;
            border-radius: 7px;
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
            min-height: 275mm;
            max-height: 295mm;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 6mm 10mm 5mm 10mm;
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
            top: 3mm;
            right: 3mm;
            bottom: 3mm;
            left: 3mm;
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
            background-size: 42% auto;
            opacity: 0.035;
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
            padding-bottom: 5px;
            margin-bottom: 5px;
            border-bottom: 1.5px solid #005a9c;
        }

        .header-hospital-ar {
            text-align: right;
            flex: 1;
        }
        .header-hospital-ar h2 {
            font-size: 15px;
            font-weight: 900;
            color: #005a9c;
            margin-bottom: 1px;
            line-height: 1.15;
        }
        .header-hospital-ar h4 {
            font-size: 11px;
            font-weight: 800;
            color: #0284c7;
            margin-bottom: 1px;
        }
        .header-hospital-ar p {
            font-size: 8.5px;
            color: #64748b;
            font-weight: 600;
        }

        .header-logo {
            text-align: center;
            width: 85px;
            padding: 0 6px;
        }
        .header-logo img {
            max-width: 80px;
            max-height: 50px;
            object-fit: contain;
        }

        .header-hospital-en {
            text-align: left;
            direction: ltr;
            flex: 1;
        }
        .header-hospital-en h2 {
            font-size: 13.5px;
            font-weight: 900;
            color: #005a9c;
            margin-bottom: 1px;
            line-height: 1.15;
        }
        .header-hospital-en h4 {
            font-size: 10px;
            font-weight: 800;
            color: #0284c7;
            margin-bottom: 1px;
        }
        .header-hospital-en p {
            font-size: 8px;
            color: #64748b;
            font-weight: 600;
        }

        /* Document Title Banner */
        .document-title-banner {
            background: linear-gradient(90deg, #005a9c 0%, #0284c7 100%);
            color: #ffffff;
            text-align: center;
            padding: 4px 10px;
            border-radius: 5px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .document-title-banner h3 {
            font-size: 12px;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: 0.2px;
        }
        .banner-badges {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .report-code-badge {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            padding: 1px 8px;
            border-radius: 20px;
            direction: ltr;
            border: 1px solid rgba(255, 255, 255, 0.35);
        }
        .report-status-badge {
            background: #ffffff;
            color: {{ $statusColor }};
            font-size: 9.5px;
            font-weight: 800;
            padding: 1px 8px;
            border-radius: 20px;
        }

        /* Patient Info Section */
        .patient-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: #f8fafc;
            padding: 5px 10px;
            margin-bottom: 5px;
        }
        .patient-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px 10px;
        }
        .p-field {
            display: flex;
            flex-direction: column;
        }
        .p-label {
            font-size: 8.5px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 1px;
        }
        .p-val {
            font-size: 10.5px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.25;
        }
        .badge-coverage {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 9px;
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

        /* Section Cards */
        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 5px;
            overflow: hidden;
            background: #ffffff;
        }
        .section-header {
            background-color: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 3px 8px;
            font-size: 10.5px;
            font-weight: 800;
            color: #005a9c;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .section-header-title {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .section-body {
            padding: 5px 8px;
            font-size: 10px;
            line-height: 1.4;
            color: #1e293b;
        }

        /* Two columns row */
        .grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 5px;
        }

        /* Medical Team Grid */
        .team-grid {
            display: grid;
            gap: 6px;
            padding: 4px 8px;
            background: #f8fafc;
            border-radius: 5px;
        }
        .team-member {
            display: flex;
            flex-direction: column;
        }
        .team-role {
            font-size: 8.5px;
            font-weight: 700;
            color: #64748b;
        }
        .team-name {
            font-size: 10.5px;
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Highlight Boxes */
        .highlight-box-blue {
            border-right: 3px solid #005a9c;
            background-color: #f8fafc;
            padding: 4px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
        }
        .highlight-title {
            font-size: 9px;
            font-weight: 800;
            color: #005a9c;
            margin-bottom: 1px;
        }
        .highlight-text {
            font-size: 10px;
            font-weight: 600;
            color: #0f172a;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.35;
        }

        .highlight-box-green {
            border-right: 3px solid #059669;
            background-color: #f0fdf4;
            padding: 4px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
            border-top: 1px solid #bbf7d0;
            border-bottom: 1px solid #bbf7d0;
            border-left: 1px solid #bbf7d0;
        }
        .highlight-box-green .highlight-title {
            color: #059669;
        }
        .highlight-box-green .highlight-text {
            color: #14532d;
        }

        .highlight-box-amber {
            border-right: 3px solid #d97706;
            background-color: #fffbeb;
            padding: 4px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
            border-top: 1px solid #fde68a;
            border-bottom: 1px solid #fde68a;
            border-left: 1px solid #fde68a;
        }
        .highlight-box-amber .highlight-title {
            color: #b45309;
        }
        .highlight-box-amber .highlight-text {
            color: #78350f;
        }

        /* Mini Tables */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .report-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            padding: 3px 6px;
            border: 1px solid #cbd5e1;
            text-align: right;
        }
        .report-table td {
            padding: 3px 6px;
            border: 1px solid #e2e8f0;
            color: #0f172a;
        }

        /* Signatures Block */
        .auth-block {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 5px 8px;
            background-color: #ffffff;
            margin-top: 5px;
            display: grid;
            grid-template-columns: 1.5fr 1.5fr 1fr 1fr;
            gap: 8px;
            align-items: center;
        }
        .auth-cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .sig-empty-line {
            width: 85%;
            height: 22px;
            border-bottom: 1.5px dashed #005a9c;
            margin-bottom: 3px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            color: #94a3b8;
            font-size: 9px;
            font-style: italic;
        }
        .sig-title {
            font-size: 10px;
            font-weight: 800;
            color: #005a9c;
            margin-bottom: 1px;
            white-space: nowrap;
        }
        .sig-subtitle {
            font-size: 8.5px;
            color: #64748b;
            font-weight: 600;
        }

        /* Hospital Stamp */
        .stamp-circle-box {
            width: 54px;
            height: 54px;
            border: 1.5px dashed #005a9c;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #005a9c;
            background: #f8fafc;
            padding: 2px;
        }
        .stamp-circle-box i {
            font-size: 13px !important;
            margin-bottom: 1px !important;
        }
        .stamp-circle-box span {
            font-size: 7.5px;
            font-weight: 800;
            line-height: 1.1;
        }

        /* QR Placeholder */
        .qr-render-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .qr-code-stub {
            width: 46px;
            height: 46px;
            border: 1px solid #cbd5e1;
            padding: 2px;
            border-radius: 4px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .qr-code-stub i {
            font-size: 24px;
            color: #005a9c;
        }
        .qr-label {
            font-size: 7.5px;
            font-weight: 700;
            color: #005a9c;
            margin-top: 1px;
            direction: ltr;
        }

        /* Report Footer */
        .report-footer {
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8.5px;
            color: #64748b;
            font-weight: 600;
        }
        .footer-contacts {
            display: flex;
            gap: 12px;
        }

        /* Print Media Queries */
        @media print {
            html, body {
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                overflow: hidden !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet-container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .report-page {
                box-shadow: none !important;
                border: 1.2px solid #005a9c !important;
                margin: 0 !important;
                padding: 4mm 8mm 3mm 8mm !important;
                width: 100% !important;
                min-height: auto !important;
                max-height: 288mm !important;
                height: auto !important;
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .page-border-inner {
                border-color: #7dd3fc !important;
                top: 2mm !important;
                right: 2mm !important;
                bottom: 2mm !important;
                left: 2mm !important;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Toolbar (Screen Only) -->
    <div class="no-print">
        <div class="action-group">
            <span style="font-weight: 800; color: #005a9c; font-size: 13.5px;">
                <i class="fas fa-file-medical me-1"></i> {{ __('استمارة التقرير الجراحي المعتمد للعملية') }}
            </span>
            <span style="color: #cbd5e1;">|</span>
            <span class="badge-coverage {{ $isInsured ? 'badge-insurance' : 'badge-cash' }}">
                {{ $isInsured ? __('🛡️ مريض مسجل بالضمان الصحي') : __('💵 حساب نقدي (كاش)') }}
            </span>
            <span style="color: #cbd5e1;">|</span>
            <span style="font-weight: 700; color: {{ $statusColor }};">
                <i class="fas fa-circle" style="font-size: 8px;"></i> {{ $statusLabel }}
            </span>
        </div>
        <div class="action-group">
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> {{ __('طباعة التقرير (Print)') }}
            </button>
            <button class="btn btn-pdf" onclick="window.print()">
                <i class="fas fa-file-pdf"></i> {{ __('حفظ كـ PDF') }}
            </button>
            <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-back">
                <i class="fas fa-arrow-right"></i> {{ __('رجوع للعملية') }}
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
                        <h2>{{ $hospitalNameAr }}</h2>
                        <h4>{{ __('قسم العمليات الجراحية والتخدير') }}</h4>
                        <p>{{ __('جناح العمليات الكبرى • التقرير الجراحي المعتمد') }}</p>
                    </div>
                    <div class="header-logo">
                        <img src="{!! $hospitalLogoSrc !!}" 
                             alt="Hospital Logo" 
                             class="hospital-logo-img" 
                             onerror="this.onerror=null; this.src='{{ asset('images/1.jpg') }}';">
                    </div>
                    <div class="header-hospital-en">
                        <h2>{{ $hospitalNameEn }}</h2>
                        <h4>Department of Surgery & Anesthesia</h4>
                        <p>Major Operating Theaters • Official Surgical Report</p>
                    </div>
                </div>

                <!-- 2. Document Title Banner -->
                <div class="document-title-banner">
                    <h3>{{ __('تقرير العملية الجراحية المعتمد (Operative & Surgical Report)') }}</h3>
                    <div class="banner-badges">
                        <span class="report-code-badge">OR-#{{ $surgery->id }}</span>
                        <span class="report-status-badge">{{ $statusLabel }}</span>
                    </div>
                </div>

                <!-- 3. Patient Information Grid -->
                <div class="patient-card">
                    <div class="patient-grid">
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-user text-secondary me-1"></i> {{ __('اسم المريض:') }}</span>
                            <span class="p-val" style="color: #005a9c; font-size: 12px;">{{ $patientName }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-birthday-cake text-secondary me-1"></i> {{ __('العمر والجنس:') }}</span>
                            <span class="p-val">{{ $patientAge }} {{ __('سنة') }} • {{ $patientGender }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-hashtag text-secondary me-1"></i> {{ __('رقم الملف / القيد:') }}</span>
                            <span class="p-val" style="direction: ltr; text-align: right;">{{ $patientMedNo }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-phone text-secondary me-1"></i> {{ __('رقم الهاتف:') }}</span>
                            <span class="p-val" style="direction: ltr; text-align: right;">{{ $patientPhone }}</span>
                        </div>

                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-shield-alt text-secondary me-1"></i> {{ __('التغطية المالية:') }}</span>
                            <span class="badge-coverage {{ $isInsured ? 'badge-insurance' : 'badge-cash' }}">
                                {{ $isInsured ? __('🛡️ الضمان الصحي') : __('💵 حساب نقدي') }}
                            </span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-door-open text-secondary me-1"></i> {{ __('الموقع / الصالة:') }}</span>
                            <span class="p-val">{{ $roomName }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-calendar-alt text-secondary me-1"></i> {{ __('تاريخ وموعد العملية:') }}</span>
                            <span class="p-val">{{ $scheduledDate }} • {{ $scheduledTime }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-clock text-secondary me-1"></i> {{ __('توقيت الإجراء:') }}</span>
                            <span class="p-val">
                                @if($surgeryStartFormatted || $surgeryEndFormatted)
                                    {{ $surgeryStartFormatted ?: '—' }} إلى {{ $surgeryEndFormatted ?: '—' }}
                                    @if($surgery->duration_minutes) ({{ $surgery->duration_minutes }} دقيقة) @endif
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 4. Medical Team Section -->
                <div class="section-box">
                    <div class="section-header">
                        <div class="section-header-title">
                            <i class="fas fa-user-md"></i>
                            <span>{{ __('الفريق الطبي والجراحي المشرف (Surgical & Anesthesia Team)') }}</span>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="team-grid" style="grid-template-columns: repeat({{ $secondaryAnesthName ? 5 : 4 }}, 1fr);">
                            <div class="team-member">
                                <span class="team-role">{{ __('الطبيب الجراح الرئيسي:') }}</span>
                                <span class="team-name" style="color: #005a9c;">{{ __('د.') }} {{ $surgeonName }}</span>
                            </div>
                            <div class="team-member">
                                <span class="team-role">{{ __('مساعد الجراح:') }}</span>
                                <span class="team-name">{{ $surgicalAssistantName ?: '—' }}</span>
                            </div>
                            <div class="team-member">
                                <span class="team-role">{{ $secondaryAnesthName ? __('طبيب التخدير الأول:') : __('طبيب التخدير المشرف:') }}</span>
                                <span class="team-name" style="color: #0284c7;">{{ $primaryAnesthName ? (__('د.') . ' ' . $primaryAnesthName) : '—' }}</span>
                            </div>
                            @if($secondaryAnesthName)
                                <div class="team-member">
                                    <span class="team-role">{{ __('طبيب التخدير الثاني:') }}</span>
                                    <span class="team-name" style="color: #0284c7;">{{ __('د.') }} {{ $secondaryAnesthName }}</span>
                                </div>
                            @endif
                            <div class="team-member">
                                <span class="team-role">{{ __('مساعد / تقني التخدير:') }}</span>
                                <span class="team-name">{{ $anesthesiaAssistantName ?: '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Procedure & Operations Overview -->
                <div class="section-box">
                    <div class="section-header">
                        <div class="section-header-title">
                            <i class="fas fa-procedures"></i>
                            <span>{{ __('الإجراء الجراحي والعمليات المنفذة (Surgical Procedures)') }}</span>
                        </div>
                        <span style="font-size: 10px; font-weight: 700; color: #64748b;">
                            {{ __('التصنيف:') }} <strong>{{ $surgery->surgery_classification ?? __('عملية كبرى') }}</strong>
                        </span>
                    </div>
                    <div class="section-body">
                        <div style="font-size: 12px; font-weight: 800; color: #005a9c; margin-bottom: 4px;">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            {{ __('العملية الأساسية:') }} {{ $surgery->surgery_type ?? $surgery->surgicalOperation?->name ?? __('غير محدد') }}
                        </div>

                        @if($additionalOps->count() > 0)
                            <div style="margin-top: 6px;">
                                <div style="font-size: 10px; font-weight: 800; color: #475569; margin-bottom: 3px;">{{ __('عمليات وإجراءات جراحية إضافية منفذة:') }}</div>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 35%;">{{ __('اسم العملية الإضافية') }}</th>
                                            <th style="width: 25%;">{{ __('التصنيف') }}</th>
                                            <th>{{ __('ملاحظات وتفاصيل الجراح') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($additionalOps as $op)
                                            <tr>
                                                <td style="font-weight: 700;">{{ $op->surgicalOperation?->name ?? $op->operation_name ?? 'إجراء جراحي' }}</td>
                                                <td>{{ $op->surgicalOperation?->classification ?? 'إضافية' }}</td>
                                                <td>{{ $op->notes ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 6. Two Columns: Diagnosis & Anesthesia -->
                <div class="grid-2col">
                    <!-- Column A: Surgeon Diagnosis & Findings -->
                    <div class="section-box" style="margin-bottom: 0;">
                        <div class="section-header">
                            <div class="section-header-title">
                                <i class="fas fa-stethoscope text-primary"></i>
                                <span>{{ __('التشخيص الطبي والتوصيات الجراحية') }}</span>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="highlight-box-blue">
                                <div class="highlight-title">{{ __('التشخيص الطبي للجراح (Surgeon Diagnosis):') }}</div>
                                <div class="highlight-text">{{ $surgery->diagnosis ?: __('لم يُسجل تشخيص بعد') }}</div>
                            </div>

                            @if($surgery->description)
                                <div class="highlight-box-amber">
                                    <div class="highlight-title">{{ __('الوصف والتقنية الجراحية (Operative Technique):') }}</div>
                                    <div class="highlight-text">{{ $surgery->description }}</div>
                                </div>
                            @endif

                            @if($surgery->doctor_recommendation)
                                <div class="highlight-box-green">
                                    <div class="highlight-title">{{ __('توصيات الجراح وخطة المتابعة:') }}</div>
                                    <div class="highlight-text">{{ $surgery->doctor_recommendation }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Column B: Anesthesia Documentation -->
                    <div class="section-box" style="margin-bottom: 0;">
                        <div class="section-header">
                            <div class="section-header-title">
                                <i class="fas fa-syringe text-info"></i>
                                <span>{{ __('توثيق وخطة التخدير (Anesthesia Protocol)') }}</span>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="highlight-box-blue">
                                <div class="highlight-title">{{ __('نوع التخدير المعتمد (Anesthesia Type):') }}</div>
                                <div class="highlight-text" style="font-weight: 800; color: #0284c7;">
                                    {{ $currentAnesthesiaType ?: __('لم يُحدد نوع التخدير') }}
                                </div>
                            </div>

                            <div class="highlight-box-amber">
                                <div class="highlight-title">{{ __('ملاحظات التخدير وتقييم الإفاقة:') }}</div>
                                <div class="highlight-text">{{ $currentAnesthesiaNotes ?: __('لم تُسجل ملاحظات تخدير خاصة') }}</div>
                            </div>

                            @if($devices->count() > 0)
                                <div style="margin-top: 4px; font-size: 10px; color: #475569;">
                                    <strong>{{ __('الأجهزة الطبية المستخدمة:') }}</strong>
                                    {{ $devices->pluck('name')->join('، ') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 7. Treatments & Medications (if any) -->
                @if($treatments->count() > 0)
                    <div class="section-box">
                        <div class="section-header">
                            <div class="section-header-title">
                                <i class="fas fa-pills text-danger"></i>
                                <span>{{ __('الأدوية والعلاجات المصروفة أثناء العملية (Medications Administered)') }}</span>
                            </div>
                        </div>
                        <div class="section-body" style="padding: 4px 10px;">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('الدواء / المحلول') }}</th>
                                        <th>{{ __('الجرعة') }}</th>
                                        <th>{{ __('طريقة الإعطاء') }}</th>
                                        <th>{{ __('التعليمات والتوقيت') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($treatments as $tr)
                                        <tr>
                                            <td style="font-weight: 700;">{{ $tr->medication_name ?? $tr->name }}</td>
                                            <td>{{ $tr->dosage ?? '—' }}</td>
                                            <td>{{ $tr->route ?? 'I.V' }}</td>
                                            <td>{{ $tr->instructions ?? $tr->notes ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- 8. Official Authentication & Signatures Block -->
                <div class="auth-block">
                    <div class="auth-cell">
                        <div class="sig-empty-line">
                            <span>{{ __('توقيع الطبيب الجراح') }}</span>
                        </div>
                        <div class="sig-title">{{ __('د.') }} {{ $surgeonName }}</div>
                        <div class="sig-subtitle">{{ __('الطبيب الجراح المشرف') }}</div>
                    </div>

                    <div class="auth-cell">
                        <div class="sig-empty-line">
                            <span>{{ __('توقيع طبيب التخدير') }}</span>
                        </div>
                        <div class="sig-title">
                            {{ $primaryAnesthName ? (__('د.') . ' ' . $primaryAnesthName) : '—' }}
                            @if($secondaryAnesthName)
                                <div style="font-size: 8.5px; color: #475569; font-weight: 700; margin-top: 1px;">
                                    • {{ __('د.') }} {{ $secondaryAnesthName }}
                                </div>
                            @endif
                        </div>
                        <div class="sig-subtitle">{{ __('أخصائي التخدير والعناية المركزة') }}</div>
                    </div>

                    <div class="auth-cell">
                        <div class="stamp-circle-box">
                            <i class="fas fa-stamp" style="font-size: 16px; margin-bottom: 2px;"></i>
                            <span>{{ __('ختم صالة العمليات') }}</span>
                            <span style="font-size: 7px; color: #64748b;">{{ __('معتمد رسمياً') }}</span>
                        </div>
                    </div>

                    <div class="auth-cell">
                        <div class="qr-render-box">
                            <div class="qr-code-stub">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <span class="qr-label">OR-VERIFIED-{{ $surgery->id }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 9. Official Footer -->
            <div class="report-footer">
                <div>
                    <span>{{ __('رقم ترخيص المستشفى:') }} <strong>{{ $licenseNumber }}</strong></span>
                </div>
                <div class="footer-contacts">
                    <span><i class="fas fa-map-marker-alt me-1"></i> {{ $hospitalAddress }}</span>
                    <span><i class="fas fa-phone me-1"></i> {{ $hospitalPhone }}</span>
                    <span><i class="fas fa-clock me-1"></i> {{ __('طُبع في:') }} {{ now()->format('Y-m-d H:i') }}</span>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
