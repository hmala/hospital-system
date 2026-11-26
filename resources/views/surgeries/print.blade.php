<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $patient = $surgery->patient;
        $patientName = $patient?->name ?? $patient?->user?->name ?? __('مريض غير محدد');
        $patientAge = $patient?->age ?? ($patient?->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : '—');
        $patientGender = match($patient?->gender) {
            'male' => __('ذكر'),
            'female' => __('أنثى'),
            default => ($patient?->gender ?: '—')
        };
        $patientMedNo = $patient?->medical_number ?? $patient?->id ?? '—';
        $patientPhone = $patient?->phone ?? $patient?->user?->phone ?? '—';

        $surgeon = $surgery->doctor;
        $surgeonName = $surgery->surgeonStation?->surgeon?->user?->full_name
            ?? $surgery->surgeonStation?->surgeon?->user?->name
            ?? $surgeon?->user?->full_name
            ?? $surgeon?->user?->name
            ?? __('غير محدد');

        $surgicalAssistantName = $surgery->surgical_assistant_name;
        $anesthesiaAssistantName = $surgery->anesthesia_assistant_name;

        $primaryAnesthName = $surgery->anesthesiaStation?->anesthesiologist?->user?->full_name
            ?? $surgery->anesthesiaStation?->anesthesiologist?->user?->name
            ?? $surgery->anesthesiologist?->user?->full_name
            ?? $surgery->anesthesiologist?->user?->name
            ?? '';

        $secondaryAnesthName = $surgery->anesthesiaStation?->anesthesiologist2?->user?->full_name
            ?? $surgery->anesthesiaStation?->anesthesiologist2?->user?->name
            ?? $surgery->anesthesiologist2?->user?->full_name
            ?? $surgery->anesthesiologist2?->user?->name
            ?? '';

        $orNurseName = $surgery->operationTheaterStation?->orNurse?->name ?? $surgery->nurse_name ?? '';
        $residentName = $surgery->surgeonStation?->residentAssigned?->user?->name
            ?? ($surgery->residentStations?->first()?->resident?->user?->name ?? '');

        $currentAnesthesiaType = $surgery->anesthesiaStation?->anesthesia_type ?? $surgery->anesthesia_type ?? '';
        $currentAnesthesiaNotes = $surgery->anesthesiaStation?->notes ?: ($surgery->anesthesia_notes ?? '');

        $room = $surgery->room;
        $roomName = $room ? ($room->name . ' (' . ($room->floor ? __('طابق ') . $room->floor . ' - ' : '') . ($room->type_label ?? $room->type) . ')') : __('غير محددة');
        $roomCheckinAt = $surgery->room_checkin_at ? \Carbon\Carbon::parse($surgery->room_checkin_at)->format('Y-m-d H:i') : null;
        $dischargedAt = $surgery->discharged_at ? \Carbon\Carbon::parse($surgery->discharged_at)->format('Y-m-d H:i') : null;

        $insuranceLabel = $surgery->insurance_company ?: ($surgery->insurance_type ?: __('تأمين صحي'));
        $isInsured = (bool) $surgery->is_insured;

        $surgeryType = $surgery->surgicalOperation?->name ?: ($surgery->surgery_type ?: __('غير محدد'));
        $scheduledDate = $surgery->scheduled_date ? \Carbon\Carbon::parse($surgery->scheduled_date)->format('Y-m-d') : ($surgery->created_at ? $surgery->created_at->format('Y-m-d') : '—');
        
        $surgeryStartFormatted = '';
        if ($surgery->start_time) {
            $surgeryStartFormatted = is_string($surgery->start_time) ? \Carbon\Carbon::parse($surgery->start_time)->format('H:i') : $surgery->start_time->format('H:i');
        } elseif ($surgery->started_at) {
            $surgeryStartFormatted = $surgery->started_at->format('H:i');
        }

        $surgeryEndFormatted = '';
        if ($surgery->end_time) {
            $surgeryEndFormatted = is_string($surgery->end_time) ? \Carbon\Carbon::parse($surgery->end_time)->format('H:i') : $surgery->end_time->format('H:i');
        } elseif ($surgery->ended_at) {
            $surgeryEndFormatted = $surgery->ended_at->format('H:i');
        }

        $hospitalNameAr = $hospital?->name ?: config('app.name', 'مستشفى الفيحاء الأهلي');
        $hospitalNameEn = $hospital?->name_en ?: 'Al-Fayhaa Hospital';
        $hospitalLogo = $hospital?->logo_url ?: asset('images/1.jpg');
        $hospitalLogoSrc = $hospitalLogo;
        if (!empty($hospital?->logo)) {
            $logoRel = ltrim($hospital->logo, '/');
            if (file_exists(public_path($logoRel))) {
                $ext = pathinfo(public_path($logoRel), PATHINFO_EXTENSION);
                $hospitalLogoSrc = 'data:image/' . ($ext ?: 'jpeg') . ';base64,' . base64_encode(file_get_contents(public_path($logoRel)));
            } elseif (file_exists(storage_path('app/public/' . $logoRel))) {
                $ext = pathinfo(storage_path('app/public/' . $logoRel), PATHINFO_EXTENSION);
                $hospitalLogoSrc = 'data:image/' . ($ext ?: 'jpeg') . ';base64,' . base64_encode(file_get_contents(storage_path('app/public/' . $logoRel)));
            }
        }

        $statusLabel = match($surgery->status) {
            'completed' => __('مكتملة ومُغلقة'),
            'in_progress' => __('جارية بالصالة'),
            'waiting' => __('بانتظار الإجراء'),
            'cancelled' => __('ملغاة'),
            default => $surgery->status,
        };

        $additionalOps = $surgery->additionalOperations ?? collect();
        $allTreatments = $surgery->surgeryTreatments ?? collect();
        $medications = $allTreatments->where('item_type', \App\Models\SurgeryTreatment::TYPE_MEDICATION);
        $supplies = $allTreatments->where('item_type', \App\Models\SurgeryTreatment::TYPE_SUPPLY);
        $devices = $surgery->medicalDevices ?? collect();
        $labTests = $surgery->labTests ?? collect();
        $radiologyTests = $surgery->radiologyTests ?? collect();
        $consultations = $surgery->consultations ?? collect();
        
        $treatmentPlan = $surgery->treatment_plan ?: ($surgery->surgeonStation?->treatment_plan ?? null);
        $postOpNotes = $surgery->post_op_notes ?: ($surgery->residentStations?->where('phase', 'post_op')->first()?->post_op_notes ?? $surgery->operationTheaterStation?->post_op_notes ?? null);
        $surgeonNotes = $surgery->surgeonStation?->notes ?: ($surgery->notes ?? null);
        $procedureNotes = $surgery->procedure_notes ?: ($surgery->operationTheaterStation?->procedure_notes ?? $surgery->operationTheaterStation?->notes ?? $surgery->description ?? null);
        $doctorRecommendation = $surgery->doctor_recommendation ?: ($surgery->surgeonStation?->doctor_recommendation ?? null);
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
            margin: 6mm 8mm !important;
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
            font-size: 10px;
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
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-print { background-color: #005a9c; color: #ffffff; }
        .btn-print:hover { background-color: #004377; }
        .btn-pdf { background-color: #0284c7; color: #ffffff; }
        .btn-pdf:hover { background-color: #0369a1; }
        .btn-back { background-color: #64748b; color: #ffffff; }
        .btn-back:hover { background-color: #475569; }

        /* A4 Page Container (210mm) */
        .sheet-container {
            margin: 0 auto;
            max-width: 210mm;
        }

        .report-page {
            width: 210mm;
            min-height: 275mm;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 7mm 9mm 6mm 9mm;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            position: relative;
            box-sizing: border-box;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }

        .report-content-wrapper {
            position: relative;
            z-index: 2;
        }

        /* 1. Official Header */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #005a9c;
            padding-bottom: 6px;
            margin-bottom: 6px;
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
            letter-spacing: -0.3px;
        }
        .header-hospital-ar h4 {
            font-size: 10.5px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 1px;
        }
        .header-hospital-ar p {
            font-size: 9px;
            font-weight: 600;
            color: #64748b;
        }

        .header-logo {
            text-align: center;
            padding: 0 14px;
        }
        .hospital-logo-img {
            max-height: 52px;
            max-width: 90px;
            object-fit: contain;
        }

        .header-hospital-en {
            text-align: left;
            flex: 1;
            direction: ltr;
        }
        .header-hospital-en h2 {
            font-size: 13.5px;
            font-weight: 900;
            color: #005a9c;
            margin-bottom: 1px;
        }
        .header-hospital-en h4 {
            font-size: 10px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 1px;
        }
        .header-hospital-en p {
            font-size: 8.5px;
            font-weight: 600;
            color: #64748b;
        }

        /* 2. Document Title Banner */
        .doc-title-bar {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 10px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .doc-title-bar h3 {
            font-size: 12px;
            font-weight: 900;
            color: #005a9c;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .doc-title-bar h3 span {
            color: #475569;
            font-weight: 600;
            font-size: 10px;
        }
        .meta-badges {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .badge-code {
            background: #005a9c;
            color: #ffffff;
            font-weight: 800;
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 3px;
            letter-spacing: 0.5px;
        }
        .badge-status {
            background: #e2e8f0;
            color: #334155;
            font-weight: 700;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 3px;
        }

        /* Section Container & Heading */
        .sec-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 5.5px;
            background: #ffffff;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .sec-head {
            background-color: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 3.5px 8px;
            font-size: 9.5px;
            font-weight: 800;
            color: #005a9c;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .sec-head i {
            margin-inline-end: 4px;
        }
        .sec-body {
            padding: 5px 8px;
        }

        /* Demographic Table */
        .demographic-table {
            width: 100%;
            border-collapse: collapse;
        }
        .demographic-table td {
            padding: 3px 6px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
            vertical-align: middle;
        }
        .demographic-table .lbl {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            width: 13%;
        }
        .demographic-table .val {
            color: #0f172a;
            font-weight: 600;
            width: 20%;
        }
        .demographic-table .val-highlight {
            color: #005a9c;
            font-weight: 800;
        }

        /* Team Row */
        .team-row {
            display: flex;
            flex-wrap: wrap;
            border-collapse: collapse;
            background: #f8fafc;
            border-radius: 3px;
            border: 1px solid #e2e8f0;
        }
        .team-col {
            flex: 1;
            min-width: 14%;
            padding: 4px 6px;
            border-inline-end: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        .team-col:last-child {
            border-inline-end: none;
        }
        .team-role {
            font-size: 8px;
            font-weight: 700;
            color: #64748b;
        }
        .team-name {
            font-size: 9.5px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Clinical Notes & Details Box */
        .clinical-entry {
            margin-bottom: 4px;
            padding: 4px 7px;
            border-radius: 3px;
            border-right: 3px solid #cbd5e1;
            background-color: #f8fafc;
        }
        [dir="ltr"] .clinical-entry {
            border-right: none;
            border-left: 3px solid #cbd5e1;
        }
        .clinical-entry:last-child {
            margin-bottom: 0;
        }
        .clinical-entry.entry-diagnosis {
            border-color: #0284c7;
            background-color: #f0f9ff;
        }
        .clinical-entry.entry-postop {
            border-color: #d97706;
            background-color: #fffbeb;
        }
        .clinical-entry.entry-anesthesia {
            border-color: #059669;
            background-color: #f0fdf4;
        }
        .clinical-entry.entry-plan {
            border-color: #10b981;
            background-color: #ecfdf5;
        }
        .clinical-entry-title {
            font-size: 9px;
            font-weight: 800;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .entry-diagnosis .clinical-entry-title { color: #0369a1; }
        .entry-postop .clinical-entry-title { color: #b45309; }
        .entry-anesthesia .clinical-entry-title { color: #047857; }
        .entry-plan .clinical-entry-title { color: #065f46; }

        .clinical-entry-text {
            font-size: 9.5px;
            color: #1e293b;
            line-height: 1.45;
            white-space: pre-line;
        }

        /* 2-Column Grid */
        .dual-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5.5px;
            margin-bottom: 5.5px;
        }

        /* Clean Data Tables */
        .clean-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-top: 2px;
        }
        .clean-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 800;
            padding: 3px 5px;
            border: 1px solid #cbd5e1;
            text-align: right;
        }
        [dir="ltr"] .clean-table th {
            text-align: left;
        }
        .clean-table td {
            padding: 2.5px 5px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            vertical-align: middle;
        }
        .clean-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Official Authentication Block */
        .auth-container {
            margin-top: 5px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 4px 8px;
            background-color: #f8fafc;
            display: grid;
            grid-template-columns: 1.2fr 1.2fr 1fr 0.9fr 0.7fr;
            gap: 6px;
            align-items: center;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .auth-box {
            text-align: center;
        }
        .auth-sig-line {
            border-top: 1px dashed #64748b;
            padding-top: 2px;
            margin-top: 18px;
        }
        .auth-sig-role {
            font-size: 8px;
            font-weight: 700;
            color: #64748b;
        }
        .auth-sig-name {
            font-size: 9px;
            font-weight: 800;
            color: #0f172a;
        }
        .auth-stamp-box {
            border: 1.5px dashed #005a9c;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #005a9c;
            font-size: 6.5px;
            font-weight: 800;
            text-align: center;
        }
        .auth-qr-box {
            text-align: center;
        }
        .qr-placeholder {
            width: 38px;
            height: 38px;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #005a9c;
            font-size: 16px;
        }

        /* Footer */
        .report-footer {
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            margin-top: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7.5px;
            color: #64748b;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 5mm 7mm !important;
            }
            html, body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 8.8px !important;
                line-height: 1.35 !important;
                height: 100% !important;
                max-height: 297mm !important;
                overflow: hidden !important;
            }
            .no-print { display: none !important; }
            .sheet-container {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .report-page {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                min-height: auto !important;
                max-height: 287mm !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
            }
            .report-header {
                padding-bottom: 2.5px !important;
                margin-bottom: 3.5px !important;
            }
            .hospital-logo-img {
                max-height: 42px !important;
            }
            .header-hospital-ar h2, .header-hospital-en h2 {
                font-size: 13px !important;
            }
            .doc-title-bar {
                padding: 2.5px 7px !important;
                margin-bottom: 3.5px !important;
            }
            .sec-card {
                margin-bottom: 3px !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            .sec-head {
                padding: 2px 6px !important;
                font-size: 8.5px !important;
            }
            .sec-body {
                padding: 3px 6px !important;
            }
            .demographic-table td {
                padding: 2px 4px !important;
                font-size: 8px !important;
            }
            .team-col {
                padding: 2px 4px !important;
            }
            .team-role {
                font-size: 7.5px !important;
            }
            .team-name {
                font-size: 8.5px !important;
            }
            .dual-grid {
                gap: 3.5px !important;
                margin-bottom: 3px !important;
            }
            .clinical-entry {
                padding: 2px 5px !important;
                margin-bottom: 2px !important;
            }
            .clinical-entry-title {
                font-size: 8px !important;
                margin-bottom: 1px !important;
            }
            .clinical-entry-text {
                font-size: 8px !important;
                line-height: 1.3 !important;
            }
            .clean-table th, .clean-table td {
                padding: 1.8px 4px !important;
                font-size: 7.8px !important;
            }
            .auth-container {
                margin-top: 2.5px !important;
                padding: 2px 5px !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            .auth-sig-line {
                margin-top: 10px !important;
            }
            .auth-stamp-box {
                width: 42px !important;
                height: 42px !important;
            }
            .qr-placeholder {
                width: 30px !important;
                height: 30px !important;
                font-size: 12px !important;
            }
            .report-footer {
                padding-top: 2px !important;
                margin-top: 2.5px !important;
                font-size: 7px !important;
            }
        }
    </style>
</head>
<body>

    <!-- Screen Toolbar -->
    <div class="no-print">
        <div class="action-group">
            <button onclick="window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> {{ __('طباعة التقرير (Print A4)') }}
            </button>
            <button onclick="window.print()" class="btn btn-pdf">
                <i class="fas fa-file-pdf"></i> {{ __('حفظ بتنسيق PDF') }}
            </button>
        </div>
        <div class="action-group">
            <span style="font-weight: 700; color: #64748b; font-size: 11px;">
                OR-#{{ $surgery->id }} • {{ $patientName }}
            </span>
            <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-back">
                <i class="fas fa-arrow-right"></i> {{ __('العودة لملف العملية') }}
            </a>
        </div>
    </div>

    <!-- Main Printable Sheet -->
    <div class="sheet-container">
        <div class="report-page">
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
                        <p>Major Operating Theaters • Comprehensive Surgery Report</p>
                    </div>
                </div>

                <!-- 2. Document Title Banner -->
                <div class="doc-title-bar">
                    <h3>
                        <i class="fas fa-file-medical"></i>
                        {{ __('تقرير العملية الجراحية المعتمد') }}
                        <span>• {{ __('السجل السريري الشامل (Operative & Clinical Record)') }}</span>
                    </h3>
                    <div class="meta-badges">
                        <span class="badge-code">OR-#{{ $surgery->id }}</span>
                        <span class="badge-status">{{ $statusLabel }}</span>
                        @if($surgeryStartFormatted)
                            <span class="badge-status">
                                ⏰ {{ $surgeryStartFormatted }} {{ $surgeryEndFormatted ? '— ' . $surgeryEndFormatted : '' }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- 3. Patient Information & Hospital Stay -->
                <div class="sec-card">
                    <div class="sec-head">
                        <div><i class="fas fa-user-circle"></i>{{ __('بيانات المريض والإقامة الفندقية (Patient & Stay Demographics)') }}</div>
                        <span style="font-size: 8px; color: #64748b;">تاريخ العملية: {{ $scheduledDate }}</span>
                    </div>
                    <div class="sec-body" style="padding: 0;">
                        <table class="demographic-table">
                            <tr>
                                <td class="lbl">{{ __('اسم المريض:') }}</td>
                                <td class="val val-highlight" style="font-size: 11px;">{{ $patientName }}</td>
                                <td class="lbl">{{ __('العمر والجنس:') }}</td>
                                <td class="val">{{ $patientAge }} {{ __('سنة') }} • {{ $patientGender }}</td>
                                <td class="lbl">{{ __('رقم القيد / الملف:') }}</td>
                                <td class="val" style="direction: ltr; text-align: right; font-weight: 800;">{{ $patientMedNo }}</td>
                            </tr>
                            <tr>
                                <td class="lbl">{{ __('الغرفة والإقامة:') }}</td>
                                <td class="val val-highlight">{{ $roomName }}</td>
                                <td class="lbl">{{ __('توقيت الدخول / الخروج:') }}</td>
                                <td class="val">
                                    {{ $roomCheckinAt ?: '—' }}
                                    @if($dischargedAt) <span style="color: #dc2626;">إلى {{ $dischargedAt }}</span> @endif
                                </td>
                                <td class="lbl">{{ __('التغطية المالية:') }}</td>
                                <td class="val">{{ $isInsured ? ('🛡️ ' . $insuranceLabel) : __('💵 حساب نقدي (كاش)') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- 4. Surgical, Anesthesia & Nursing Team -->
                <div class="sec-card">
                    <div class="sec-head">
                        <div><i class="fas fa-users-cog"></i>{{ __('الفريق الطبي والجراحي والتمريضي المشرف (Surgical & Clinical Team)') }}</div>
                    </div>
                    <div class="sec-body" style="padding: 0;">
                        <div class="team-row">
                            <div class="team-col">
                                <span class="team-role">{{ __('الجراح الرئيسي:') }}</span>
                                <span class="team-name" style="color: #005a9c;">{{ __('د.') }} {{ $surgeonName }}</span>
                            </div>
                            <div class="team-col">
                                <span class="team-role">{{ __('مساعد الجراح:') }}</span>
                                <span class="team-name">{{ $surgicalAssistantName ?: '—' }}</span>
                            </div>
                            <div class="team-col">
                                <span class="team-role">{{ $secondaryAnesthName ? __('طبيب التخدير الأول:') : __('طبيب التخدير المشرف:') }}</span>
                                <span class="team-name" style="color: #0284c7;">{{ $primaryAnesthName ? (__('د.') . ' ' . $primaryAnesthName) : '—' }}</span>
                            </div>
                            @if($secondaryAnesthName)
                                <div class="team-col">
                                    <span class="team-role">{{ __('طبيب التخدير الثاني:') }}</span>
                                    <span class="team-name" style="color: #0284c7;">{{ __('د.') }} {{ $secondaryAnesthName }}</span>
                                </div>
                            @endif
                            <div class="team-col">
                                <span class="team-role">{{ __('مساعد / تقني التخدير:') }}</span>
                                <span class="team-name">{{ $anesthesiaAssistantName ?: '—' }}</span>
                            </div>
                            <div class="team-col">
                                <span class="team-role">{{ __('كادر تمريض الصالة:') }}</span>
                                <span class="team-name">{{ $orNurseName ?: '—' }}</span>
                            </div>
                            @if($residentName)
                                <div class="team-col">
                                    <span class="team-role">{{ __('الطبيب المقيم المكلف:') }}</span>
                                    <span class="team-name">{{ __('د.') }} {{ $residentName }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 5. Primary Procedure & Additional Operations -->
                <div class="sec-card">
                    <div class="sec-head">
                        <div><i class="fas fa-procedures"></i>{{ __('الإجراء الجراحي والعمليات المنفذة (Surgical Procedures)') }}</div>
                        @if($additionalOps->count() > 0)
                            <span style="font-size: 8px; color: #0284c7;">+ {{ $additionalOps->count() }} تداخل إضافي</span>
                        @endif
                    </div>
                    <div class="sec-body" style="padding: 4px 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                            <div>
                                <span style="font-size: 8.5px; color: #64748b; font-weight: 700;">{{ __('العملية الأساسية (Primary Procedure):') }}</span>
                                <strong style="font-size: 11px; color: #005a9c; margin-inline-start: 4px;">{{ $surgeryType }}</strong>
                            </div>
                            @if($surgery->surgicalOperation?->code)
                                <span style="font-size: 8.5px; background: #eff6ff; color: #1d4ed8; padding: 1px 6px; border-radius: 3px; font-weight: 800;">
                                    كود: {{ $surgery->surgicalOperation->code }}
                                </span>
                            @endif
                        </div>

                        @if($additionalOps->count() > 0)
                            <table class="clean-table" style="margin-top: 4px;">
                                <thead>
                                    <tr>
                                        <th style="width: 45%;">{{ __('العملية الإضافية') }}</th>
                                        <th style="width: 25%;">{{ __('نوع التداخل') }}</th>
                                        <th style="width: 30%;">{{ __('الجراح المنفذ') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($additionalOps as $op)
                                        <tr>
                                            <td style="font-weight: 700;">{{ $op->surgicalOperation?->name ?? $op->operation_name }}</td>
                                            <td>{{ $op->operation_type ?: 'تداخل إضافي' }}</td>
                                            <td>{{ $op->doctor?->user?->name ? ('د. ' . $op->doctor->user->name) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>

                <!-- 6. Operative Technique, Post-Op Notes & Anesthesia Protocol (Dual Grid) -->
                <div class="dual-grid">
                    <!-- Column A: Surgeon Documentation & Operative Notes -->
                    <div class="sec-card" style="margin-bottom: 0;">
                        <div class="sec-head">
                            <div><i class="fas fa-stethoscope"></i>{{ __('التشخيص والتوثيق الجراحي (Surgical Findings)') }}</div>
                        </div>
                        <div class="sec-body">
                            <!-- Diagnosis -->
                            <div class="clinical-entry entry-diagnosis">
                                <div class="clinical-entry-title">
                                    <i class="fas fa-diagnoses"></i> {{ __('التشخيص الطبي (Surgeon Diagnosis):') }}
                                </div>
                                <div class="clinical-entry-text" style="font-weight: 800; color: #0369a1;">
                                    {{ $surgery->diagnosis ?: __('لم يُسجل تشخيص سريري بعد') }}
                                </div>
                            </div>

                            <!-- Post-Op Notes -->
                            @if($postOpNotes)
                                <div class="clinical-entry entry-postop">
                                    <div class="clinical-entry-title">
                                        <i class="fas fa-clipboard-check"></i> {{ __('ملاحظات ما بعد العملية (Post-Operative Notes):') }}
                                    </div>
                                    <div class="clinical-entry-text" style="font-weight: 600;">{!! nl2br(e($postOpNotes)) !!}</div>
                                </div>
                            @endif

                            <!-- Procedure Notes / Description (if distinct) -->
                            @if(($procedureNotes || $surgery->description) && ($procedureNotes ?: $surgery->description) !== $postOpNotes)
                                <div class="clinical-entry">
                                    <div class="clinical-entry-title">
                                        <i class="fas fa-notes-medical"></i> {{ __('الوصف والتقنية الجراحية وملاحظات الصالة:') }}
                                    </div>
                                    <div class="clinical-entry-text">{!! nl2br(e($procedureNotes ?: $surgery->description)) !!}</div>
                                </div>
                            @endif

                            @if($doctorRecommendation)
                                <div class="clinical-entry entry-plan">
                                    <div class="clinical-entry-title">
                                        <i class="fas fa-user-md"></i> {{ __('توصيات الجراح وخطة المتابعة:') }}
                                    </div>
                                    <div class="clinical-entry-text">{!! nl2br(e($doctorRecommendation)) !!}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Column B: Anesthesia Documentation & Protocol -->
                    <div class="sec-card" style="margin-bottom: 0;">
                        <div class="sec-head">
                            <div><i class="fas fa-syringe"></i>{{ __('توثيق وخطة التخدير (Anesthesia Protocol & Recovery)') }}</div>
                        </div>
                        <div class="sec-body">
                            <div class="clinical-entry entry-anesthesia">
                                <div class="clinical-entry-title">
                                    <i class="fas fa-check-circle"></i> {{ __('نوع التخدير المعتمد (Anesthesia Type):') }}
                                </div>
                                <div class="clinical-entry-text" style="font-weight: 800; color: #047857;">
                                    {{ $currentAnesthesiaType ?: __('تخدير عام') }}
                                </div>
                            </div>

                            <div class="clinical-entry entry-anesthesia">
                                <div class="clinical-entry-title">
                                    <i class="fas fa-file-medical-alt"></i> {{ __('ملاحظات التخدير وسير العملية والإفاقة:') }}
                                </div>
                                <div class="clinical-entry-text">
                                    {!! nl2br(e($currentAnesthesiaNotes ?: __('سارت إجراءات التخدير بصورة طبيعية دون مضاعفات.'))) !!}
                                </div>
                            </div>

                            @if($devices->count() > 0)
                                <div class="clinical-entry">
                                    <div class="clinical-entry-title">
                                        <i class="fas fa-microchip"></i> {{ __('الأجهزة الطبية المستخدمة:') }}
                                    </div>
                                    <div class="clinical-entry-text">{{ $devices->pluck('name')->join('، ') }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 7. Treatment Plan & Medications Administered -->
                @if($treatmentPlan || $medications->count() > 0)
                    <div class="sec-card">
                        <div class="sec-head">
                            <div>
                                <i class="fas fa-pills text-danger"></i>
                                {{ __('خطة العلاج والأدوية (Treatment Plan & Medications)') }}
                            </div>
                            @if($medications->count() > 0)
                                <span style="font-size: 8px; color: #0284c7;">{{ $medications->count() }} دواء مسجل بالرقود</span>
                            @endif
                        </div>
                        <div class="sec-body">
                            <!-- Prescribed Treatment Plan -->
                            @if($treatmentPlan)
                                <div class="clinical-entry entry-plan" style="margin-bottom: 5px;">
                                    <div class="clinical-entry-title" style="font-size: 8.5px; color: #065f46;">
                                        <i class="fas fa-prescription me-1"></i> {{ __('الأدوية والتوجيهات الموصوفة بعد العملية:') }}
                                    </div>
                                    <div class="clinical-entry-text" style="font-weight: 700; color: #064e3b; font-size: 9.5px; line-height: 1.5;">
                                        {!! nl2br(e($treatmentPlan)) !!}
                                    </div>
                                </div>
                            @endif

                            <!-- Inpatient Medications Table -->
                            @if($medications->count() > 0)
                                <table class="clean-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 32%;">{{ __('الدواء / المحلول العلاجي') }}</th>
                                            <th style="width: 15%;">{{ __('الجرعة') }}</th>
                                            <th style="width: 15%;">{{ __('طريقة الإعطاء') }}</th>
                                            <th style="width: 22%;">{{ __('التكرار والتعليمات') }}</th>
                                            <th style="width: 16%;">{{ __('حالة الإعطاء والتوقيت') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($medications as $tr)
                                            @php
                                                $medName = $tr->description ?: ($tr->medicine?->name ?? $tr->name ?? 'علاج طبي');
                                                $medDosage = $tr->dosage ?: ($tr->quantity ? $tr->quantity . ' وحدة' : 'حسب الوصفة');
                                                $medRoute = $tr->route ?: 'I.V';
                                                $medTiming = $tr->timing ?: ($tr->instructions ?: ($tr->frequency_mode ? ($tr->frequency_preset_label ?? $tr->frequency_mode) : '—'));
                                                $isGiven = ($tr->status === 'administered');
                                                $givenTime = $tr->administered_at ? \Carbon\Carbon::parse($tr->administered_at)->format('Y-m-d H:i') : null;
                                            @endphp
                                            <tr>
                                                <td style="font-weight: 700; color: #005a9c;">{{ $medName }}</td>
                                                <td>{{ $medDosage }}</td>
                                                <td>{{ $medRoute }}</td>
                                                <td>{{ $medTiming }}</td>
                                                <td>
                                                    <span style="font-size: 7.5px; padding: 1px 4px; border-radius: 2px; font-weight: 700; background: {{ $isGiven ? '#dcfce7; color: #166534;' : '#fef3c7; color: #92400e;' }}">
                                                        {{ $isGiven ? __('تم الصرف والإعطاء') : __('مخطط له') }}
                                                    </span>
                                                    @if($givenTime) <small style="display: block; font-size: 7px; color: #64748b;">{{ $givenTime }}</small> @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- 8. Surgical Supplies & Consumables -->
                @if($supplies->count() > 0)
                    <div class="sec-card">
                        <div class="sec-head">
                            <div>
                                <i class="fas fa-band-aid text-warning"></i>
                                {{ __('المستلزمات والمواد الجراحية المستهلكة (Surgical Supplies & Consumables)') }}
                            </div>
                            <span style="font-size: 8px; color: #b45309;">{{ $supplies->count() }} مادة مستهلكة</span>
                        </div>
                        <div class="sec-body" style="padding: 3px 8px;">
                            <table class="clean-table">
                                <thead>
                                    <tr>
                                        <th style="width: 42%;">{{ __('المستلزم الطبي / المادة المستهلكة') }}</th>
                                        <th style="width: 14%; text-align: center;">{{ __('الكمية') }}</th>
                                        <th style="width: 18%;">{{ __('الوحدة / القياس') }}</th>
                                        <th>{{ __('الملاحظات ومصدر الصرف') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($supplies as $sup)
                                        @php
                                            $supName = $sup->description ?: ($sup->product?->name ?? $sup->name ?? 'مستلزم جراحي');
                                            $supQty = $sup->quantity ?? 1;
                                            $supDosage = $sup->dosage ?: 'قطعة';
                                            $supTiming = $sup->timing ?: 'صرف من عهدة العمليات';
                                        @endphp
                                        <tr>
                                            <td style="font-weight: 700;">{{ $supName }}</td>
                                            <td style="text-align: center; font-weight: 800; color: #0284c7;">{{ $supQty }}</td>
                                            <td>{{ $supDosage }}</td>
                                            <td>{{ $supTiming }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- 9. Lab & Radiology Investigations (if any) -->
                @if($labTests->count() > 0 || $radiologyTests->count() > 0)
                    <div class="dual-grid">
                        @if($labTests->count() > 0)
                            <div class="sec-card" style="margin-bottom: 0;">
                                <div class="sec-head">
                                    <div><i class="fas fa-flask text-danger"></i>{{ __('الفحوصات المخبرية (Lab Tests)') }}</div>
                                    <span style="font-size: 8px; color: #64748b;">{{ $labTests->count() }} فحص</span>
                                </div>
                                <div class="sec-body" style="padding: 3px 6px;">
                                    <table class="clean-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('التحليل') }}</th>
                                                <th>{{ __('الحالة') }}</th>
                                                <th>{{ __('النتيجة') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($labTests as $lab)
                                                <tr>
                                                    <td style="font-weight: 700;">{{ $lab->labTest?->name ?? ('تحليل #' . $lab->lab_test_id) }}</td>
                                                    <td>{{ $lab->status_text }}</td>
                                                    <td style="font-weight: 800; color: #005a9c;">{{ $lab->result ?: '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if($radiologyTests->count() > 0)
                            <div class="sec-card" style="margin-bottom: 0;">
                                <div class="sec-head">
                                    <div><i class="fas fa-x-ray text-primary"></i>{{ __('الفحوصات الشعاعية (Radiology)') }}</div>
                                    <span style="font-size: 8px; color: #64748b;">{{ $radiologyTests->count() }} فحص</span>
                                </div>
                                <div class="sec-body" style="padding: 3px 6px;">
                                    <table class="clean-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('نوع الفحص') }}</th>
                                                <th>{{ __('الحالة') }}</th>
                                                <th>{{ __('التقرير / النتيجة') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($radiologyTests as $rad)
                                                <tr>
                                                    <td style="font-weight: 700;">{{ $rad->radiologyType?->name ?? ('أشعة #' . $rad->radiology_type_id) }}</td>
                                                    <td>{{ $rad->status_text }}</td>
                                                    <td>{{ $rad->result ?: 'بانتظار التقرير' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- 10. Medical Consultations (if any) -->
                @if($consultations->count() > 0)
                    <div class="sec-card">
                        <div class="sec-head">
                            <div><i class="fas fa-stethoscope"></i>{{ __('الاستشارات الطبية المرافقة (Medical Consultations)') }}</div>
                        </div>
                        <div class="sec-body" style="padding: 3px 6px;">
                            <table class="clean-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('التخصص المطلوب') }}</th>
                                        <th>{{ __('الطبيب الاستشاري') }}</th>
                                        <th>{{ __('الأولوية') }}</th>
                                        <th>{{ __('سبب الاستشارة') }}</th>
                                        <th>{{ __('رد وتوصيات الاستشاري') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($consultations as $con)
                                        <tr>
                                            <td style="font-weight: 700;">{{ $con->specialty }}</td>
                                            <td>{{ $con->doctor?->user?->name ? ('د. ' . $con->doctor->user->name) : '—' }}</td>
                                            <td>{{ $con->priorityLabel() }}</td>
                                            <td>{{ $con->reason }}</td>
                                            <td style="font-weight: 700; color: #059669;">{{ $con->consultant_response ?: 'قيد المتابعة' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- 11. Official Signatures & Seal Block -->
                <div class="auth-container">
                    <div class="auth-box">
                        <div class="auth-sig-line">
                            <div class="auth-sig-role">{{ __('توقيع الطبيب الجراح') }} / Surgeon Signature</div>
                            <div class="auth-sig-name">{{ __('د.') }} {{ $surgeonName }}</div>
                        </div>
                    </div>

                    <div class="auth-box">
                        <div class="auth-sig-line">
                            <div class="auth-sig-role">{{ __('توقيع طبيب التخدير') }} / Anesthesiologist Signature</div>
                            <div class="auth-sig-name">
                                {{ $primaryAnesthName ? (__('د.') . ' ' . $primaryAnesthName) : '—' }}
                                @if($secondaryAnesthName)
                                    <div style="font-size: 7.5px; color: #64748b;">• د. {{ $secondaryAnesthName }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="auth-box">
                        <div class="auth-sig-line">
                            <div class="auth-sig-role">{{ __('توقيع مسؤولة / كادر الصالة') }}</div>
                            <div class="auth-sig-name">{{ $orNurseName ?: __('كادر تمريض الصالة') }}</div>
                        </div>
                    </div>

                    <div class="auth-box">
                        <div class="auth-stamp-box">
                            <i class="fas fa-stamp" style="font-size: 11px; margin-bottom: 1px;"></i>
                            <span>{{ __('ختم صالة العمليات') }}</span>
                            <span style="font-size: 5.5px; color: #64748b;">Operating Theater Stamp</span>
                        </div>
                    </div>

                    <div class="auth-qr-box">
                        <div class="qr-placeholder">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <span style="font-size: 6.5px; color: #64748b; font-weight: 700;">OR-VERIFIED-{{ $surgery->id }}</span>
                    </div>
                </div>

                <!-- 12. Document Footer -->
                <div class="report-footer">
                    <div>
                        <span>{{ __('رقم ترخيص المنشأة الطبية:') }} <strong>{{ $hospital?->license_number ?: 'LIC-SURG-01' }}</strong></span>
                    </div>
                    <div>
                        <span><i class="fas fa-map-marker-alt me-1"></i> {{ $hospital?->address ?: 'بغداد - المنصور' }}</span>
                        <span style="margin: 0 6px;">•</span>
                        <span><i class="fas fa-phone me-1"></i> {{ $hospital?->phone ?: '07700000000' }}</span>
                        <span style="margin: 0 6px;">•</span>
                        <span><i class="fas fa-clock me-1"></i> {{ __('طُبع بتاريخ:') }} {{ now()->format('Y-m-d H:i') }}</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
