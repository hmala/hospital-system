 <!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير الطبي لحالة الطوارئ - #{{ $emergency->id }} - {{ $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'مريض' }}</title>
    <!-- Google Fonts Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Local QRCode JS Library -->
    <script src="{{ asset('js/qrcode.min.js') }}"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            color: #1a1a1a;
        }

        body {
            direction: rtl;
            text-align: right;
            background-color: #f1f5f9;
            padding: 20px 10px;
            font-size: 13px;
            line-height: 1.45;
        }

        /* Top Action Bar (Screen Only) */
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
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
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
            background-color: #0f766e;
            color: #ffffff;
        }
        .btn-print:hover { background-color: #0d5f58; }

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

        /* A4 Page Container */
        .report-page {
            width: 210mm;
            min-height: 296mm;
            margin: 0 auto 25px auto;
            background: #ffffff;
            padding: 15mm 16mm 14mm 16mm;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Watermark */
        .report-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url('{{ asset('images/1.jpg') }}');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 42% auto;
            opacity: 0.045;
            pointer-events: none;
            z-index: 0;
        }

        .report-page > * {
            position: relative;
            z-index: 1;
        }

        /* Outer Border Frame */
        .page-frame {
            position: absolute;
            top: 6mm;
            right: 6mm;
            bottom: 6mm;
            left: 6mm;
            border: 1.5px solid #0f766e;
            pointer-events: none;
            border-radius: 4px;
        }
        .page-frame-inner {
            position: absolute;
            top: 7.5mm;
            right: 7.5mm;
            bottom: 7.5mm;
            left: 7.5mm;
            border: 0.5px solid #99f6e4;
            pointer-events: none;
            border-radius: 2px;
        }

        /* Header Layout */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .header-hospital-ar {
            text-align: right;
            flex: 1;
        }
        .header-hospital-ar h2 {
            font-size: 19px;
            font-weight: 900;
            color: #0f766e;
            margin-bottom: 2px;
        }
        .header-hospital-ar h4 {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
        }
        .header-hospital-ar p {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
        }

        .header-logo {
            text-align: center;
            flex: 0 0 110px;
        }
        .header-logo img {
            max-width: 90px;
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
            font-weight: 800;
            color: #0f766e;
            margin-bottom: 2px;
        }
        .header-hospital-en h4 {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }
        .header-hospital-en p {
            font-size: 10px;
            color: #64748b;
            font-weight: 600;
        }

        /* Document Title Bar */
        .title-strip {
            background: linear-gradient(90deg, #0f766e, #115e59);
            color: #ffffff;
            padding: 6px 14px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .title-strip h3 {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 0.5px;
            color: #ffffff;
        }
        .copy-type-badge {
            background-color: #ffffff;
            color: #0f766e;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 800;
        }
        .report-ref-badge {
            font-size: 11px;
            font-weight: 700;
            color: #ccfbf1;
            direction: ltr;
        }

        /* Section Cards */
        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 8px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
        }
        .section-title {
            background: #f1f5f9;
            color: #0f766e;
            font-weight: 800;
            font-size: 12px;
            padding: 4px 10px;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px 10px;
            padding: 7px 10px;
            font-size: 11.5px;
        }
        .grid-item {
            display: flex;
            flex-direction: column;
        }
        .grid-item.col-span-2 { grid-column: span 2; }
        .grid-item.col-span-3 { grid-column: span 3; }
        .grid-item.col-span-4 { grid-column: span 4; }

        .grid-label {
            font-size: 10px;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 1px;
        }
        .grid-value {
            font-size: 12px;
            color: #0f172a;
            font-weight: 700;
        }

        /* Vital Signs Bar */
        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px;
            padding: 6px 8px;
            background: #f8fafc;
        }
        .vital-cell {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 6px;
            text-align: center;
        }
        .vital-title {
            font-size: 9.5px;
            font-weight: 700;
            color: #64748b;
        }
        .vital-num {
            font-size: 13px;
            font-weight: 900;
            color: #0f766e;
            margin-top: 1px;
        }
        .vital-unit {
            font-size: 8.5px;
            color: #94a3b8;
        }

        /* Medical Table */
        .med-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .med-table th, .med-table td {
            border: 1px solid #e2e8f0;
            padding: 4px 8px;
            text-align: right;
        }
        .med-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 800;
            font-size: 10.5px;
        }
        .med-table tr:nth-child(even) td {
            background-color: #fafafa;
        }

        /* Clinical Text Box */
        .clinical-text {
            padding: 6px 10px;
            font-size: 11.5px;
            color: #1e293b;
            line-height: 1.45;
            min-height: 28px;
            white-space: pre-wrap;
        }

        /* Badge Styles */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 800;
        }
        .badge-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #f87171; }
        .badge-warning { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }
        .badge-success { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .badge-info { background: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc; }
        .badge-secondary { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        /* Report Footer & Signatures */
        .report-footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1.5px dashed #cbd5e1;
            display: grid;
            grid-template-columns: 140px 1fr 200px;
            gap: 12px;
            align-items: center;
        }

        /* QR Code Box */
        .qr-section {
            text-align: center;
            border: 1px solid #e2e8f0;
            padding: 6px;
            border-radius: 6px;
            background: #ffffff;
        }
        .qr-canvas {
            display: inline-block;
            margin: 0 auto;
        }
        .qr-label {
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
            margin-top: 2px;
        }

        /* Center Stamp Section */
        .stamp-section {
            text-align: center;
        }
        .stamp-circle {
            width: 100px;
            height: 100px;
            margin: 0 auto;
            border: 2px dashed #0f766e;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #0f766e;
            font-weight: 800;
            font-size: 10px;
            line-height: 1.25;
            opacity: 0.85;
            background: rgba(204, 251, 241, 0.15);
        }

        /* Doctor Signature Section */
        .signature-section {
            text-align: center;
        }
        .doctor-title {
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
        }
        .doctor-spec {
            font-size: 10.5px;
            color: #64748b;
            font-weight: 600;
        }
        .sig-container {
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 2px 0;
        }
        .sig-container img {
            max-height: 60px;
            max-width: 180px;
            object-fit: contain;
            mix-blend-mode: multiply;
        }
        .sig-date {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 600;
        }

        /* Bottom Security Strip */
        .security-strip {
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
            margin-top: 6px;
            font-weight: 600;
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
            .report-page {
                box-shadow: none;
                margin: 0;
                width: 210mm;
                height: 296mm;
                page-break-after: always;
            }
            .report-page:last-child {
                page-break-after: auto;
            }
            .page-frame {
                top: 5mm;
                right: 5mm;
                bottom: 5mm;
                left: 5mm;
            }
            .page-frame-inner {
                top: 6.5mm;
                right: 6.5mm;
                bottom: 6.5mm;
                left: 6.5mm;
            }
        }
    </style>
</head>
<body>

@php
    $patientName = $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'غير محدد';
    $patientPhone = $emergency->patient?->user?->phone ?? $emergency->emergencyPatient?->phone ?? '-';
    $patientAge = $emergency->patient?->user?->age ?? $emergency->patient?->age ?? $emergency->emergencyPatient?->age ?? '-';
    $patientGender = $emergency->patient?->user?->gender ?? $emergency->patient?->gender ?? $emergency->emergencyPatient?->gender ?? null;
    $patientFileNo = $emergency->patient?->id ? ('MRN-' . str_pad($emergency->patient->id, 5, '0', STR_PAD_LEFT)) : ($emergency->emergency_patient_id ? ('ER-' . $emergency->emergency_patient_id) : '-');
    $emergencyId = 'EMR-' . str_pad($emergency->id, 6, '0', STR_PAD_LEFT);
    $verifyUrl = route('emergency.verify-report', $emergency->id);

    // Latest Vitals
    $vitalSign = $emergency->latestVitalSign ?? $emergency->vitalSignReadings->first();
    $rawVitals = is_array($emergency->vital_signs) ? $emergency->vital_signs : [];

    $bp = $vitalSign->blood_pressure ?? $rawVitals['blood_pressure'] ?? $rawVitals['bp'] ?? '-';
    $hr = $vitalSign->heart_rate ?? $rawVitals['heart_rate'] ?? $rawVitals['pulse'] ?? '-';
    $temp = $vitalSign->temperature ?? $rawVitals['temperature'] ?? $rawVitals['temp'] ?? '-';
    $rr = $vitalSign->respiratory_rate ?? $rawVitals['respiratory_rate'] ?? $rawVitals['rr'] ?? '-';
    $spo2 = $vitalSign->oxygen_saturation ?? $rawVitals['oxygen_saturation'] ?? $rawVitals['spo2'] ?? '-';
    $rbs = $vitalSign->blood_glucose ?? $rawVitals['blood_glucose'] ?? $rawVitals['sugar'] ?? '-';

    // Doctor information & signature
    $doctorName = $doctor?->user?->name ?? $emergency->doctor?->user?->name ?? 'د. مهند إسماعيل';
    $doctorSpec = $doctor?->specialization ?? $emergency->doctor?->specialization ?? 'طب الطوارئ والحالات الحرجة';
    $signaturePath = $doctor?->signature ?? $emergency->doctor?->signature ?? 'images/signatures/doctor_244.png';
@endphp

<!-- Screen Action Toolbar -->
<div class="no-print">
    <div class="action-group">
        <span class="fw-bold" style="font-weight: 800; color: #0f766e;">📄 التقرير الطبي لحالة الطوارئ</span>
        <span style="color: #94a3b8;">|</span>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-mode active" id="btnModeBoth" onclick="switchCopyMode('both')">
                <i class="fas fa-copy"></i> طباعة النسختين معاً (المريض + الأرشيف)
            </button>
            <button type="button" class="btn btn-mode" id="btnModePatient" onclick="switchCopyMode('patient')">
                <i class="fas fa-user"></i> نسخة المريض فقط
            </button>
            <button type="button" class="btn btn-mode" id="btnModeAdmin" onclick="switchCopyMode('admin')">
                <i class="fas fa-archive"></i> نسخة الإدارة والأرشيف فقط
            </button>
        </div>
    </div>
    <div class="action-group">
        <button class="btn btn-print" onclick="window.print()">
            <span>🖨️ طباعة التقرير</span>
        </button>
        <a href="{{ route('emergency.show', $emergency) }}" class="btn btn-back">
            <span>↩️ رجوع لمحطة الكشف</span>
        </a>
    </div>
</div>

{{-- Helper Macro / Template for Single Report Page --}}
@php
    $copies = [
        'patient' => [
            'id' => 'page-patient',
            'title' => 'نسخة المريض (Patient Copy)',
            'watermark_label' => 'نسخة المريض الرسمية',
            'note' => 'تُسلّم هذه النسخة للمريض أو ذويه لتقديمها لأي جهة طبية أو استشارية عند الحاجة.'
        ],
        'admin' => [
            'id' => 'page-admin',
            'title' => 'نسخة الإدارة والأرشيف (Administrative & Archive Copy)',
            'watermark_label' => 'أرشيف قسم الطوارئ',
            'note' => 'تُحفظ هذه النسخة في الأرشيف الطبي وسجلات إدارة المستشفى للتوثيق والتدقيق الطبي والقانوني.'
        ],
    ];
@endphp

@foreach($copies as $copyKey => $copyInfo)
<div class="report-page report-copy-{{ $copyKey }}" id="{{ $copyInfo['id'] }}">
    <div class="page-frame"></div>
    <div class="page-frame-inner"></div>

    <div>
        <!-- Official Hospital Header -->
        <div class="report-header">
            <div class="header-hospital-ar">
                <h2>مستشفى الكفاءات الأهلي</h2>
                <h4>قسم الطوارئ والحالات الحرجة</h4>
                <p>صرح طبي متكامل لرعاية الحالات الطارئة 24/7</p>
            </div>
            <div class="header-logo">
                <img src="{{ asset('images/1.jpg') }}" alt="Hospital Logo">
            </div>
            <div class="header-hospital-en">
                <h2>Al-Kafaat Private Hospital</h2>
                <h4>Emergency & Critical Care Dept.</h4>
                <p>Specialized Medical Care 24/7</p>
            </div>
        </div>

        <!-- Document Title Strip -->
        <div class="title-strip">
            <h3>التقرير الطبي لحالة طوارئ — EMERGENCY MEDICAL REPORT</h3>
            <span class="copy-type-badge">{{ $copyInfo['title'] }}</span>
            <span class="report-ref-badge">{{ $emergencyId }}</span>
        </div>

        <!-- Section 1: Patient Demographics & Admission Details -->
        <div class="section-box">
            <div class="section-title">
                <span>👤 البيانات الشخصية وتفاصيل الدخول</span>
                <span style="font-size: 10px; color: #64748b;">تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}</span>
            </div>
            <div class="info-grid">
                <div class="grid-item col-span-2">
                    <span class="grid-label">اسم المريض الرباعي</span>
                    <span class="grid-value" style="font-size: 13.5px; color: #0f766e;">{{ $patientName }}</span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">رقم الإضبارة / الملف</span>
                    <span class="grid-value">{{ $patientFileNo }}</span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">العمر / الجنس</span>
                    <span class="grid-value">{{ $patientAge }} سنة / {{ $patientGender === 'female' ? 'أنثى' : ($patientGender === 'male' ? 'ذكر' : '-') }}</span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">رقم الهاتف للتواصل</span>
                    <span class="grid-value">{{ $patientPhone }}</span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">وقت وتاريخ الدخول للطوارئ</span>
                    <span class="grid-value">{{ $emergency->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="grid-item">
                    <span class="grid-label">درجة الخطورة والفرز السريري</span>
                    <div>
                        @if($emergency->priority === 'critical')
                            <span class="badge badge-danger">حالة حرجة (Critical - Red)</span>
                        @elseif($emergency->priority === 'urgent')
                            <span class="badge badge-warning">حالة عاجلة (Urgent - Yellow)</span>
                        @elseif($emergency->priority === 'semi_urgent')
                            <span class="badge badge-info">شبه عاجلة (Semi-urgent)</span>
                        @else
                            <span class="badge badge-secondary">غير عاجلة (Non-urgent - Green)</span>
                        @endif
                    </div>
                </div>
                <div class="grid-item">
                    <span class="grid-label">التغطية المالية / الضمان</span>
                    <span class="grid-value">{{ $emergency->is_insured ? 'مشمول بالضمان الصحي' : 'حساب نقدي (Cash)' }}</span>
                </div>
            </div>
        </div>

        <!-- Section 2: Vitals at Presentation -->
        <div class="section-box">
            <div class="section-title">
                <span>🩺 العلامات الحيوية عند الاستقبال (Vital Signs at Presentation)</span>
                <span style="font-size: 10px; color: #64748b;">
                    @if($vitalSign)
                        سُجلت: {{ $vitalSign->created_at->format('H:i') }} بواسطة: {{ $vitalSign->recordedBy->name ?? 'الكادر التمريضي' }}
                    @endif
                </span>
            </div>
            <div class="vitals-grid">
                <div class="vital-cell">
                    <div class="vital-title">ضغط الدم (BP)</div>
                    <div class="vital-num">{{ $bp }}</div>
                    <div class="vital-unit">mmHg</div>
                </div>
                <div class="vital-cell">
                    <div class="vital-title">النبض (Pulse)</div>
                    <div class="vital-num">{{ $hr }}</div>
                    <div class="vital-unit">bpm</div>
                </div>
                <div class="vital-cell">
                    <div class="vital-title">الحرارة (Temp)</div>
                    <div class="vital-num">{{ $temp }}</div>
                    <div class="vital-unit">°C</div>
                </div>
                <div class="vital-cell">
                    <div class="vital-title">التنفس (RR)</div>
                    <div class="vital-num">{{ $rr }}</div>
                    <div class="vital-unit">breaths/min</div>
                </div>
                <div class="vital-cell">
                    <div class="vital-title">الأكسجين (SpO2)</div>
                    <div class="vital-num">{{ $spo2 }}</div>
                    <div class="vital-unit">%</div>
                </div>
                <div class="vital-cell">
                    <div class="vital-title">السكر (RBS)</div>
                    <div class="vital-num">{{ $rbs }}</div>
                    <div class="vital-unit">mg/dL</div>
                </div>
            </div>
        </div>

        <!-- Section 3: Clinical Presentation, Chief Complaint & Diagnosis -->
        <div class="section-box">
            <div class="section-title">
                <span>📋 الشكوى الرئيسية، التقييم الطبي، والتشخيص السريري</span>
            </div>
            <div style="padding: 7px 10px;">
                <div style="margin-bottom: 5px;">
                    <strong style="color: #0f766e; font-size: 11px;">الشكوى والأعراض عند الدخول:</strong>
                    <div class="clinical-text">{{ $emergency->symptoms ?: 'لم تُسجل أعراض تفصيلية' }}</div>
                </div>
                <div style="margin-bottom: 5px;">
                    <strong style="color: #0f766e; font-size: 11px;">التشخيص السريري والانطباع الطبي (Diagnosis & Clinical Impression):</strong>
                    <div class="clinical-text" style="font-weight: 800; font-size: 12.5px; color: #b91c1c;">
                        {{ $emergency->diagnosis ?: ($emergency->initial_assessment ?: 'قيد الفحص والملاحظة السريرية') }}
                    </div>
                </div>
                @if($emergency->notes)
                    <div>
                        <strong style="color: #0f766e; font-size: 11px;">ملاحظات الطبيب السريرية:</strong>
                        <div class="clinical-text">{{ $emergency->notes }}</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Section 4: Emergency Medical Procedures & Treatments Given -->
        <div class="section-box">
            <div class="section-title">
                <span>💉 التدخلات الطبية، العلاجات، والخدمات التمريضية المقدمة</span>
            </div>
            <div style="padding: 6px 8px;">
                @php
                    $allInterventions = [];
                    // Services
                    foreach($emergency->services as $svc) {
                        $allInterventions[] = [
                            'type' => 'خدمة / إجراء',
                            'title' => $svc->name,
                            'details' => $svc->description ?: 'تم تقديم الخدمة بقسم الطوارئ',
                            'time' => $emergency->created_at->format('H:i')
                        ];
                    }
                    // Treatments
                    foreach($emergency->treatments as $trt) {
                        $allInterventions[] = [
                            'type' => 'علاج / محاليل',
                            'title' => $trt->treatment_type ?? 'علاج طارئ',
                            'details' => $trt->description . ($trt->notes ? " ({$trt->notes})" : ''),
                            'time' => $trt->started_at ? $trt->started_at->format('H:i') : ($trt->created_at ? $trt->created_at->format('H:i') : '-')
                        ];
                    }
                @endphp

                @if(count($allInterventions) > 0)
                    <table class="med-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">النوع</th>
                                <th style="width: 35%;">الإجراء / العلاج</th>
                                <th style="width: 35%;">التفاصيل والملاحظات</th>
                                <th style="width: 15%; text-align: center;">التوقيت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allInterventions as $item)
                                <tr>
                                    <td><span class="badge badge-info">{{ $item['type'] }}</span></td>
                                    <td><strong>{{ $item['title'] }}</strong></td>
                                    <td>{{ $item['details'] }}</td>
                                    <td style="text-align: center;">{{ $item['time'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="clinical-text" style="color: #64748b;">تمت مراقبة الحالة السريرية وإعطاء الرعاية الطبية الأساسية.</div>
                @endif
            </div>
        </div>

        <!-- Section 5: Diagnostic Investigations (Lab & Radiology) -->
        @if(($emergency->labRequests && $emergency->labRequests->count() > 0) || ($emergency->radiologyRequests && $emergency->radiologyRequests->count() > 0))
            <div class="section-box">
                <div class="section-title">
                    <span>🔬 الفحوصات التشخيصية (المختبر والأشعة)</span>
                </div>
                <div style="padding: 6px 8px;">
                    <table class="med-table">
                        <thead>
                            <tr>
                                <th style="width: 20%;">القسم</th>
                                <th style="width: 40%;">الفحص المطلوب</th>
                                <th style="width: 25%;">النتيجة / الحالة</th>
                                <th style="width: 15%; text-align: center;">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($emergency->labRequests as $labReq)
                                @foreach($labReq->labTests as $t)
                                    <tr>
                                        <td><span class="badge badge-secondary">مختبر الطوارئ</span></td>
                                        <td><strong>{{ $t->name }}</strong></td>
                                        <td>
                                            @php
                                                $res = $t->pivot->result;
                                                $resData = is_string($res) ? json_decode($res, true) : $res;
                                                $val = is_array($resData) ? ($resData['value'] ?? '') : $res;
                                            @endphp
                                            {{ $val ? $val : 'تم إجراء الفحص السريع' }}
                                        </td>
                                        <td style="text-align: center;"><span class="badge badge-success">منجز</span></td>
                                    </tr>
                                @endforeach
                            @endforeach

                            @foreach($emergency->radiologyRequests as $radReq)
                                @foreach($radReq->radiologyTypes as $r)
                                    <tr>
                                        <td><span class="badge badge-warning">أشعة / تصوير</span></td>
                                        <td><strong>{{ $r->name }}</strong></td>
                                        <td>{{ $r->pivot->result ?: 'تم التصوير الإشعاعي' }}</td>
                                        <td style="text-align: center;"><span class="badge badge-success">منجز</span></td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Section 6: Discharge Status & Doctor's Recommendations -->
        <div class="section-box">
            <div class="section-title">
                <span>🏁 القرار الطبي، حالة الخروج، وتوصيات المتابعة</span>
                <span>
                    @if($emergency->status === 'discharged')
                        <span class="badge badge-success">تم إنهاء وتخريج الحالة</span>
                    @else
                        <span class="badge badge-warning">الحالة قيد العلاج والمتابعة</span>
                    @endif
                </span>
            </div>
            <div style="padding: 7px 10px;">
                <div class="info-grid" style="padding: 0; margin-bottom: 5px;">
                    <div class="grid-item col-span-2">
                        <span class="grid-label">المسار الطبي وخاتمة الحالة</span>
                        <span class="grid-value">
                            @if($emergency->discharge_type === 'recovered')
                                <span class="badge badge-success" style="font-size: 11.5px;">خروج بعد الاستقرار والتعافي (Discharged Recovered)</span>
                            @elseif($emergency->discharge_type === 'against_medical_advice')
                                <span class="badge badge-warning" style="font-size: 11.5px;">خروج على مسؤولية المريض أو ذويه (Discharged AMA)</span>
                            @elseif($emergency->discharge_type === 'transferred_to_consultation')
                                <span class="badge badge-info" style="font-size: 11.5px;">إحالة وتحويل للعيادات الاستشارية التخصصية (Referred to Consultation)</span>
                            @elseif($emergency->requires_surgery || $emergency->status === 'transferred_to_surgery')
                                <span class="badge badge-danger" style="font-size: 11.5px;">تحويل طارئ إلى صالة العمليات الجراحية (Transferred to OR)</span>
                            @elseif($emergency->requires_admission || $emergency->status === 'transferred_to_admission')
                                <span class="badge badge-info" style="font-size: 11.5px;">تحويل إلى الرقود والأقسام الداخلية (Admitted Inpatient)</span>
                            @else
                                <span class="badge badge-secondary" style="font-size: 11.5px;">تم استكمال الرعاية الطارئة والملاحظة</span>
                            @endif
                        </span>
                    </div>
                    <div class="grid-item">
                        <span class="grid-label">توقيت الخروج</span>
                        <span class="grid-value">{{ $emergency->discharge_time ? $emergency->discharge_time->format('Y-m-d H:i') : ($emergency->status === 'discharged' ? $emergency->updated_at->format('Y-m-d H:i') : 'مستمر') }}</span>
                    </div>
                    <div class="grid-item">
                        <span class="grid-label">مدة الإقامة بالطوارئ</span>
                        <span class="grid-value">
                            @php
                                $endTime = $emergency->discharge_time ?? now();
                                $diffMin = $emergency->created_at->diffInMinutes($endTime);
                                $hours = floor($diffMin / 60);
                                $mins = $diffMin % 60;
                            @endphp
                            {{ $hours > 0 ? "{$hours} ساعة و " : '' }}{{ $mins }} دقيقة
                        </span>
                    </div>
                </div>

                @if($emergency->discharge_notes)
                    <div style="margin-top: 4px;">
                        <strong style="color: #0f766e; font-size: 11px;">توصيات الطبيب المعالج وخطة المتابعة المنزلية:</strong>
                        <div class="clinical-text">{{ $emergency->discharge_notes }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Official Authentication & Signature Footer -->
    <div>
        <div class="report-footer">
            <!-- QR Code Verification -->
            <div class="qr-section">
                <div class="qr-box" id="qrcode-{{ $copyKey }}" data-url="{{ $verifyUrl }}" style="width: 80px; height: 80px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($verifyUrl) }}" alt="QR Code" width="80" height="80" style="display: block; width: 80px; height: 80px; object-fit: contain;" />
                </div>
                <div class="qr-label">امسح للتحقق الرقمي المعتمد</div>
                <div style="font-size: 8px; color: #94a3b8; font-weight: 700;">{{ $emergencyId }}</div>
            </div>

            <!-- Official Hospital Emergency Stamp -->
            <div class="stamp-section">
                <div class="stamp-circle">
                    <div>مستشفى الكفاءات</div>
                    <div style="font-size: 8px;">★ قسم الطوارئ ★</div>
                    <div style="font-size: 8.5px; color: #b91c1c; font-weight: 900;">مُعتمَــد</div>
                    <div style="font-size: 7.5px;">EMERGENCY DEPT</div>
                </div>
            </div>

            <!-- Attending Doctor Signature -->
            <div class="signature-section">
                <div class="doctor-title">{{ $doctorName }}</div>
                <div class="doctor-spec">{{ $doctorSpec }}</div>
                <div class="sig-container">
                    @if($signaturePath && file_exists(public_path($signaturePath)))
                        <img src="{{ asset($signaturePath) }}" alt="توقيع د. مهند إسماعيل">
                    @elseif(file_exists(public_path('images/signatures/doctor_244.png')))
                        <img src="{{ asset('images/signatures/doctor_244.png') }}" alt="توقيع د. مهند إسماعيل">
                    @else
                        <div style="font-size: 11px; font-weight: 800; color: #0f766e; border-bottom: 1.5px solid #0f766e; padding-bottom: 2px;">
                            {{ $doctorName }}
                        </div>
                    @endif
                </div>
                <div class="sig-date">تاريخ التوثيق: {{ now()->format('Y-m-d') }}</div>
            </div>
        </div>

        <div class="security-strip">
            <span>{{ $copyInfo['note'] }}</span>
            <span style="margin: 0 8px;">•</span>
            <span>نظام المستشفى الإلكتروني الموحد — رمز الوثيقة الرقمية: {{ md5($emergency->id . '-' . $emergency->created_at) }}</span>
        </div>
    </div>
</div>
@endforeach

<script>
    // Generate QR Codes via local library
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof QRCode !== 'undefined') {
            document.querySelectorAll('.qr-box').forEach(function(box) {
                var url = box.getAttribute('data-url');
                box.innerHTML = ''; // replace fallback image with local sharp vector QR code
                new QRCode(box, {
                    text: url,
                    width: 80,
                    height: 80,
                    colorDark: '#0f766e',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        }
    });

    // Copy mode switcher
    function switchCopyMode(mode) {
        var pagePatient = document.getElementById('page-patient');
        var pageAdmin = document.getElementById('page-admin');
        var btnBoth = document.getElementById('btnModeBoth');
        var btnPatient = document.getElementById('btnModePatient');
        var btnAdmin = document.getElementById('btnModeAdmin');

        // Reset active buttons
        [btnBoth, btnPatient, btnAdmin].forEach(function(b) {
            if (b) b.classList.remove('active');
        });

        if (mode === 'both') {
            pagePatient.style.display = 'flex';
            pageAdmin.style.display = 'flex';
            btnBoth.classList.add('active');
        } else if (mode === 'patient') {
            pagePatient.style.display = 'flex';
            pageAdmin.style.display = 'none';
            btnPatient.classList.add('active');
        } else if (mode === 'admin') {
            pagePatient.style.display = 'none';
            pageAdmin.style.display = 'flex';
            btnAdmin.classList.add('active');
        }
    }
</script>

</body>
</html>
