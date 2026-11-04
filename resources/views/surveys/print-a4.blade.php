<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استبيان تقييم غرفة الرقود في المستشفى</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', 'Tajawal', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            direction: rtl;
            background: #f1f5f9;
            color: #1e293b;
            padding: 20px 0;
            font-size: 11.5px;
            line-height: 1.35;
        }

        .print-page {
            width: 210mm;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 8mm 12mm 8mm 12mm;
            box-sizing: border-box;
            border: 1.5px solid #005a9c;
            border-radius: 4px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Subtle Watermark (MEDIX ERP System Logo) */
        .print-page::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url('{{ asset('images/logo.png') }}');
            background-repeat: no-repeat;
            background-position: center 50%;
            background-size: 58% auto;
            opacity: 0.12;
            pointer-events: none;
            z-index: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-page > * {
            position: relative;
            z-index: 1;
        }

        /* Toolbar */
        .print-toolbar {
            position: fixed;
            top: 15px;
            left: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
            z-index: 9999;
        }

        .btn-tool {
            padding: 6px 14px;
            border: none;
            border-radius: 20px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-print {
            background: #2563eb;
            color: #fff;
        }
        .btn-print:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #475569;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #334155;
        }

        /* Hospital Report 3-Zone Header */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #005a9c;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }

        .header-hospital-ar {
            width: 38%;
            text-align: right;
        }
        .header-hospital-ar h2 {
            font-size: 15px;
            font-weight: 900;
            color: #005a9c;
            margin-bottom: 1px;
        }
        .header-hospital-ar h4 {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1px;
        }
        .header-hospital-ar p {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 600;
        }

        .header-logo {
            width: 24%;
            text-align: center;
        }
        .header-logo img {
            max-height: 60px;
            max-width: 90px;
            object-fit: contain;
        }

        .header-hospital-en {
            width: 38%;
            text-align: left;
            direction: ltr;
        }
        .header-hospital-en h2 {
            font-size: 13.5px;
            font-weight: 800;
            color: #005a9c;
            margin-bottom: 1px;
        }
        .header-hospital-en h4 {
            font-size: 10.5px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1px;
        }
        .header-hospital-en p {
            font-size: 9px;
            color: #64748b;
            font-weight: 600;
        }

        /* Document Title Banner */
        .document-title-banner {
            background: linear-gradient(135deg, #005a9c 0%, #034677 100%);
            color: #ffffff;
            border-radius: 4px;
            padding: 4px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .document-title-banner h3 {
            font-size: 13.5px;
            font-weight: 800;
            letter-spacing: 0.2px;
        }

        .report-code-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 2px 8px;
            border-radius: 3px;
            font-family: monospace;
            font-weight: 700;
            font-size: 11px;
        }

        /* Introduction text */
        .intro-box {
            font-size: 10px;
            line-height: 1.4;
            color: #334155;
            background: #f8fafc;
            border-right: 3px solid #005a9c;
            padding: 4px 8px;
            margin-bottom: 6px;
            border-radius: 0 3px 3px 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .intro-box strong {
            color: #005a9c;
            font-weight: 800;
        }

        /* Section Title */
        .section-header {
            font-size: 11px;
            font-weight: 800;
            color: #005a9c;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 2px;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-header .sub-guide {
            font-size: 9.5px;
            font-weight: 600;
            color: #64748b;
        }

        /* Basic Info Strip (Section 1) */
        .basic-info-strip {
            display: grid;
            grid-template-columns: 1.1fr 1.3fr 1fr 1.6fr;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 10.5px;
            margin-bottom: 6px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .info-label {
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
        }
        .info-val {
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px dotted #94a3b8;
            flex: 1;
            padding: 0 3px;
            min-height: 16px;
        }

        /* Checkbox Box Item */
        .check-box-item {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-weight: 600;
            font-size: 10px;
        }
        .box-sq {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 13px;
            height: 13px;
            border: 1.2px solid #334155;
            border-radius: 2px;
            font-size: 10px;
            line-height: 1;
            font-weight: 900;
            color: #005a9c;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Evaluation Table (Section 2) */
        .eval-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            font-size: 10px;
        }
        .eval-table th, .eval-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 6px;
            vertical-align: middle;
        }
        .eval-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
            text-align: center;
            font-size: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .eval-table th.col-crit {
            text-align: right;
            width: 48%;
        }
        .eval-table th.col-rate {
            width: 10.4%;
        }
        .eval-table td.col-crit {
            font-weight: 700;
            color: #1e293b;
        }
        .eval-table td.col-rate {
            text-align: center;
        }
        .eval-table tr:nth-child(even) td {
            background: #fafafa;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Dotted Line for writing */
        .dotted-line-block {
            display: flex;
            align-items: flex-end;
            margin-top: 2px;
            font-size: 10px;
            font-weight: 600;
            color: #334155;
        }
        .dotted-line {
            flex: 1;
            border-bottom: 1px dotted #94a3b8;
            margin-right: 4px;
            min-height: 15px;
            padding: 0 4px;
            font-weight: 700;
            color: #0f172a;
        }

        /* Section 3 Open Questions */
        .open-questions-box {
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin-bottom: 5px;
            font-size: 10px;
        }
        .question-row {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .question-label {
            font-weight: 800;
            color: #1e293b;
            font-size: 10.5px;
        }
        .multi-dotted-row {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 9.5px;
            margin-top: 1px;
        }

        /* Doctor Evaluation Box */
        .doctor-eval-card {
            background: #f8fafc;
            border: 1.2px solid #cbd5e1;
            border-radius: 4px;
            padding: 4px 8px;
            margin-bottom: 5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .doctor-eval-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
        }

        /* Report Footer */
        .report-footer-wrapper {
            margin-top: auto;
            border-top: 1.5px solid #005a9c;
            padding-top: 4px;
        }
        .report-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
            color: #475569;
            font-weight: 600;
        }
        .hospital-contact-line {
            text-align: center;
            font-size: 8.5px;
            color: #64748b;
            margin-top: 3px;
            font-weight: 600;
            direction: ltr;
        }

        @media print {
            html, body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .print-page {
                border: 1.5px solid #005a9c !important;
                box-shadow: none !important;
                margin: 0 auto !important;
                width: 210mm !important;
                max-width: 210mm !important;
                height: 297mm !important;
                min-height: 297mm !important;
                padding: 8mm 12mm 8mm 12mm !important;
                box-sizing: border-box !important;
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .print-toolbar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    @php
        $isBlank = !empty($isBlank);
        $surveyId = $survey?->id ?? null;
        $surveyUuid = $survey?->uuid ?? null;
        $surveyDate = $survey?->survey_date ? $survey->survey_date->format('Y/m/d') : now()->format('Y/m/d');
        $patientName = $isBlank ? '' : ($survey?->patient?->user?->name ?? $surgery?->patient?->user?->name ?? '');
        $locationName = $isBlank ? '' : ($survey?->location?->name ?? $surgery?->room?->location?->name ?? '');
        $roomNumber = $isBlank ? '' : ($survey?->room?->room_number ?? $surgery?->room?->room_number ?? '');
        $roomType = $isBlank ? '' : ($survey?->room_type ?? $survey?->room?->type ?? $surgery?->room?->type ?? 'regular');

        $criteria = [
            'cleanliness_room' => '١- النظافة العامة للغرفة',
            'cleanliness_bathroom' => '٢- نظافة الحمام والمرافق الصحية',
            'temperature_ac' => '٣- درجة حرارة الغرفة (التكييف / التدفئة)',
            'lighting' => '٤- إضاءة الغرفة (قوة / سطوع الإضاءة)',
            'quietness_privacy' => '٥- مستوى الهدوء والخصوصية في الغرفة',
            'bed_comfort' => '٦- راحة السرير والفراش',
            'control_panel' => '٧- سهولة استخدام لوحة التحكم السرير، الإنارة، الممرض',
            'storage_space' => '٨- توفر مساحة كافية للتخزين للأغراض الشخصية',
            'furniture_safety' => '٩- جودة وأمان الأثاث الكرسي، طاولة الطعام',
            'entertainment' => '١٠- جودة وإمكانية الوصول إلى وسائل الترفيه التلفاز، الإنترنت',
        ];

        $ratings = [
            5 => 'ممتاز',
            4 => 'جيد جداً',
            3 => 'جيد',
            2 => 'مقبول',
            1 => 'ضعيف',
        ];

        $topImprovements = (!$isBlank && is_array($survey?->top_three_improvements)) ? $survey->top_three_improvements : [];
    @endphp

    <!-- Floating Print Controls (Hidden on Print) -->
    <div class="print-toolbar">
        <button type="button" onclick="window.print()" class="btn-tool btn-print">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            طباعة الاستبيان (Ctrl + P)
        </button>

        @if(!empty($surgery))
            @if($isBlank)
                <a href="{{ route('inpatient-surveys.print-a4', $surgery) }}" class="btn-tool btn-secondary">
                    عرض بالبيانات المعبأة
                </a>
            @else
                <a href="{{ route('inpatient-surveys.print-a4', ['surgery' => $surgery->id, 'blank' => 1]) }}" class="btn-tool btn-secondary">
                    عرض نموذج فارغ
                </a>
            @endif
            <a href="{{ route('clinical-station.show', $surgery) }}" class="btn-tool btn-secondary">
                العودة لطبلة المريض
            </a>
        @else
            <button type="button" onclick="window.close()" class="btn-tool btn-secondary">
                إغلاق
            </button>
        @endif
    </div>

    <div class="print-page">
        <div>
            <!-- Hospital 3-Zone Header (Consistent with Laboratory & Radiology Reports) -->
            <div class="report-header">
                <div class="header-hospital-ar">
                    <h2>مستشفى الكفاءات الأهلي</h2>
                    <h4>قسم الجودة والرقابة الصحية</h4>
                    <p>وحدة استبيان وتقييم غرف الرقود</p>
                </div>
                <div class="header-logo">
                    <img src="{{ asset('images/1.jpg') }}" 
                         alt="شعار المستشفى" 
                         onerror="this.style.display='none'">
                </div>
                <div class="header-hospital-en">
                    <h2>Al-Kafa'at Private Hospital</h2>
                    <h4>Inpatient Quality & Patient Safety</h4>
                    <p>Room Quality & Patient Experience Survey</p>
                </div>
            </div>

            <!-- Document Title Banner -->
            <div class="document-title-banner">
                <h3>استبيان تقييم غرفة الرقود في المستشفى</h3>
                <div class="report-code-badge">
                    {{ $surveyId ? 'SURVEY-#' . str_pad($surveyId, 5, '0', STR_PAD_LEFT) : 'الرقم: ........................' }}
                </div>
            </div>

            <!-- Intro Box -->
            <div class="intro-box">
                <strong>المقدمة: عزيزي المريض..</strong><br>
                نهتم برأيك وآرائك بشكل كبير ونقدّره كثيراً، فملاحظاتك تساعدنا على تحسين جودة خدماتنا، نرجو منك تخصيص بضع دقائق للإجابة على هذا الاستبيان لتقييم غرف المستشفى. جميع معلوماتك سرية وسيتم استخدامها لأغراض التطوير فقط، نشكرك مقدماً على وقتك وتعاونك.
            </div>

            <!-- Section 1: Basic Info -->
            <div class="section-header">
                <span>القسم الأول: المعلومات الأساسية (اختياري)</span>
                @if(!$isBlank && $survey?->overall_score)
                    <span class="sub-guide" style="color: #005a9c; font-weight: 800;">
                        النسبة المعتمدة للرضا: {{ number_format($survey->overall_score, 1) }}%
                    </span>
                @endif
            </div>

            <div class="basic-info-strip">
                <div class="info-item">
                    <span class="info-label">تاريخ اليوم:</span>
                    <span class="info-val">{{ $isBlank ? '____ / ____ / 202_' : $surveyDate }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">طابق / قسم المستشفى:</span>
                    <span class="info-val">{{ $locationName }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">رقم الغرفة:</span>
                    <span class="info-val">({{ $roomNumber ?: '..........' }})</span>
                </div>
                <div class="info-item" style="gap: 8px;">
                    <span class="info-label">نوع الغرفة:</span>
                    <span class="check-box-item">
                        <span class="box-sq">{{ (!$isBlank && in_array(strtolower($roomType), ['regular', 'standard', 'normal', 'عادية'])) ? '✓' : '' }}</span>
                        عادية
                    </span>
                    <span class="check-box-item">
                        <span class="box-sq">{{ (!$isBlank && in_array(strtolower($roomType), ['vip', 'خاصة'])) ? '✓' : '' }}</span>
                        V.I.P.
                    </span>
                    <span class="check-box-item">
                        <span class="box-sq">{{ (!$isBlank && in_array(strtolower($roomType), ['vvip', 'suite', 'جناح'])) ? '✓' : '' }}</span>
                        V.V.I.P.
                    </span>
                </div>
            </div>

            <!-- Section 2: Room Evaluation Matrix -->
            <div class="section-header">
                <span>القسم الثاني: تقييم غرفة الرقود</span>
                <span class="sub-guide">يرجى وضع علامة (✓) في الخانة التي تعبر عن رأيك</span>
            </div>

            <table class="eval-table">
                <thead>
                    <tr>
                        <th class="col-crit">المعيار / الخدمة</th>
                        @foreach($ratings as $score => $label)
                            <th class="col-rate">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($criteria as $key => $title)
                        @php
                            $currentVal = (!$isBlank && $survey) ? $survey->$key : null;
                        @endphp
                        <tr>
                            <td class="col-crit">{{ $title }}</td>
                            @foreach($ratings as $score => $label)
                                <td class="col-rate">
                                    <span class="box-sq">
                                        {{ ($currentVal == $score) ? '✓' : '' }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="dotted-line-block" style="margin-bottom: 5px;">
                <span>ملاحظاتك (إن وجدت):</span>
                <span class="dotted-line">{{ (!$isBlank && $survey?->room_notes) ? $survey->room_notes : '' }}</span>
            </div>

            <!-- Section 3: Open Questions -->
            <div class="section-header">
                <span>القسم الثالث: أسئلة مفتوحة لمزيد من التفاصيل</span>
            </div>

            <div class="open-questions-box">
                <div class="question-row">
                    <span class="question-label">١- ما هي أهم ثلاثة أشياء تحتاج إلى تحسين في غرفة الرقود؟</span>
                    <div class="multi-dotted-row">
                        <span>•</span>
                        <span class="dotted-line">{{ $topImprovements[0] ?? '' }}</span>
                    </div>
                    <div class="multi-dotted-row">
                        <span>•</span>
                        <span class="dotted-line">{{ $topImprovements[1] ?? '' }}</span>
                    </div>
                    <div class="multi-dotted-row">
                        <span>•</span>
                        <span class="dotted-line">{{ $topImprovements[2] ?? '' }}</span>
                    </div>
                </div>

                <div class="question-row" style="margin-top: 2px;">
                    <span class="question-label">٢- هل لديك أي اقتراحات أخرى لتحسين راحة ورفاهية المرضى في غرف الرقود؟</span>
                    <span class="dotted-line" style="min-height: 18px;">{{ (!$isBlank && $survey?->patient_suggestions) ? $survey->patient_suggestions : '' }}</span>
                </div>

                <div class="question-row" style="margin-top: 2px;">
                    <span class="question-label">٣- ما مدى احتمالية أن تنصح الأصدقاء أو العائلة بالعلاج في هذا المستشفى؟</span>
                    <div class="dotted-line-block">
                        <span class="dotted-line" style="min-height: 18px;">
                            @if(!$isBlank && $survey?->recommend_score_nps)
                                مقياس التوصية: {{ $survey->recommend_score_nps }} من 10 (NPS)
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Doctor Evaluation Card (Matching Paper Layout) -->
            <div class="doctor-eval-card">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #cbd5e1; padding-bottom: 3px; margin-bottom: 4px;">
                    <span style="font-weight: 800; color: #005a9c; font-size: 11px;">تقييم الفريق الطبي (زيارة الطبيب المعالج):</span>
                    <span style="font-size: 9.5px; color: #64748b; font-weight: 600;">يرجى وضع علامة (✓) في الخانة التي تعبر عن رأيك</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-weight: 700; color: #1e293b; font-size: 10.5px;">١. كيف كانت زيارة الطبيب والتواصل معك:</span>
                    <div style="display: flex; gap: 14px; align-items: center;">
                        @foreach($ratings as $score => $label)
                            <span class="check-box-item" style="font-size: 10.5px;">
                                <span class="box-sq">
                                    {{ (!$isBlank && $survey?->doctor_communication == $score) ? '✓' : '' }}
                                </span>
                                {{ $label }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="dotted-line-block">
                    <span style="white-space: nowrap;">. ملاحظات إضافية حول زيارة الطبيب:</span>
                    <span class="dotted-line">{{ (!$isBlank && $survey?->doctor_notes) ? $survey->doctor_notes : '' }}</span>
                </div>
            </div>
        </div>

        <!-- Official Report Footer (Matching Laboratory / Radiology Layout) -->
        <div class="report-footer-wrapper">
            <div class="report-footer">
                <div>
                    طُبع بواسطة: <strong>{{ auth()->user()->name ?? 'كادر المستشفى' }}</strong>
                </div>
                <div>
                    وحدة مراقبة الجودة وتجربة المريض (Patient Experience Unit)
                </div>
                <div>
                    تاريخ ووقت الطباعة: <strong>{{ now()->format('Y/m/d h:i A') }}</strong>
                </div>
            </div>
            <div class="hospital-contact-line">
                العراق - كربلاء المقدسة &nbsp;|&nbsp; هاتف: +964 (0) 778 050 7060 &nbsp;|&nbsp; info@alkafaathospital.com
            </div>
        </div>
    </div>

</body>
</html>
