<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $patient = $surgery->patient;
        $patientUser = $patient?->user;
        $patientName = $patientUser?->name ?? 'مريض غير محدد';
        $patientPhone = $patientUser?->phone ?? $patient?->phone ?? '—';
        $patientAge = $patient?->age ?? '—';
        $patientGender = $patient?->gender === 'male' ? 'ذكر' : ($patient?->gender === 'female' ? 'أنثى' : '—');
        $patientMedNo = $patient?->medical_number ?? ($patient?->id ? '#' . $patient->id : '—');

        $insuranceType = $surgery->insurance_type ?? $patient?->insurance_type ?? 'none';
        $isInsured = ($insuranceType && $insuranceType !== 'none');

        $doctor = $surgery->doctor;
        $surgeonName = $doctor?->user?->name ?? $surgery->surgeon_name ?? 'الاستشارية الجراحية';

        $primaryAnesthName = $surgery->anesthesiaStation?->anesthesiologist?->user?->name
            ?? $surgery->anesthesiologist?->user?->name
            ?? 'غير محدد';
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
            'completed' => 'مكتملة (Post-Op)',
            'in_progress' => 'جارية داخل الصالة (Intra-Op)',
            'waiting' => 'في قائمة الانتظار (Pre-Op)',
            'scheduled' => 'مجدولة (Scheduled)',
            'cancelled' => 'ملغاة (Cancelled)',
            default => $surgery->status,
        };
        $statusColor = match($surgery->status) {
            'completed' => '#059669',
            'in_progress' => '#0284c7',
            'waiting' => '#d97706',
            'cancelled' => '#dc2626',
            default => '#475569',
        };

        $roomName = $surgery->room ? ('غرفة ' . $surgery->room->room_number) : ($surgery->location?->name ?? 'صالة العمليات');
        $additionalOps = $surgery->additionalOperations ?? collect();
        $treatments = ($surgery->surgeryTreatments ?? collect())->where('item_type', \App\Models\SurgeryTreatment::TYPE_MEDICATION);
        $devices = $surgery->medicalDevices ?? collect();
    @endphp

    <title>تقرير العملية الجراحية - OR-#{{ $surgery->id }} - {{ $patientName }}</title>

    <!-- Google Fonts Cairo & Tajawal -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #0f172a;
        }

        body {
            direction: rtl;
            text-align: right;
            background-color: #f1f5f9;
            padding: 20px 10px;
            font-size: 11.5px;
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
            padding: 10mm 14mm 10mm 14mm;
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
            background-size: 45% auto;
            opacity: 0.04;
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
            font-size: 12px;
            font-weight: 800;
            color: #0284c7;
            margin-bottom: 1px;
        }
        .header-hospital-ar p {
            font-size: 9.5px;
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
            font-size: 11px;
            font-weight: 800;
            color: #0284c7;
            margin-bottom: 1px;
        }
        .header-hospital-en p {
            font-size: 9px;
            color: #64748b;
            font-weight: 600;
        }

        /* Document Title Banner */
        .document-title-banner {
            background: linear-gradient(90deg, #005a9c 0%, #0284c7 100%);
            color: #ffffff;
            text-align: center;
            padding: 6px 14px;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .document-title-banner h3 {
            font-size: 13.5px;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: 0.3px;
        }
        .banner-badges {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .report-code-badge {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 10px;
            border-radius: 20px;
            direction: ltr;
            border: 1px solid rgba(255, 255, 255, 0.35);
        }
        .report-status-badge {
            background: #ffffff;
            color: {{ $statusColor }};
            font-size: 10.5px;
            font-weight: 800;
            padding: 2px 9px;
            border-radius: 20px;
        }

        /* Patient Info Section */
        .patient-card {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background-color: #f8fafc;
            padding: 9px 12px;
            margin-bottom: 10px;
        }
        .patient-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 7px 12px;
        }
        .p-field {
            display: flex;
            flex-direction: column;
        }
        .p-label {
            font-size: 9.5px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 1px;
        }
        .p-val {
            font-size: 11.5px;
            font-weight: 800;
            color: #0f172a;
        }
        .badge-coverage {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 10px;
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
            border-radius: 7px;
            margin-bottom: 9px;
            overflow: hidden;
            background: #ffffff;
        }
        .section-header {
            background-color: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 5px 12px;
            font-size: 11.5px;
            font-weight: 800;
            color: #005a9c;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .section-header-title {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .section-body {
            padding: 8px 12px;
            font-size: 11px;
            line-height: 1.55;
            color: #1e293b;
        }

        /* Two columns row */
        .grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 9px;
            margin-bottom: 9px;
        }

        /* Medical Team Grid */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            padding: 6px 10px;
            background: #f8fafc;
            border-radius: 6px;
        }
        .team-member {
            display: flex;
            flex-direction: column;
        }
        .team-role {
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
        }
        .team-name {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Highlight Boxes */
        .highlight-box-blue {
            border-right: 3.5px solid #005a9c;
            background-color: #f8fafc;
            padding: 7px 10px;
            border-radius: 5px;
            margin-bottom: 6px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
        }
        .highlight-title {
            font-size: 10px;
            font-weight: 800;
            color: #005a9c;
            margin-bottom: 2px;
        }
        .highlight-text {
            font-size: 11px;
            font-weight: 600;
            color: #0f172a;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .highlight-box-green {
            border-right: 3.5px solid #059669;
            background-color: #f0fdf4;
            padding: 7px 10px;
            border-radius: 5px;
            margin-bottom: 6px;
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
            border-right: 3.5px solid #d97706;
            background-color: #fffbeb;
            padding: 7px 10px;
            border-radius: 5px;
            margin-bottom: 6px;
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
            font-size: 10.5px;
        }
        .report-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
            text-align: right;
        }
        .report-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            color: #0f172a;
        }

        /* Signatures Block */
        .auth-block {
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            padding: 10px 14px;
            background-color: #ffffff;
            margin-top: 8px;
            display: grid;
            grid-template-columns: 1.5fr 1.5fr 1fr 1fr;
            gap: 12px;
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
            height: 38px;
            border-bottom: 1.5px dashed #005a9c;
            margin-bottom: 4px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            color: #94a3b8;
            font-size: 10px;
            font-style: italic;
        }
        .sig-title {
            font-size: 10.5px;
            font-weight: 800;
            color: #005a9c;
            margin-bottom: 1px;
        }
        .sig-subtitle {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 600;
        }

        /* Hospital Stamp */
        .stamp-circle-box {
            width: 76px;
            height: 76px;
            border: 2px dashed #005a9c;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #005a9c;
            background: #f8fafc;
            padding: 3px;
        }
        .stamp-circle-box span {
            font-size: 8px;
            font-weight: 800;
            line-height: 1.15;
        }

        /* QR Placeholder */
        .qr-render-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .qr-code-stub {
            width: 65px;
            height: 65px;
            border: 1px solid #cbd5e1;
            padding: 3px;
            border-radius: 4px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .qr-code-stub i {
            font-size: 32px;
            color: #005a9c;
        }
        .qr-label {
            font-size: 8px;
            font-weight: 700;
            color: #005a9c;
            margin-top: 2px;
            direction: ltr;
        }

        /* Report Footer */
        .report-footer {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
            color: #64748b;
            font-weight: 600;
        }
        .footer-contacts {
            display: flex;
            gap: 14px;
        }

        /* Print Media Queries */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
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
                border: 1.5px solid #005a9c !important;
                margin: 0 auto !important;
                padding: 10mm 12mm 8mm 12mm !important;
                page-break-after: avoid;
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
                <i class="fas fa-file-medical me-1"></i> استمارة التقرير الجراحي المعتمد للعملية
            </span>
            <span style="color: #cbd5e1;">|</span>
            <span class="badge-coverage {{ $isInsured ? 'badge-insurance' : 'badge-cash' }}">
                {{ $isInsured ? '🛡️ مريض مسجل بالضمان الصحي' : '💵 حساب نقدي (كاش)' }}
            </span>
            <span style="color: #cbd5e1;">|</span>
            <span style="font-weight: 700; color: {{ $statusColor }};">
                <i class="fas fa-circle" style="font-size: 8px;"></i> {{ $statusLabel }}
            </span>
        </div>
        <div class="action-group">
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> طباعة التقرير (Print)
            </button>
            <button class="btn btn-pdf" onclick="window.print()">
                <i class="fas fa-file-pdf"></i> حفظ كـ PDF
            </button>
            <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-back">
                <i class="fas fa-arrow-right"></i> رجوع للعملية
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
                        <h4>قسم العمليات الجراحية والتخدير</h4>
                        <p>جناح العمليات الكبرى • التقرير الجراحي المعتمد</p>
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
                    <h3>تقرير العملية الجراحية المعتمد (Operative & Surgical Report)</h3>
                    <div class="banner-badges">
                        <span class="report-code-badge">OR-#{{ $surgery->id }}</span>
                        <span class="report-status-badge">{{ $statusLabel }}</span>
                    </div>
                </div>

                <!-- 3. Patient Information Grid -->
                <div class="patient-card">
                    <div class="patient-grid">
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-user text-secondary me-1"></i> اسم المريض:</span>
                            <span class="p-val" style="color: #005a9c; font-size: 12px;">{{ $patientName }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-birthday-cake text-secondary me-1"></i> العمر والجنس:</span>
                            <span class="p-val">{{ $patientAge }} سنة • {{ $patientGender }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-hashtag text-secondary me-1"></i> رقم الملف / القيد:</span>
                            <span class="p-val" style="direction: ltr; text-align: right;">{{ $patientMedNo }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-phone text-secondary me-1"></i> رقم الهاتف:</span>
                            <span class="p-val" style="direction: ltr; text-align: right;">{{ $patientPhone }}</span>
                        </div>

                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-shield-alt text-secondary me-1"></i> التغطية المالية:</span>
                            <span class="badge-coverage {{ $isInsured ? 'badge-insurance' : 'badge-cash' }}">
                                {{ $isInsured ? '🛡️ الضمان الصحي' : '💵 حساب نقدي' }}
                            </span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-door-open text-secondary me-1"></i> الموقع / الصالة:</span>
                            <span class="p-val">{{ $roomName }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-calendar-alt text-secondary me-1"></i> تاريخ وموعد العملية:</span>
                            <span class="p-val">{{ $scheduledDate }} • {{ $scheduledTime }}</span>
                        </div>
                        <div class="p-field">
                            <span class="p-label"><i class="fas fa-clock text-secondary me-1"></i> توقيت الإجراء:</span>
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
                            <span>الفريق الطبي والجراحي المشرف (Surgical & Anesthesia Team)</span>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="team-grid">
                            <div class="team-member">
                                <span class="team-role">الطبيب الجراح الرئيسي:</span>
                                <span class="team-name" style="color: #005a9c;">د. {{ $surgeonName }}</span>
                            </div>
                            <div class="team-member">
                                <span class="team-role">مساعد الجراح:</span>
                                <span class="team-name">{{ $surgicalAssistantName ?: '—' }}</span>
                            </div>
                            <div class="team-member">
                                <span class="team-role">طبيب التخدير المشرف:</span>
                                <span class="team-name" style="color: #0284c7;">د. {{ $primaryAnesthName }}</span>
                            </div>
                            <div class="team-member">
                                <span class="team-role">مساعد / كادر التخدير:</span>
                                <span class="team-name">
                                    {{ $secondaryAnesthName ? ('د. ' . $secondaryAnesthName) : ($anesthesiaAssistantName ?: '—') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Procedure & Operations Overview -->
                <div class="section-box">
                    <div class="section-header">
                        <div class="section-header-title">
                            <i class="fas fa-procedures"></i>
                            <span>الإجراء الجراحي والعمليات المنفذة (Surgical Procedures)</span>
                        </div>
                        <span style="font-size: 10px; font-weight: 700; color: #64748b;">
                            التصنيف: <strong>{{ $surgery->surgery_classification ?? 'عملية كبرى' }}</strong>
                        </span>
                    </div>
                    <div class="section-body">
                        <div style="font-size: 12px; font-weight: 800; color: #005a9c; margin-bottom: 4px;">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            العملية الأساسية: {{ $surgery->surgery_type ?? $surgery->surgicalOperation?->name ?? 'غير محدد' }}
                        </div>

                        @if($additionalOps->count() > 0)
                            <div style="margin-top: 6px;">
                                <div style="font-size: 10px; font-weight: 800; color: #475569; margin-bottom: 3px;">عمليات وإجراءات جراحية إضافية منفذة:</div>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 35%;">اسم العملية الإضافية</th>
                                            <th style="width: 25%;">التصنيف</th>
                                            <th>ملاحظات وتفاصيل الجراح</th>
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
                                <span>التشخيص الطبي والتوصيات الجراحية</span>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="highlight-box-blue">
                                <div class="highlight-title">التشخيص الطبي للجراح (Surgeon Diagnosis):</div>
                                <div class="highlight-text">{{ $surgery->diagnosis ?: 'لم يُسجل تشخيص بعد' }}</div>
                            </div>

                            @if($surgery->description)
                                <div class="highlight-box-amber">
                                    <div class="highlight-title">الوصف والتقنية الجراحية (Operative Technique):</div>
                                    <div class="highlight-text">{{ $surgery->description }}</div>
                                </div>
                            @endif

                            @if($surgery->doctor_recommendation)
                                <div class="highlight-box-green">
                                    <div class="highlight-title">توصيات الجراح وخطة المتابعة:</div>
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
                                <span>توثيق وخطة التخدير (Anesthesia Protocol)</span>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="highlight-box-blue">
                                <div class="highlight-title">نوع التخدير المعتمد (Anesthesia Type):</div>
                                <div class="highlight-text" style="font-weight: 800; color: #0284c7;">
                                    {{ $currentAnesthesiaType ?: 'لم يُحدد نوع التخدير' }}
                                </div>
                            </div>

                            <div class="highlight-box-amber">
                                <div class="highlight-title">ملاحظات التخدير وتقييم الإفاقة:</div>
                                <div class="highlight-text">{{ $currentAnesthesiaNotes ?: 'لم تُسجل ملاحظات تخدير خاصة' }}</div>
                            </div>

                            @if($devices->count() > 0)
                                <div style="margin-top: 4px; font-size: 10px; color: #475569;">
                                    <strong>الأجهزة الطبية المستخدمة:</strong>
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
                                <span>الأدوية والعلاجات المصروفة أثناء العملية (Medications Administered)</span>
                            </div>
                        </div>
                        <div class="section-body" style="padding: 4px 10px;">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>الدواء / المحلول</th>
                                        <th>الجرعة</th>
                                        <th>طريقة الإعطاء</th>
                                        <th>التعليمات والتوقيت</th>
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
                            <span>توقيع الطبيب الجراح</span>
                        </div>
                        <div class="sig-title">د. {{ $surgeonName }}</div>
                        <div class="sig-subtitle">الطبيب الجراح المشرف</div>
                    </div>

                    <div class="auth-cell">
                        <div class="sig-empty-line">
                            <span>توقيع طبيب التخدير</span>
                        </div>
                        <div class="sig-title">د. {{ $primaryAnesthName }}</div>
                        <div class="sig-subtitle">أخصائي التخدير والعناية المركزة</div>
                    </div>

                    <div class="auth-cell">
                        <div class="stamp-circle-box">
                            <i class="fas fa-stamp" style="font-size: 16px; margin-bottom: 2px;"></i>
                            <span>ختم صالة العمليات</span>
                            <span style="font-size: 7px; color: #64748b;">معتمد رسمياً</span>
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
                    <span>رقم ترخيص المستشفى: <strong>{{ $licenseNumber }}</strong></span>
                </div>
                <div class="footer-contacts">
                    <span><i class="fas fa-map-marker-alt me-1"></i> {{ $hospitalAddress }}</span>
                    <span><i class="fas fa-phone me-1"></i> {{ $hospitalPhone }}</span>
                    <span><i class="fas fa-clock me-1"></i> طُبع في: {{ now()->format('Y-m-d H:i') }}</span>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
