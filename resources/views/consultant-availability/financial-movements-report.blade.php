<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الحركات المالية للعيادات الاستشارية والسونار - مستشفى الأهلي التخصصي</title>
    <!-- Google Fonts Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-size: 12px;
            line-height: 1.45;
        }

        /* Top Action Bar (Screen Only) */
        .no-print {
            max-width: 297mm;
            margin: 0 auto 16px auto;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .action-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 18px;
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

        /* Report Page Container (A4 Landscape Form) */
        .report-page {
            width: 297mm;
            min-height: 210mm;
            margin: 0 auto 25px auto;
            background: #ffffff;
            padding: 14mm 16mm 14mm 16mm;
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
            background-size: 28% auto;
            opacity: 0.04;
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
            top: 5mm;
            right: 5mm;
            bottom: 5mm;
            left: 5mm;
            border: 1.5px solid #0f766e;
            pointer-events: none;
            border-radius: 4px;
        }
        .page-frame-inner {
            position: absolute;
            top: 6.5mm;
            right: 6.5mm;
            bottom: 6.5mm;
            left: 6.5mm;
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
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .header-hospital-ar {
            text-align: right;
            flex: 1;
        }
        .header-hospital-ar h2 {
            font-size: 18px;
            font-weight: 900;
            color: #0f766e;
            margin-bottom: 1px;
        }
        .header-hospital-ar h4 {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
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
            max-width: 80px;
            max-height: 70px;
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
            margin-bottom: 1px;
        }
        .header-hospital-en h4 {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
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

        /* Scope & Filter Info Grid */
        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 8px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.98);
        }
        .section-title {
            background: #f1f5f9;
            color: #0f766e;
            font-weight: 800;
            font-size: 11.5px;
            padding: 4px 10px;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px 14px;
            padding: 6px 12px;
            font-size: 11px;
        }
        .grid-item {
            display: flex;
            flex-direction: column;
        }
        .grid-label {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 1px;
        }
        .grid-value {
            font-size: 11.5px;
            color: #0f172a;
            font-weight: 700;
        }

        /* Financial Summary Cards Row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 10px;
        }
        .stat-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 10px;
            background: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-card.received {
            border-right: 4px solid #16a34a;
            background: #f0fdf4;
        }
        .stat-card.refunded {
            border-right: 4px solid #dc2626;
            background: #fef2f2;
        }
        .stat-card.net {
            border-right: 4px solid #0284c7;
            background: #f0f9ff;
        }
        .stat-card.count {
            border-right: 4px solid #6366f1;
            background: #faf5ff;
        }
        .stat-title {
            font-size: 10px;
            color: #475569;
            font-weight: 700;
        }
        .stat-amount {
            font-size: 13px;
            font-weight: 900;
            color: #0f172a;
            margin-top: 1px;
        }

        /* Table Styling */
        .table-container {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 8px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .report-table th, .report-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            text-align: right;
            vertical-align: middle;
        }
        .report-table th {
            background-color: #f1f5f9;
            color: #0f766e;
            font-weight: 800;
            font-size: 10.5px;
            white-space: nowrap;
        }
        .report-table tr:nth-child(even) td {
            background-color: #fafbfc;
        }
        .report-table tr.row-cancelled td {
            background-color: #f8fafc;
            color: #64748b;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9.5px;
            font-weight: 800;
            white-space: nowrap;
        }
        .badge-success { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .badge-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #f87171; }
        .badge-secondary { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .badge-primary { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-info { background: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc; }
        .badge-warning { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }

        /* Report Footer & Official Stamp & Signatures */
        .report-footer {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1.5px dashed #cbd5e1;
            display: grid;
            grid-template-columns: 130px 1fr 220px;
            gap: 14px;
            align-items: center;
        }

        .qr-section {
            text-align: center;
            border: 1px solid #e2e8f0;
            padding: 5px;
            border-radius: 6px;
            background: #ffffff;
        }
        .qr-canvas {
            display: inline-block;
            margin: 0 auto;
        }
        .qr-label {
            font-size: 8.5px;
            font-weight: 700;
            color: #64748b;
            margin-top: 2px;
        }

        .stamp-section {
            text-align: center;
        }
        .stamp-circle {
            width: 90px;
            height: 90px;
            margin: 0 auto;
            border: 2px dashed #0f766e;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #0f766e;
            font-weight: 800;
            font-size: 9.5px;
            line-height: 1.25;
            opacity: 0.85;
            background: rgba(204, 251, 241, 0.15);
        }

        .signatures-section {
            display: flex;
            justify-content: space-around;
            gap: 15px;
            text-align: center;
        }
        .sig-block {
            flex: 1;
        }
        .sig-title {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
        }
        .sig-sub {
            font-size: 9.5px;
            color: #64748b;
            margin-bottom: 25px;
        }
        .sig-line {
            border-top: 1px solid #94a3b8;
            font-size: 9px;
            color: #64748b;
            padding-top: 2px;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .report-page {
                box-shadow: none !important;
                margin: 0 !important;
                width: 100% !important;
                min-height: auto !important;
                padding: 6mm 8mm !important;
                page-break-after: always;
            }
            @page {
                size: A4 landscape;
                margin: 6mm 6mm;
            }
            .report-table th, .report-table td {
                padding: 3px 5px !important;
                font-size: 9.5px !important;
            }
        }
    </style>
</head>
<body>

    <!-- Screen Action Bar -->
    <div class="no-print">
        <div class="action-group">
            <button type="button" onclick="window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> طباعة التقرير الفوري
            </button>
            <a href="{{ route('consultant-availability.financial-movements', request()->query()) }}" class="btn btn-back">
                <i class="fas fa-arrow-right"></i> الرجوع للحركات المالية
            </a>
            <span style="font-size: 11px; color: #64748b; font-weight: 600; margin-right: 10px;">
                <i class="fas fa-file-invoice-dollar text-primary me-1"></i> إجمالي الحركات المعروضة: {{ $payments->count() }} قيد
            </span>
        </div>
        <div class="action-group">
            <span style="font-size: 11px; color: #0f766e; font-weight: 700;">
                <i class="fas fa-check-circle me-1"></i> جاهز للطباعة بحجم A4 عريض (Landscape)
            </span>
        </div>
    </div>

    <!-- Official Report Page Container -->
    <div class="report-page">
        <!-- Frames -->
        <div class="page-frame"></div>
        <div class="page-frame-inner"></div>

        <!-- Top Content Section -->
        <div>
            <!-- Header -->
            <div class="report-header">
                <div class="header-hospital-ar">
                    <h2>جمهورية العراق - وزارة الصحة</h2>
                    <h4>مستشفى الأهلي التخصصي للجراحة العامة</h4>
                    <p>قسم الإدارة المالية والتدقيق الحسابي</p>
                </div>

                <div class="header-logo">
                    <img src="{{ asset('images/1.jpg') }}" alt="شعار المستشفى">
                </div>

                <div class="header-hospital-en">
                    <h2>Republic of Iraq - Ministry of Health</h2>
                    <h4>Al-Ahli Specialized Hospital</h4>
                    <p>Financial Audit & Revenue Department</p>
                </div>
            </div>

            <!-- Title Strip -->
            <div class="title-strip">
                <div>
                    <h3><i class="fas fa-chart-pie me-2"></i> تقرير الحركات المالية للعيادات الاستشارية والسونار</h3>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="report-ref-badge">REF-FIN-{{ now()->format('Ymd-His') }}</span>
                    <span class="copy-type-badge">نسخة الإدارة والتدقيق</span>
                </div>
            </div>

            <!-- Scope & Filter Metadata Box -->
            <div class="section-box">
                <div class="section-title">
                    <span><i class="fas fa-filter me-1"></i> محددات ومعايير التقرير المالي</span>
                    <span style="font-size: 10px; color: #64748b;">تاريخ التوليد: {{ now()->format('Y-m-d h:i A') }}</span>
                </div>
                <div class="info-grid">
                    <div class="grid-item">
                        <span class="grid-label">الفترة الزمنية:</span>
                        <span class="grid-value">من {{ $fromDate ?? 'البداية' }} إلى {{ $toDate ?? 'حتى تاريخه' }}</span>
                    </div>
                    <div class="grid-item">
                        <span class="grid-label">فترة الدوام:</span>
                        <span class="grid-value">
                            @if(($shift ?? '') === 'morning') ☀️ الدوام الصباحي (8 ص - 3 م)
                            @elseif(($shift ?? '') === 'evening') 🌙 الدوام المسائي (3 م فما بعد)
                            @else كامل اليوم (صباحي ومسائي) @endif
                        </span>
                    </div>
                    <div class="grid-item">
                        <span class="grid-label">نوع الخدمة / الإيراد:</span>
                        <span class="grid-value">
                            @if(($serviceCategory ?? '') === 'ultrasound') 📡 السونار الخارجي المباشر
                            @elseif(($serviceCategory ?? '') === 'consultation') 🩺 كشفيات العيادات فقط
                            @else الكل (عيادات استشارية + سونار) @endif
                        </span>
                    </div>
                    <div class="grid-item">
                        <span class="grid-label">نوع الحركة:</span>
                        <span class="grid-value">
                            @if($filterType === 'payment') حركات قبض (النشطة فقط)
                            @elseif($filterType === 'refund') حركات استرجاع فقط
                            @elseif($filterType === 'appointment_refunded') المواعيد المسترجعة والملغاة
                            @elseif($filterType === 'appointment_paid') المواعيد المدفوعة بالكامل
                            @else كل الحركات المالية @endif
                        </span>
                    </div>
                    <div class="grid-item">
                        <span class="grid-label">حالة التغطية:</span>
                        <span class="grid-value">{{ $insuranceStatus === 'cash' ? 'نقداً فقط (كاش)' : ($insuranceStatus === 'insurance' ? 'مشمول بالضمان' : 'الكل (كاش وضمان)') }}</span>
                    </div>
                    <div class="grid-item">
                        <span class="grid-label">طريقة الدفع:</span>
                        <span class="grid-value">{{ $paymentMethod ? strtoupper($paymentMethod) : 'الكل' }}</span>
                    </div>
                    <div class="grid-item">
                        <span class="grid-label">المستخدم / المحاسب:</span>
                        <span class="grid-value">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="grid-item">
                        <span class="grid-label">إجمالي القيود المشمولة:</span>
                        <span class="grid-value" style="color: #0f766e;">{{ $payments->count() }} قيد مالي</span>
                    </div>
                </div>
            </div>

            <!-- Financial Summary Cards -->
            <div class="stats-row">
                <div class="stat-card received">
                    <div>
                        <div class="stat-title">المستلم نقداً بالصندوق (الدرج)</div>
                        <div class="stat-amount" style="color: #16a34a;">{{ number_format($totalReceived, 2) }} IQD</div>
                    </div>
                    <i class="fas fa-hand-holding-dollar fa-2x" style="color: #16a34a; opacity: 0.7;"></i>
                </div>
                <div class="stat-card refunded">
                    <div>
                        <div class="stat-title">المسترجع نقداً للمرضى</div>
                        <div class="stat-amount" style="color: #dc2626;">{{ number_format($totalRefunded, 2) }} IQD</div>
                    </div>
                    <i class="fas fa-undo-alt fa-2x" style="color: #dc2626; opacity: 0.7;"></i>
                </div>
                <div class="stat-card net">
                    <div>
                        <div class="stat-title">الصافي النقدي الفعلي</div>
                        <div class="stat-amount" style="color: #0284c7;">{{ number_format($netTotal, 2) }} IQD</div>
                    </div>
                    <i class="fas fa-calculator fa-2x" style="color: #0284c7; opacity: 0.7;"></i>
                </div>
                <div class="stat-card count">
                    <div>
                        <div class="stat-title">إجمالي عدد الحركات المشمولة</div>
                        <div class="stat-amount" style="color: #4f46e5;">{{ $totalCount }} حركة</div>
                    </div>
                    <i class="fas fa-receipt fa-2x" style="color: #4f46e5; opacity: 0.7;"></i>
                </div>
            </div>

            <!-- Detailed Movements Table -->
            <div class="table-container">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 25px; text-align: center;">#</th>
                            <th style="width: 110px;">التاريخ والتوقيت</th>
                            <th>المريض</th>
                            <th>الطبيب</th>
                            <th>نوع الخدمة / القسم</th>
                            <th style="width: 110px;">المبلغ الفعلي (نقداً)</th>
                            @if(auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']))
                            <th style="width: 85px;">حصة الطبيب</th>
                            <th style="width: 85px;">حصة المستشفى</th>
                            @endif
                            <th style="width: 85px; text-align: center;">نوع الحركة</th>
                            <th style="width: 110px;">طريقة الدفع والتغطية</th>
                            <th style="width: 95px; text-align: center;">رقم الإيصال</th>
                            <th style="width: 90px;">الكاشير</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            @php
                                $actualPaid = $payment->patient_paid_amount;
                                $isRefund = $payment->movement_type === 'refund' || $actualPaid < 0;
                                $isCancelledOrRefunded = $payment->is_refunded_or_cancelled;
                                $isInsurance = $payment->is_insurance;
                                $insuranceShare = $payment->insurance_share_amount;
                                $totalTariff = abs($payment->total_amount);
                                $isMorning = $payment->paid_at && $payment->paid_at->format('H:i:s') < '15:00:00';
                                $isUltrasound = $payment->payment_type === 'ultrasound';
                            @endphp
                            <tr class="{{ $isCancelledOrRefunded && !$isRefund ? 'row-cancelled' : '' }}">
                                <td style="text-align: center; font-weight: 700;">{{ $loop->iteration }}</td>
                                <td>
                                    <div style="font-weight: 700;">{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d') : '-' }}</div>
                                    @if($payment->paid_at)
                                        <div style="font-size: 8.5px; color: #64748b; margin-top: 1px;">
                                            {{ $payment->paid_at->format('h:i A') }}
                                            <span style="font-weight: 700; color: {{ $isMorning ? '#b45309' : '#6f42c1' }};">
                                                {{ $isMorning ? '(صباحي)' : '(مسائي)' }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ optional(optional(optional($payment->appointment)->patient)->user)->name ?? optional(optional($payment->patient)->user)->name ?? optional($payment->patient)->name ?? '-' }}</strong>
                                </td>
                                <td>{{ optional(optional(optional($payment->appointment)->doctor)->user)->name ?? optional(optional($payment->doctor)->user)->name ?? '-' }}</td>
                                <td>
                                    @if($isUltrasound)
                                        <span class="badge badge-info">سونار خارجي</span>
                                    @else
                                        <span class="badge badge-primary">كشفية عيادة</span>
                                        <div style="font-size: 8.5px; color: #64748b;">{{ optional(optional($payment->appointment)->department)->name ?? 'الاستشارية' }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($isCancelledOrRefunded && !$isRefund)
                                        <div style="font-weight: 700; text-decoration: line-through; color: #94a3b8;">
                                            {{ number_format(abs($actualPaid), 2) }} IQD
                                        </div>
                                        <span class="badge badge-secondary" style="font-size: 8px;">ملغى ومسترجع</span>
                                    @else
                                        <div style="font-weight: 800; color: {{ $isRefund ? '#dc2626' : '#15803d' }};">
                                            {{ number_format(abs($actualPaid), 2) }} IQD
                                        </div>
                                    @endif
                                    @if($isInsurance)
                                        <div style="font-size: 8px; color: #b45309;">
                                            تحمل: {{ number_format(abs($actualPaid), 0) }} | ضمان: {{ number_format($insuranceShare, 0) }}
                                        </div>
                                    @endif
                                </td>
                                @if(auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']))
                                <td style="color: #15803d; font-weight: 700;">{{ number_format($payment->doctor_share, 2) }}</td>
                                <td style="color: #0369a1; font-weight: 700;">{{ number_format($payment->hospital_share, 2) }}</td>
                                @endif
                                <td style="text-align: center;">
                                    @if($isRefund)
                                        <span class="badge badge-danger">استرجاع</span>
                                    @elseif($isCancelledOrRefunded)
                                        <span class="badge badge-secondary">ملغى ومسترجع</span>
                                    @else
                                        <span class="badge badge-success">قبض</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $isInsurance ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $payment->insurance_type_label }}
                                    </span>
                                    <div style="font-size: 8.5px; color: #64748b; margin-top: 1px;">{{ $payment->payment_method ?? 'نقد' }}</div>
                                </td>
                                <td style="text-align: center; font-family: monospace; font-weight: 700;">{{ $payment->receipt_number ?? '-' }}</td>
                                <td>{{ optional($payment->cashier)->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" style="text-align: center; padding: 20px; color: #64748b;">
                                    لا توجد حركات مالية مسجلة وفق المحددات المختارة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Official Report Footer & Signatures -->
        <div class="report-footer">
            <!-- QR Code Verification Section -->
            <div class="qr-section">
                <div id="qrcode" class="qr-canvas"></div>
                <div class="qr-label">رمز التحقق الإلكتروني المعتمد</div>
            </div>

            <!-- Official Hospital Stamp -->
            <div class="stamp-section">
                <div class="stamp-circle">
                    <span>مستشفى الأهلي</span>
                    <span>قسم الحسابات</span>
                    <span style="font-size: 8.5px; margin: 1px 0;">والتدقيق المالي</span>
                    <span style="font-size: 7.5px; color: #0d9488;">★ معتمد رسمياً ★</span>
                </div>
            </div>

            <!-- Signatures Section -->
            <div class="signatures-section">
                <div class="sig-block">
                    <div class="sig-title">المحاسب المنظم</div>
                    <div class="sig-sub">{{ auth()->user()->name }}</div>
                    <div class="sig-line">التوقيع والاعتماد</div>
                </div>
                <div class="sig-block">
                    <div class="sig-title">المدير المالي / الإدارة</div>
                    <div class="sig-sub">مستشفى الأهلي التخصصي</div>
                    <div class="sig-line">الختم والمصادقة</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script to Generate QR Code -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var qrText = "مستشفى الأهلي التخصصي - تقرير الحركات المالية للاستشارية\n" +
                         "المرجع: REF-FIN-{{ now()->format('Ymd-His') }}\n" +
                         "الفترة: {{ $fromDate ?? 'البداية' }} إلى {{ $toDate ?? 'الآن' }}\n" +
                         "الصافي: {{ number_format($netTotal, 2) }} IQD\n" +
                         "العدد: {{ $payments->count() }} حركة\n" +
                         "المحاسب: {{ auth()->user()->name }}\n" +
                         "التاريخ: {{ now()->format('Y-m-d H:i') }}";

            var qrElement = document.getElementById("qrcode");
            if (qrElement && typeof QRCode !== 'undefined') {
                new QRCode(qrElement, {
                    text: qrText,
                    width: 78,
                    height: 78,
                    colorDark : "#0f766e",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.M
                });
            }
        });
    </script>
</body>
</html>
